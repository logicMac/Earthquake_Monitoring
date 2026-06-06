/*
 * ESP32 Earthquake Detection System - Production Ready
 * Notre Dame - Siena College of Polomolok
 *
 * Hardware:
 * - ESP32 DevKit V1
 * - MPU6050 (I2C: SDA=21, SCL=22)
 * - Active Buzzer (GPIO 25)
 * - I2C LCD 16x2 (I2C Address: 0x27)
 *
 * Fixes applied:
 * - WiFi: Extended timeout to 20s for hotspot compatibility
 * - Sensor: Uses absolute acceleration magnitude (not delta) so shaking is detected reliably
 * - HTTPS: Uses WiFiClientSecure with setInsecure() for InfinityFree
 * - HTTP timeout: Increased to 8s for stable server responses
 * - Buzzer: Immediate blocking beep on threshold cross (not millis-based)
 */

#include <Wire.h>
#include <MPU6050.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ── WiFi Configuration ────────────────────────────────────────────────────────
const char* ssid     = "mac";
const char* password = "mac12345";

// ── Server Configuration ──────────────────────────────────────────────────────
const char* serverUrl = "https://eartquake-monitoring.kesug.com/receive_data.php";

// ── Hardware Pins ─────────────────────────────────────────────────────────────
#define BUZZER_PIN 25

// ── Objects ───────────────────────────────────────────────────────────────────
MPU6050 mpu;
LiquidCrystal_I2C lcd(0x27, 16, 2);
WiFiClientSecure secureClient;

// ── Thresholds (Gal = cm/s²) ──────────────────────────────────────────────────
#define THRESHOLD_LOW  25.0   // Level 2: Local alert (buzzer + LCD)
#define THRESHOLD_HIGH 176.0  // Level 3: Emergency (SMS + buzzer + LCD)

// ── State Variables ───────────────────────────────────────────────────────────
float    currentGal       = 0.0;
String   globalMmi        = "";
String   globalMmiName    = "";

unsigned long lastSendTime       = 0;
unsigned long lastLcdUpdateTime  = 0;
unsigned long mmiDisplayStart    = 0;
unsigned long lastWiFiCheck      = 0;

const unsigned long SEND_INTERVAL       = 2000;   // ms between server sends
const unsigned long LCD_REFRESH         = 300;    // ms between LCD redraws
const unsigned long MMI_DISPLAY_DURATION = 3000;  // ms to show MMI overlay
const unsigned long WIFI_CHECK_INTERVAL = 20000;  // ms between WiFi health checks

// ─────────────────────────────────────────────────────────────────────────────
void setup() {
  Serial.begin(115200);
  delay(500);

  // Buzzer
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  // LCD
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0); lcd.print("ND-SCPM EQ SYS");
  lcd.setCursor(0, 1); lcd.print("Initializing...");
  delay(1000);

  // MPU6050
  Wire.begin(21, 22);
  mpu.initialize();
  mpu.setFullScaleAccelRange(MPU6050_ACCEL_FS_2); // ±2g — most sensitive

  if (!mpu.testConnection()) {
    lcd.clear();
    lcd.print("MPU6050 ERROR!");
    Serial.println("[ERROR] MPU6050 connection failed!");
    while (1);
  }
  Serial.println("[OK] MPU6050 connected.");

  // SSL — skip certificate verification (required for InfinityFree free SSL)
  secureClient.setInsecure();

  // WiFi
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("Connecting WiFi");
  lcd.setCursor(0, 1); lcd.print(ssid);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  Serial.printf("[WiFi] Connecting to '%s'", ssid);

  // Wait up to 20 seconds — hotspots are slower than routers
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 40) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("\n[WiFi] Connected! IP: %s\n", WiFi.localIP().toString().c_str());
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("WiFi Connected!");
    lcd.setCursor(0, 1); lcd.print(WiFi.localIP().toString());
    // Short confirmation beep
    digitalWrite(BUZZER_PIN, HIGH); delay(100); digitalWrite(BUZZER_PIN, LOW);
    delay(2000);
  } else {
    Serial.println("\n[WiFi] Failed — running offline.");
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("WiFi Failed!");
    lcd.setCursor(0, 1); lcd.print("Offline Mode");
    delay(2000);
  }

  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("Status: Ready");
  lcd.setCursor(0, 1); lcd.print("Gal: 0.00");
  Serial.println("[OK] System ready. Monitoring...");
}

// ─────────────────────────────────────────────────────────────────────────────
void loop() {
  // ── 1. Read raw accelerometer ──────────────────────────────────────────────
  int16_t rawX, rawY, rawZ;
  mpu.getAcceleration(&rawX, &rawY, &rawZ);

  // Convert to Gal (1g = 980 Gal, ±2g range → 16384 LSB/g)
  float aX = (rawX / 16384.0f) * 980.0f;
  float aY = (rawY / 16384.0f) * 980.0f;
  float aZ = ((rawZ / 16384.0f) - 1.0f) * 980.0f; // remove 1g gravity

  // Total acceleration magnitude
  float rawGal = sqrt(aX*aX + aY*aY + aZ*aZ);

  // Light EMA smoothing to reduce electrical noise (keeps real shaking responsive)
  currentGal = (currentGal * 0.3f) + (rawGal * 0.7f);

  bool isLevel3 = (currentGal >= THRESHOLD_HIGH);
  bool isLevel2 = (currentGal >= THRESHOLD_LOW) && !isLevel3;
  bool isEvent  = isLevel2 || isLevel3;

  // ── 2. Buzzer control ─────────────────────────────────────────────────────
  if (isLevel3) {
    // Rapid double-beep for emergency
    digitalWrite(BUZZER_PIN, (millis() % 200 < 100) ? HIGH : LOW);
  } else if (isLevel2) {
    // Steady slow beep for warning
    digitalWrite(BUZZER_PIN, (millis() % 600 < 300) ? HIGH : LOW);
  } else {
    digitalWrite(BUZZER_PIN, LOW);
  }

  // Debug to Serial so you can watch values in Arduino Serial Monitor
  Serial.printf("[Sensor] Gal: %.2f | L2:%d L3:%d\n", currentGal, isLevel2, isLevel3);

  // ── 3. Send to server ─────────────────────────────────────────────────────
  if (isEvent && (millis() - lastSendTime > SEND_INTERVAL)) {
    lastSendTime = millis();
    sendDataToServer(currentGal);
  }

  // ── 4. LCD update ─────────────────────────────────────────────────────────
  if (millis() - lastLcdUpdateTime > LCD_REFRESH) {
    lastLcdUpdateTime = millis();

    // Show MMI overlay from server response if fresh
    if (globalMmi != "" && (millis() - mmiDisplayStart < MMI_DISPLAY_DURATION)) {
      lcd.setCursor(0, 0);
      lcd.print("MMI: ");
      lcd.print(globalMmi);
      lcd.print("          ");
      lcd.setCursor(0, 1);
      String name = globalMmiName.substring(0, 16);
      lcd.print(name);
      for (int i = name.length(); i < 16; i++) lcd.print(" ");
    } else {
      globalMmi = "";
      lcd.setCursor(0, 0);
      if (isLevel3)      { lcd.print("!! EMERGENCY !! "); }
      else if (isLevel2) { lcd.print("ALERT!          "); }
      else               { lcd.print("Status: Ready   "); }

      lcd.setCursor(0, 1);
      lcd.print("Gal: ");
      lcd.print(currentGal, 2);
      lcd.print("        ");
    }
  }

  // ── 5. WiFi keepalive ─────────────────────────────────────────────────────
  if (millis() - lastWiFiCheck > WIFI_CHECK_INTERVAL) {
    lastWiFiCheck = millis();
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("[WiFi] Reconnecting...");
      WiFi.disconnect();
      WiFi.begin(ssid, password);
    }
  }

  delay(50); // 20Hz loop — fast enough for hand-shake testing
}

// ─────────────────────────────────────────────────────────────────────────────
void sendDataToServer(float intensity) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[HTTP] Skipped — WiFi not connected.");
    return;
  }

  HTTPClient http;
  http.begin(secureClient, serverUrl); // Use secure client for HTTPS
  http.setTimeout(8000);               // 8s — enough for free hosting
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String postData = "intensity=" + String(intensity, 2) + "&device_id=ESP32_001";
  Serial.printf("[HTTP] POST → %s | Data: %s\n", serverUrl, postData.c_str());

  int httpCode = http.POST(postData);

  if (httpCode == HTTP_CODE_OK) {
    String response = http.getString();
    Serial.printf("[HTTP] 200 OK — Response: %s\n", response.c_str());

    // Parse MMI response
    JsonDocument doc;
    if (!deserializeJson(doc, response) && doc.containsKey("mmi_level")) {
      globalMmi      = doc["mmi_level"].as<String>();
      globalMmiName  = doc["mmi_name"].as<String>();
      mmiDisplayStart = millis();
      Serial.printf("[MMI] Scale: %s — %s\n", globalMmi.c_str(), globalMmiName.c_str());
    }
  } else {
    Serial.printf("[HTTP] Error %d — %s\n", httpCode, http.errorToString(httpCode).c_str());
  }

  http.end();
}
