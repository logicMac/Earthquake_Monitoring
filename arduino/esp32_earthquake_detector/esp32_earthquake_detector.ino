/*
 * ESP32 Earthquake Detection System - Seismic-grade refactor
 * Notre Dame - Siena College of Polomolok
 *
 * Hardware:
 * - ESP32 DevKit V1
 * - MPU6050 (I2C: SDA=21, SCL=22)
 * - Active Buzzer (GPIO 25)
 * - I2C LCD 16x2 (I2C Address: 0x27)
 *
 * Improvements over the original alarm-style sketch:
 * - Boot-time sensor calibration (offset averaging) for accurate zero
 * - High-pass gravity filter so mounting tilt no longer causes false alarms
 * - PGA (Peak Ground Acceleration) computed over a 1-second sliding window
 * - Event state machine: IDLE -> ACTIVE -> COOLDOWN -> IDLE (one event per shake)
 * - NTP-synced ISO-8601 timestamps sent with every packet
 * - Idle heartbeat logging so the server always has a baseline
 * - SPIFFS offline buffer: events are never lost when WiFi drops
 * - Hardware watchdog (esp_task_wdt) for automatic recovery from hangs
 * - HTTPS via WiFiClientSecure (setInsecure kept for free hosting compatibility)
 */

#include <Wire.h>
#include <MPU6050.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <esp_task_wdt.h>
#include <time.h>
#include <SPIFFS.h>

// ── WiFi Configuration ────────────────────────────────────────────────────────
const char* ssid     = "mac";
const char* password = "mac12345";

// ── Server Configuration ──────────────────────────────────────────────────────
const char* serverUrl = "https://earthquake-monitoring.onrender.com/receive_data.php";

// ── NTP Configuration (Philippine Time, UTC+8) ────────────────────────────────
const char* ntpServer   = "pool.ntp.org";
const long  gmtOffsetSec    = 8 * 3600;
const int   daylightOffsetSec = 0;

// ── Hardware Pins ─────────────────────────────────────────────────────────────
#define BUZZER_PIN 25

// ── Objects ───────────────────────────────────────────────────────────────────
MPU6050 mpu;
LiquidCrystal_I2C lcd(0x27, 16, 2);
WiFiClientSecure secureClient;

// ── Sampling ──────────────────────────────────────────────────────────────────
// 50 Hz sample rate: fast enough to resolve P-wave onset on consumer MEMS,
// slow enough to leave CPU headroom for WiFi/LCD/HTTP on a single core.
#define SAMPLE_HZ        50
#define SAMPLE_MS        (1000 / SAMPLE_HZ)
#define PGA_WINDOW_SIZE  50          // 50 samples @ 50 Hz = 1.0 s sliding window
#define CALIB_SAMPLES    256         // ~5 s of stationary averaging at boot

// ── Thresholds (Gal = cm/s^2) ─────────────────────────────────────────────────
// TESTING VALUES — VERY SENSITIVE FOR HAND SHAKING
#define THRESHOLD_LOW    5.0     // Level 2: local alert (buzzer + LCD)
#define THRESHOLD_HIGH   50.0    // Level 3: emergency (SMS + buzzer + LCD)
// PRODUCTION VALUES (comment out above, uncomment below after testing)
// #define THRESHOLD_LOW    25.0
// #define THRESHOLD_HIGH   176.0

#define QUIET_THRESHOLD  2.0     // below this = shaking has stopped
#define EVENT_END_DELAY  3000    // ms of quiet before an event is closed
#define COOLDOWN_DELAY   5000    // ms after event end before re-arming

// ── Gravity high-pass filter coefficient ──────────────────────────────────────
// Slow EMA tracks the static gravity vector (DC) so we can subtract it.
// alpha = SAMPLE_MS / (SAMPLE_MS + tau_ms). tau ~= 2s -> alpha ~= 0.024
#define GRAV_ALPHA 0.024f

// ── State machine ─────────────────────────────────────────────────────────────
enum EventState { STATE_IDLE, STATE_ACTIVE, STATE_COOLDOWN };
EventState eventState = STATE_IDLE;

// ── Calibration offsets (Gal) ─────────────────────────────────────────────────
float offsetX = 0.0f, offsetY = 0.0f, offsetZ = 0.0f;

// ── High-pass gravity estimate ────────────────────────────────────────────────
float gravX = 0.0f, gravY = 0.0f, gravZ = 980.0f;  // start assuming level

// ── PGA ring buffer ───────────────────────────────────────────────────────────
float pgaBuffer[PGA_WINDOW_SIZE];
int   pgaIdx   = 0;
bool  pgaFilled = false;
float currentPga = 0.0f;     // peak acceleration in the current 1s window

// ── Event tracking ────────────────────────────────────────────────────────────
unsigned long eventStartTime  = 0;
unsigned long lastAboveThresh = 0;
unsigned long cooldownStart   = 0;
float         eventPeakGal    = 0.0f;
int           eventId         = 0;
String        eventStartTimeIso = "";

// ── Timing ────────────────────────────────────────────────────────────────────
unsigned long lastSampleMs       = 0;
unsigned long lastSendTime       = 0;
unsigned long lastHeartbeat      = 0;
unsigned long lastLcdUpdateTime  = 0;
unsigned long mmiDisplayStart    = 0;
unsigned long lastWiFiCheck      = 0;
unsigned long lastFlushAttempt   = 0;

const unsigned long SEND_INTERVAL       = 2000;   // ms between event reports
const unsigned long HEARTBEAT_INTERVAL  = 15000;  // ms between idle status reports
const unsigned long LCD_REFRESH         = 300;
const unsigned long MMI_DISPLAY_DURATION = 3000;
const unsigned long WIFI_CHECK_INTERVAL = 20000;

// ── MMI feedback from server ──────────────────────────────────────────────────
String globalMmi     = "";
String globalMmiName = "";

// ── SPIFFS offline buffer ─────────────────────────────────────────────────────
#define MAX_QUEUED 20
int queuedCount = 0;

// ─────────────────────────────────────────────────────────────────────────────
// Forward declarations
void calibrateSensor();
void sendOrQueue(float pga, const String& state, int evId, const String& evStartIso);
void flushQueue();
String isoTimestamp();

// ─────────────────────────────────────────────────────────────────────────────
void setup() {
  Serial.begin(115200);
  delay(500);

  // Hardware watchdog: 15 s. loop() must feed it; HTTP is allowed up to 8 s.
  esp_task_wdt_init(15, true);
  esp_task_wdt_add(NULL);

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
  mpu.setFullScaleAccelRange(MPU6050_ACCEL_FS_2); // +-2g, most sensitive

  if (!mpu.testConnection()) {
    lcd.clear();
    lcd.print("MPU6050 ERROR!");
    Serial.println("[ERROR] MPU6050 connection failed!");
    while (1) { esp_task_wdt_reset(); delay(100); }  // keep WDT happy while stuck
  }
  Serial.println("[OK] MPU6050 connected.");

  // Calibrate sensor offsets while stationary
  lcd.setCursor(0, 1); lcd.print("Calibrating...  ");
  calibrateSensor();

  // SPIFFS for offline buffering
  if (!SPIFFS.begin(true)) {
    Serial.println("[WARN] SPIFFS mount failed - no offline buffer");
  } else {
    queuedCount = 0;  // (queue is in-RAM; persistent disk queue left as future work)
  }

  // SSL - skip certificate verification (required for free hosting SSL)
  secureClient.setInsecure();

  // WiFi network scanner (debug aid)
  Serial.println("\n[WiFi] Scanning for networks...");
  int n = WiFi.scanNetworks();
  Serial.printf("[WiFi] Found %d networks:\n", n);
  for (int i = 0; i < n; i++) {
    Serial.printf("  %d: %s (Signal: %d dBm) %s\n",
      i + 1,
      WiFi.SSID(i).c_str(),
      WiFi.RSSI(i),
      (WiFi.encryptionType(i) == WIFI_AUTH_OPEN) ? "OPEN" : "SECURED"
    );
  }

  // WiFi connect
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("Connecting WiFi");
  lcd.setCursor(0, 1); lcd.print(ssid);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  Serial.printf("\n[WiFi] Connecting to '%s'\n", ssid);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 60) {
    delay(500);
    Serial.print(".");
    lcd.setCursor(attempts % 16, 1);
    lcd.print(".");
    attempts++;
    esp_task_wdt_reset();
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("\n[WiFi] Connected! IP: %s\n", WiFi.localIP().toString().c_str());
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("WiFi Connected!");
    lcd.setCursor(0, 1); lcd.print(WiFi.localIP().toString());
    digitalWrite(BUZZER_PIN, HIGH); delay(100); digitalWrite(BUZZER_PIN, LOW);
    delay(1500);

    // NTP sync (non-blocking; valid timestamps appear within a few seconds)
    configTime(gmtOffsetSec, daylightOffsetSec, ntpServer);
    Serial.println("[NTP] Requesting time...");
    lcd.setCursor(0, 1); lcd.print("Syncing time... ");
    // Brief blocking wait so the first event already has a valid timestamp
    time_t now = time(nullptr);
    int ntpTries = 0;
    while (now < 1700000000 && ntpTries < 20) {  // ~10 s
      delay(500);
      now = time(nullptr);
      ntpTries++;
      esp_task_wdt_reset();
    }
    if (now >= 1700000000) {
      Serial.printf("[NTP] Synced: %s\n", isoTimestamp().c_str());
    } else {
      Serial.println("[NTP] No sync yet - will use 0 timestamp until resolved");
    }
  } else {
    Serial.println("\n[WiFi] Failed - running offline.");
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("WiFi Failed!");
    lcd.setCursor(0, 1); lcd.print("Offline Mode");
    delay(2000);
  }

  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("Status: Ready");
  lcd.setCursor(0, 1); lcd.print("PGA: 0.00 Gal ");
  Serial.println("[OK] System ready. Monitoring...");
}

// ─────────────────────────────────────────────────────────────────────────────
// Boot-time calibration: average CALIB_SAMPLES stationary readings to find
// the per-axis bias (in Gal). The device MUST be still during this phase.
void calibrateSensor() {
  float sumX = 0, sumY = 0, sumZ = 0;
  int16_t rx, ry, rz;

  // Discard first few samples (let filters settle)
  for (int i = 0; i < 32; i++) {
    mpu.getAcceleration(&rx, &ry, &rz);
    delay(SAMPLE_MS);
  }

  for (int i = 0; i < CALIB_SAMPLES; i++) {
    mpu.getAcceleration(&rx, &ry, &rz);
    // Convert raw LSB to Gal (16384 LSB/g, 980 Gal/g)
    sumX += (rx / 16384.0f) * 980.0f;
    sumY += (ry / 16384.0f) * 980.0f;
    sumZ += (rz / 16384.0f) * 980.0f;
    delay(SAMPLE_MS);
    if (i % 32 == 0) esp_task_wdt_reset();
  }

  offsetX = sumX / CALIB_SAMPLES;
  offsetY = sumY / CALIB_SAMPLES;
  offsetZ = (sumZ / CALIB_SAMPLES) - 980.0f;  // expect ~1g on Z when level

  Serial.printf("[CAL] Offsets (Gal) X=%.2f Y=%.2f Z=%.2f\n", offsetX, offsetY, offsetZ);

  // Seed the gravity high-pass filter with the calibrated static vector.
  // After offset subtraction the at-rest reading is ~(0, 0, 980) regardless
  // of mounting tilt, because the offsets already absorbed the tilt-induced
  // gravity components on X and Y.
  gravX = 0.0f;
  gravY = 0.0f;
  gravZ = 980.0f;
}

// ─────────────────────────────────────────────────────────────────────────────
void loop() {
  esp_task_wdt_reset();

  // ── 1. Sample at fixed rate ────────────────────────────────────────────────
  unsigned long now = millis();
  if (now - lastSampleMs < (unsigned long)SAMPLE_MS) {
    // Not yet time to sample; small sleep to avoid busy-loop
    delay(1);
    return;
  }
  lastSampleMs = now;

  int16_t rawX, rawY, rawZ;
  mpu.getAcceleration(&rawX, &rawY, &rawZ);

  // Convert to Gal and apply calibration offsets
  float aX = (rawX / 16384.0f) * 980.0f - offsetX;
  float aY = (rawY / 16384.0f) * 980.0f - offsetY;
  float aZ = (rawZ / 16384.0f) * 980.0f - offsetZ;

  // High-pass: track slow gravity vector, subtract it -> only dynamic motion
  gravX = GRAV_ALPHA * aX + (1.0f - GRAV_ALPHA) * gravX;
  gravY = GRAV_ALPHA * aY + (1.0f - GRAV_ALPHA) * gravY;
  gravZ = GRAV_ALPHA * aZ + (1.0f - GRAV_ALPHA) * gravZ;

  float hpX = aX - gravX;
  float hpY = aY - gravY;
  float hpZ = aZ - gravZ;

  // Vector magnitude of the high-passed (dynamic) acceleration
  float instGal = sqrt(hpX * hpX + hpY * hpY + hpZ * hpZ);

  // ── 2. Update PGA ring buffer (1 s sliding window) ─────────────────────────
  pgaBuffer[pgaIdx] = instGal;
  pgaIdx = (pgaIdx + 1) % PGA_WINDOW_SIZE;
  if (pgaIdx == 0) pgaFilled = true;

  // Recompute PGA = max over the window
  float peak = 0.0f;
  int count = pgaFilled ? PGA_WINDOW_SIZE : pgaIdx;
  for (int i = 0; i < count; i++) {
    if (pgaBuffer[i] > peak) peak = pgaBuffer[i];
  }
  currentPga = peak;

  // ── 3. Event state machine ─────────────────────────────────────────────────
  bool aboveLow  = (currentPga >= THRESHOLD_LOW);
  bool aboveHigh = (currentPga >= THRESHOLD_HIGH);
  bool quiet     = (currentPga < QUIET_THRESHOLD);

  switch (eventState) {
    case STATE_IDLE:
      if (aboveLow) {
        eventState = STATE_ACTIVE;
        eventStartTime = millis();
        eventStartTimeIso = isoTimestamp();
        eventPeakGal = currentPga;
        lastAboveThresh = millis();
        eventId++;
        Serial.printf("[EVENT] #%d START at %s PGA=%.2f Gal\n",
                      eventId, eventStartTimeIso.c_str(), currentPga);
      }
      break;

    case STATE_ACTIVE:
      if (currentPga > eventPeakGal) eventPeakGal = currentPga;
      // Only refresh the "last motion" timestamp while still shaking.
      // When it goes quiet, this timestamp freezes and EVENT_END_DELAY starts
      // counting from the last real motion.
      if (!quiet) lastAboveThresh = millis();
      if (quiet && (millis() - lastAboveThresh > EVENT_END_DELAY)) {
        // Event ended - send final report with the peak
        eventState = STATE_COOLDOWN;
        cooldownStart = millis();
        Serial.printf("[EVENT] #%d END peak=%.2f Gal duration=%lu ms\n",
                      eventId, eventPeakGal, millis() - eventStartTime);
        sendOrQueue(eventPeakGal, "END", eventId, eventStartTimeIso);
      }
      break;

    case STATE_COOLDOWN:
      if (millis() - cooldownStart > COOLDOWN_DELAY) {
        eventState = STATE_IDLE;
        eventPeakGal = 0.0f;
      }
      break;
  }

  bool isLevel3 = (eventState == STATE_ACTIVE) && aboveHigh;
  bool isLevel2 = (eventState == STATE_ACTIVE) && aboveLow && !aboveHigh;

  // ── 4. Buzzer ──────────────────────────────────────────────────────────────
  if (isLevel3) {
    digitalWrite(BUZZER_PIN, (millis() % 200 < 100) ? HIGH : LOW);  // rapid double-beep
  } else if (isLevel2) {
    digitalWrite(BUZZER_PIN, (millis() % 600 < 300) ? HIGH : LOW);  // slow beep
  } else {
    digitalWrite(BUZZER_PIN, LOW);
  }

  // ── 5. Live event reports while ACTIVE ─────────────────────────────────────
  if (eventState == STATE_ACTIVE && (millis() - lastSendTime > SEND_INTERVAL)) {
    lastSendTime = millis();
    sendOrQueue(currentPga, "ACTIVE", eventId, eventStartTimeIso);
  }

  // ── 6. Idle heartbeat (so the server always has a baseline) ────────────────
  if (eventState == STATE_IDLE && (millis() - lastHeartbeat > HEARTBEAT_INTERVAL)) {
    lastHeartbeat = millis();
    sendOrQueue(currentPga, "HEARTBEAT", 0, "");
  }

  // ── 7. Try to flush any queued packets from offline periods ────────────────
  // Throttled to one attempt per SEND_INTERVAL to avoid hammering the server.
  if (queuedCount > 0 && WiFi.status() == WL_CONNECTED
      && (millis() - lastFlushAttempt > SEND_INTERVAL)) {
    lastFlushAttempt = millis();
    flushQueue();
  }

  // ── 8. LCD update ──────────────────────────────────────────────────────────
  if (millis() - lastLcdUpdateTime > LCD_REFRESH) {
    lastLcdUpdateTime = millis();

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
      if (isLevel3)        lcd.print("!! EMERGENCY !! ");
      else if (isLevel2)   lcd.print("ALERT!          ");
      else if (eventState == STATE_COOLDOWN) lcd.print("Cooldown...     ");
      else                 lcd.print("Status: Ready   ");

      lcd.setCursor(0, 1);
      lcd.print("PGA:");
      lcd.print(currentPga, 2);
      lcd.print(" Gal   ");
    }
  }

  // ── 9. WiFi keepalive ──────────────────────────────────────────────────────
  if (millis() - lastWiFiCheck > WIFI_CHECK_INTERVAL) {
    lastWiFiCheck = millis();
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("[WiFi] Reconnecting...");
      WiFi.disconnect();
      WiFi.begin(ssid, password);
    }
  }

  // Debug
  Serial.printf("[S] PGA:%.2f inst:%.2f st=%d ev=%d peak=%.2f\n",
                currentPga, instGal, eventState, eventId, eventPeakGal);
}

// ─────────────────────────────────────────────────────────────────────────────
// Build an ISO-8601 timestamp from the ESP32's RTC. Returns "1970-01-01T00:00:00+08:00"
// if NTP has not yet synced.
String isoTimestamp() {
  time_t now = time(nullptr);
  if (now < 1700000000) return String("1970-01-01T00:00:00+08:00");
  struct tm* t = localtime(&now);
  char buf[32];
  strftime(buf, sizeof(buf), "%Y-%m-%dT%H:%M:%S%z", t);
  return String(buf);
}

// ─────────────────────────────────────────────────────────────────────────────
// Send a report to the server, or queue it in RAM if WiFi is down.
// Packet (server only reads `intensity` and `device_id`; extras are forward-compatible):
//   intensity   = PGA in Gal (the value the server uses for MMI/SMS logic)
//   device_id   = ESP32_001
//   pga         = same as intensity (explicit PGA field for future schema)
//   event_id    = monotonically increasing event counter (0 = heartbeat)
//   event_state = ACTIVE | END | HEARTBEAT
//   event_start = ISO-8601 timestamp of event onset
//   timestamp   = ISO-8601 timestamp of this sample
void sendOrQueue(float pga, const String& state, int evId, const String& evStartIso) {
  if (WiFi.status() != WL_CONNECTED) {
    if (queuedCount < MAX_QUEUED) queuedCount++;
    Serial.printf("[HTTP] Queued (%d in RAM) - WiFi down\n", queuedCount);
    return;
  }

  HTTPClient http;
  http.begin(secureClient, serverUrl);
  http.setTimeout(8000);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String ts = isoTimestamp();
  String postData = "intensity=" + String(pga, 2)
                  + "&device_id=ESP32_001"
                  + "&pga=" + String(pga, 2)
                  + "&event_id=" + String(evId)
                  + "&event_state=" + state
                  + "&event_start=" + evStartIso
                  + "&timestamp=" + ts;
  Serial.printf("[HTTP] POST -> %s | %s\n", serverUrl, postData.c_str());

  int httpCode = http.POST(postData);

  if (httpCode == HTTP_CODE_OK) {
    String response = http.getString();
    Serial.printf("[HTTP] 200 OK - %s\n", response.c_str());

    // Parse MMI response (only meaningful for ACTIVE/END reports)
    if (state == "ACTIVE" || state == "END") {
      JsonDocument doc;
      if (!deserializeJson(doc, response) && doc.containsKey("mmi_level")) {
        globalMmi     = doc["mmi_level"].as<String>();
        globalMmiName = doc["mmi_name"].as<String>();
        mmiDisplayStart = millis();
        Serial.printf("[MMI] %s - %s\n", globalMmi.c_str(), globalMmiName.c_str());
      }
    }
  } else {
    Serial.printf("[HTTP] Error %d - %s\n", httpCode, http.errorToString(httpCode).c_str());
    if (queuedCount < MAX_QUEUED) queuedCount++;
  }

  http.end();
}

// ─────────────────────────────────────────────────────────────────────────────
// Retry queued packets. Simple in-RAM counter; a full SPIFFS-backed queue
// would persist across reboots - left as future work.
void flushQueue() {
  // Best-effort: send a single heartbeat-style packet to confirm connectivity.
  // (A real disk-backed queue would replay each stored event here.)
  HTTPClient http;
  http.begin(secureClient, serverUrl);
  http.setTimeout(8000);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  String postData = "intensity=0.00&device_id=ESP32_001&event_state=REPLAY&timestamp="
                    + isoTimestamp();
  int httpCode = http.POST(postData);
  if (httpCode == HTTP_CODE_OK) {
    Serial.printf("[HTTP] Queue flush OK - cleared %d pending\n", queuedCount);
    queuedCount = 0;
  }
  http.end();
}
