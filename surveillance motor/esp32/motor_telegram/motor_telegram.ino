/*
 * ESP32 — Tableau de bord Telegram (admin + observateur)
 * Libs : WiFi + UniversalTelegramBot uniquement
 * (pas ArduinoJson, pas config.h)
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <UniversalTelegramBot.h>

// ========== CONFIG À REMPLIR ==========
const char* WIFI_SSID             = "VOTRE_SSID";
const char* WIFI_PASSWORD         = "VOTRE_MOT_DE_PASSE";
const char* TELEGRAM_BOT_TOKEN    = "123456:ABC-DEF_votre_token";
const char* TELEGRAM_ADMIN_CHAT_ID = "123456789";
const char* TELEGRAM_VIEWER_CHAT_ID = "";  // optionnel
// =====================================

HardwareSerial& UnoSerial = Serial2;
WiFiClientSecure securedClient;
UniversalTelegramBot bot(TELEGRAM_BOT_TOKEN, securedClient);

struct Telemetry {
  float ax = 0, ay = 0, az = 0;
  float rms = 0, vrms = 0;
  float rpm = 0, freq = 0;
  unsigned long imp = 0;
  int urg = 0;
  int alerte = 0;
  bool motorOn = false;
  unsigned long updatedAt = 0;
  bool valid = false;
} tel;

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

// ---- Parse JSON simple (sans ArduinoJson) ----
bool jsonHasKey(const String& line, const char* key) {
  String pat = String("\"") + key + "\"";
  return line.indexOf(pat) >= 0;
}

float jsonGetFloat(const String& line, const char* key, float defVal = 0) {
  String pat = String("\"") + key + "\":";
  int i = line.indexOf(pat);
  if (i < 0) return defVal;
  i += pat.length();
  while (i < (int)line.length() && (line[i] == ' ')) i++;
  return line.substring(i).toFloat();
}

long jsonGetLong(const String& line, const char* key, long defVal = 0) {
  String pat = String("\"") + key + "\":";
  int i = line.indexOf(pat);
  if (i < 0) return defVal;
  i += pat.length();
  while (i < (int)line.length() && (line[i] == ' ')) i++;
  return line.substring(i).toInt();
}

String jsonGetString(const String& line, const char* key) {
  String pat = String("\"") + key + "\":\"";
  int i = line.indexOf(pat);
  if (i < 0) return "";
  i += pat.length();
  int j = line.indexOf('"', i);
  if (j < 0) return "";
  return line.substring(i, j);
}

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
  if (TELEGRAM_VIEWER_CHAT_ID[0] != '\0' &&
      chatId == String(TELEGRAM_VIEWER_CHAT_ID)) return true;
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

String formatDashboardCore() {
  if (!tel.valid) {
    return String("Aucune donnee Uno.\nVerifiez UART / capteurs.");
  }
  String s;
  s.reserve(420);
  s += "TABLEAU DE BORD MOTEUR\n";
  s += "=====================\n";
  s += "ax   : "; s += String(tel.ax, 3); s += " g\n";
  s += "ay   : "; s += String(tel.ay, 3); s += " g\n";
  s += "az   : "; s += String(tel.az, 3); s += " g\n";
  s += "RMS  : "; s += String(tel.rms, 3); s += " g\n";
  s += "vRMS : "; s += String(tel.vrms, 2); s += " mm/s\n";
  s += "RPM  : "; s += String(tel.rpm, 0); s += " tr/min\n";
  s += "Impulsions : "; s += String(tel.imp); s += "\n";
  s += "Frequence  : "; s += String(tel.freq, 2); s += " Hz\n";
  s += "Urgence : "; s += urgLabel(tel.urg);
  s += " ("; s += String(tel.urg); s += ")\n";
  s += "Alerte  : "; s += tel.alerte ? "OUI" : "NON"; s += "\n";
  s += "---------------------\n";
  s += "Moteur : "; s += tel.motorOn ? "ON" : "OFF";
  s += "\nMaj : "; s += String((millis() - tel.updatedAt) / 1000); s += " s";
  return s;
}

String formatHistory() {
  String s = "HISTORIQUE ALERTES\n==================\n";
  int shown = 0;
  for (int i = 0; i < HIST_SIZE; i++) {
    int idx = (histHead - 1 - i + HIST_SIZE * 2) % HIST_SIZE;
    if (!hist[idx].used) continue;
    unsigned long ageSec = (millis() - hist[idx].ms) / 1000;
    s += "- ["; s += String(ageSec); s += "s] ";
    s += hist[idx].text; s += "\n";
    shown++;
  }
  if (shown == 0) s += "(vide)\n";
  return s;
}

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

String viewerKeyboardJson() {
  return String(
    "[[{\"text\":\"Actualiser\",\"callback_data\":\"refresh\"},"
    "{\"text\":\"Alertes\",\"callback_data\":\"alerts\"}]]"
  );
}

void sendAdminDashboard(const String& chatId) {
  sendToUno("STATUS");
  delay(350);
  bot.sendMessageWithInlineKeyboard(chatId, "ADMIN\n" + formatDashboardCore(), "", adminKeyboardJson());
}

void sendViewerDashboard(const String& chatId) {
  sendToUno("STATUS");
  delay(350);
  bot.sendMessageWithInlineKeyboard(chatId, "OBSERVATEUR\n" + formatDashboardCore(), "", viewerKeyboardJson());
}

void notifyChats(const String& msg) {
  bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, msg, "");
  if (TELEGRAM_VIEWER_CHAT_ID[0] != '\0') {
    bot.sendMessage(TELEGRAM_VIEWER_CHAT_ID, msg, "");
  }
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
    String a = "Etat alertes\nUrgence : ";
    a += urgLabel(tel.urg);
    a += "\nAlerte  : ";
    a += tel.alerte ? "OUI" : "NON";
    a += "\nRMS=";
    a += String(tel.rms, 3);
    a += "g vRMS=";
    a += String(tel.vrms, 2);
    a += " mm/s";
    bot.sendMessageWithInlineKeyboard(
      chat, a, "", isAdmin(chat) ? adminKeyboardJson() : viewerKeyboardJson());
    return;
  }

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
        "Tableau de bord ADMIN\n/dashboard /status /historique /on /off /urgence", "");
      sendAdminDashboard(chat);
    } else {
      bot.sendMessage(chat,
        "Tableau de bord OBSERVATEUR\n/dashboard /status", "");
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
    if (!isAdmin(chat)) { bot.sendMessage(chat, "Reserve admin.", ""); return; }
    sendToUno("MOTOR_ON");
    pushHistory("CMD /on");
    bot.sendMessage(chat, "MOTOR ON", "");
  } else if (text == "/off") {
    if (!isAdmin(chat)) { bot.sendMessage(chat, "Reserve admin.", ""); return; }
    sendToUno("MOTOR_OFF");
    pushHistory("CMD /off");
    bot.sendMessage(chat, "MOTOR OFF", "");
  } else if (text == "/urgence" || text == "/emergency") {
    if (!isAdmin(chat)) { bot.sendMessage(chat, "Reserve admin.", ""); return; }
    sendToUno("MOTOR_OFF");
    pushHistory("CMD /urgence");
    notifyChats("URGENCE : arret moteur.");
  } else if (text == "/ping") {
    if (isAdmin(chat)) sendToUno("PING");
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
  if (jsonHasKey(line, "evt")) {
    String evt = jsonGetString(line, "evt");
    Serial.print(F("Evt: "));
    Serial.println(evt);
    if (evt == "SAFE_STOP") {
      pushHistory("SAFE_STOP Uno (urgence)");
      notifyChats("STOP SECURITE Uno — moteur coupe.");
    } else if (evt == "PONG") {
      bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, "Uno : PONG", "");
    } else if (evt == "MOTOR_ON" || evt == "MOTOR_OFF") {
      pushHistory(String("Uno ") + evt);
      notifyChats(String("Uno : ") + evt);
    } else if (evt == "UNO_READY") {
      int adxl = (int)jsonGetLong(line, "adxl", 0);
      String m = adxl ? "Uno pret — ADXL345 OK" : "Uno pret — ADXL345 ABSENT";
      pushHistory(m);
      bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, m, "");
    }
    return true;
  }

  if (!jsonHasKey(line, "ax")) return false;

  tel.ax = jsonGetFloat(line, "ax");
  tel.ay = jsonGetFloat(line, "ay");
  tel.az = jsonGetFloat(line, "az");
  tel.rms = jsonGetFloat(line, "rms");
  tel.vrms = jsonGetFloat(line, "vrms");
  tel.rpm = jsonGetFloat(line, "rpm");
  tel.imp = (unsigned long)jsonGetLong(line, "imp");
  tel.freq = jsonGetFloat(line, "freq");
  tel.urg = (int)jsonGetLong(line, "urg");
  tel.alerte = (int)jsonGetLong(line, "alerte");
  tel.motorOn = jsonGetLong(line, "m") == 1;
  tel.updatedAt = millis();
  tel.valid = true;

  // === MONITEUR SERIE ESP32 USB @ 115200 ===
  Serial.println(F("--- ESP32 MONITOR ---"));
  Serial.println(formatDashboardCore());
  Serial.println();
  return true;
}

void readUnoSerial() {
  while (UnoSerial.available()) {
    char c = (char)UnoSerial.read();
    if (c == '\n') {
      unoLineBuf.trim();
      if (unoLineBuf.length() > 0) {
        Serial.print(F("[UART Uno] "));
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
  Serial.begin(115200);  // MONITEUR 2 — USB ESP32
  delay(200);
  Serial.println(F("=== MONITEUR ESP32 USB 115200 ==="));
  Serial.println(F("UART Uno : GPIO16=RX GPIO17=TX @9600"));

  for (int i = 0; i < HIST_SIZE; i++) hist[i].used = false;

  UnoSerial.begin(9600, SERIAL_8N1, 16, 17);
  connectWifi();

  securedClient.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  // securedClient.setInsecure();

  String boot = "Bot pret.\nIP: " + WiFi.localIP().toString() + "\n/dashboard";
  bot.sendMessage(TELEGRAM_ADMIN_CHAT_ID, boot, "");
  pushHistory("ESP32 boot OK");
  Serial.println(boot);
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
