/*
 * ESP32 — Tableau de bord Telegram (admin + observateur)
 * Libs : WiFi + UniversalTelegramBot uniquement
 * (pas ArduinoJson, pas config.h)
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <UniversalTelegramBot.h>
#include <time.h>

// ========== CONFIG À REMPLIR ==========
const char* WIFI_SSID             = "VOTRE_SSID";
const char* WIFI_PASSWORD         = "VOTRE_MOT_DE_PASSE";
const char* TELEGRAM_BOT_TOKEN    = "123456:ABC-DEF_votre_token";
const char* TELEGRAM_ADMIN_CHAT_ID = "123456789";
const char* TELEGRAM_VIEWER_CHAT_ID = "";  // optionnel
// Fuseau horaire : UTC+1 (Afrique Centrale / WAT). Changer si besoin.
const long GMT_OFFSET_SEC = 3600;
const int  DAYLIGHT_OFFSET_SEC = 0;
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

// Historique avec date, heure et données
const int HIST_SIZE = 10;
struct HistEntry {
  char dateStr[12];   // JJ/MM/AAAA
  char timeStr[10];   // HH:MM:SS
  char event[48];
  float ax, ay, az, rms, vrms, rpm, freq;
  unsigned long imp;
  int urg, alerte;
  bool motorOn;
  bool hasData;
  bool used;
} hist[HIST_SIZE];
int histHead = 0;
bool timeOk = false;

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

void syncTime() {
  configTime(GMT_OFFSET_SEC, DAYLIGHT_OFFSET_SEC, "pool.ntp.org", "time.google.com");
  Serial.print(F("NTP"));
  struct tm ti;
  int tries = 0;
  while (!getLocalTime(&ti) && tries < 20) {
    delay(500);
    Serial.print('.');
    tries++;
  }
  Serial.println();
  timeOk = getLocalTime(&ti);
  if (timeOk) {
    Serial.printf("Date/heure: %02d/%02d/%04d %02d:%02d:%02d\n",
                  ti.tm_mday, ti.tm_mon + 1, ti.tm_year + 1900,
                  ti.tm_hour, ti.tm_min, ti.tm_sec);
  } else {
    Serial.println(F("NTP echec — historique sans horloge reseau"));
  }
}

String nowDateStr() {
  struct tm ti;
  if (!getLocalTime(&ti)) return "--/--/----";
  char buf[12];
  snprintf(buf, sizeof(buf), "%02d/%02d/%04d", ti.tm_mday, ti.tm_mon + 1, ti.tm_year + 1900);
  return String(buf);
}

String nowTimeStr() {
  struct tm ti;
  if (!getLocalTime(&ti)) return "--:--:--";
  char buf[10];
  snprintf(buf, sizeof(buf), "%02d:%02d:%02d", ti.tm_hour, ti.tm_min, ti.tm_sec);
  return String(buf);
}

void pushHistory(const String& event) {
  HistEntry& e = hist[histHead];
  String d = nowDateStr();
  String t = nowTimeStr();
  strncpy(e.dateStr, d.c_str(), sizeof(e.dateStr) - 1);
  e.dateStr[sizeof(e.dateStr) - 1] = '\0';
  strncpy(e.timeStr, t.c_str(), sizeof(e.timeStr) - 1);
  e.timeStr[sizeof(e.timeStr) - 1] = '\0';
  strncpy(e.event, event.c_str(), sizeof(e.event) - 1);
  e.event[sizeof(e.event) - 1] = '\0';

  e.hasData = tel.valid;
  e.ax = tel.ax; e.ay = tel.ay; e.az = tel.az;
  e.rms = tel.rms; e.vrms = tel.vrms;
  e.rpm = tel.rpm; e.freq = tel.freq; e.imp = tel.imp;
  e.urg = tel.urg; e.alerte = tel.alerte;
  e.motorOn = tel.motorOn;
  e.used = true;
  histHead = (histHead + 1) % HIST_SIZE;

  Serial.print(F("[HIST] "));
  Serial.print(e.dateStr); Serial.print(' ');
  Serial.print(e.timeStr); Serial.print(" | ");
  Serial.println(e.event);
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

String htmlEscape(const String& in) {
  String o;
  o.reserve(in.length() + 8);
  for (unsigned i = 0; i < in.length(); i++) {
    char c = in[i];
    if (c == '&') o += F("&amp;");
    else if (c == '<') o += F("&lt;");
    else if (c == '>') o += F("&gt;");
    else o += c;
  }
  return o;
}

String padCell(const String& s, int w) {
  String o = s;
  if ((int)o.length() > w) return o.substring(0, w);
  while ((int)o.length() < w) o += ' ';
  return o;
}

String tableRow(const String& a, const String& b) {
  return "| " + padCell(a, 12) + " | " + padCell(b, 14) + " |\n";
}

String tableSep() {
  return "+--------------+----------------+\n";
}

/** Tableau de bord en forme de tableau (HTML <pre> pour Telegram) */
String formatDashboardCore() {
  if (!tel.valid) {
    return F("<pre>Aucune donnee Uno.\nVerifiez UART / capteurs.</pre>");
  }
  String s;
  s.reserve(700);
  s += F("<pre>");
  s += F("TABLEAU DE BORD\n");
  s += tableSep();
  s += tableRow("Champ", "Valeur");
  s += tableSep();
  s += tableRow("Date", nowDateStr());
  s += tableRow("Heure", nowTimeStr());
  s += tableRow("ax", String(tel.ax, 3) + " g");
  s += tableRow("ay", String(tel.ay, 3) + " g");
  s += tableRow("az", String(tel.az, 3) + " g");
  s += tableRow("RMS", String(tel.rms, 3) + " g");
  s += tableRow("vRMS", String(tel.vrms, 2) + " mm/s");
  s += tableRow("RPM", String(tel.rpm, 0) + " tr/min");
  s += tableRow("Impulsions", String(tel.imp));
  s += tableRow("Frequence", String(tel.freq, 2) + " Hz");
  s += tableRow("Urgence", urgLabel(tel.urg));
  s += tableRow("Alerte", tel.alerte ? "OUI" : "NON");
  s += tableRow("Moteur", tel.motorOn ? "ON" : "OFF");
  s += tableSep();
  s += F("</pre>");
  return s;
}

/** Historique : chaque evenement = tableau date/heure/donnees */
String formatHistory() {
  String s;
  s.reserve(2500);
  s += F("<pre>HISTORIQUE\n");
  int shown = 0;
  for (int i = 0; i < HIST_SIZE; i++) {
    int idx = (histHead - 1 - i + HIST_SIZE * 2) % HIST_SIZE;
    if (!hist[idx].used) continue;
    const HistEntry& e = hist[idx];
    s += '\n';
    s += tableSep();
    s += tableRow("Date", String(e.dateStr));
    s += tableRow("Heure", String(e.timeStr));
    s += tableRow("Evenement", htmlEscape(String(e.event)));
    if (e.hasData) {
      s += tableSep();
      s += tableRow("ax", String(e.ax, 3) + " g");
      s += tableRow("ay", String(e.ay, 3) + " g");
      s += tableRow("az", String(e.az, 3) + " g");
      s += tableRow("RMS", String(e.rms, 3) + " g");
      s += tableRow("vRMS", String(e.vrms, 2) + " mm/s");
      s += tableRow("RPM", String(e.rpm, 0));
      s += tableRow("Frequence", String(e.freq, 2) + " Hz");
      s += tableRow("Impulsions", String(e.imp));
      s += tableRow("Urgence", urgLabel(e.urg));
      s += tableRow("Alerte", e.alerte ? "OUI" : "NON");
      s += tableRow("Moteur", e.motorOn ? "ON" : "OFF");
    } else {
      s += tableRow("Donnees", "(aucune)");
    }
    s += tableSep();
    shown++;
  }
  if (shown == 0) s += "(vide)\n";
  s += F("</pre>");
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
    "{\"text\":\"Historique\",\"callback_data\":\"history\"}],"
    "[{\"text\":\"Alertes\",\"callback_data\":\"alerts\"},"
    "{\"text\":\"Tableau\",\"callback_data\":\"refresh\"}]]"
  );
}

/** Toujours renvoyer les boutons en bas de la reponse */
void replyWithButtons(const String& chat, const String& text, const String& parseMode = "") {
  if (isAdmin(chat)) {
    bot.sendMessageWithInlineKeyboard(chat, text, parseMode, adminKeyboardJson());
  } else {
    bot.sendMessageWithInlineKeyboard(chat, text, parseMode, viewerKeyboardJson());
  }
}

void sendAdminDashboard(const String& chatId) {
  sendToUno("STATUS");
  delay(350);
  replyWithButtons(chatId, "<b>ADMIN</b>\n" + formatDashboardCore(), "HTML");
}

void sendViewerDashboard(const String& chatId) {
  sendToUno("STATUS");
  delay(350);
  replyWithButtons(chatId, "<b>OBSERVATEUR</b>\n" + formatDashboardCore(), "HTML");
}

void notifyChats(const String& msg) {
  bot.sendMessageWithInlineKeyboard(TELEGRAM_ADMIN_CHAT_ID, msg, "", adminKeyboardJson());
  if (TELEGRAM_VIEWER_CHAT_ID[0] != '\0') {
    bot.sendMessageWithInlineKeyboard(TELEGRAM_VIEWER_CHAT_ID, msg, "", viewerKeyboardJson());
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
    String a = "<pre>";
    a += tableSep();
    a += tableRow("Champ", "Valeur");
    a += tableSep();
    a += tableRow("Urgence", urgLabel(tel.urg));
    a += tableRow("Alerte", tel.alerte ? "OUI" : "NON");
    a += tableRow("RMS", String(tel.rms, 3) + " g");
    a += tableRow("vRMS", String(tel.vrms, 2) + " mm/s");
    a += tableSep();
    a += "</pre>";
    replyWithButtons(chat, a, "HTML");
    return;
  }

  if (data == "history") {
    if (!isAdmin(chat)) {
      replyWithButtons(chat, "Historique reserve a l'administrateur.");
      return;
    }
    replyWithButtons(chat, formatHistory(), "HTML");
    return;
  }

  if (!isAdmin(chat)) {
    replyWithButtons(chat, "Reserve a l'administrateur.");
    return;
  }

  if (data == "motor_on") {
    sendToUno("MOTOR_ON");
    pushHistory("CMD admin: MOTOR ON");
    replyWithButtons(chat, "Commande <b>ON</b> envoyee.\n>>> RELAIS ALLUME", "HTML");
  } else if (data == "motor_off") {
    sendToUno("MOTOR_OFF");
    pushHistory("CMD admin: MOTOR OFF");
    replyWithButtons(chat, "Commande <b>OFF</b> envoyee.\n>>> RELAIS ETEINT", "HTML");
  } else if (data == "emergency") {
    sendToUno("MOTOR_OFF");
    pushHistory("URGENCE STOP admin");
    notifyChats("URGENCE : arret moteur demande par admin.");
    replyWithButtons(chat, "<b>URGENCE STOP</b> execute.\n>>> RELAIS ETEINT", "HTML");
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
      replyWithButtons(chat,
        "Tableau de bord <b>ADMIN</b>\nUtilisez les boutons ci-dessous.", "HTML");
      sendAdminDashboard(chat);
    } else {
      replyWithButtons(chat,
        "Tableau de bord <b>OBSERVATEUR</b>\nUtilisez les boutons ci-dessous.", "HTML");
      sendViewerDashboard(chat);
    }
  } else if (text == "/status") {
    sendToUno("STATUS");
    delay(350);
    replyWithButtons(chat, formatDashboardCore(), "HTML");
  } else if (text == "/historique" || text == "/history") {
    if (!isAdmin(chat)) {
      replyWithButtons(chat, "Historique reserve a l'admin.");
      return;
    }
    replyWithButtons(chat, formatHistory(), "HTML");
  } else if (text == "/on") {
    if (!isAdmin(chat)) { replyWithButtons(chat, "Reserve admin."); return; }
    sendToUno("MOTOR_ON");
    pushHistory("CMD /on");
    replyWithButtons(chat, "<b>MOTOR ON</b>\n>>> RELAIS ALLUME", "HTML");
  } else if (text == "/off") {
    if (!isAdmin(chat)) { replyWithButtons(chat, "Reserve admin."); return; }
    sendToUno("MOTOR_OFF");
    pushHistory("CMD /off");
    replyWithButtons(chat, "<b>MOTOR OFF</b>\n>>> RELAIS ETEINT", "HTML");
  } else if (text == "/urgence" || text == "/emergency") {
    if (!isAdmin(chat)) { replyWithButtons(chat, "Reserve admin."); return; }
    sendToUno("MOTOR_OFF");
    pushHistory("CMD /urgence");
    notifyChats("URGENCE : arret moteur.");
    replyWithButtons(chat, "<b>URGENCE STOP</b>\n>>> RELAIS ETEINT", "HTML");
  } else if (text == "/ping") {
    if (isAdmin(chat)) {
      sendToUno("PING");
      replyWithButtons(chat, "PING envoye au Uno.");
    }
  } else {
    replyWithButtons(chat, "Commande inconnue. Utilisez les boutons ou /help");
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
  notifyChats(reason);
  bot.sendMessageWithInlineKeyboard(
    TELEGRAM_ADMIN_CHAT_ID, formatDashboardCore(), "HTML", adminKeyboardJson());
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
      bot.sendMessageWithInlineKeyboard(TELEGRAM_ADMIN_CHAT_ID, "Uno : PONG", "", adminKeyboardJson());
    } else if (evt == "MOTOR_ON" || evt == "MOTOR_OFF") {
      pushHistory(String("Uno ") + evt);
      notifyChats(String("Uno : ") + evt);
    } else if (evt == "UNO_READY") {
      int adxl = (int)jsonGetLong(line, "adxl", 0);
      String m = adxl ? "Uno pret — ADXL345 OK" : "Uno pret — ADXL345 ABSENT";
      pushHistory(m);
      bot.sendMessageWithInlineKeyboard(TELEGRAM_ADMIN_CHAT_ID, m, "", adminKeyboardJson());
    } else if (evt == "CALIB_OK") {
      bot.sendMessageWithInlineKeyboard(TELEGRAM_ADMIN_CHAT_ID, "Calibration ADXL OK", "", adminKeyboardJson());
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
  syncTime();

  securedClient.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  // securedClient.setInsecure();

  String boot = "Bot pret.\nIP: " + WiFi.localIP().toString();
  boot += "\nDate: " + nowDateStr() + " " + nowTimeStr();
  boot += "\n/dashboard";
  bot.sendMessageWithInlineKeyboard(TELEGRAM_ADMIN_CHAT_ID, boot, "", adminKeyboardJson());
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
