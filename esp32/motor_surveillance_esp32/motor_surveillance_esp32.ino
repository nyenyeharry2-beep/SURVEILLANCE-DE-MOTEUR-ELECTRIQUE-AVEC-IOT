/*
 * Passerelle ESP32 - Surveillance moteur
 * Relais Arduino <-> Cloud InfinityFree (HTTP)
 * GPIO16 = RX (Arduino TX), GPIO17 = TX (Arduino RX)
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Preferences.h>

// ========== CONFIGURATION A MODIFIER ==========
const char *WIFI_SSID = "VOTRE_SSID_WIFI";
const char *WIFI_PASSWORD = "VOTRE_MOT_DE_PASSE_WIFI";

// URL de base InfinityFree (sans slash final)
const char *SERVER_BASE_URL = "http://surveillancemoteurharry.ct.ws";
const char *API_KEY = "harry_surveillance_2026";

const uint8_t ARDUINO_RX_PIN = 16;
const uint8_t ARDUINO_TX_PIN = 17;

const unsigned long COMMAND_POLL_MS = 5000UL;
const unsigned long WIFI_RETRY_MS = 10000UL;
const unsigned long HTTP_TIMEOUT_MS = 8000UL;
const uint8_t HTTP_MAX_RETRIES = 3;
// ==============================================

HardwareSerial ArduinoLink(2);

Preferences preferences;

String serialBuffer;
String lastRelayState = "OFF";
unsigned long lastCommandPollMs = 0;
unsigned long lastWifiAttemptMs = 0;

struct TelemetryData {
  float ax, ay, az, rpm, arms, vrms, ecart;
  String etat;
  String relay;
  bool anomalieVibration;
  bool anomalieVitesse;
  bool valid;
};

TelemetryData pendingTelemetry;
bool hasPendingTelemetry = false;

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return;
  }

  unsigned long now = millis();
  if (now - lastWifiAttemptMs < WIFI_RETRY_MS) {
    return;
  }
  lastWifiAttemptMs = now;

  Serial.println(F("Connexion WiFi..."));
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  uint8_t attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print('.');
    attempts++;
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print(F("WiFi OK - IP: "));
    Serial.println(WiFi.localIP());
  } else {
    Serial.println(F("Echec connexion WiFi"));
  }
}

String extractValue(const String &data, const String &key) {
  int start = data.indexOf(key);
  if (start < 0) {
    return "";
  }
  start += key.length();
  int end = data.indexOf(',', start);
  if (end < 0) {
    return data.substring(start);
  }
  return data.substring(start, end);
}

bool parseTelemetryLine(const String &line, TelemetryData &out) {
  if (!line.startsWith("AX=")) {
    return false;
  }

  out.ax = extractValue(line, "AX=").toFloat();
  out.ay = extractValue(line, "AY=").toFloat();
  out.az = extractValue(line, "AZ=").toFloat();
  out.rpm = extractValue(line, "RPM=").toFloat();
  out.arms = extractValue(line, "ARMS=").toFloat();
  out.vrms = extractValue(line, "VRMS=").toFloat();
  out.ecart = extractValue(line, "ECART=").toFloat();
  out.etat = extractValue(line, "ETAT=");
  out.relay = extractValue(line, "RELAY=");
  out.anomalieVibration = (out.arms > 2.0f) || (out.vrms > 4.5f);
  out.anomalieVitesse = (fabs(out.ecart) > 5.0f) && (out.relay == "ON");
  out.valid = true;
  return true;
}

bool httpPostForm(const String &url, const String &payload, String &response) {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);
  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.addHeader("X-API-Key", API_KEY);

  int code = -1;
  for (uint8_t i = 0; i < HTTP_MAX_RETRIES; i++) {
    code = http.POST(payload);
    if (code > 0) {
      break;
    }
    delay(300);
  }

  if (code > 0) {
    response = http.getString();
  }
  http.end();
  return code == 200;
}

bool httpGetText(const String &url, String &response) {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);
  http.begin(url);
  http.addHeader("X-API-Key", API_KEY);

  int code = http.GET();
  if (code == 200) {
    response = http.getString();
    response.trim();
  }
  http.end();
  return code == 200;
}

String buildInsertPayload(const TelemetryData &t) {
  String payload;
  payload.reserve(256);
  payload += "ax=" + String(t.ax, 3);
  payload += "&ay=" + String(t.ay, 3);
  payload += "&az=" + String(t.az, 3);
  payload += "&rpm=" + String(t.rpm, 1);
  payload += "&arms=" + String(t.arms, 3);
  payload += "&vrms=" + String(t.vrms, 3);
  payload += "&ecart=" + String(t.ecart, 2);
  payload += "&etat=" + t.etat;
  payload += "&relay=" + t.relay;
  payload += "&anomalie_vibration=" + String(t.anomalieVibration ? 1 : 0);
  payload += "&anomalie_vitesse=" + String(t.anomalieVitesse ? 1 : 0);
  payload += "&api_key=" + String(API_KEY);
  return payload;
}

bool sendTelemetryToCloud(const TelemetryData &t) {
  String url = String(SERVER_BASE_URL) + "/insert_data.php";
  String payload = buildInsertPayload(t);
  String response;

  bool ok = httpPostForm(url, payload, response);
  if (ok) {
    Serial.print(F("Donnees envoyees: "));
    Serial.println(response);
    return true;
  }

  Serial.println(F("Echec envoi cloud, mise en tampon"));
  pendingTelemetry = t;
  hasPendingTelemetry = true;
  preferences.begin("motor", false);
  preferences.putString("pending", payload);
  preferences.end();
  return false;
}

void flushPendingTelemetry() {
  if (!hasPendingTelemetry) {
    preferences.begin("motor", true);
    String saved = preferences.getString("pending", "");
    preferences.end();
    if (saved.length() > 0) {
      String response;
      String url = String(SERVER_BASE_URL) + "/insert_data.php";
      if (httpPostForm(url, saved, response)) {
        preferences.begin("motor", false);
        preferences.remove("pending");
        preferences.end();
        Serial.println(F("Tampon envoye"));
      }
      return;
    }
  }

  if (hasPendingTelemetry) {
    if (sendTelemetryToCloud(pendingTelemetry)) {
      hasPendingTelemetry = false;
      preferences.begin("motor", false);
      preferences.remove("pending");
      preferences.end();
    }
  }
}

void sendRelayCommandToArduino(const String &cmd) {
  for (uint8_t attempt = 0; attempt < HTTP_MAX_RETRIES; attempt++) {
    ArduinoLink.print("RELAY=");
    ArduinoLink.println(cmd);
    Serial.println(F("Commande envoyee a Arduino: RELAY=") + cmd);

    unsigned long start = millis();
    while (millis() - start < 2000UL) {
      if (ArduinoLink.available()) {
        String confirm = ArduinoLink.readStringUntil('\n');
        confirm.trim();
        if (confirm == "CONFIRMATION=RELAY_ON" && cmd == "ON") {
          lastRelayState = "ON";
          return;
        }
        if (confirm == "CONFIRMATION=RELAY_OFF" && cmd == "OFF") {
          lastRelayState = "OFF";
          return;
        }
      }
      delay(20);
    }
  }
  Serial.println(F("Avertissement: confirmation relais non recue"));
}

void pollCloudCommands() {
  unsigned long now = millis();
  if (now - lastCommandPollMs < COMMAND_POLL_MS) {
    return;
  }
  lastCommandPollMs = now;

  String url = String(SERVER_BASE_URL) + "/get_command.php?api_key=" + String(API_KEY);
  String response;
  if (!httpGetText(url, response)) {
    return;
  }

  response.toUpperCase();
  if (response == "ON" || response == "OFF") {
    if (response != lastRelayState) {
      sendRelayCommandToArduino(response);
    }
  }
}

void processArduinoSerial() {
  while (ArduinoLink.available()) {
    char c = ArduinoLink.read();
    if (c == '\n' || c == '\r') {
      if (serialBuffer.length() == 0) {
        continue;
      }

      serialBuffer.trim();
      if (serialBuffer.startsWith("CONFIRMATION=")) {
        if (serialBuffer.endsWith("RELAY_ON")) {
          lastRelayState = "ON";
        } else if (serialBuffer.endsWith("RELAY_OFF")) {
          lastRelayState = "OFF";
        }
      } else {
        TelemetryData data;
        if (parseTelemetryLine(serialBuffer, data)) {
          sendTelemetryToCloud(data);
        }
      }
      serialBuffer = "";
    } else {
      serialBuffer += c;
      if (serialBuffer.length() > 256) {
        serialBuffer = "";
      }
    }
  }
}

void setup() {
  Serial.begin(115200);
  ArduinoLink.begin(9600, SERIAL_8N1, ARDUINO_RX_PIN, ARDUINO_TX_PIN);

  preferences.begin("motor", false);

  Serial.println(F("ESP32 passerelle surveillance moteur"));
  connectWiFi();
}

void loop() {
  connectWiFi();
  processArduinoSerial();
  pollCloudCommands();
  flushPendingTelemetry();
}
