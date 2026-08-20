/*
 * ESP32 Earthquake Detection System - v5
 * Notre Dame - Siena College of Polomolok
 *
 * IMPROVED MPU6050 CALIBRATION
 *
 * IMPORTANT:
 * This version calibrates the ACTUAL resting acceleration vector.
 * It does NOT assume the MPU6050 is perfectly horizontal.
 *
 * PGA SCALE:
 *
 * < 14 Gal       = Below Intensity IV / SAFE
 * 14 - 38 Gal    = Intensity IV / LIGHT
 * 38 - 90 Gal    = Intensity V / MODERATE
 * 90 - 180 Gal   = Intensity VI / STRONG
 * 180 - 330 Gal  = Intensity VII / VERY STRONG
 * > 330 Gal      = Intensity VIII+ / SEVERE
 *
 * SYSTEM ALERT:
 * 14 Gal  = warning
 * 115 Gal = STRONG / BUZZER
 *
 * Other components remain unchanged:
 * - ESP32 DevKit V1
 * - MPU6050 SDA=21, SCL=22
 * - Buzzer GPIO 25
 * - LCD 0x27
 * - Button GPIO 12
 * - LEDs GPIO 32,33,26,14
 * - WiFi
 * - HTTP server
 * - Existing state machine
 */

#include <Wire.h>
#include <MPU6050.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ─────────────────────────────────────────────────────────────────────────────
// WiFi
// ─────────────────────────────────────────────────────────────────────────────

const char* ssid     = "mac";
const char* password = "mac12345";

// ─────────────────────────────────────────────────────────────────────────────
// Server
// ─────────────────────────────────────────────────────────────────────────────

const char* serverUrl =
  "https://earthquake-monitoring.onrender.com/receive_data.php";

// ─────────────────────────────────────────────────────────────────────────────
// Hardware
// ─────────────────────────────────────────────────────────────────────────────

#define BUZZER_PIN 25
#define BUTTON_PIN 12

#define LED1_PIN 32
#define LED2_PIN 33
#define LED3_PIN 26
#define LED4_PIN 14

const byte ledPins[4] = {
  LED1_PIN,
  LED2_PIN,
  LED3_PIN,
  LED4_PIN
};

#define BUZZER_ENABLED true

// ─────────────────────────────────────────────────────────────────────────────
// Objects
// ─────────────────────────────────────────────────────────────────────────────

MPU6050 mpu;

LiquidCrystal_I2C lcd(0x27, 16, 2);

WiFiClientSecure secureClient;

// ─────────────────────────────────────────────────────────────────────────────
// PGA THRESHOLDS
// ─────────────────────────────────────────────────────────────────────────────

#define THRESHOLD_WARNING 14.0f

// Strong trigger requested from your scale.
// Buzzer starts here.
#define THRESHOLD_STRONG 115.0f

// ─────────────────────────────────────────────────────────────────────────────
// System states
// ─────────────────────────────────────────────────────────────────────────────

enum SystemState : byte {

  STATE_CALIBRATING,
  STATE_IDLE,
  STATE_ALARM_ACTIVE,
  STATE_VERIFICATION,
  STATE_COUNTDOWN
};

SystemState currentState = STATE_CALIBRATING;

// ─────────────────────────────────────────────────────────────────────────────
// State timing
// ─────────────────────────────────────────────────────────────────────────────

#define VERIFICATION_TIME 10000
#define COUNTDOWN_TIME    10000

unsigned long verificationStartTime = 0;
unsigned long countdownStartTime = 0;

// ─────────────────────────────────────────────────────────────────────────────
// CALIBRATION
// ─────────────────────────────────────────────────────────────────────────────

// Increased calibration sample count.
#define CALIBRATION_SAMPLES 500

// Delay between calibration samples.
#define CALIBRATION_DELAY 5

// Samples used to determine remaining stationary noise.
#define NOISE_SAMPLES 200

// Actual stationary acceleration vector.
// These values include Earth's gravity in whatever direction
// the MPU6050 is physically mounted.
float offsetX = 0.0f;
float offsetY = 0.0f;
float offsetZ = 0.0f;

// Measured stationary noise.
float noiseFloor = 0.0f;

// Minimum noise deadband.
#define MIN_NOISE_DEADBAND 1.5f

// ─────────────────────────────────────────────────────────────────────────────
// Sensor values
// ─────────────────────────────────────────────────────────────────────────────

float currentGal = 0.0f;

float filteredGal = 0.0f;

// ─────────────────────────────────────────────────────────────────────────────
// Server/MMI
// ─────────────────────────────────────────────────────────────────────────────

String globalMmi = "";
String globalMmiName = "";

unsigned long totalDataSent = 0;

// ─────────────────────────────────────────────────────────────────────────────
// Timers
// ─────────────────────────────────────────────────────────────────────────────

unsigned long lastSendTime = 0;
unsigned long lastLcdUpdateTime = 0;
unsigned long mmiDisplayStart = 0;
unsigned long lastWiFiCheck = 0;

const unsigned long SEND_INTERVAL = 2000;
const unsigned long LCD_REFRESH = 300;
const unsigned long MMI_DISPLAY_DURATION = 3000;
const unsigned long WIFI_CHECK_INTERVAL = 20000;

// ─────────────────────────────────────────────────────────────────────────────
// WiFi connection state
// ─────────────────────────────────────────────────────────────────────────────

bool wifiConnecting = false;

unsigned long wifiConnectStarted = 0;

const unsigned long WIFI_CONNECT_ATTEMPT_TIMEOUT = 20000;

// ─────────────────────────────────────────────────────────────────────────────
// Button
// ─────────────────────────────────────────────────────────────────────────────

#define DEBOUNCE_DELAY 50

byte lastButtonState = HIGH;
byte buttonState = HIGH;

unsigned long lastDebounceTime = 0;

// ─────────────────────────────────────────────────────────────────────────────
// LEDs
// ─────────────────────────────────────────────────────────────────────────────

unsigned long lastLedStep = 0;

byte ledStepIndex = 0;

const unsigned long LED_STEP_INTERVAL_WARNING = 300;
const unsigned long LED_STEP_INTERVAL_STRONG = 100;

// ─────────────────────────────────────────────────────────────────────────────
// SETUP
// ─────────────────────────────────────────────────────────────────────────────

void setup() {

  Serial.begin(115200);

  delay(500);

  // ─────────────────────────────────────────────────────────────────────────
  // Buzzer
  // ─────────────────────────────────────────────────────────────────────────

  pinMode(BUZZER_PIN, OUTPUT);

  digitalWrite(BUZZER_PIN, LOW);

  // ─────────────────────────────────────────────────────────────────────────
  // Button
  // ─────────────────────────────────────────────────────────────────────────

  pinMode(BUTTON_PIN, INPUT_PULLUP);

  // ─────────────────────────────────────────────────────────────────────────
  // LEDs
  // ─────────────────────────────────────────────────────────────────────────

  for (byte i = 0; i < 4; i++) {

    pinMode(ledPins[i], OUTPUT);

    digitalWrite(ledPins[i], LOW);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // LCD
  // ─────────────────────────────────────────────────────────────────────────

  lcd.init();

  lcd.backlight();

  lcd.setCursor(0, 0);
  lcd.print("ND-SCPM EQ SYS");

  lcd.setCursor(0, 1);
  lcd.print("System Check...");

  delay(1000);

  // ─────────────────────────────────────────────────────────────────────────
  // MPU6050
  // ─────────────────────────────────────────────────────────────────────────

  Wire.begin(21, 22);

  mpu.initialize();

  // ±2g = highest accelerometer resolution
  mpu.setFullScaleAccelRange(MPU6050_ACCEL_FS_2);

  if (!mpu.testConnection()) {

    lcd.clear();

    lcd.print("MPU6050 ERROR!");

    Serial.println(
      "[ERROR] MPU6050 connection failed!"
    );

    while (1);
  }

  Serial.println(
    "[OK] MPU6050 connected."
  );

  // ─────────────────────────────────────────────────────────────────────────
  // SSL
  // ─────────────────────────────────────────────────────────────────────────

  secureClient.setInsecure();

  // ─────────────────────────────────────────────────────────────────────────
  // WiFi
  // ─────────────────────────────────────────────────────────────────────────

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("WiFi: connecting");

  lcd.setCursor(0, 1);
  lcd.print("(background)");

  startWiFiConnect();

  delay(800);

  // ─────────────────────────────────────────────────────────────────────────
  // Calibration
  // ─────────────────────────────────────────────────────────────────────────

  performCalibration();

  // Make absolutely sure the system starts from zero.
  filteredGal = 0.0f;
  currentGal = 0.0f;

  currentState = STATE_IDLE;

  Serial.println();
  Serial.println(
    "[OK] Calibration finished."
  );

  Serial.println(
    "[OK] System ready. Monitoring..."
  );

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("Status: Ready");

  lcd.setCursor(0, 1);
  lcd.print("Gal: 0.00");
}

// ─────────────────────────────────────────────────────────────────────────────
// LOOP
// ─────────────────────────────────────────────────────────────────────────────

void loop() {

  // ─────────────────────────────────────────────────────────────────────────
  // 0. Button and WiFi
  // ─────────────────────────────────────────────────────────────────────────

  checkButton();

  updateWiFiStatus();

  // ─────────────────────────────────────────────────────────────────────────
  // 1. Read MPU6050
  // ─────────────────────────────────────────────────────────────────────────

  int16_t rawX;
  int16_t rawY;
  int16_t rawZ;

  mpu.getAcceleration(
    &rawX,
    &rawY,
    &rawZ
  );

  // Convert raw readings to Gal.
  //
  // At ±2g:
  //
  // 16384 counts = 1g
  // 1g ≈ 980 Gal
  //

  float measuredX =
    (rawX / 16384.0f) * 980.0f;

  float measuredY =
    (rawY / 16384.0f) * 980.0f;

  float measuredZ =
    (rawZ / 16384.0f) * 980.0f;

  // ─────────────────────────────────────────────────────────────────────────
  // 2. Remove calibrated resting acceleration
  // ─────────────────────────────────────────────────────────────────────────
  //
  // IMPORTANT:
  //
  // We subtract the ACTUAL resting X/Y/Z vector.
  //
  // Therefore we do NOT assume:
  // X = 0
  // Y = 0
  // Z = 980
  //
  // This works even if the MPU6050 is slightly tilted.
  //

  float aX =
    measuredX - offsetX;

  float aY =
    measuredY - offsetY;

  float aZ =
    measuredZ - offsetZ;

  // ─────────────────────────────────────────────────────────────────────────
  // 3. Calculate PGA magnitude
  // ─────────────────────────────────────────────────────────────────────────

  float rawGal =
    sqrt(
      aX * aX +
      aY * aY +
      aZ * aZ
    );

  // ─────────────────────────────────────────────────────────────────────────
  // 4. Remove stationary noise
  // ─────────────────────────────────────────────────────────────────────────

  if (rawGal < noiseFloor) {

    rawGal = 0.0f;
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 5. EMA filtering
  // ─────────────────────────────────────────────────────────────────────────

  filteredGal =
    (filteredGal * 0.80f) +
    (rawGal * 0.20f);

  currentGal = filteredGal;

  // Prevent tiny residual readings.
  if (currentGal < noiseFloor) {

    currentGal = 0.0f;
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 6. Determine alert levels
  // ─────────────────────────────────────────────────────────────────────────

  bool isLevel3 =
    (currentGal >= THRESHOLD_STRONG);

  bool isLevel2 =
    (currentGal >= THRESHOLD_WARNING) &&
    !isLevel3;

  // ─────────────────────────────────────────────────────────────────────────
  // 7. State machine
  // ─────────────────────────────────────────────────────────────────────────

  updateStateMachine();

  bool alertActive =
    (currentState == STATE_ALARM_ACTIVE ||
     currentState == STATE_VERIFICATION ||
     currentState == STATE_COUNTDOWN);

  // ─────────────────────────────────────────────────────────────────────────
  // 8. Buzzer
  // ─────────────────────────────────────────────────────────────────────────
  //
  // Earthquake buzzer ONLY starts at 115 Gal.
  //

  if (!BUZZER_ENABLED || !isLevel3) {

    digitalWrite(
      BUZZER_PIN,
      LOW
    );

  } else {

    digitalWrite(
      BUZZER_PIN,
      (millis() % 200 < 100)
        ? HIGH
        : LOW
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 9. LEDs
  // ─────────────────────────────────────────────────────────────────────────

  updateLEDs(
    alertActive,
    isLevel3
  );

  // ─────────────────────────────────────────────────────────────────────────
  // 10. Serial monitor
  // ─────────────────────────────────────────────────────────────────────────

  Serial.printf(
    "[Sensor] Gal: %.2f | Raw: %.2f | Noise: %.2f | State:%d | L2:%d L3:%d | WiFi:%d\n",
    currentGal,
    rawGal,
    noiseFloor,
    currentState,
    isLevel2,
    isLevel3,
    WiFi.status() == WL_CONNECTED
  );

  // ─────────────────────────────────────────────────────────────────────────
  // 11. Server
  // ─────────────────────────────────────────────────────────────────────────
  //
  // FIX: lastSendTime is set AFTER sendDataToServer() returns, not before.
  // The HTTP call is blocking (up to 8s timeout). If we set lastSendTime
  // before the call, the 2s interval is measured from the start of the
  // previous call, which means the next send could fire while the previous
  // one is still in progress. Setting it after ensures the interval is
  // measured from when the HTTP call completed.
  //

  if (
    alertActive &&
    (millis() - lastSendTime > SEND_INTERVAL)
  ) {

    sendDataToServer(currentGal);

    lastSendTime = millis();
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 12. LCD
  // ─────────────────────────────────────────────────────────────────────────

  updateLCD(isLevel3);

  delay(50);
}

// ─────────────────────────────────────────────────────────────────────────────
// STATE MACHINE
// ─────────────────────────────────────────────────────────────────────────────

void updateStateMachine() {

  unsigned long now = millis();

  switch (currentState) {

    case STATE_CALIBRATING:

      break;

    // ───────────────────────────────────────────────────────────────────────
    // IDLE
    // ───────────────────────────────────────────────────────────────────────

    case STATE_IDLE:

      if (currentGal >= THRESHOLD_WARNING) {

        currentState = STATE_ALARM_ACTIVE;

        Serial.println(
          "[State] >= 14 Gal -> ALARM_ACTIVE"
        );
      }

      break;

    // ───────────────────────────────────────────────────────────────────────
    // ALARM ACTIVE
    // ───────────────────────────────────────────────────────────────────────

    case STATE_ALARM_ACTIVE:

      if (currentGal < THRESHOLD_WARNING) {

        currentState = STATE_VERIFICATION;

        verificationStartTime = now;

        Serial.println(
          "[State] Below 14 Gal -> VERIFICATION"
        );
      }

      break;

    // ───────────────────────────────────────────────────────────────────────
    // VERIFICATION
    // ───────────────────────────────────────────────────────────────────────

    case STATE_VERIFICATION:

      if (currentGal >= THRESHOLD_WARNING) {

        currentState = STATE_ALARM_ACTIVE;

        Serial.println(
          "[State] Shaking resumed -> ALARM_ACTIVE"
        );

      } else if (
        now - verificationStartTime >=
        VERIFICATION_TIME
      ) {

        currentState = STATE_COUNTDOWN;

        countdownStartTime = now;

        Serial.println(
          "[State] Calm verified -> COUNTDOWN"
        );
      }

      break;

    // ───────────────────────────────────────────────────────────────────────
    // COUNTDOWN
    // ───────────────────────────────────────────────────────────────────────

    case STATE_COUNTDOWN:

      if (currentGal >= THRESHOLD_WARNING) {

        currentState = STATE_ALARM_ACTIVE;

        Serial.println(
          "[State] Shaking resumed -> ALARM_ACTIVE"
        );

      } else if (
        now - countdownStartTime >=
        COUNTDOWN_TIME
      ) {

        currentState = STATE_IDLE;

        Serial.println(
          "[State] Cleared -> IDLE"
        );
      }

      break;
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// IMPROVED CALIBRATION
// ─────────────────────────────────────────────────────────────────────────────

void performCalibration() {

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("CALIBRATING...");

  lcd.setCursor(0, 1);
  lcd.print("KEEP SENSOR STILL");

  Serial.println();
  Serial.println(
    "========================================"
  );

  Serial.println(
    "[CALIBRATION]"
  );

  Serial.println(
    "Keep MPU6050 completely still."
  );

  Serial.println(
    "Do not touch the sensor."
  );

  Serial.println(
    "========================================"
  );

  // ─────────────────────────────────────────────────────────────────────────
  // Stabilization period
  // ─────────────────────────────────────────────────────────────────────────

  for (int i = 3; i > 0; i--) {

    lcd.setCursor(0, 1);

    lcd.print("Starting: ");
    lcd.print(i);
    lcd.print("       ");

    Serial.printf(
      "[Calib] Starting in %d...\n",
      i
    );

    delay(1000);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Collect resting acceleration vector
  // ─────────────────────────────────────────────────────────────────────────

  double sumX = 0.0;
  double sumY = 0.0;
  double sumZ = 0.0;

  Serial.println(
    "[Calib] Collecting 500 stationary samples..."
  );

  for (
    int i = 0;
    i < CALIBRATION_SAMPLES;
    i++
  ) {

    int16_t rawX;
    int16_t rawY;
    int16_t rawZ;

    mpu.getAcceleration(
      &rawX,
      &rawY,
      &rawZ
    );

    float measuredX =
      (rawX / 16384.0f) * 980.0f;

    float measuredY =
      (rawY / 16384.0f) * 980.0f;

    float measuredZ =
      (rawZ / 16384.0f) * 980.0f;

    // Store the actual stationary vector.
    sumX += measuredX;
    sumY += measuredY;
    sumZ += measuredZ;

    if (i % 50 == 0) {

      lcd.setCursor(0, 1);

      lcd.print(i);
      lcd.print("/");
      lcd.print(CALIBRATION_SAMPLES);
      lcd.print("        ");
    }

    delay(CALIBRATION_DELAY);

    updateWiFiStatus();
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Calculate actual resting vector
  // ─────────────────────────────────────────────────────────────────────────

  offsetX =
    sumX / CALIBRATION_SAMPLES;

  offsetY =
    sumY / CALIBRATION_SAMPLES;

  offsetZ =
    sumZ / CALIBRATION_SAMPLES;

  Serial.println();

  Serial.println(
    "[Calib] Resting acceleration vector:"
  );

  Serial.printf(
    "[Calib] X = %.3f Gal\n",
    offsetX
  );

  Serial.printf(
    "[Calib] Y = %.3f Gal\n",
    offsetY
  );

  Serial.printf(
    "[Calib] Z = %.3f Gal\n",
    offsetZ
  );

  Serial.printf(
    "[Calib] Magnitude = %.3f Gal\n",
    sqrt(
      offsetX * offsetX +
      offsetY * offsetY +
      offsetZ * offsetZ
    )
  );

  // ─────────────────────────────────────────────────────────────────────────
  // Measure remaining stationary noise
  // ─────────────────────────────────────────────────────────────────────────

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("MEASURING NOISE");

  lcd.setCursor(0, 1);
  lcd.print("KEEP STILL...");

  Serial.println(
    "[Calib] Measuring stationary noise..."
  );

  double noiseSum = 0.0;

  for (
    int i = 0;
    i < NOISE_SAMPLES;
    i++
  ) {

    int16_t rawX;
    int16_t rawY;
    int16_t rawZ;

    mpu.getAcceleration(
      &rawX,
      &rawY,
      &rawZ
    );

    float measuredX =
      (rawX / 16384.0f) * 980.0f;

    float measuredY =
      (rawY / 16384.0f) * 980.0f;

    float measuredZ =
      (rawZ / 16384.0f) * 980.0f;

    // Remove actual calibrated resting vector.
    float aX =
      measuredX - offsetX;

    float aY =
      measuredY - offsetY;

    float aZ =
      measuredZ - offsetZ;

    float sampleGal =
      sqrt(
        aX * aX +
        aY * aY +
        aZ * aZ
      );

    noiseSum += sampleGal;

    if (i % 20 == 0) {

      lcd.setCursor(0, 1);

      lcd.print(i);
      lcd.print("/");
      lcd.print(NOISE_SAMPLES);
      lcd.print("        ");
    }

    delay(CALIBRATION_DELAY);
  }

  noiseFloor =
    noiseSum / NOISE_SAMPLES;

  // Minimum safety deadband.
  if (
    noiseFloor < MIN_NOISE_DEADBAND
  ) {

    noiseFloor =
      MIN_NOISE_DEADBAND;
  }

  Serial.printf(
    "[Calib] Noise floor = %.3f Gal\n",
    noiseFloor
  );

  Serial.println(
    "[Calib] Calibration complete."
  );

  Serial.println(
    "========================================"
  );

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("CALIBRATION OK");

  lcd.setCursor(0, 1);
  lcd.print("Noise:");
  lcd.print(noiseFloor, 1);
  lcd.print(" Gal");

  delay(1500);
}

// ─────────────────────────────────────────────────────────────────────────────
// WiFi
// ─────────────────────────────────────────────────────────────────────────────

void startWiFiConnect() {

  Serial.printf(
    "[WiFi] Connecting to '%s' (background)\n",
    ssid
  );

  WiFi.mode(WIFI_STA);

  WiFi.begin(
    ssid,
    password
  );

  wifiConnecting = true;

  wifiConnectStarted = millis();
}

// ─────────────────────────────────────────────────────────────────────────────

void updateWiFiStatus() {

  if (wifiConnecting) {

    if (WiFi.status() == WL_CONNECTED) {

      wifiConnecting = false;

      Serial.printf(
        "[WiFi] Connected! IP: %s\n",
        WiFi.localIP().toString().c_str()
      );

      // Existing WiFi connection beep.
      if (BUZZER_ENABLED) {

        digitalWrite(
          BUZZER_PIN,
          HIGH
        );

        delay(80);

        digitalWrite(
          BUZZER_PIN,
          LOW
        );
      }

    } else if (
      millis() - wifiConnectStarted >
      WIFI_CONNECT_ATTEMPT_TIMEOUT
    ) {

      Serial.println(
        "[WiFi] Attempt timed out - continuing offline, will retry."
      );

      wifiConnecting = false;

      lastWiFiCheck = millis();
    }

  } else {

    if (
      millis() - lastWiFiCheck >
      WIFI_CHECK_INTERVAL
    ) {

      lastWiFiCheck = millis();

      if (
        WiFi.status() != WL_CONNECTED
      ) {

        Serial.println(
          "[WiFi] Disconnected - reconnecting in background..."
        );

        WiFi.disconnect();

        startWiFiConnect();
      }
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// BUTTON
// ─────────────────────────────────────────────────────────────────────────────

void checkButton() {

  byte reading =
    digitalRead(BUTTON_PIN);

  if (
    reading != lastButtonState
  ) {

    lastDebounceTime =
      millis();
  }

  if (
    millis() - lastDebounceTime >
    DEBOUNCE_DELAY
  ) {

    if (
      reading != buttonState
    ) {

      buttonState = reading;

      if (
        buttonState == LOW
      ) {

        forceIdleMode();
      }
    }
  }

  lastButtonState =
    reading;
}

// ─────────────────────────────────────────────────────────────────────────────

void forceIdleMode() {

  if (
    currentState == STATE_CALIBRATING
  ) {

    return;
  }

  Serial.println(
    "[Button] MANUAL RESET"
  );

  digitalWrite(
    BUZZER_PIN,
    LOW
  );

  setAllLEDs(LOW);

  ledStepIndex = 0;

  globalMmi = "";

  currentState = STATE_IDLE;

  lcd.clear();

  lcd.setCursor(0, 0);
  lcd.print("MANUAL RESET");

  lcd.setCursor(0, 1);

  lcd.print("Sent:");
  lcd.print(totalDataSent);
  lcd.print(" pkts");

  delay(1500);

  lcd.clear();
}

// ─────────────────────────────────────────────────────────────────────────────
// LEDs
// ─────────────────────────────────────────────────────────────────────────────

void setAllLEDs(byte state) {

  for (byte i = 0; i < 4; i++) {

    digitalWrite(
      ledPins[i],
      state
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────

void updateLEDs(
  bool active,
  bool strong
) {

  if (!active) {

    setAllLEDs(LOW);

    ledStepIndex = 0;

    return;
  }

  unsigned long interval =
    strong
      ? LED_STEP_INTERVAL_STRONG
      : LED_STEP_INTERVAL_WARNING;

  if (
    millis() - lastLedStep >= interval
  ) {

    lastLedStep =
      millis();

    setAllLEDs(LOW);

    digitalWrite(
      ledPins[ledStepIndex],
      HIGH
    );

    ledStepIndex =
      (ledStepIndex + 1) % 4;
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// LCD
// ─────────────────────────────────────────────────────────────────────────────

void updateLCD(
  bool isLevel3
) {

  if (
    millis() - lastLcdUpdateTime <
    LCD_REFRESH
  ) {

    return;
  }

  lastLcdUpdateTime =
    millis();

  // ─────────────────────────────────────────────────────────────────────────
  // MMI overlay
  // ─────────────────────────────────────────────────────────────────────────

  if (
    globalMmi != "" &&
    millis() - mmiDisplayStart <
    MMI_DISPLAY_DURATION
  ) {

    lcd.setCursor(0, 0);

    lcd.print("MMI: ");
    lcd.print(globalMmi);
    lcd.print("          ");

    lcd.setCursor(0, 1);

    String name =
      globalMmiName.substring(0, 16);

    lcd.print(name);

    for (
      int i = name.length();
      i < 16;
      i++
    ) {

      lcd.print(" ");
    }

    return;
  }

  globalMmi = "";

  lcd.setCursor(0, 0);

  switch (currentState) {

    case STATE_CALIBRATING:

      lcd.print("CALIBRATING...  ");

      break;

    case STATE_IDLE:

      lcd.print(
        WiFi.status() == WL_CONNECTED
          ? "SAFE (WiFi ON) "
          : "STATUS: SAFE   "
      );

      lcd.setCursor(0, 1);

      lcd.print("Gal: ");

      lcd.print(
        currentGal,
        1
      );

      lcd.print("        ");

      return;

    case STATE_ALARM_ACTIVE:

      lcd.print(
        isLevel3
          ? "!! EMERGENCY !! "
          : "! EARTHQUAKE !  "
      );

      lcd.setCursor(0, 1);

      lcd.print("Gal: ");

      lcd.print(
        currentGal,
        2
      );

      lcd.print("      ");

      return;

    case STATE_VERIFICATION:

      lcd.print("*** WARNING *** ");

      lcd.setCursor(0, 1);

      lcd.print(
        WiFi.status() == WL_CONNECTED
          ? "Alert sent      "
          : "No WiFi         "
      );

      return;

    case STATE_COUNTDOWN: {

      unsigned long elapsed =
        millis() - countdownStartTime;

      int remaining =
        (COUNTDOWN_TIME - elapsed) /
        1000 + 1;

      lcd.print("Clearing in:    ");

      lcd.setCursor(0, 1);

      lcd.print("    ");

      lcd.print(remaining);

      lcd.print(" seconds    ");

      return;
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// SERVER
// ─────────────────────────────────────────────────────────────────────────────

void sendDataToServer(
  float intensity
) {

  if (
    WiFi.status() != WL_CONNECTED
  ) {

    Serial.println(
      "[HTTP] Skipped — WiFi not connected."
    );

    return;
  }

  HTTPClient http;

  http.begin(
    secureClient,
    serverUrl
  );

  http.setTimeout(8000);

  http.addHeader(
    "Content-Type",
    "application/x-www-form-urlencoded"
  );

  String postData =
    "intensity=" +
    String(intensity, 2) +
    "&device_id=ESP32_001";

  Serial.printf(
    "[HTTP] POST → %s | Data: %s\n",
    serverUrl,
    postData.c_str()
  );

  int httpCode =
    http.POST(postData);

  if (
    httpCode == HTTP_CODE_OK
  ) {

    String response =
      http.getString();

    Serial.printf(
      "[HTTP] 200 OK — Response: %s\n",
      response.c_str()
    );

    totalDataSent++;

    JsonDocument doc;

    if (
      !deserializeJson(doc, response) &&
      doc.containsKey("mmi_level")
    ) {

      globalMmi =
        doc["mmi_level"].as<String>();

      globalMmiName =
        doc["mmi_name"].as<String>();

      mmiDisplayStart =
        millis();

      Serial.printf(
        "[MMI] Scale: %s — %s\n",
        globalMmi.c_str(),
        globalMmiName.c_str()
      );
    }

  } else {

    Serial.printf(
      "[HTTP] Error %d — %s\n",
      httpCode,
      http.errorToString(httpCode).c_str()
    );
  }

  http.end();
}
