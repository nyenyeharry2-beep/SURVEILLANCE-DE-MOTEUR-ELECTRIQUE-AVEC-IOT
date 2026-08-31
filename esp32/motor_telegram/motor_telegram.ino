/*
 * Surveillance moteur électrique — ESP32
 * - Wi-Fi
 * - Bot Telegram (alertes + commandes)
 * - Pont UART avec Arduino Uno (Serial2)
 *
 * Bibliothèque requise (Arduino IDE / Library Manager) :
 *   UniversalTelegramBot by Brian Lough
 *   ArduinoJson by Benoit Blanchon (v6)
 *
 * Carte : ESP32 Dev Module
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <UniversalTelegramBot.h>
#include <ArduinoJson.h>
#include "config.h"

// UART vers Arduino Uno
HardwareSerial& UnoSerial = Serial2; // RX=16, TX=17

WiFiClientSecure securedClient;
UniversalTelegramBot bot(TELEGRAM_BOT_TOKEN, securedClient);

// Dernière télémétrie
struct Telemetry {
  float current = 0;
  float temp = 0;
  float voltage = 0;
  int vib = 0;
  float rpm = 0;
  bool motorOn = false;
  unsigned long updatedAt = 0;
  bool valid = false;
} tel;

unsigned long lastBotCheck = 0;
unsigned long lastAlertMs = 0;
const unsigned long BOT_INTERVAL_MS = 1000;
const unsigned long ALERT_COOLDOWN_MS = 60000; // 1 min entre alertes

String unoLineBuf;

void connectWifi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print(F("WiFi"));
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 40) {
    delay(500);
    Serial.print('.');
    tries++;
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print(F("IP: "));
    Serial.println(WiFi.localIP());
  } else {
    Serial.println(F("WiFi ECHEC — redémarrage dans 10s"));
    delay(10000);
    ESP.restart();
  }
}

bool authorized(const String& chatId) {
  return chatId == String(TELEGRAM_CHAT_ID);
}

String formatStatus() {
  if (!tel.valid) {
    return String("Aucune donnée du Uno pour le moment.\nVérifiez le câblage UART.");
  }
  String s;
  s.reserve(256);
  s += "Surveillance moteur\n";
  s += "-------------------\n";
  s += "Moteur : ";
  s += tel.motorOn ? "ON" : "OFF";
  s += "\nCourant : ";
  s += String(tel.current, 2);
  s += " A\nTension : ";
  s += String(tel.voltage, 1);
  s += " V\nTemp. : ";
  s += String(tel.temp, 1);
  s += " C\nVibration : ";
  s += tel.vib ? "DETECTEE" : "OK";
  s += "\nRPM : ";
  s += String(tel.rpm, 0);
  s += "\nMaj : ";
  s += String((millis() - tel.updatedAt) / 1000);
  s += " s";
  return s;
}

void sendToUno(const char* cmd) {
  UnoSerial.println(cmd);
  Serial.print(F(">> Uno: "));
  Serial.println(cmd);
}

void handleTelegramMessage(const telegramMessage& msg) {
  if (!authorized(msg.chat_id)) {
    bot.sendMessage(msg.chat_id, "Acces refuse.", "");
    return;
  }

  String text = msg.text;
  text.trim();
  String chat = msg.chat_id;

  if (text == "/start" || text == "/help") {
    String help =
      "Commandes moteur IoT\n"
      "/status  - etat capteurs\n"
      "/on      - demarrer moteur\n"
      "/off     - arreter moteur\n"
      "/ping    - test Uno\n"
      "/help    - aide";
    bot.sendMessage(chat, help, "");
  } else if (text == "/status") {
    sendToUno("STATUS");
    delay(300);
    bot.sendMessage(chat, formatStatus(), "");
  } else if (text == "/on") {
    sendToUno("MOTOR_ON");
    bot.sendMessage(chat, "Commande MOTOR_ON envoyee.", "");
  } else if (text == "/off") {
    sendToUno("MOTOR_OFF");
    bot.sendMessage(chat, "Commande MOTOR_OFF envoyee.", "");
  } else if (text == "/ping") {
    sendToUno("PING");
    bot.sendMessage(chat, "PING envoye au Uno.", "");
  } else {
    bot.sendMessage(chat, "Commande inconnue. /help", "");
  }
}

void pollTelegram() {
  int n = bot.getUpdates(bot.last_message_received + 1);
  while (n) {
    for (int i = 0; i < n; i++) {
      handleTelegramMessage(bot.messages[i]);
    }
    n = bot.getUpdates(bot.last_message_received + 1);
  }
}

void maybeAlert() {
  if (!tel.valid) return;
  unsigned long now = millis();
  if (now - lastAlertMs < ALERT_COOLDOWN_MS) return;

  String reason;
  if (tel.temp >= TEMP_MAX_C) {
    reason += "Temperature elevee: ";
    reason += String(tel.temp, 1);
    reason += " C\n";
  }
  if (tel.current >= CURRENT_MAX_A) {
    reason += "Courant eleve: ";
    reason += String(tel.current, 2);
    reason += " A\n";
  }
  if (tel.vib && VIB_ALERT_ENABLE) {
    reason += "Vibration detectee\n";
  }
  if (tel.voltage > 0 && tel.voltage < VOLTAGE_MIN_V) {
    reason += "Tension basse: ";
    reason += String(tel.voltage, 1);
    reason += " V\n";
  }

  if (reason.length() == 0) return;

  lastAlertMs = now;
  String alert = "ALERTE MOTEUR\n" + reason + "\n" + formatStatus();
  bot.sendMessage(TELEGRAM_CHAT_ID, alert, "");
  Serial.println(F("Alerte Telegram envoyee"));
}

bool parseTelemetry(const String& line) {
  // {"c":1.23,"t":45.6,"v":220.1,"vib":0,"rpm":1450,"m":1}
  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, line);
  if (err) return false;

  if (doc.containsKey("evt")) {
    const char* evt = doc["evt"];
    Serial.print(F("Evt Uno: "));
    Serial.println(evt);
    if (strcmp(evt, "SAFE_STOP") == 0) {
      bot.sendMessage(TELEGRAM_CHAT_ID,
                      "STOP SECURITE : seuil depasse, moteur coupe par le Uno.",
                      "");
    } else if (strcmp(evt, "PONG") == 0) {
      bot.sendMessage(TELEGRAM_CHAT_ID, "Uno repond : PONG", "");
    } else if (strcmp(evt, "MOTOR_ON") == 0 || strcmp(evt, "MOTOR_OFF") == 0) {
      String m = String("Uno : ") + evt;
      bot.sendMessage(TELEGRAM_CHAT_ID, m, "");
    }
    return true;
  }

  if (!doc.containsKey("c")) return false;

  tel.current  = doc["c"] | 0.0;
  tel.temp     = doc["t"] | 0.0;
  tel.voltage  = doc["v"] | 0.0;
  tel.vib      = doc["vib"] | 0;
  tel.rpm      = doc["rpm"] | 0.0;
  tel.motorOn  = (doc["m"] | 0) == 1;
  tel.updatedAt = millis();
  tel.valid = true;
  return true;
}

void readUnoSerial() {
  while (UnoSerial.available()) {
    char c = (char)UnoSerial.read();
    if (c == '\n') {
      unoLineBuf.trim();
      if (unoLineBuf.length() > 0) {
        Serial.print(F("<< Uno: "));
        Serial.println(unoLineBuf);
        parseTelemetry(unoLineBuf);
      }
      unoLineBuf = "";
    } else if (c != '\r') {
      if (unoLineBuf.length() < 300) unoLineBuf += c;
    }
  }
}

void setup() {
  Serial.begin(115200);
  delay(200);
  Serial.println(F("ESP32 Motor Monitor + Telegram"));

  UnoSerial.begin(9600, SERIAL_8N1, 16, 17); // RX=16, TX=17

  connectWifi();

  // Telegram HTTPS
  securedClient.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  // Si certificats posent probleme en test : securedClient.setInsecure();

  String boot = "ESP32 connecte.\nIP: " + WiFi.localIP().toString() +
                "\nBot pret. Envoyez /help";
  bot.sendMessage(TELEGRAM_CHAT_ID, boot, "");
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectWifi();
  }

  readUnoSerial();

  unsigned long now = millis();
  if (now - lastBotCheck >= BOT_INTERVAL_MS) {
    lastBotCheck = now;
    pollTelegram();
    maybeAlert();
  }
}
