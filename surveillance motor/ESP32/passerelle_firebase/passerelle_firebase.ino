/**
 * =============================================================================
 * ESP32 — Passerelle Wi-Fi / Firebase
 * =============================================================================
 * L'Arduino Uno mesure (ADXL345 + IR), commande relais D8 et buzzer.
 * L'ESP32 reçoit les trames série et publie vers Firebase Realtime Database.
 * Les commandes Web (relais / mute / seuils) sont renvoyées à l'Uno.
 *
 * Liaison UART :
 *   ESP32 Serial2 RX = GPIO 16  ←  TX Uno D4 (via diviseur 5 V → 3,3 V)
 *   ESP32 Serial2 TX = GPIO 17  →  RX Uno D3
 *   Baud : 9600
 *
 * Bibliothèques : WiFi (core ESP32), Firebase ESP Client (Mobizt)
 * =============================================================================
 */

#include <Arduino.h>
#include <WiFi.h>
#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"
#include "addons/RTDBHelper.h"

#define WIFI_SSID        "VOTRE_SSID_WIFI"
#define WIFI_PASSWORD    "VOTRE_MOT_DE_PASSE_WIFI"
#define FIREBASE_HOST    "https://VOTRE_PROJET-default-rtdb.REGION.firebasedatabase.app"
#define FIREBASE_AUTH    "VOTRE_CLE_SECRETE_DATABASE"

#define PATH_LIVE        "/moteur/live"
#define PATH_CONFIG      "/moteur/config"
#define PATH_COMMAND     "/moteur/command"
#define PATH_HISTORIQUE  "/moteur/historique"

#define PIN_LED_STATUS   2
#define UNO_RX_PIN      16   /* Serial2 RX */
#define UNO_TX_PIN      17   /* Serial2 TX */
#define UNO_BAUD        9600

#define LOOP_SEND_MS     1000
#define LOOP_CONFIG_MS   5000
#define LOOP_CMD_MS      1000
#define LOOP_HISTO_MS    10000
#define WIFI_RETRY_MS    10000
#define UNO_STALE_MS     5000

FirebaseData fbdo;
FirebaseData fbdoConfig;
FirebaseAuth auth;
FirebaseConfig fbConfig;

bool firebaseReady = false;
unsigned long lastSendMs = 0;
unsigned long lastConfigMs = 0;
unsigned long lastCmdMs = 0;
unsigned long lastHistoMs = 0;
unsigned long lastWifiTry = 0;
unsigned long lastUnoMs = 0;

/* Dernière mesure reçue de l'Uno */
float ax_g = 0, ay_g = 0, az_g = 0;
float a_rms_ms2 = 0;
float vibration_rms_mms = 0;
float rpm = 0;
String motorStatus = "INCONNU";
String alertLevel = "INCONNU";
String diagnosticMsg = "En_attente_Uno";
String anomalyHint = "";
bool relayState = false;
bool buzzerState = false;
bool buzzerMute = false;
bool unoOnline = false;

/* Config / commandes */
float cfg_rpm_nominal = 1500;
float cfg_rpm_min = 1200;
float cfg_rpm_max = 1800;
float cfg_vib_normal = 2.8f;
float cfg_vib_alerte = 4.5f;
float cfg_vib_critique = 7.1f;
float cfg_a_rms_normal = 1.5f;
float cfg_a_rms_alerte = 3.0f;
float cfg_a_rms_critique = 5.0f;
bool cfg_auto_stop = true;
bool cfg_buzzer_enable = true;
bool cmd_relay = false;
bool cmd_mute = false;
int lastCmdRelay = -1;
int lastCmdMute = -1;
bool configDirty = true;

void connectWiFi();
void connectFirebase();
void handleConnection();
void ensureDefaults();
void loadConfigFromFirebase();
void loadCommandFromFirebase();
void pushConfigToUno();
void pushCommandToUno();
void pollUno();
void parseMeasLine(const String &line);
String unsanitize(const String &s);
void sendDataFirebase();
void pushHistorique();
void printStatus();

void setup() {
  Serial.begin(115200);
  delay(400);
  pinMode(PIN_LED_STATUS, OUTPUT);
  digitalWrite(PIN_LED_STATUS, LOW);

  Serial2.begin(UNO_BAUD, SERIAL_8N1, UNO_RX_PIN, UNO_TX_PIN);

  Serial.println(F("=== ESP32 Passerelle Firebase (Uno → Cloud) ==="));
  Serial.println(F("UART2: RX=GPIO16 TX=GPIO17 @ 9600 (Uno D4→16, 17→Uno D3)"));

  connectWiFi();
  connectFirebase();

  lastSendMs = lastConfigMs = lastCmdMs = lastHistoMs = millis();
}

void loop() {
  handleConnection();
  pollUno();

  unsigned long now = millis();
  unoOnline = (lastUnoMs > 0) && ((now - lastUnoMs) < UNO_STALE_MS);

  if (firebaseReady && (now - lastConfigMs >= LOOP_CONFIG_MS)) {
    lastConfigMs = now;
    loadConfigFromFirebase();
    if (configDirty) {
      pushConfigToUno();
      configDirty = false;
    }
  }

  if (firebaseReady && (now - lastCmdMs >= LOOP_CMD_MS)) {
    lastCmdMs = now;
    loadCommandFromFirebase();
    if (cmd_relay != (bool)lastCmdRelay || cmd_mute != (bool)lastCmdMute) {
      pushCommandToUno();
      lastCmdRelay = cmd_relay ? 1 : 0;
      lastCmdMute = cmd_mute ? 1 : 0;
    }
  }

  /* Si ALARME + auto-stop : forcer commande relay OFF côté Firebase aussi */
  if (cfg_auto_stop && (motorStatus == "ALARME" || alertLevel == "ALARME") && cmd_relay) {
    cmd_relay = false;
    if (firebaseReady) {
      Firebase.RTDB.setBool(&fbdo, (String(PATH_COMMAND) + "/relay").c_str(), false);
    }
    pushCommandToUno();
    lastCmdRelay = 0;
  }

  if (now - lastSendMs >= LOOP_SEND_MS) {
    lastSendMs = now;
    sendDataFirebase();
    printStatus();
  }

  if (firebaseReady && (now - lastHistoMs >= LOOP_HISTO_MS)) {
    lastHistoMs = now;
    pushHistorique();
  }

  delay(5);
}

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;
  Serial.print(F("[WiFi] "));
  Serial.println(WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
    digitalWrite(PIN_LED_STATUS, !digitalRead(PIN_LED_STATUS));
    delay(400);
    Serial.print('.');
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print(F("[OK] IP "));
    Serial.println(WiFi.localIP());
    digitalWrite(PIN_LED_STATUS, HIGH);
  } else {
    Serial.println(F("[ERREUR] WiFi"));
    digitalWrite(PIN_LED_STATUS, LOW);
  }
}

void connectFirebase() {
  if (WiFi.status() != WL_CONNECTED) {
    firebaseReady = false;
    return;
  }
  fbConfig.database_url = FIREBASE_HOST;
  fbConfig.signer.tokens.legacy_token = FIREBASE_AUTH;
  Firebase.reconnectNetwork(true);
  fbdo.setBSSLBufferSize(4096, 1024);
  Firebase.begin(&fbConfig, &auth);
  firebaseReady = true;
  Serial.println(F("[OK] Firebase"));
  ensureDefaults();
}

void ensureDefaults() {
  if (!Firebase.RTDB.getJSON(&fbdo, PATH_CONFIG)) {
    FirebaseJson json;
    json.set("rpm_nominal", cfg_rpm_nominal);
    json.set("rpm_min", cfg_rpm_min);
    json.set("rpm_max", cfg_rpm_max);
    json.set("vib_normal_mms", cfg_vib_normal);
    json.set("vib_alerte_mms", cfg_vib_alerte);
    json.set("vib_critique_mms", cfg_vib_critique);
    json.set("a_rms_normal_ms2", cfg_a_rms_normal);
    json.set("a_rms_alerte_ms2", cfg_a_rms_alerte);
    json.set("a_rms_critique_ms2", cfg_a_rms_critique);
    json.set("auto_stop_on_alarm", cfg_auto_stop);
    json.set("buzzer_enabled", cfg_buzzer_enable);
    json.set("note", "Seuils a calibrer — acquisition sur Arduino Uno + IR");
    Firebase.RTDB.setJSON(&fbdo, PATH_CONFIG, &json);
  }
  if (!Firebase.RTDB.getJSON(&fbdo, PATH_COMMAND)) {
    FirebaseJson json;
    json.set("relay", false);
    json.set("buzzer_mute", false);
    Firebase.RTDB.setJSON(&fbdo, PATH_COMMAND, &json);
  }
}

void handleConnection() {
  if (WiFi.status() != WL_CONNECTED) {
    firebaseReady = false;
    digitalWrite(PIN_LED_STATUS, LOW);
    unsigned long now = millis();
    if (now - lastWifiTry >= WIFI_RETRY_MS) {
      lastWifiTry = now;
      connectWiFi();
      if (WiFi.status() == WL_CONNECTED) connectFirebase();
    }
  } else if (!firebaseReady) {
    connectFirebase();
  }
}

void loadConfigFromFirebase() {
  if (!firebaseReady) return;
  if (!Firebase.RTDB.getJSON(&fbdoConfig, PATH_CONFIG)) return;

  FirebaseJson &json = fbdoConfig.jsonObject();
  FirebaseJsonData data;
  float oldNom = cfg_rpm_nominal;

  if (json.get(data, "rpm_nominal") && data.success) cfg_rpm_nominal = data.to<float>();
  if (json.get(data, "rpm_min") && data.success) cfg_rpm_min = data.to<float>();
  if (json.get(data, "rpm_max") && data.success) cfg_rpm_max = data.to<float>();
  if (json.get(data, "vib_normal_mms") && data.success) cfg_vib_normal = data.to<float>();
  if (json.get(data, "vib_alerte_mms") && data.success) cfg_vib_alerte = data.to<float>();
  if (json.get(data, "vib_critique_mms") && data.success) cfg_vib_critique = data.to<float>();
  if (json.get(data, "a_rms_normal_ms2") && data.success) cfg_a_rms_normal = data.to<float>();
  if (json.get(data, "a_rms_alerte_ms2") && data.success) cfg_a_rms_alerte = data.to<float>();
  if (json.get(data, "a_rms_critique_ms2") && data.success) cfg_a_rms_critique = data.to<float>();
  if (json.get(data, "auto_stop_on_alarm") && data.success) cfg_auto_stop = data.to<bool>();
  if (json.get(data, "buzzer_enabled") && data.success) cfg_buzzer_enable = data.to<bool>();

  if (cfg_rpm_min >= cfg_rpm_max) {
    cfg_rpm_min = cfg_rpm_nominal * 0.8f;
    cfg_rpm_max = cfg_rpm_nominal * 1.2f;
  }
  configDirty = true;
  (void)oldNom;
}

void loadCommandFromFirebase() {
  if (!firebaseReady) return;
  if (!Firebase.RTDB.getJSON(&fbdoConfig, PATH_COMMAND)) return;
  FirebaseJson &json = fbdoConfig.jsonObject();
  FirebaseJsonData data;
  if (json.get(data, "relay") && data.success) cmd_relay = data.to<bool>();
  if (json.get(data, "buzzer_mute") && data.success) cmd_mute = data.to<bool>();
}

void pushCommandToUno() {
  String line = "CMD,";
  line += cmd_relay ? '1' : '0';
  line += ',';
  line += cmd_mute ? '1' : '0';
  Serial2.println(line);
  Serial.print(F("→ Uno "));
  Serial.println(line);
}

void pushConfigToUno() {
  String line = "CFG,";
  line += String(cfg_rpm_nominal, 2); line += ',';
  line += String(cfg_rpm_min, 2); line += ',';
  line += String(cfg_rpm_max, 2); line += ',';
  line += String(cfg_vib_normal, 3); line += ',';
  line += String(cfg_vib_alerte, 3); line += ',';
  line += String(cfg_vib_critique, 3); line += ',';
  line += String(cfg_a_rms_normal, 3); line += ',';
  line += String(cfg_a_rms_alerte, 3); line += ',';
  line += String(cfg_a_rms_critique, 3); line += ',';
  line += cfg_auto_stop ? "1" : "0"; line += ',';
  line += cfg_buzzer_enable ? "1" : "0"; line += ',';
  line += "0";
  Serial2.println(line);
  Serial.println(F("→ Uno CFG"));
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
    } else {
      if (buf.length() < 300) buf += c;
    }
  }
}

String unsanitize(const String &s) {
  String out = s;
  out.replace('_', ' ');
  return out;
}

void parseMeasLine(const String &line) {
  /* MEAS,ax,ay,az,a_rms,vib,rpm,status,alert,relay,buzzer,mute,diag,hint */
  String parts[14];
  int n = 0;
  int start = 0;
  for (int i = 0; i <= (int)line.length() && n < 14; i++) {
    if (i == (int)line.length() || line.charAt(i) == ',') {
      parts[n++] = line.substring(start, i);
      start = i + 1;
    }
  }
  if (n < 12) {
    Serial.println(F("[WARN] Trame MEAS incomplete"));
    return;
  }

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
  if (n > 12) diagnosticMsg = unsanitize(parts[12]);
  if (n > 13) anomalyHint = unsanitize(parts[13]);
}

void sendDataFirebase() {
  if (!firebaseReady || WiFi.status() != WL_CONNECTED) {
    Serial.println(F("[Firebase] hors ligne"));
    return;
  }

  FirebaseJson json;
  json.set("ax", ax_g);
  json.set("ay", ay_g);
  json.set("az", az_g);
  json.set("a_rms", a_rms_ms2);
  json.set("vibration_rms", vibration_rms_mms);
  json.set("rpm", rpm);
  json.set("rpm_nominal", cfg_rpm_nominal);
  json.set("status", motorStatus);
  json.set("alert_level", alertLevel);
  json.set("diagnostic", diagnosticMsg);
  json.set("anomaly_hint", anomalyHint);
  json.set("relay_state", relayState);
  json.set("buzzer_state", buzzerState);
  json.set("buzzer_mute", buzzerMute);
  json.set("auto_stop_on_alarm", cfg_auto_stop);
  json.set("uno_online", unoOnline);
  json.set("speed_sensor", "IR");
  json.set("controller", "Arduino_Uno");
  json.set("gateway", "ESP32");
  json.set("timestamp", (int)(millis() / 1000));
  json.set("online", unoOnline);
  json.set("unit_a_rms", "m/s2");
  json.set("unit_vibration_rms", "mm/s (estime)");
  json.set("note_vibration", "Mesure sur Uno; vibration_rms estimee; a_rms fiable");

  if (Firebase.RTDB.setJSON(&fbdo, PATH_LIVE, &json)) {
    Serial.println(F("[OK] live"));
  } else {
    Serial.print(F("[ERREUR] "));
    Serial.println(fbdo.errorReason());
    firebaseReady = false;
  }
}

void pushHistorique() {
  if (!firebaseReady || !unoOnline) return;
  FirebaseJson json;
  json.set("timestamp", (int)(millis() / 1000));
  json.set("epoch_ms", (double)millis());
  json.set("vibration_rms", vibration_rms_mms);
  json.set("a_rms", a_rms_ms2);
  json.set("rpm", rpm);
  json.set("ax", ax_g);
  json.set("ay", ay_g);
  json.set("az", az_g);
  json.set("status", motorStatus);
  json.set("diagnostic", diagnosticMsg);
  json.set("relay_state", relayState);
  String path = String(PATH_HISTORIQUE) + "/" + String(millis());
  Firebase.RTDB.setJSON(&fbdo, path.c_str(), &json);
}

void printStatus() {
  Serial.printf("Uno=%s | RPM=%.1f | A_RMS=%.3f | Vib=%.2f | %s | Relais=%s\n",
                unoOnline ? "OK" : "OFF",
                rpm, a_rms_ms2, vibration_rms_mms,
                motorStatus.c_str(),
                relayState ? "ON" : "OFF");
}
