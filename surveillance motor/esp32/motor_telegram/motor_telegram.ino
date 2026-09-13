/*
 * ESP32 — Tableau de bord Telegram (admin + observateur)
 * Libs : WiFi + UniversalTelegramBot + LittleFS
 * Historique persistant 30 jours (date/heure de chaque action)
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <UniversalTelegramBot.h>
#include <LittleFS.h>
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
  float seuil = 10;
  float niveau = 0;
  bool motorOn = false;
  unsigned long updatedAt = 0;
  bool valid = false;
} tel;

// ---- Historique persistant (1 mois) ----
const char* HIST_PATH = "/hist.log";
const char* HIST_TMP  = "/hist.tmp";
const int HIST_RETENTION_DAYS = 30;
const int HIST_PAGE_SIZE = 8;
const int HIST_MAX_LINES = 2000;  // plafond sécurité flash

struct Stamp {
  char dateStr[12];
  char timeStr[10];
  bool ok;
} lastDataStamp = {"--/--/----", "--:--:--", false};

struct LastAction {
  char dateStr[12];
  char timeStr[10];
  char event[56];
  bool ok;
} lastAction = {"--/--/----", "--:--:--", "(aucune)", false};

bool fsOk = false;
bool timeOk = false;
time_t lastPruneAt = 0;

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

time_t nowEpoch() {
  struct tm ti;
  if (!getLocalTime(&ti)) return 0;
  return mktime(&ti);
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

void stampNow(Stamp& st) {
  String d = nowDateStr();
  String t = nowTimeStr();
  strncpy(st.dateStr, d.c_str(), sizeof(st.dateStr) - 1);
  st.dateStr[sizeof(st.dateStr) - 1] = '\0';
  strncpy(st.timeStr, t.c_str(), sizeof(st.timeStr) - 1);
  st.timeStr[sizeof(st.timeStr) - 1] = '\0';
  st.ok = true;
}

bool initHistoryFs() {
  if (!LittleFS.begin(true)) {
    Serial.println(F("LittleFS echec"));
    fsOk = false;
    return false;
  }
  fsOk = true;
  if (!LittleFS.exists(HIST_PATH)) {
    File f = LittleFS.open(HIST_PATH, "w");
    if (f) f.close();
  }
  Serial.println(F("LittleFS OK — historique 30 jours"));
  return true;
}

String fieldAt(const String& line, int idx) {
  int start = 0;
  int cur = 0;
  for (unsigned i = 0; i <= line.length(); i++) {
    if (i == line.length() || line[i] == '|') {
      if (cur == idx) return line.substring(start, i);
      cur++;
      start = i + 1;
    }
  }
  return "";
}

bool lineWithinMonth(const String& line, time_t cutoff) {
  if (cutoff == 0) return true;
  time_t ep = (time_t)fieldAt(line, 0).toInt();
  if (ep == 0) return true;
  return ep >= cutoff;
}

time_t monthCutoff() {
  time_t now = nowEpoch();
  if (now == 0) return 0;
  return now - (time_t)HIST_RETENTION_DAYS * 24L * 3600L;
}

int countHistoryLines(time_t cutoff) {
  if (!fsOk) return 0;
  File f = LittleFS.open(HIST_PATH, "r");
  if (!f) return 0;
  int n = 0;
  while (f.available()) {
    String line = f.readStringUntil('\n');
    line.trim();
    if (line.length() == 0) continue;
    if (lineWithinMonth(line, cutoff)) n++;
  }
  f.close();
  return n;
}

void pruneHistoryIfNeeded(bool force = false) {
  if (!fsOk) return;
  time_t now = nowEpoch();
  if (!force && lastPruneAt != 0 && now != 0 && (now - lastPruneAt) < 3600) return;

  time_t cutoff = monthCutoff();
  File in = LittleFS.open(HIST_PATH, "r");
  if (!in) return;

  File out = LittleFS.open(HIST_TMP, "w");
  if (!out) { in.close(); return; }

  int kept = 0;
  while (in.available()) {
    String line = in.readStringUntil('\n');
    line.trim();
    if (line.length() == 0) continue;
    if (!lineWithinMonth(line, cutoff)) continue;
    out.println(line);
    kept++;
    if (kept >= HIST_MAX_LINES) {
      // garder seulement la fin : on lit tout en tmp puis on re-coupe si besoin
    }
  }
  in.close();
  out.close();

  LittleFS.remove(HIST_PATH);
  LittleFS.rename(HIST_TMP, HIST_PATH);

  // Si trop de lignes, garder les HIST_MAX_LINES plus recentes
  int total = countHistoryLines(0);
  if (total > HIST_MAX_LINES) {
    int skip = total - HIST_MAX_LINES;
    in = LittleFS.open(HIST_PATH, "r");
    out = LittleFS.open(HIST_TMP, "w");
    if (in && out) {
      int seen = 0;
      while (in.available()) {
        String line = in.readStringUntil('\n');
        line.trim();
        if (line.length() == 0) continue;
        if (seen++ < skip) continue;
        out.println(line);
      }
    }
    if (in) in.close();
    if (out) out.close();
    LittleFS.remove(HIST_PATH);
    LittleFS.rename(HIST_TMP, HIST_PATH);
  }

  lastPruneAt = now;
  Serial.print(F("[HIST] prune OK, lignes ~"));
  Serial.println(countHistoryLines(monthCutoff()));
}

void loadLastActionFromFs() {
  if (!fsOk) return;
  File f = LittleFS.open(HIST_PATH, "r");
  if (!f) return;
  String last;
  while (f.available()) {
    String line = f.readStringUntil('\n');
    line.trim();
    if (line.length() > 0) last = line;
  }
  f.close();
  if (last.length() == 0) return;
  String d = fieldAt(last, 1);
  String t = fieldAt(last, 2);
  String ev = fieldAt(last, 3);
  strncpy(lastAction.dateStr, d.c_str(), sizeof(lastAction.dateStr) - 1);
  lastAction.dateStr[sizeof(lastAction.dateStr) - 1] = '\0';
  strncpy(lastAction.timeStr, t.c_str(), sizeof(lastAction.timeStr) - 1);
  lastAction.timeStr[sizeof(lastAction.timeStr) - 1] = '\0';
  strncpy(lastAction.event, ev.c_str(), sizeof(lastAction.event) - 1);
  lastAction.event[sizeof(lastAction.event) - 1] = '\0';
  lastAction.ok = true;
}

void pushHistory(const String& event) {
  String d = nowDateStr();
  String t = nowTimeStr();
  time_t ep = nowEpoch();

  // Eviter le caractere '|' qui casse le format du journal
  String evClean = event;
  evClean.replace('|', '/');
  if (evClean.length() > 55) evClean = evClean.substring(0, 55);

  strncpy(lastAction.dateStr, d.c_str(), sizeof(lastAction.dateStr) - 1);
  lastAction.dateStr[sizeof(lastAction.dateStr) - 1] = '\0';
  strncpy(lastAction.timeStr, t.c_str(), sizeof(lastAction.timeStr) - 1);
  lastAction.timeStr[sizeof(lastAction.timeStr) - 1] = '\0';
  strncpy(lastAction.event, evClean.c_str(), sizeof(lastAction.event) - 1);
  lastAction.event[sizeof(lastAction.event) - 1] = '\0';
  lastAction.ok = true;

  Serial.print(F("[HIST] "));
  Serial.print(d); Serial.print(' ');
  Serial.print(t); Serial.print(" | ");
  Serial.println(evClean);

  if (!fsOk) return;

  // ligne: epoch|date|heure|event|niveau|seuil|urg|moteur|rms|rpm
  String line;
  line.reserve(160);
  line += String((unsigned long)ep);
  line += '|';
  line += d;
  line += '|';
  line += t;
  line += '|';
  line += evClean;
  line += '|';
  line += String(tel.niveau, 1);
  line += '|';
  line += String(tel.seuil, 0);
  line += '|';
  line += String(tel.urg);
  line += '|';
  line += (tel.motorOn ? "ON" : "OFF");
  line += '|';
  line += String(tel.rms, 3);
  line += '|';
  line += String(tel.rpm, 0);

  File f = LittleFS.open(HIST_PATH, "a");
  if (f) {
    f.println(line);
    f.close();
  }
  pruneHistoryIfNeeded(false);
}

String htmlEscape(const String& in);
String padCell(const String& s, int w);
String tableRow(const String& a, const String& b);
String tableSep();
String urgLabel(int u);

/** page 1 = plus recent. Retourne HTML <pre> */
String formatHistoryPage(int page) {
  if (page < 1) page = 1;
  time_t cutoff = monthCutoff();
  int total = countHistoryLines(cutoff);
  int pages = (total + HIST_PAGE_SIZE - 1) / HIST_PAGE_SIZE;
  if (pages < 1) pages = 1;
  if (page > pages) page = pages;

  String s;
  s.reserve(3200);
  s += F("<pre>");
  s += "HISTORIQUE 30 JOURS\n";
  s += "Page ";
  s += String(page);
  s += "/";
  s += String(pages);
  s += "  (";
  s += String(total);
  s += " actions)\n";
  s += tableSep();
  s += tableRow("Date", "Heure");
  s += tableSep();

  if (!fsOk || total == 0) {
    s += "(aucune action ce mois)\n</pre>";
    return s;
  }

  // Indices a afficher parmi les entrees valides, ordre newest-first
  // index 0 = plus recent. Page 1 = indices 0..PAGE-1
  int startIdx = (page - 1) * HIST_PAGE_SIZE; // 0-based from newest
  int endIdx = startIdx + HIST_PAGE_SIZE;     // exclusive

  // Lire toutes les lignes du mois dans un buffer d'offsets trop gros —
  // on fait 2 passes: compte, puis selectionne via skip.
  // Pass: stocker les lignes recentes necessaires uniquement.
  // Approche: premiere passe compte total; deuxieme passe lit et garde
  // les lignes dont (total-1-i) est dans [startIdx, endIdx).

  File f = LittleFS.open(HIST_PATH, "r");
  if (!f) {
    s += "(lecture FS impossible)\n</pre>";
    return s;
  }

  int validIdx = 0; // from oldest
  // On a besoin des lignes avec oldestIndex dans [total-endIdx, total-startIdx)
  int fromOld = total - endIdx;
  int toOld = total - startIdx; // exclusive
  if (fromOld < 0) fromOld = 0;

  String chunk[HIST_PAGE_SIZE];
  int chunkN = 0;

  while (f.available()) {
    String line = f.readStringUntil('\n');
    line.trim();
    if (line.length() == 0) continue;
    if (!lineWithinMonth(line, cutoff)) continue;
    if (validIdx >= fromOld && validIdx < toOld && chunkN < HIST_PAGE_SIZE) {
      chunk[chunkN++] = line;
    }
    validIdx++;
  }
  f.close();

  // chunk est oldest→newest dans la page; afficher newest first
  int shown = 0;
  for (int i = chunkN - 1; i >= 0; i--) {
    const String& line = chunk[i];
    String date = fieldAt(line, 1);
    String heure = fieldAt(line, 2);
    String ev = fieldAt(line, 3);
    String niveau = fieldAt(line, 4);
    String seuil = fieldAt(line, 5);
    String urg = fieldAt(line, 6);
    String mot = fieldAt(line, 7);
    String rms = fieldAt(line, 8);
    String rpm = fieldAt(line, 9);

    s += '\n';
    s += tableRow("Date", date);
    s += tableRow("Heure", heure);
    s += tableRow("Action", htmlEscape(ev));
    s += tableRow("Niveau", niveau + "/" + seuil);
    s += tableRow("Urgence", urgLabel(urg.toInt()));
    s += tableRow("Moteur", mot);
    s += tableRow("RMS", rms + " g");
    s += tableRow("RPM", rpm);
    s += tableSep();
    shown++;
  }

  if (shown == 0) s += "(vide)\n";
  if (page < pages) {
    s += "\nSuite: /historique ";
    s += String(page + 1);
    s += "\n";
  }
  s += F("</pre>");
  return s;
}

String historyKeyboardJson(int page, int pages) {
  String s = "[[";
  if (page > 1) {
    s += "{\"text\":\"<< Precedent\",\"callback_data\":\"history_";
    s += String(page - 1);
    s += "\"},";
  }
  s += "{\"text\":\"Page ";
  s += String(page);
  s += "/";
  s += String(pages);
  s += "\",\"callback_data\":\"history_";
  s += String(page);
  s += "\"}";
  if (page < pages) {
    s += ",{\"text\":\"Suivant >>\",\"callback_data\":\"history_";
    s += String(page + 1);
    s += "\"}";
  }
  s += "],[{\"text\":\"Actualiser\",\"callback_data\":\"refresh\"},";
  s += "{\"text\":\"Tableau\",\"callback_data\":\"refresh\"}]]";
  return s;
}

void sendHistoryPage(const String& chat, int page) {
  time_t cutoff = monthCutoff();
  int total = countHistoryLines(cutoff);
  int pages = (total + HIST_PAGE_SIZE - 1) / HIST_PAGE_SIZE;
  if (pages < 1) pages = 1;
  if (page < 1) page = 1;
  if (page > pages) page = pages;

  String body = "<b>HISTORIQUE (1 mois)</b>\n" + formatHistoryPage(page);
  bot.sendMessageWithInlineKeyboard(chat, body, "HTML", historyKeyboardJson(page, pages));
}

String urgLabel(int u) {
  if (u >= 2) return "URGENCE";
  if (u == 1) return "ALERTE";
  return "OK";
}

String normalizeId(String id) {
  id.trim();
  return id;
}

bool isAdmin(const String& chatId) {
  return normalizeId(chatId) == normalizeId(String(TELEGRAM_ADMIN_CHAT_ID));
}

bool isViewer(const String& chatId) {
  if (isAdmin(chatId)) return true;
  if (TELEGRAM_VIEWER_CHAT_ID[0] != '\0' &&
      normalizeId(chatId) == normalizeId(String(TELEGRAM_VIEWER_CHAT_ID))) return true;
  return false;
}

String roleLabel(const String& chatId) {
  if (isAdmin(chatId)) return "ADMIN";
  if (isViewer(chatId)) return "OBSERVATEUR";
  return "INCONNU";
}

String accessDeniedMsg(const String& chatId) {
  String id = normalizeId(chatId);
  String s = "Acces refuse.\n\n";
  s += "Votre Chat ID Telegram :\n";
  s += "<code>";
  s += id;
  s += "</code>\n\n";
  s += "Copiez cet ID dans le code ESP32 :\n";
  s += "TELEGRAM_ADMIN_CHAT_ID  (commandes)\n";
  s += "ou TELEGRAM_VIEWER_CHAT_ID (lecture)";
  return s;
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

/** Tableau de bord — date/heure courante + derniere action + donnees */
String formatDashboardCore() {
  String s;
  s.reserve(900);
  s += F("<pre>");
  s += F("TABLEAU DE BORD\n");
  s += tableSep();
  s += tableRow("Champ", "Valeur");
  s += tableSep();
  s += tableRow("Date maintenant", nowDateStr());
  s += tableRow("Heure maintenant", nowTimeStr());
  s += tableSep();
  s += tableRow("Dern. action", lastAction.ok ? String(lastAction.event) : String("(aucune)"));
  s += tableRow("Date action", lastAction.ok ? String(lastAction.dateStr) : String("--/--/----"));
  s += tableRow("Heure action", lastAction.ok ? String(lastAction.timeStr) : String("--:--:--"));
  s += tableSep();
  s += tableRow("Maj donnees", lastDataStamp.ok
      ? (String(lastDataStamp.dateStr) + " " + String(lastDataStamp.timeStr))
      : String("(aucune)"));

  if (!tel.valid) {
    s += tableSep();
    s += "Aucune donnee Uno.\nVerifiez UART / capteurs.\n";
    s += F("</pre>");
    return s;
  }

  s += tableSep();
  s += tableRow("ax", String(tel.ax, 3) + " g");
  s += tableRow("ay", String(tel.ay, 3) + " g");
  s += tableRow("az", String(tel.az, 3) + " g");
  s += tableRow("RMS", String(tel.rms, 3) + " g");
  s += tableRow("vRMS", String(tel.vrms, 2) + " mm/s");
  s += tableRow("RPM", String(tel.rpm, 0) + " tr/min");
  s += tableRow("Impulsions", String(tel.imp));
  s += tableRow("Frequence", String(tel.freq, 2) + " Hz");
  s += tableRow("Niveau", String(tel.niveau, 1));
  s += tableRow("Seuil", String(tel.seuil, 0));
  s += tableRow("Urgence", urgLabel(tel.urg));
  s += tableRow("Alerte", tel.alerte ? "OUI" : "NON");
  s += tableRow("Moteur", tel.motorOn ? "ON" : "OFF");
  s += tableSep();
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
    "{\"text\":\"Alertes\",\"callback_data\":\"alerts\"}],"
    "[{\"text\":\"Seuil\",\"callback_data\":\"seuil_info\"},"
    "{\"text\":\"Mon ID\",\"callback_data\":\"my_id\"}]]"
  );
}

String viewerKeyboardJson() {
  return String(
    "[[{\"text\":\"Actualiser\",\"callback_data\":\"refresh\"},"
    "{\"text\":\"Alertes\",\"callback_data\":\"alerts\"}],"
    "[{\"text\":\"Tableau\",\"callback_data\":\"refresh\"},"
    "{\"text\":\"Mon ID\",\"callback_data\":\"my_id\"}]]"
  );
}

String formatSeuilHelp() {
  String s = "<pre>";
  s += tableSep();
  s += tableRow("Champ", "Valeur");
  s += tableSep();
  s += tableRow("Niveau", String(tel.niveau, 1));
  s += tableRow("Seuil alerte", String(tel.seuil, 0));
  s += tableRow("Seuil urg.", String(tel.seuil * 2.0f, 0));
  s += tableRow("Urgence", urgLabel(tel.urg));
  s += tableSep();
  s += "\nReglage admin:\n";
  s += "/seuil 10\n";
  s += "/seuil 15\n";
  s += "/seuil 25\n";
  s += "\nNiveau = RMS(g) x 100\n";
  s += "Alerte si Niveau >= Seuil\n";
  s += "STOP si Niveau >= 2x Seuil\n";
  s += "</pre>";
  return s;
}

String formatMyId(const String& chat) {
  String s = "<b>IDENTITE TELEGRAM</b>\n<pre>";
  s += tableSep();
  s += tableRow("Chat ID", normalizeId(chat));
  s += tableRow("Role", roleLabel(chat));
  s += tableSep();
  s += "\nAdmin config: ";
  s += normalizeId(String(TELEGRAM_ADMIN_CHAT_ID));
  s += "\nViewer config: ";
  if (TELEGRAM_VIEWER_CHAT_ID[0] != '\0')
    s += normalizeId(String(TELEGRAM_VIEWER_CHAT_ID));
  else
    s += "(vide)";
  s += "</pre>";
  return s;
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

  // Mon ID accessible même sans autorisation (pour configurer le code)
  if (data == "my_id") {
    bot.sendMessage(chat, formatMyId(chat), "HTML");
    if (isViewer(chat)) replyWithButtons(chat, "Role: " + roleLabel(chat));
    return;
  }

  if (!isViewer(chat)) {
    bot.sendMessage(chat, accessDeniedMsg(chat), "HTML");
    return;
  }

  if (data == "refresh") {
    if (isAdmin(chat)) sendAdminDashboard(chat);
    else sendViewerDashboard(chat);
    return;
  }

  if (data == "alerts" || data == "seuil_info") {
    replyWithButtons(chat, formatSeuilHelp(), "HTML");
    return;
  }

  if (data == "history" || data.startsWith("history_")) {
    if (!isAdmin(chat)) {
      replyWithButtons(chat, "Historique reserve a l'administrateur.");
      return;
    }
    int page = 1;
    if (data.startsWith("history_")) {
      page = data.substring(8).toInt();
      if (page < 1) page = 1;
    }
    sendHistoryPage(chat, page);
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

  String text = msg.text;
  text.trim();

  // /id toujours autorise — pour recuperer son Chat ID
  if (text == "/id" || text == "/whoami" || text.startsWith("/id@")) {
    bot.sendMessage(chat, formatMyId(chat), "HTML");
    return;
  }

  if (!isViewer(chat)) {
    bot.sendMessage(chat, accessDeniedMsg(chat), "HTML");
    return;
  }

  if (text == "/start" || text == "/help" || text == "/dashboard" ||
      text.startsWith("/start@") || text.startsWith("/help@") || text.startsWith("/dashboard@")) {
    if (isAdmin(chat)) {
      replyWithButtons(chat,
        "Tableau de bord <b>ADMIN</b>\n"
        "Role: ADMIN | Chat ID: <code>" + normalizeId(chat) + "</code>\n"
        "Utilisez les boutons ou /seuil 15", "HTML");
      sendAdminDashboard(chat);
    } else {
      replyWithButtons(chat,
        "Tableau de bord <b>OBSERVATEUR</b>\n"
        "Role: VIEWER | Chat ID: <code>" + normalizeId(chat) + "</code>\n"
        "Utilisez les boutons ci-dessous.", "HTML");
      sendViewerDashboard(chat);
    }
  } else if (text == "/status" || text.startsWith("/status@")) {
    sendToUno("STATUS");
    delay(350);
    replyWithButtons(chat, formatDashboardCore(), "HTML");
  } else if (text == "/seuil" || text.startsWith("/seuil ")) {
    if (text == "/seuil" || text == "/seuil ") {
      replyWithButtons(chat, formatSeuilHelp(), "HTML");
      return;
    }
    if (!isAdmin(chat)) {
      replyWithButtons(chat, "Reglage seuil reserve a l'admin.\nUtilisez /seuil pour voir.");
      return;
    }
    float s = text.substring(7).toFloat();
    if (s < 1.0f || s > 200.0f) {
      replyWithButtons(chat, "Valeur invalide. Exemple: <code>/seuil 10</code>", "HTML");
      return;
    }
    String cmd = "SET_SEUIL ";
    cmd += String(s, 0);
    sendToUno(cmd.c_str());
    pushHistory(String("CMD /seuil ") + String(s, 0));
    replyWithButtons(chat,
      "Seuil demande: <b>" + String(s, 0) + "</b>\n"
      "Alerte >= " + String(s, 0) + " | Urgence STOP >= " + String(s * 2.0f, 0), "HTML");
  } else if (text == "/historique" || text == "/history" ||
             text.startsWith("/historique ") || text.startsWith("/history ")) {
    if (!isAdmin(chat)) {
      replyWithButtons(chat, "Historique reserve a l'admin.");
      return;
    }
    int page = 1;
    int sp = text.indexOf(' ');
    if (sp > 0) {
      page = text.substring(sp + 1).toInt();
      if (page < 1) page = 1;
    }
    sendHistoryPage(chat, page);
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
    replyWithButtons(chat,
      "Commandes:\n/dashboard /status /seuil /id\n"
      "Admin: /on /off /urgence /seuil 15\n"
      "/historique  (30 jours, page 1)\n"
      "/historique 2  (page suivante)", "");
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
  reason += " | Niveau=";
  reason += String(tel.niveau, 1);
  reason += "/";
  reason += String(tel.seuil, 0);
  reason += " RMS=";
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
    } else if (evt == "SEUIL_OK") {
      float s = jsonGetFloat(line, "seuil", tel.seuil);
      tel.seuil = s;
      String m = "Seuil applique: " + String(s, 0) + " (urgence " + String(s * 2.0f, 0) + ")";
      pushHistory(m);
      bot.sendMessageWithInlineKeyboard(TELEGRAM_ADMIN_CHAT_ID, m, "", adminKeyboardJson());
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
  if (jsonHasKey(line, "seuil")) tel.seuil = jsonGetFloat(line, "seuil", tel.seuil);
  if (jsonHasKey(line, "niveau")) tel.niveau = jsonGetFloat(line, "niveau", tel.niveau);
  else tel.niveau = tel.rms * 100.0f;
  tel.motorOn = jsonGetLong(line, "m") == 1;
  tel.updatedAt = millis();
  tel.valid = true;
  stampNow(lastDataStamp);

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

  initHistoryFs();

  UnoSerial.begin(9600, SERIAL_8N1, 16, 17);
  connectWifi();
  syncTime();
  pruneHistoryIfNeeded(true);
  loadLastActionFromFs();

  securedClient.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  // securedClient.setInsecure();

  String boot = "Bot pret.\nIP: " + WiFi.localIP().toString();
  boot += "\nDate: " + nowDateStr() + " " + nowTimeStr();
  boot += "\nHistorique: 30 jours (LittleFS)";
  boot += "\n/dashboard  /historique";
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
