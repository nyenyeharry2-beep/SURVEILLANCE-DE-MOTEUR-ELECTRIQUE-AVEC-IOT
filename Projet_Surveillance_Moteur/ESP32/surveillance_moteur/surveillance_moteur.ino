/**
 * =============================================================================
 * SYSTÈME DE MAINTENANCE PRÉDICTIVE IoT — Surveillance moteur électrique
 * =============================================================================
 * Matériel  : ESP32 DevKit V1 (ESP32-WROOM-32)
 * Capteurs  : ADXL345 (vibrations I2C) + capteur Hall (vitesse RPM)
 * Cloud     : Firebase Realtime Database
 *
 * Bibliothèques requises (Arduino IDE → Gestionnaire de bibliothèques) :
 *   - Adafruit ADXL345        (par Adafruit)
 *   - Adafruit Unified Sensor (dépendance Adafruit)
 *   - Firebase ESP Client     (par Mobizt)  — Firebase_ESP_Client
 *
 * IMPORTANT — Vibrations :
 *   L'ADXL345 mesure une ACCÉLÉRATION (g / m/s²), pas une vitesse vibratoire.
 *   A_RMS est la grandeur fiable. Vibration_RMS (mm/s) est une ESTIMATION
 *   par intégration numérique (voir Docs/EQUATIONS.md). Ne pas la traiter
 *   comme une mesure ISO 10816 certifiée.
 * =============================================================================
 */

#include <Arduino.h>
#include <WiFi.h>
#include <Wire.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_ADXL345_U.h>
#include <Firebase_ESP_Client.h>

/* --- Credentials Firebase (générés par l'assistant) --- */
#include "addons/TokenHelper.h"
#include "addons/RTDBHelper.h"

/* =============================================================================
 * CONFIGURATION UTILISATEUR — À MODIFIER
 * =============================================================================
 */
#define WIFI_SSID        "VOTRE_SSID_WIFI"
#define WIFI_PASSWORD    "VOTRE_MOT_DE_PASSE_WIFI"

/* URL RTDB complète (avec https://), ex. :
 * https://mon-projet-default-rtdb.europe-west1.firebasedatabase.app
 */
#define FIREBASE_HOST    "https://VOTRE_PROJET-default-rtdb.REGION.firebasedatabase.app"
#define FIREBASE_AUTH    "VOTRE_CLE_SECRETE_DATABASE"   /* Database secret (legacy) */

/* Chemins RTDB */
#define PATH_LIVE        "/moteur/live"
#define PATH_CONFIG      "/moteur/config"
#define PATH_HISTORIQUE  "/moteur/historique"

/* GPIO */
#define PIN_SDA          21
#define PIN_SCL          22
#define PIN_HALL         18   /* entrée interruption — logique 3,3 V */
#define PIN_LED_STATUS    2

/* Acquisition vibrations */
#define SAMPLE_COUNT     64          /* N échantillons pour le RMS */
#define SAMPLE_DELAY_US  2000        /* ~500 Hz (adapté bande utile ADXL345) */
#define ADXL_RANGE       ADXL345_RANGE_16_G
#define ADXL_DATARATE    ADXL345_DATARATE_800_HZ

/* Vitesse — 1 aimant = 1 impulsion / tour */
#define PULSES_PER_REV   1
#define RPM_WINDOW_MS    1000        /* fenêtre de calcul RPM */
#define HALL_DEBOUNCE_US 3000        /* anti-rebond logiciel */

/* Périodes de boucle */
#define LOOP_SEND_MS     1000        /* envoi Firebase */
#define LOOP_CONFIG_MS   5000        /* lecture seuils */
#define LOOP_HISTO_MS    10000       /* historique (éviter surcharge RTDB) */
#define WIFI_RETRY_MS    10000
#define STALE_ONLINE_MS  5000

/* Gravité pour conversion g → m/s² */
#define G_TO_MS2         9.80665f

/* =============================================================================
 * OBJETS GLOBAUX
 * =============================================================================
 */
Adafruit_ADXL345_Unified accel = Adafruit_ADXL345_Unified(12345);

FirebaseData fbdo;
FirebaseData fbdoConfig;
FirebaseAuth auth;
FirebaseConfig config;

bool firebaseReady = false;
unsigned long lastSendMs   = 0;
unsigned long lastConfigMs = 0;
unsigned long lastHistoMs  = 0;
unsigned long lastWifiTry  = 0;

/* Capteur Hall — volatiles pour ISR */
volatile uint32_t pulseCount = 0;
volatile uint32_t lastPulseUs = 0;

/* Mesures courantes */
float ax_g = 0, ay_g = 0, az_g = 0;
float a_rms_ms2 = 0;          /* accélération RMS (m/s²) — fiable */
float vibration_rms_mms = 0;  /* estimation vitesse vibratoire (mm/s) */
float rpm = 0;

String motorStatus   = "INCONNU";
String alertLevel    = "INCONNU";
String diagnosticMsg = "Initialisation...";
String anomalyHint   = "";

/* Seuils configurables (surchargés depuis Firebase) */
float cfg_rpm_nominal   = 1500.0f;
float cfg_rpm_min       = 1200.0f;
float cfg_rpm_max       = 1800.0f;
float cfg_vib_normal    = 2.8f;   /* mm/s estimés — À CALIBRER */
float cfg_vib_alerte    = 4.5f;
float cfg_vib_critique  = 7.1f;
float cfg_a_rms_normal  = 1.5f;   /* m/s² — seuil secondaire fiable */
float cfg_a_rms_alerte  = 3.0f;
float cfg_a_rms_critique= 5.0f;

/* =============================================================================
 * PROTOTYPES
 * =============================================================================
 */
void IRAM_ATTR hallISR();
void initPins();
bool initSensor();
void initSpeedSensor();
bool connectWiFi();
bool connectFirebase();
void handleConnection();
void readVibration();
float calculateRMS(const float *samples, int n);
float estimateVelocityRmsMmS(const float *ax, const float *ay, const float *az, int n, float dt_s);
void calculateRPM();
void diagnoseMotor();
void loadConfigFromFirebase();
void sendDataFirebase();
void pushHistorique();
void printSerialStatus();

/* =============================================================================
 * SETUP
 * =============================================================================
 */
void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println();
  Serial.println(F("=== Surveillance moteur IoT — ESP32 ==="));

  initPins();
  initSpeedSensor();

  if (!initSensor()) {
    Serial.println(F("[ERREUR] ADXL345 introuvable. Vérifiez le câblage I2C."));
  }

  connectWiFi();
  connectFirebase();

  lastSendMs   = millis();
  lastConfigMs = millis();
  lastHistoMs  = millis();
}

/* =============================================================================
 * LOOP
 * =============================================================================
 */
void loop() {
  handleConnection();

  readVibration();
  calculateRPM();
  diagnoseMotor();

  unsigned long now = millis();

  if (firebaseReady && (now - lastConfigMs >= LOOP_CONFIG_MS)) {
    lastConfigMs = now;
    loadConfigFromFirebase();
  }

  if (now - lastSendMs >= LOOP_SEND_MS) {
    lastSendMs = now;
    sendDataFirebase();
    printSerialStatus();
  }

  if (firebaseReady && (now - lastHistoMs >= LOOP_HISTO_MS)) {
    lastHistoMs = now;
    pushHistorique();
  }

  delay(10);
}

/* =============================================================================
 * INITIALISATION MATÉRIELLE
 * =============================================================================
 */
void initPins() {
  pinMode(PIN_LED_STATUS, OUTPUT);
  digitalWrite(PIN_LED_STATUS, LOW);
  pinMode(PIN_HALL, INPUT_PULLUP);
}

bool initSensor() {
  Wire.begin(PIN_SDA, PIN_SCL);

  if (!accel.begin()) {
    return false;
  }

  accel.setRange(ADXL_RANGE);
  accel.setDataRate(ADXL_DATARATE);

  Serial.println(F("[OK] ADXL345 initialisé (I2C 0x53)"));
  Serial.print(F("     Plage : ±16 g | DataRate : 800 Hz | SDA=GPIO"));
  Serial.print(PIN_SDA);
  Serial.print(F(" SCL=GPIO"));
  Serial.println(PIN_SCL);
  return true;
}

void initSpeedSensor() {
  pulseCount = 0;
  lastPulseUs = 0;
  attachInterrupt(digitalPinToInterrupt(PIN_HALL), hallISR, FALLING);
  Serial.print(F("[OK] Capteur Hall sur GPIO "));
  Serial.print(PIN_HALL);
  Serial.println(F(" (1 impulsion = 1 tour)"));
}

void IRAM_ATTR hallISR() {
  uint32_t now = micros();
  if ((now - lastPulseUs) > HALL_DEBOUNCE_US) {
    pulseCount++;
    lastPulseUs = now;
  }
}

/* =============================================================================
 * WI-FI & FIREBASE
 * =============================================================================
 */
bool connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }

  Serial.print(F("[WiFi] Connexion à "));
  Serial.println(WIFI_SSID);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - start) < 20000) {
    digitalWrite(PIN_LED_STATUS, !digitalRead(PIN_LED_STATUS));
    delay(400);
    Serial.print('.');
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print(F("[OK] WiFi IP = "));
    Serial.println(WiFi.localIP());
    digitalWrite(PIN_LED_STATUS, HIGH);
    return true;
  }

  Serial.println(F("[ERREUR] WiFi non connecté"));
  digitalWrite(PIN_LED_STATUS, LOW);
  return false;
}

bool connectFirebase() {
  if (WiFi.status() != WL_CONNECTED) {
    firebaseReady = false;
    return false;
  }

  config.database_url = FIREBASE_HOST;
  config.signer.tokens.legacy_token = FIREBASE_AUTH;

  Firebase.reconnectNetwork(true);
  fbdo.setBSSLBufferSize(4096, 1024);
  Firebase.begin(&config, &auth);

  firebaseReady = true;
  Serial.println(F("[OK] Firebase initialisé (Realtime Database)"));

  /* Publier une config par défaut si absente */
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
    json.set("note", "Seuils a calibrer selon moteur et norme applicable");
    Firebase.RTDB.setJSON(&fbdo, PATH_CONFIG, &json);
    Serial.println(F("[OK] Config par défaut écrite dans Firebase"));
  }

  return true;
}

void handleConnection() {
  if (WiFi.status() != WL_CONNECTED) {
    firebaseReady = false;
    digitalWrite(PIN_LED_STATUS, LOW);
    unsigned long now = millis();
    if (now - lastWifiTry >= WIFI_RETRY_MS) {
      lastWifiTry = now;
      Serial.println(F("[WiFi] Reconnexion..."));
      connectWiFi();
      if (WiFi.status() == WL_CONNECTED) {
        connectFirebase();
      }
    }
  } else if (!firebaseReady) {
    connectFirebase();
  }
}

/* =============================================================================
 * VIBRATIONS — ADXL345
 * =============================================================================
 *
 * Étapes :
 *  1. Acquérir N échantillons (ax, ay, az) en g
 *  2. Retirer la composante continue (gravité / biais) sur chaque axe
 *  3. Calculer A_RMS en m/s² sur le vecteur accélération dynamique
 *  4. Estimer Vibration_RMS (mm/s) par intégration trapézoïdale
 *
 * A_RMS (fiable) :
 *   A_RMS = sqrt( (1/N) * Σ (ax_i² + ay_i² + az_i²) )  avec ax en m/s² AC
 *
 * Vibration_RMS (estimation) :
 *   v = ∫ a_ac dt  puis RMS de |v| — sensible au bruit basse fréquence.
 */
void readVibration() {
  float bufX[SAMPLE_COUNT];
  float bufY[SAMPLE_COUNT];
  float bufZ[SAMPLE_COUNT];

  float sumX = 0, sumY = 0, sumZ = 0;

  for (int i = 0; i < SAMPLE_COUNT; i++) {
    sensors_event_t event;
    accel.getEvent(&event);

    /* Adafruit renvoie déjà m/s² ; on travaille en m/s² */
    bufX[i] = event.acceleration.x;
    bufY[i] = event.acceleration.y;
    bufZ[i] = event.acceleration.z;

    sumX += bufX[i];
    sumY += bufY[i];
    sumZ += bufZ[i];

    delayMicroseconds(SAMPLE_DELAY_US);
  }

  /* Moyennes = gravité + biais (composante continue) */
  float meanX = sumX / SAMPLE_COUNT;
  float meanY = sumY / SAMPLE_COUNT;
  float meanZ = sumZ / SAMPLE_COUNT;

  /* Dernière mesure « instantanée » en g (pour affichage axes) */
  ax_g = meanX / G_TO_MS2;
  ay_g = meanY / G_TO_MS2;
  az_g = meanZ / G_TO_MS2;

  /* Accélération dynamique (AC) */
  float acX[SAMPLE_COUNT];
  float acY[SAMPLE_COUNT];
  float acZ[SAMPLE_COUNT];
  float mag2[SAMPLE_COUNT];

  for (int i = 0; i < SAMPLE_COUNT; i++) {
    acX[i] = bufX[i] - meanX;
    acY[i] = bufY[i] - meanY;
    acZ[i] = bufZ[i] - meanZ;
    mag2[i] = acX[i] * acX[i] + acY[i] * acY[i] + acZ[i] * acZ[i];
  }

  a_rms_ms2 = calculateRMS(mag2, SAMPLE_COUNT);
  /* calculateRMS sur mag² donne sqrt(mean(mag²)) = RMS vectoriel */

  float dt = SAMPLE_DELAY_US * 1e-6f;
  vibration_rms_mms = estimateVelocityRmsMmS(acX, acY, acZ, SAMPLE_COUNT, dt);
}

float calculateRMS(const float *samples, int n) {
  if (n <= 0) return 0.0f;
  double acc = 0.0;
  for (int i = 0; i < n; i++) {
    acc += (double)samples[i];
  }
  return (float)sqrt(acc / (double)n);
}

/**
 * Estimation vitesse vibratoire RMS (mm/s) par intégration trapézoïdale
 * de l'accélération AC sur chaque axe, puis RMS de la norme.
 *
 * Hypothèses / limites :
 *  - bande utile limitée ; dérive si le retrait de moyenne est imparfait ;
 *  - ADXL345 non calibré comme vibromètre ISO ;
 *  - résultat indicatif pour mémoire / démonstration, pas certification.
 */
float estimateVelocityRmsMmS(const float *ax, const float *ay, const float *az,
                             int n, float dt_s) {
  if (n < 2 || dt_s <= 0.0f) return 0.0f;

  double vx = 0.0, vy = 0.0, vz = 0.0;
  double sumSq = 0.0;

  for (int i = 1; i < n; i++) {
    vx += 0.5 * ((double)ax[i - 1] + (double)ax[i]) * (double)dt_s;
    vy += 0.5 * ((double)ay[i - 1] + (double)ay[i]) * (double)dt_s;
    vz += 0.5 * ((double)az[i - 1] + (double)az[i]) * (double)dt_s;

    double vmag = sqrt(vx * vx + vy * vy + vz * vz); /* m/s */
    sumSq += vmag * vmag;
  }

  double v_rms_ms = sqrt(sumSq / (double)(n - 1));
  return (float)(v_rms_ms * 1000.0); /* mm/s */
}

/* =============================================================================
 * VITESSE — CAPTEUR HALL
 * =============================================================================
 * RPM = (impulsions / PULSES_PER_REV) * (60000 / fenêtre_ms)
 * Avec 1 aimant : PULSES_PER_REV = 1
 */
void calculateRPM() {
  static uint32_t lastWindowMs = 0;
  static uint32_t lastCount = 0;

  unsigned long now = millis();
  if (lastWindowMs == 0) {
    lastWindowMs = now;
    noInterrupts();
    lastCount = pulseCount;
    interrupts();
    return;
  }

  unsigned long elapsed = now - lastWindowMs;
  if (elapsed < RPM_WINDOW_MS) {
    return;
  }

  uint32_t countNow;
  noInterrupts();
  countNow = pulseCount;
  interrupts();

  uint32_t delta = countNow - lastCount;
  float revs = (float)delta / (float)PULSES_PER_REV;
  rpm = revs * (60000.0f / (float)elapsed);

  lastCount = countNow;
  lastWindowMs = now;
}

/* =============================================================================
 * DIAGNOSTIC
 * =============================================================================
 * Les seuils de vibration dépendent du moteur, du montage et des normes
 * (ex. ISO 10816). Ils sont CONFIGURABLES via Firebase — jamais « universels ».
 */
void diagnoseMotor() {
  anomalyHint = "";

  bool rpmLow  = (rpm < cfg_rpm_min);
  bool rpmHigh = (rpm > cfg_rpm_max);
  bool rpmOk   = !rpmLow && !rpmHigh;

  /* Moteur arrêté / très lent : pas d'alarme vibration abusive */
  bool motorRunning = (rpm > 50.0f);

  /* Niveau vibration : on croise estimation mm/s ET A_RMS (plus fiable) */
  int vibLevel = 0; /* 0 normal, 1 surveillance, 2 avertissement, 3 alarme */
  if (motorRunning) {
    if (vibration_rms_mms >= cfg_vib_critique || a_rms_ms2 >= cfg_a_rms_critique) {
      vibLevel = 3;
    } else if (vibration_rms_mms >= cfg_vib_alerte || a_rms_ms2 >= cfg_a_rms_alerte) {
      vibLevel = 2;
    } else if (vibration_rms_mms >= cfg_vib_normal || a_rms_ms2 >= cfg_a_rms_normal) {
      vibLevel = 1;
    }
  }

  int rpmLevel = 0;
  if (motorRunning) {
    if (rpmLow || rpmHigh) rpmLevel = 2;
  } else {
    /* Arrêt : état particulier */
    motorStatus = "ARRET";
    alertLevel  = "INFO";
    diagnosticMsg = "Moteur a l'arret ou vitesse < 50 tr/min. Pas de diagnostic vibratoire actif.";
    anomalyHint = "Verifier si l'arret est volontaire.";
    return;
  }

  /* Combinaison */
  int severity = vibLevel;
  if (rpmLevel > severity) severity = rpmLevel;

  switch (severity) {
    case 0:
      motorStatus = "NORMAL";
      alertLevel  = "NORMAL";
      diagnosticMsg = "Fonctionnement dans les plages configurees.";
      break;
    case 1:
      motorStatus = "SURVEILLANCE";
      alertLevel  = "SURVEILLANCE";
      diagnosticMsg = "Vibrations legerement elevees. Surveiller l'evolution.";
      break;
    case 2:
      motorStatus = "AVERTISSEMENT";
      alertLevel  = "AVERTISSEMENT";
      diagnosticMsg = "Anomalie probable. Intervention recommandee.";
      break;
    default:
      motorStatus = "ALARME";
      alertLevel  = "ALARME";
      diagnosticMsg = "Seuil critique atteint. Anomalie probable — controler le moteur.";
      break;
  }

  /* Aide au diagnostic (probabiliste — pas un diagnostic certifie) */
  if (vibLevel >= 2 && rpmOk) {
    anomalyHint = "Anomalie probable : desequilibre, defaut de roulement, jeu mecanique ou mauvaise fixation. La vitesse est nominale.";
  } else if (vibLevel >= 2 && !rpmOk) {
    anomalyHint = "Anomalie probable : probleme mecanique et/ou d'entrainement, surcharge, glissement ou defaut d'alimentation associe.";
  } else if (vibLevel == 0 && rpmLow) {
    anomalyHint = "Anomalie probable : sous-vitesse (charge excessive, glissement, tension insuffisante, probleme d'entrainement).";
  } else if (vibLevel == 0 && rpmHigh) {
    anomalyHint = "Anomalie probable : survitesse (perte de charge, consigne incorrecte, derive regulation).";
  } else if (vibLevel == 1) {
    anomalyHint = "Tendance vibratoire a surveiller. Comparer a l'historique et recalibrer les seuils si besoin.";
  } else {
    anomalyHint = "Aucun signe d'anomalie selon les seuils actuels.";
  }
}

/* =============================================================================
 * FIREBASE — CONFIG / LIVE / HISTORIQUE
 * =============================================================================
 */
void loadConfigFromFirebase() {
  if (!firebaseReady) return;

  if (Firebase.RTDB.getJSON(&fbdoConfig, PATH_CONFIG)) {
    FirebaseJson &json = fbdoConfig.jsonObject();
    FirebaseJsonData data;

    if (json.get(data, "rpm_nominal") && data.success) cfg_rpm_nominal = data.to<float>();
    if (json.get(data, "rpm_min") && data.success) cfg_rpm_min = data.to<float>();
    if (json.get(data, "rpm_max") && data.success) cfg_rpm_max = data.to<float>();
    if (json.get(data, "vib_normal_mms") && data.success) cfg_vib_normal = data.to<float>();
    if (json.get(data, "vib_alerte_mms") && data.success) cfg_vib_alerte = data.to<float>();
    if (json.get(data, "vib_critique_mms") && data.success) cfg_vib_critique = data.to<float>();
    if (json.get(data, "a_rms_normal_ms2") && data.success) cfg_a_rms_normal = data.to<float>();
    if (json.get(data, "a_rms_alerte_ms2") && data.success) cfg_a_rms_alerte = data.to<float>();
    if (json.get(data, "a_rms_critique_ms2") && data.success) cfg_a_rms_critique = data.to<float>();

    /* Garde-fous anti mauvaise configuration */
    if (cfg_rpm_min >= cfg_rpm_max) {
      cfg_rpm_min = cfg_rpm_nominal * 0.8f;
      cfg_rpm_max = cfg_rpm_nominal * 1.2f;
      Serial.println(F("[WARN] Seuils RPM incoherents — valeurs de secours appliquees"));
    }
    if (!(cfg_vib_normal < cfg_vib_alerte && cfg_vib_alerte < cfg_vib_critique)) {
      Serial.println(F("[WARN] Seuils vibration incoherents — conservation des valeurs precedentes si possible"));
    }
  }
}

void sendDataFirebase() {
  if (!firebaseReady || WiFi.status() != WL_CONNECTED) {
    Serial.println(F("[Firebase] Envoi ignore (hors ligne)"));
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
  json.set("timestamp", (int)(millis() / 1000));
  json.set("online", true);
  json.set("unit_a_rms", "m/s2");
  json.set("unit_vibration_rms", "mm/s (estime)");
  json.set("note_vibration", "vibration_rms = estimation par integration; a_rms est la grandeur fiable");

  if (Firebase.RTDB.setJSON(&fbdo, PATH_LIVE, &json)) {
    Serial.println(F("[OK] Donnees live envoyees"));
  } else {
    Serial.print(F("[ERREUR] Firebase setJSON: "));
    Serial.println(fbdo.errorReason());
    firebaseReady = false;
  }
}

void pushHistorique() {
  if (!firebaseReady) return;

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

  String path = String(PATH_HISTORIQUE) + "/" + String(millis());
  if (!Firebase.RTDB.setJSON(&fbdo, path.c_str(), &json)) {
    Serial.print(F("[WARN] Historique: "));
    Serial.println(fbdo.errorReason());
  }
}

void printSerialStatus() {
  Serial.println(F("---------- MESURE ----------"));
  Serial.printf("Ax=%.3f g  Ay=%.3f g  Az=%.3f g\n", ax_g, ay_g, az_g);
  Serial.printf("A_RMS=%.3f m/s2 | Vib_RMS(est)=%.3f mm/s | RPM=%.1f\n",
                a_rms_ms2, vibration_rms_mms, rpm);
  Serial.printf("Etat=%s | Alerte=%s\n", motorStatus.c_str(), alertLevel.c_str());
  Serial.printf("Diagnostic: %s\n", diagnosticMsg.c_str());
  Serial.printf("Hypothese: %s\n", anomalyHint.c_str());
  Serial.println(F("----------------------------"));
}
