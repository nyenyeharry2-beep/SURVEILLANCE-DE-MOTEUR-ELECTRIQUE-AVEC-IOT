/**
 * ESP32 — Passerelle Uno → InfinityFree (sans Firebase)
 * Uno envoie MEAS,... sur Serial2 ; ESP32 POST vers mesure.php
 * Lit commande.php + config pour relais / seuils
 */
#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* WIFI_SSID = "VOTRE_WIFI";
const char* WIFI_PASS = "VOTRE_MOT_DE_PASSE";
const char* SERVER_HOST = "otornyenye.rf.gd";
const char* DEVICE_KEY = "lumen-esp32-nyenye-7f3a9c";

#define UNO_RX_PIN 16
#define UNO_TX_PIN 17
#define UNO_BAUD 9600

#define LOOP_SEND_MS 1000
#define LOOP_CMD_MS 1000
#define LOOP_CONFIG_MS 5000
#define UNO_STALE_MS 5000

float ax_g = 0, ay_g = 0, az_g = 0, a_rms_ms2 = 0, vibration_rms_mms = 0, rpm = 0;
String motorStatus = "ARRET";
String alertLevel = "INFO";
String diagnosticMsg = "En_attente_Uno";
String anomalyHint = "";
bool relayState = false, buzzerState = false, buzzerMute = false;
bool unoOnline = false;
unsigned long lastUnoMs = 0, lastSendMs = 0, lastCmdMs = 0, lastConfigMs = 0;

float cfg_rpm_nominal = 1500, cfg_rpm_min = 1200, cfg_rpm_max = 1800;
float cfg_vib_normal = 2.8f, cfg_vib_alerte = 4.5f, cfg_vib_critique = 7.1f;
float cfg_a_rms_normal = 1.5f, cfg_a_rms_alerte = 3.0f, cfg_a_rms_critique = 5.0f;
bool cfg_auto_stop = true, cfg_buzzer_enable = true;
bool cmd_relay = false, cmd_mute = false;
bool configDirty = true;

void pollUno();
void parseMeasLine(const String& line);
void pushConfigToUno();
void pushCommandToUno();
bool httpPostMesure(bool historique);
bool httpGetCommandeConfig();
String url(const char* path) {
  return String("http://") + SERVER_HOST + path;
}

void setup() {
  Serial.begin(115200);
  Serial2.begin(UNO_BAUD, SERIAL_8N1, UNO_RX_PIN, UNO_TX_PIN);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.println(F("=== ESP32 → InfinityFree (Uno+ESP32) ==="));
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) {
    delay(400);
    Serial.print('.');
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) Serial.println(WiFi.localIP());
}

void loop() {
  pollUno();
  unsigned long now = millis();
  unoOnline = (now - lastUnoMs) < UNO_STALE_MS;

  if (now - lastConfigMs >= LOOP_CONFIG_MS) {
    lastConfigMs = now;
    if (httpGetCommandeConfig()) configDirty = true;
  }
  if (configDirty) {
    pushConfigToUno();
    configDirty = false;
  }
  if (now - lastCmdMs >= LOOP_CMD_MS) {
    lastCmdMs = now;
    pushCommandToUno();
  }
  if (now - lastSendMs >= LOOP_SEND_MS) {
    lastSendMs = now;
    httpPostMesure(false);
  }
  static unsigned long lastHisto = 0;
  if (now - lastHisto >= 10000) {
    lastHisto = now;
    httpPostMesure(true);
  }
}

void pollUno() {
  static String buf;
  while (Serial2.available()) {
    char c = (char)Serial2.read();
    if (c == '\n' || c == '\r') {
      if (buf.length() > 0) {
        buf.trim();
        if (buf.startsWith("MEAS,")) {
          parseMeasLine(buf);
          lastUnoMs = millis();
        }
        buf = "";
      }
    } else if (buf.length() < 300) {
      buf += c;
    }
  }
}

void parseMeasLine(const String& line) {
  String parts[14];
  int n = 0, start = 0;
  for (int i = 0; i <= (int)line.length() && n < 14; i++) {
    if (i == (int)line.length() || line.charAt(i) == ',') {
      parts[n++] = line.substring(start, i);
      start = i + 1;
    }
  }
  if (n < 12) return;
  ax_g = parts[1].toFloat();
  ay_g = parts[2].toFloat();
  az_g = parts[3].toFloat();
  a_rms_ms2 = parts[4].toFloat();
  vibration_rms_mms = parts[5].toFloat();
  rpm = parts[6].toFloat();
  motorStatus = parts[7];
  alertLevel = parts[8];
  relayState = parts[9].toInt() != 0;
  buzzerState = parts[10].toInt() != 0;
  buzzerMute = parts[11].toInt() != 0;
  if (n > 12) diagnosticMsg = parts[12];
  diagnosticMsg.replace('_', ' ');
  if (n > 13) anomalyHint = parts[13];
  anomalyHint.replace('_', ' ');
}

bool httpPostMesure(bool historique) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  WiFiClient client;
  if (!http.begin(client, url("/mesure.php"))) return false;
  http.setUserAgent("Mozilla/5.0 LumenESP32-Uno");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", DEVICE_KEY);

  StaticJsonDocument<768> doc;
  doc["key"] = DEVICE_KEY;
  doc["ax"] = ax_g;
  doc["ay"] = ay_g;
  doc["az"] = az_g;
  doc["a_rms"] = a_rms_ms2;
  doc["vibration_rms"] = vibration_rms_mms;
  doc["rpm"] = rpm;
  doc["rpm_nominal"] = cfg_rpm_nominal;
  doc["status"] = motorStatus;
  doc["alert_level"] = alertLevel;
  doc["diagnostic"] = diagnosticMsg;
  doc["anomaly_hint"] = anomalyHint;
  doc["relay_state"] = relayState;
  doc["buzzer_state"] = buzzerState;
  doc["buzzer_mute"] = buzzerMute;
  doc["uno_online"] = unoOnline;
  doc["online"] = unoOnline;
  doc["timestamp"] = (int)(millis() / 1000);
  doc["historique"] = historique;

  String body;
  serializeJson(doc, body);
  int code = http.POST(body);
  http.end();
  if (code >= 200 && code < 300) {
    Serial.println(F("[OK] mesure.php"));
    return true;
  }
  Serial.printf("[ERR] POST %d\n", code);
  return false;
}

bool httpGetCommandeConfig() {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  WiFiClient client;
  String u = url("/commande.php");
  if (!http.begin(client, u)) return false;
  http.addHeader("X-Device-Key", DEVICE_KEY);
  http.setUserAgent("Mozilla/5.0 LumenESP32-Uno");
  int code = http.GET();
  if (code != 200) {
    http.end();
    return false;
  }
  String payload = http.getString();
  http.end();

  StaticJsonDocument<1024> doc;
  if (deserializeJson(doc, payload)) return false;
  if (doc["relay"].is<bool>()) cmd_relay = doc["relay"];
  if (doc["buzzer_mute"].is<bool>()) cmd_mute = doc["buzzer_mute"];
  JsonObject cfg = doc["config"];
  if (!cfg.isNull()) {
    if (cfg["rpm_nominal"]) cfg_rpm_nominal = cfg["rpm_nominal"];
    if (cfg["rpm_min"]) cfg_rpm_min = cfg["rpm_min"];
    if (cfg["rpm_max"]) cfg_rpm_max = cfg["rpm_max"];
    if (cfg["vib_normal_mms"]) cfg_vib_normal = cfg["vib_normal_mms"];
    if (cfg["vib_alerte_mms"]) cfg_vib_alerte = cfg["vib_alerte_mms"];
    if (cfg["vib_critique_mms"]) cfg_vib_critique = cfg["vib_critique_mms"];
    if (cfg["a_rms_normal_ms2"]) cfg_a_rms_normal = cfg["a_rms_normal_ms2"];
    if (cfg["a_rms_alerte_ms2"]) cfg_a_rms_alerte = cfg["a_rms_alerte_ms2"];
    if (cfg["a_rms_critique_ms2"]) cfg_a_rms_critique = cfg["a_rms_critique_ms2"];
    if (cfg["auto_stop_on_alarm"].is<bool>()) cfg_auto_stop = cfg["auto_stop_on_alarm"];
    if (cfg["buzzer_enabled"].is<bool>()) cfg_buzzer_enable = cfg["buzzer_enabled"];
  }
  return true;
}

void pushCommandToUno() {
  String line = "CMD,";
  line += cmd_relay ? '1' : '0';
  line += ',';
  line += cmd_mute ? '1' : '0';
  Serial2.println(line);
}

void pushConfigToUno() {
  String line = "CFG,";
  line += String(cfg_rpm_nominal, 2) + "," + String(cfg_rpm_min, 2) + "," + String(cfg_rpm_max, 2) + ",";
  line += String(cfg_vib_normal, 3) + "," + String(cfg_vib_alerte, 3) + "," + String(cfg_vib_critique, 3) + ",";
  line += String(cfg_a_rms_normal, 3) + "," + String(cfg_a_rms_alerte, 3) + "," + String(cfg_a_rms_critique, 3) + ",";
  line += cfg_auto_stop ? "1" : "0";
  line += ",";
  line += cfg_buzzer_enable ? "1" : "0";
  line += ",0";
  Serial2.println(line);
}
