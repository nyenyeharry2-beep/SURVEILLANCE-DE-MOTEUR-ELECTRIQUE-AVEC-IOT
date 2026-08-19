/**
 * ESP32 — Passerelle Uno → InfinityFree (sans Firebase)
 * Bibliothèques : WiFi, HTTPClient (core ESP32)
 */
#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>

const char* WIFI_SSID = "VOTRE_WIFI";
const char* WIFI_PASS = "VOTRE_MOT_DE_PASSE";
const char* SERVER_HOST = "otornyenye.rf.gd";
const char* DEVICE_KEY = "lumen-esp32-nyenye-7f3a9c";

#define UNO_RX_PIN 16
#define UNO_TX_PIN 17
#define UNO_BAUD 9600

float ax_g = 0, ay_g = 0, az_g = 0, a_rms_ms2 = 0, vibration_rms_mms = 0, rpm = 0;
String motorStatus = "ARRET", alertLevel = "INFO", diagnosticMsg = "En_attente_Uno", anomalyHint = "";
bool relayState = false, buzzerState = false, buzzerMute = false, unoOnline = false;
unsigned long lastUnoMs = 0, lastSendMs = 0, lastCmdMs = 0, lastConfigMs = 0;

float cfg_rpm_nominal = 1500, cfg_rpm_min = 1200, cfg_rpm_max = 1800;
float cfg_vib_normal = 2.8f, cfg_vib_alerte = 4.5f, cfg_vib_critique = 7.1f;
float cfg_a_rms_normal = 1.5f, cfg_a_rms_alerte = 3.0f, cfg_a_rms_critique = 5.0f;
bool cfg_auto_stop = true, cfg_buzzer_enable = true;
bool cmd_relay = false, cmd_mute = false;
bool configDirty = true;

String jsonEscape(const String& s) {
  String o = s;
  o.replace("\\", "\\\\");
  o.replace("\"", "\\\"");
  return o;
}

bool httpPostMesure(bool historique) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  WiFiClient client;
  String u = String("http://") + SERVER_HOST + "/mesure.php";
  if (!http.begin(client, u)) return false;
  http.setUserAgent("Mozilla/5.0 LumenESP32-Uno");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", DEVICE_KEY);

  String json = "{";
  json += "\"key\":\"" + String(DEVICE_KEY) + "\",";
  json += "\"ax\":" + String(ax_g, 4) + ",";
  json += "\"ay\":" + String(ay_g, 4) + ",";
  json += "\"az\":" + String(az_g, 4) + ",";
  json += "\"a_rms\":" + String(a_rms_ms2, 3) + ",";
  json += "\"vibration_rms\":" + String(vibration_rms_mms, 4) + ",";
  json += "\"rpm\":" + String(rpm, 1) + ",";
  json += "\"rpm_nominal\":" + String(cfg_rpm_nominal, 0) + ",";
  json += "\"status\":\"" + jsonEscape(motorStatus) + "\",";
  json += "\"alert_level\":\"" + jsonEscape(alertLevel) + "\",";
  json += "\"diagnostic\":\"" + jsonEscape(diagnosticMsg) + "\",";
  json += "\"anomaly_hint\":\"" + jsonEscape(anomalyHint) + "\",";
  json += "\"relay_state\":" + String(relayState ? "true" : "false") + ",";
  json += "\"buzzer_state\":" + String(buzzerState ? "true" : "false") + ",";
  json += "\"buzzer_mute\":" + String(buzzerMute ? "true" : "false") + ",";
  json += "\"uno_online\":" + String(unoOnline ? "true" : "false") + ",";
  json += "\"online\":true,";
  json += "\"timestamp\":" + String((int)(millis() / 1000)) + ",";
  json += "\"historique\":" + String(historique ? "true" : "false");
  json += "}";

  int code = http.POST(json);
  String resp = http.getString();
  http.end();
  Serial.printf("POST mesure HTTP %d", code);
  if (code < 200 || code >= 300) Serial.printf(" body=%s", resp.substring(0, 120).c_str());
  Serial.println();
  return code >= 200 && code < 300;
}

float jsonFloat(const String& body, const char* key, float fallback) {
  String needle = String("\"") + key + "\":";
  int i = body.indexOf(needle);
  if (i < 0) return fallback;
  i += needle.length();
  return body.substring(i).toFloat();
}

bool jsonBool(const String& body, const char* key, bool fallback) {
  String needle = String("\"") + key + "\":";
  int i = body.indexOf(needle);
  if (i < 0) return fallback;
  i += needle.length();
  while (i < (int)body.length() && body.charAt(i) == ' ') i++;
  if (body.substring(i).startsWith("true")) return true;
  if (body.substring(i).startsWith("false")) return false;
  return fallback;
}

bool httpGetCommandeConfig() {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  WiFiClient client;
  String u = String("http://") + SERVER_HOST + "/commande.php";
  if (!http.begin(client, u)) return false;
  http.addHeader("X-Device-Key", DEVICE_KEY);
  http.setUserAgent("Mozilla/5.0 LumenESP32-Uno");
  int code = http.GET();
  if (code != 200) { http.end(); return false; }
  String body = http.getString();
  http.end();

  cmd_relay = jsonBool(body, "relay", cmd_relay);
  cmd_mute = jsonBool(body, "buzzer_mute", cmd_mute);
  int cfg = body.indexOf("\"config\"");
  if (cfg >= 0) {
    String c = body.substring(cfg);
    cfg_rpm_nominal = jsonFloat(c, "rpm_nominal", cfg_rpm_nominal);
    cfg_rpm_min = jsonFloat(c, "rpm_min", cfg_rpm_min);
    cfg_rpm_max = jsonFloat(c, "rpm_max", cfg_rpm_max);
    cfg_vib_normal = jsonFloat(c, "vib_normal_mms", cfg_vib_normal);
    cfg_vib_alerte = jsonFloat(c, "vib_alerte_mms", cfg_vib_alerte);
    cfg_vib_critique = jsonFloat(c, "vib_critique_mms", cfg_vib_critique);
    cfg_a_rms_normal = jsonFloat(c, "a_rms_normal_ms2", cfg_a_rms_normal);
    cfg_a_rms_alerte = jsonFloat(c, "a_rms_alerte_ms2", cfg_a_rms_alerte);
    cfg_a_rms_critique = jsonFloat(c, "a_rms_critique_ms2", cfg_a_rms_critique);
    cfg_auto_stop = jsonBool(c, "auto_stop_on_alarm", cfg_auto_stop);
    cfg_buzzer_enable = jsonBool(c, "buzzer_enabled", cfg_buzzer_enable);
  }
  return true;
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
  ax_g = parts[1].toFloat(); ay_g = parts[2].toFloat(); az_g = parts[3].toFloat();
  a_rms_ms2 = parts[4].toFloat(); vibration_rms_mms = parts[5].toFloat(); rpm = parts[6].toFloat();
  motorStatus = parts[7]; alertLevel = parts[8];
  relayState = parts[9].toInt() != 0; buzzerState = parts[10].toInt() != 0; buzzerMute = parts[11].toInt() != 0;
  if (n > 12) { diagnosticMsg = parts[12]; diagnosticMsg.replace('_', ' '); }
  if (n > 13) { anomalyHint = parts[13]; anomalyHint.replace('_', ' '); }
}

void pollUno() {
  static String buf;
  while (Serial2.available()) {
    char c = (char)Serial2.read();
    if (c == '\n' || c == '\r') {
      if (buf.length() > 0) {
        buf.trim();
        if (buf.startsWith("MEAS,")) { parseMeasLine(buf); lastUnoMs = millis(); }
        buf = "";
      }
    } else if (buf.length() < 300) buf += c;
  }
}

void pushCommandToUno() {
  Serial2.println(String("CMD,") + (cmd_relay ? "1" : "0") + "," + (cmd_mute ? "1" : "0"));
}

void pushConfigToUno() {
  String line = "CFG,";
  line += String(cfg_rpm_nominal, 2) + "," + String(cfg_rpm_min, 2) + "," + String(cfg_rpm_max, 2) + ",";
  line += String(cfg_vib_normal, 3) + "," + String(cfg_vib_alerte, 3) + "," + String(cfg_vib_critique, 3) + ",";
  line += String(cfg_a_rms_normal, 3) + "," + String(cfg_a_rms_alerte, 3) + "," + String(cfg_a_rms_critique, 3) + ",";
  line += String(cfg_auto_stop ? "1" : "0") + "," + String(cfg_buzzer_enable ? "1" : "0") + ",0";
  Serial2.println(line);
}

void setup() {
  Serial.begin(115200);
  Serial2.begin(UNO_BAUD, SERIAL_8N1, UNO_RX_PIN, UNO_TX_PIN);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.println(F("ESP32 InfinityFree Uno+ESP32"));
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) { delay(400); Serial.print('.'); }
  Serial.println();
}

void loop() {
  pollUno();
  unsigned long now = millis();
  unoOnline = (now - lastUnoMs) < 5000;
  if (now - lastConfigMs >= 5000) { lastConfigMs = now; if (httpGetCommandeConfig()) configDirty = true; }
  if (configDirty) { pushConfigToUno(); configDirty = false; }
  if (now - lastCmdMs >= 1000) { lastCmdMs = now; pushCommandToUno(); }
  if (now - lastSendMs >= 1000) { lastSendMs = now; httpPostMesure(false); }
  static unsigned long lh = 0;
  if (now - lh >= 10000) { lh = now; httpPostMesure(true); }
}
