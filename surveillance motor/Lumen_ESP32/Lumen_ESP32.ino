/*
 * Lumen — ESP32 → InfinityFree
 *
 * URL obligatoire (le 404 nginx vient souvent d’un POST vers /mesure
 * sans .php, ou vers un site Netlify statique) :
 *   http://VOTRE-DOMAINE/mesure.php
 *
 * InfinityFree : HTTP (pas HTTPS si le certificat est invalide),
 * User-Agent navigateur, et fichier mesure.php présent dans htdocs.
 */

#include <WiFi.h>
#include <HTTPClient.h>

const char* WIFI_SSID = "VOTRE_WIFI";
const char* WIFI_PASS = "VOTRE_MOT_DE_PASSE_WIFI";

/* Domaine InfinityFree — PAS un site *.netlify.app */
const char* SERVER_HOST = "otornyenye.rf.gd";
const char* DEVICE_KEY = "lumen-esp32-nyenye-7f3a9c";

const unsigned long SEND_MS = 2000;

void setup() {
  Serial.begin(115200);
  delay(300);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("WiFi");
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
    delay(400);
    Serial.print('.');
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("IP ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("WiFi timeout");
  }
}

bool postMesure(const char* path, const String& json) {
  WiFiClient client;
  HTTPClient http;
  String url = String("http://") + SERVER_HOST + path;
  http.setTimeout(12000);
  http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
  if (!http.begin(client, url)) {
    Serial.print("begin fail ");
    Serial.println(url);
    return false;
  }
  http.setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) LumenESP32");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Device-Key", DEVICE_KEY);

  int code = http.POST(json);
  String body = http.getString();
  http.end();

  Serial.print("POST ");
  Serial.print(path);
  Serial.print(" HTTP ");
  Serial.println(code);
  Serial.println(body);

  return code >= 200 && code < 300;
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
    delay(2000);
    return;
  }

  float x = 0.02f, y = 0.01f, z = 0.03f, rpm = 0, rms = 0.04f;
  String json = "{";
  json += "\"x\":" + String(x, 4) + ",";
  json += "\"y\":" + String(y, 4) + ",";
  json += "\"z\":" + String(z, 4) + ",";
  json += "\"rpm\":" + String(rpm, 1) + ",";
  json += "\"rmsMmS\":" + String(rms, 4) + ",";
  json += "\"defautCapteur\":false,";
  json += "\"etatMoteur\":\"marche\",";
  json += "\"historique\":true,";
  json += "\"key\":\"" + String(DEVICE_KEY) + "\"";
  json += "}";

  /* Toujours .php en premier — fichier réel, nginx ne 404 pas. */
  if (!postMesure("/mesure.php", json)) {
    Serial.println("Echec /mesure.php — essai /mesure");
    if (!postMesure("/mesure", json)) {
      Serial.println("Erreur POST mesure : verifiez htdocs/mesure.php et le domaine InfinityFree (pas Netlify).");
    }
  }

  delay(SEND_MS);
}
