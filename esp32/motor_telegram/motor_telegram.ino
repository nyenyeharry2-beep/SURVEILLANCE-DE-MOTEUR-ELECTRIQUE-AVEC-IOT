/*
 * ESP32 — Tableau de bord Telegram (admin + observateur)
 *
 * Admin  : métriques + historique + boutons ON/OFF / URGENCE / Actualiser
 * Autre  : mêmes métriques (ax ay az rms vrms rpm impulsion frequence
 *          urgence alerte) — sans commandes moteur ni historique
 *
 * Libs : UniversalTelegramBot, ArduinoJson v6
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <UniversalTelegramBot.h>
#include <ArduinoJson.h>
#include "config.h"

HardwareSerial& UnoSerial = Serial2;

WiFiClientSecure securedClient;
UniversalTelegramBot bot(TELEGRAM_BOT_TOKEN, securedClient);

struct Telemetry {
  float ax = 0, ay = 0, az = 0;
  float rms = 0, vrms = 0;
  float rpm = 0, freq = 0;
  unsigned long imp = 0, impt = 0;
  int urg = 0;
  int alerte = 0;
  float current = 0, temp = 0, voltage = 0;
  bool motorOn = false;
  unsigned long updatedAt = 0;
  bool valid = false;
} tel;

// ---- Historique alertes (admin) ----
const int HIST_SIZE = 12;
struct HistEntry {
  char text[72];
  unsigned long ms;
  bool used;
} hist[HIST_SIZE];
int histHead = 0;

unsigned long lastBotCheck = 0;
unsigned long lastAlertMs = 0;
const unsigned long BOT_INTERVAL_MS = 800;
const unsigned long ALERT_COOLDOWN_MS = 45000;

String unoLineBuf;

void pushHistory(const String& line) {
  HistEntry& e = hist[histHead];
  strncpy(e.text, line.c_str(), sizeof(e.text) - 1);
  e.text[sizeof(e.text) - 1] = '\0';
  e.ms = millis();
  e.used = true;
  histHead = (histHead + 1) % HIST_SIZE;
}

String urgLabel(int u) {
  if (u >= 2) return "URGENCE";
  if (u == 1) return "ALERTE";
  return "OK";
}

bool isAdmin(const String& chatId) {
  return chatId == String(TELEGRAM_ADMIN_CHAT_ID);
}

bool isViewer(const String& chatId) {
  if (isAdmin(chatId)) return true;
#ifdef TELEGRAM_VIEWER_CHAT_ID
  if (String(TELEGRAM_VIEWER_CHAT_ID).length() > 0 &&
      chatId == String(TELEGRAM_VIEWER_CHAT_ID)) return true;
#endif
  return false;
}

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
    delay(10000);
    ESP.restart();
  }
}

void sendToUno(const char* cmd) {
  UnoSerial.println(cmd);
  Serial.print(F(">> Uno: "));
  Serial.println(cmd);
}

/** Tableau commun : ax ay az rms vrms rpm(mpr) impulsions frequence urgence alerte */
String formatDashboardCore() {
  if (!tel.valid) {
    return String("Aucune donnee Uno.\nVerifiez UART / capteurs.");
  }
  String s;
  s.reserve(420);
  s += "TABLEAU DE BORD MOTEUR\n";
  s += "=====================\n";
  s += "ax   : ";
  s += String(tel.ax, 3);
  s += " g\n";
  s += "ay   : ";
  s += String(tel.ay, 3);
  s += " g\n";
  s += "az   : ";
  s += String(tel.az, 3);
  s += " g\n";
  s += "RMS  : ";
  s += String(tel.rms, 3);
  s += " g\n";
  s += "vRMS : ";
  s += String(tel.vrms, 2);
  s += " mm/s\n";
  s += "RPM  : ";
  s += String(tel.rpm, 0);
  s += " tr/min\n";
  s += "Impulsions : ";
  s += String(tel.imp);
  s += " (fenetre) / ";
  s += String(tel.impt);
  s += " (total)\n";
  s += "Frequence  : ";
  s += String(tel.freq, 2);
  s += " Hz\n";
  s += "Urgence : ";
  s += urgLabel(tel.urg);
  s += " (";
  s += String(tel.urg);
  s += ")\n";
  s += "Alerte  : ";
  s += tel.alerte ? "OUI" : "NON";
  s += "\n";
  s += "---------------------\n";
  s += "I=";
  s += String(tel.current, 2);
  s += "A  U=";
  s += String(tel.voltage, 1);
  s += "V  T=";
  s += String(tel.temp, 1);
  s += "C\n";
  s += "Moteur : ";
  s += tel.motorOn ? "ON" : "OFF";
  s += "\nMaj : ";
  s += String((millis() - tel.updatedAt) / 1000);
  s += " s";
  return s;
}

String formatHistory() {
  String s = "HISTORIQUE ALERTES\n==================\n";
  int shown = 0;
  // Plus récent en premier
  for (int i = 0; i < HIST_SIZE; i++) {
    int idx = (histHead - 1 - i + HIST_SIZE * 2) % HIST_SIZE;
    if (!hist[idx].used) continue;
    unsigned long ageSec = (millis() - hist[idx].ms) / 1000;
    s += "- [";
    s += String(ageSec);
    s += "s] ";
    s += hist[idx].text;
    s += "\n";
    shown++;
  }
  if (shown == 0) s += "(vide)\n";
  return s;
}

/** Clavier inline ADMIN : ON OFF + Actualiser Historique Urgence */
String adminKeyboardJson() {
  return String(
    "[[{\"text\":\"ON\",\"callback_data\":\"motor_on\"},"
    "{\"text\":\"OFF\",\"callback_data\":\"motor_off\"}],"
    "[{\"text\":\"Actualiser\",\"callback_data\":\"refresh\"},"
    "{\"text\":\"Historique\",\"callback_data\":\"history\"}],"
    "[{\"text\":\"URGENCE STOP\",\"callback_data\":\"emergency\"},"
    "{\"text\":\"Alertes\",\"callback_data\":\"alerts\"}]]"
  );
}

/** Clavier observateur : lecture seule */
String viewerKeyboardJson() {
  return String(
    "[[{\"text\":\"Actualiser\",\"callback_data\":\"refresh\"},"
    "{\"text\":\"Alertes\",\"callback_data\":\"alerts\"}]]"
  );
}

void sendAdminDashboard(const String& chatId) {
  sendToUno("STATUS");
  delay(350);
  String text = "ADMIN\n" + formatDashboardCore();
  bot.sendMessageWithInlineKeyboard(chatId, text, "", adminKeyboardJson());
}

void sendViewerDashboard(const String& chatId) {
  sendToUno("STATUS");
  delay(350);
  String text = "OBSERVATEUR\n" + formatDashboardCore();
  bot.sendMessageWithInlineKeyboard(chatId, text, "", viewerKeyboardJson());
}

void notifyChats(const String& msg) {
  bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, msg, "");
#ifdef TELEGRAM_VIEWER_CHAT_ID
  if (String(TELEGRAM_VIEWER_CHAT_ID).length() > 0) {
    bot.sendMessage(TELEGRAM_VIEWER_CHAT_ID, msg, "");
  }
#endif
}

void handleCallback(telegramMessage& msg) {
  String chat = msg.chat_id;
  String data = msg.text;
  data.trim();

  if (!isViewer(chat)) {
    bot.sendMessage(chat, "Acces refuse.", "");
    return;
  }

  if (data == "refresh") {
    if (isAdmin(chat)) sendAdminDashboard(chat);
    else sendViewerDashboard(chat);
    return;
  }

  if (data == "alerts") {
    String a = "Etat alertes\n";
    a += "Urgence : ";
    a += urgLabel(tel.urg);
    a += "\nAlerte  : ";
    a += tel.alerte ? "OUI" : "NON";
    a += "\nRMS=";
    a += String(tel.rms, 3);
    a += "g vRMS=";
    a += String(tel.vrms, 2);
    a += " mm/s";
    if (isAdmin(chat)) {
      bot.sendMessageWithInlineKeyboard(chat, a, "", adminKeyboardJson());
    } else {
      bot.sendMessageWithInlineKeyboard(chat, a, "", viewerKeyboardJson());
    }
    return;
  }

  // ---- Réservé ADMIN ----
  if (!isAdmin(chat)) {
    bot.sendMessage(chat, "Reserve a l'administrateur.", "");
    return;
  }

  if (data == "motor_on") {
    sendToUno("MOTOR_ON");
    pushHistory("CMD admin: MOTOR ON");
    bot.sendMessageWithInlineKeyboard(chat, "Commande ON envoyee.", "", adminKeyboardJson());
  } else if (data == "motor_off") {
    sendToUno("MOTOR_OFF");
    pushHistory("CMD admin: MOTOR OFF");
    bot.sendMessageWithInlineKeyboard(chat, "Commande OFF envoyee.", "", adminKeyboardJson());
  } else if (data == "emergency") {
    sendToUno("MOTOR_OFF");
    pushHistory("URGENCE STOP admin");
    notifyChats("URGENCE : arret moteur demande par admin.");
    bot.sendMessageWithInlineKeyboard(chat, "URGENCE STOP execute.", "", adminKeyboardJson());
  } else if (data == "history") {
    bot.sendMessageWithInlineKeyboard(chat, formatHistory(), "", adminKeyboardJson());
  }
}

void handleTelegramMessage(telegramMessage& msg) {
  String chat = msg.chat_id;

  // Callback boutons
  if (msg.type == "callback_query") {
    handleCallback(msg);
    return;
  }

  if (!isViewer(chat)) {
    bot.sendMessage(chat, "Acces refuse. Contactez l'admin.", "");
    return;
  }

  String text = msg.text;
  text.trim();

  if (text == "/start" || text == "/help" || text == "/dashboard") {
    if (isAdmin(chat)) {
      bot.sendMessage(chat,
        "Tableau de bord ADMIN\n"
        "/dashboard - panneau + boutons\n"
        "/status    - metriques\n"
        "/historique - historique\n"
        "/on /off   - moteur\n"
        "/urgence   - stop immediat",
        "");
      sendAdminDashboard(chat);
    } else {
      bot.sendMessage(chat,
        "Tableau de bord OBSERVATEUR\n"
        "Metriques : ax ay az rms vrms rpm impulsions frequence urgence alerte\n"
        "/dashboard - panneau\n"
        "/status    - metriques\n"
        "(pas de commande moteur)",
        "");
      sendViewerDashboard(chat);
    }
  } else if (text == "/status") {
    sendToUno("STATUS");
    delay(350);
    bot.sendMessage(chat, formatDashboardCore(), "");
  } else if (text == "/historique" || text == "/history") {
    if (!isAdmin(chat)) {
      bot.sendMessage(chat, "Historique reserve a l'admin.", "");
      return;
    }
    bot.sendMessage(chat, formatHistory(), "");
  } else if (text == "/on") {
    if (!isAdmin(chat)) {
      bot.sendMessage(chat, "Reserve admin.", "");
      return;
    }
    sendToUno("MOTOR_ON");
    pushHistory("CMD /on");
    bot.sendMessage(chat, "MOTOR ON", "");
  } else if (text == "/off") {
    if (!isAdmin(chat)) {
      bot.sendMessage(chat, "Reserve admin.", "");
      return;
    }
    sendToUno("MOTOR_OFF");
    pushHistory("CMD /off");
    bot.sendMessage(chat, "MOTOR OFF", "");
  } else if (text == "/urgence" || text == "/emergency") {
    if (!isAdmin(chat)) {
      bot.sendMessage(chat, "Reserve admin.", "");
      return;
    }
    sendToUno("MOTOR_OFF");
    pushHistory("CMD /urgence");
    notifyChats("URGENCE : arret moteur.");
  } else if (text == "/ping") {
    if (!isAdmin(chat)) return;
    sendToUno("PING");
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
  if (!tel.valid || !tel.alerte) return;
  unsigned long now = millis();
  if (now - lastAlertMs < ALERT_COOLDOWN_MS) return;
  lastAlertMs = now;

  String reason = "ALERTE / ";
  reason += urgLabel(tel.urg);
  reason += " | RMS=";
  reason += String(tel.rms, 3);
  reason += "g vRMS=";
  reason += String(tel.vrms, 2);
  reason += " rpm=";
  reason += String(tel.rpm, 0);

  pushHistory(reason);
  notifyChats(reason + "\n\n" + formatDashboardCore());
}

bool parseTelemetry(const String& line) {
  StaticJsonDocument<512> doc;
  if (deserializeJson(doc, line)) return false;

  if (doc.containsKey("evt")) {
    const char* evt = doc["evt"];
    Serial.print(F("Evt: "));
    Serial.println(evt);
    if (strcmp(evt, "SAFE_STOP") == 0) {
      pushHistory("SAFE_STOP Uno (urgence)");
      notifyChats("STOP SECURITE Uno — moteur coupe.");
    } else if (strcmp(evt, "PONG") == 0) {
      bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, "Uno : PONG", "");
    } else if (strcmp(evt, "MOTOR_ON") == 0 || strcmp(evt, "MOTOR_OFF") == 0) {
      pushHistory(String("Uno ") + evt);
      notifyChats(String("Uno : ") + evt);
    } else if (strcmp(evt, "UNO_READY") == 0) {
      int adxl = doc["adxl"] | 0;
      String m = adxl ? "Uno pret — ADXL345 OK" : "Uno pret — ADXL345 ABSENT";
      pushHistory(m);
      bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, m, "");
    }
    return true;
  }

  if (!doc.containsKey("ax")) return false;

  tel.ax = doc["ax"] | 0.0;
  tel.ay = doc["ay"] | 0.0;
  tel.az = doc["az"] | 0.0;
  tel.rms = doc["rms"] | 0.0;
  tel.vrms = doc["vrms"] | 0.0;
  tel.rpm = doc["rpm"] | 0.0;
  tel.imp = doc["imp"] | 0;
  tel.impt = doc["impt"] | 0;
  tel.freq = doc["freq"] | 0.0;
  tel.urg = doc["urg"] | 0;
  tel.alerte = doc["alerte"] | 0;
  tel.current = doc["c"] | 0.0;
  tel.temp = doc["t"] | 0.0;
  tel.voltage = doc["v"] | 0.0;
  tel.motorOn = (doc["m"] | 0) == 1;
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
        Serial.print(F("<< "));
        Serial.println(unoLineBuf);
        parseTelemetry(unoLineBuf);
      }
      unoLineBuf = "";
    } else if (c != '\r') {
      if (unoLineBuf.length() < 480) unoLineBuf += c;
    }
  }
}

void setup() {
  Serial.begin(115200);
  delay(200);
  Serial.println(F("ESP32 Telegram Dashboard ADMIN/VIEWER"));

  for (int i = 0; i < HIST_SIZE; i++) hist[i].used = false;

  UnoSerial.begin(9600, SERIAL_8N1, 16, 17);
  connectWifi();

  securedClient.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  // securedClient.setInsecure();

  String boot = "Bot tableau de bord pret.\nIP: " + WiFi.localIP().toString() +
                "\nEnvoyez /dashboard";
  bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, boot, "");
  pushHistory("ESP32 boot OK");
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) connectWifi();
  readUnoSerial();

  unsigned long now = millis();
  if (now - lastBotCheck >= BOT_INTERVAL_MS) {
    lastBotCheck = now;
    pollTelegram();
    maybeAlert();
  }
}
