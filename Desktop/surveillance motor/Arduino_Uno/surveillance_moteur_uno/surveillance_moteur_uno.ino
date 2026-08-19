/**
 * =============================================================================
 * Arduino Uno — Acquisition + actionneurs (maintenance prédictive moteur)
 * =============================================================================
 * Rôle :
 *   - ADXL345 (vibrations I2C) sur SDA=A4 / SCL=A5
 *   - Capteur IR (vitesse RPM) sur D2 (interruption)
 *   - Relais 5 V sur D8
 *   - Buzzer sur D9
 *   - Envoi des mesures vers l'ESP32 (passerelle Wi-Fi / Firebase) via UART
 *
 * Liaison série vers ESP32 :
 *   SoftwareSerial : D4 (TX Uno → RX ESP32) , D3 (RX Uno ← TX ESP32)
 *   USB Serial (115200) : moniteur de debug
 *
 * IMPORTANT niveaux logiques :
 *   TX Uno D4 (5 V) → diviseur résistif → RX ESP32 (3,3 V max)
 *   TX ESP32 (3,3 V) → RX Uno D3 (accepté en pratique sur la plupart des UNO)
 *
 * Bibliothèques :
 *   - Adafruit ADXL345
 *   - Adafruit Unified Sensor
 * =============================================================================
 */

#include <Wire.h>
#include <SoftwareSerial.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_ADXL345_U.h>

/* ---- Broches Arduino Uno ---- */
#define PIN_IR           2    /* interruption INT0 — capteur IR vitesse */
#define PIN_RELAY        8    /* module relais 5 V */
#define PIN_BUZZER       9
#define PIN_ESP_RX       3    /* Uno RX  ← ESP32 TX */
#define PIN_ESP_TX       4    /* Uno TX  → ESP32 RX (via diviseur) */

#define RELAY_ACTIVE_LOW 1

#define SAMPLE_COUNT     32   /* limite SRAM Uno (~2 Ko) */
#define SAMPLE_DELAY_US  2000
#define ADXL_RANGE       ADXL345_RANGE_16_G
#define ADXL_DATARATE    ADXL345_DATARATE_800_HZ

/* 1 marque réfléchissante / bande noire = 1 impulsion par tour */
#define PULSES_PER_REV   1
#define RPM_WINDOW_MS    1000
#define IR_DEBOUNCE_US   3000

#define LOOP_SEND_MS     1000
#define G_TO_MS2         9.80665f

#define BUZZ_WARN_ON_MS   200
#define BUZZ_WARN_OFF_MS  800
#define BUZZ_ALARM_ON_MS  150
#define BUZZ_ALARM_OFF_MS 150

SoftwareSerial espSerial(PIN_ESP_RX, PIN_ESP_TX); /* RX, TX */
Adafruit_ADXL345_Unified accel = Adafruit_ADXL345_Unified(12345);

volatile uint32_t pulseCount = 0;
volatile uint32_t lastPulseUs = 0;

float ax_g = 0, ay_g = 0, az_g = 0;
float a_rms_ms2 = 0;
float vibration_rms_mms = 0;
float rpm = 0;

String motorStatus   = "INCONNU";
String alertLevel    = "INCONNU";
String diagnosticMsg = "Initialisation";
String anomalyHint   = "";

bool relayDesired      = false;
bool relayState        = false;
bool buzzerState       = false;
bool buzzerMute        = false;
bool cfg_auto_stop     = true;
bool cfg_buzzer_enable = true;
unsigned long buzzerPhaseMs = 0;
bool buzzerPhaseOn = false;

float cfg_rpm_nominal    = 1500.0f;
float cfg_rpm_min        = 1200.0f;
float cfg_rpm_max        = 1800.0f;
float cfg_vib_normal     = 2.8f;
float cfg_vib_alerte     = 4.5f;
float cfg_vib_critique   = 7.1f;
float cfg_a_rms_normal   = 1.5f;
float cfg_a_rms_alerte   = 3.0f;
float cfg_a_rms_critique = 5.0f;

unsigned long lastSendMs = 0;

void irISR();
void initPins();
bool initSensor();
void initSpeedSensor();
void readVibration();
float calculateRMS(const float *samples, int n);
float estimateVelocityRmsMmS(const float *ax, const float *ay, const float *az, int n, float dt_s);
void calculateRPM();
void diagnoseMotor();
void updateActuators();
void setRelay(bool on);
void setBuzzerRaw(bool on);
void updateBuzzerPattern();
void sendToEsp32();
void pollEsp32Commands();
String sanitizeField(const String &s);

void setup() {
  Serial.begin(115200);
  espSerial.begin(9600);
  delay(400);

  Serial.println(F("=== Arduino Uno — Surveillance moteur ==="));
  Serial.println(F("Capteurs: ADXL345 + IR | Relais D8 | Buzzer D9"));

  initPins();
  initSpeedSensor();
  if (!initSensor()) {
    Serial.println(F("[ERREUR] ADXL345 introuvable (SDA=A4 SCL=A5)."));
  }

  lastSendMs = millis();
  buzzerPhaseMs = millis();
}

void loop() {
  readVibration();
  calculateRPM();
  diagnoseMotor();
  pollEsp32Commands();
  updateActuators();

  unsigned long now = millis();
  if (now - lastSendMs >= LOOP_SEND_MS) {
    lastSendMs = now;
    sendToEsp32();
    Serial.print(F("RPM="));
    Serial.print(rpm, 1);
    Serial.print(F(" A_RMS="));
    Serial.print(a_rms_ms2, 3);
    Serial.print(F(" Vib="));
    Serial.print(vibration_rms_mms, 2);
    Serial.print(F(" Etat="));
    Serial.print(motorStatus);
    Serial.print(F(" Relais="));
    Serial.println(relayState ? F("ON") : F("OFF"));
  }

  delay(5);
}

void initPins() {
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  pinMode(PIN_IR, INPUT_PULLUP);
  setRelay(false);
  setBuzzerRaw(false);
  Serial.println(F("[OK] Relais D8 + buzzer D9 initialises (OFF)"));
}

bool initSensor() {
  Wire.begin(); /* A4/A5 */
  if (!accel.begin()) {
    return false;
  }
  accel.setRange(ADXL_RANGE);
  accel.setDataRate(ADXL_DATARATE);
  Serial.println(F("[OK] ADXL345 I2C 0x53 (SDA=A4 SCL=A5)"));
  return true;
}

void initSpeedSensor() {
  pulseCount = 0;
  lastPulseUs = 0;
  attachInterrupt(digitalPinToInterrupt(PIN_IR), irISR, FALLING);
  Serial.println(F("[OK] Capteur IR sur D2 (1 marque = 1 tour)"));
}

void irISR() {
  uint32_t now = micros();
  if ((now - lastPulseUs) > IR_DEBOUNCE_US) {
    pulseCount++;
    lastPulseUs = now;
  }
}

void readVibration() {
  /* Tampons globaux statiques pour ne pas saturer la pile (SRAM Uno) */
  static float bufX[SAMPLE_COUNT];
  static float bufY[SAMPLE_COUNT];
  static float bufZ[SAMPLE_COUNT];
  static float mag2[SAMPLE_COUNT];

  float sumX = 0, sumY = 0, sumZ = 0;

  for (int i = 0; i < SAMPLE_COUNT; i++) {
    sensors_event_t event;
    accel.getEvent(&event);
    bufX[i] = event.acceleration.x;
    bufY[i] = event.acceleration.y;
    bufZ[i] = event.acceleration.z;
    sumX += bufX[i];
    sumY += bufY[i];
    sumZ += bufZ[i];
    delayMicroseconds(SAMPLE_DELAY_US);
  }

  float meanX = sumX / SAMPLE_COUNT;
  float meanY = sumY / SAMPLE_COUNT;
  float meanZ = sumZ / SAMPLE_COUNT;

  ax_g = meanX / G_TO_MS2;
  ay_g = meanY / G_TO_MS2;
  az_g = meanZ / G_TO_MS2;

  for (int i = 0; i < SAMPLE_COUNT; i++) {
    bufX[i] -= meanX;
    bufY[i] -= meanY;
    bufZ[i] -= meanZ;
    mag2[i] = bufX[i] * bufX[i] + bufY[i] * bufY[i] + bufZ[i] * bufZ[i];
  }

  a_rms_ms2 = calculateRMS(mag2, SAMPLE_COUNT);
  vibration_rms_mms = estimateVelocityRmsMmS(bufX, bufY, bufZ, SAMPLE_COUNT, SAMPLE_DELAY_US * 1e-6f);
}

float calculateRMS(const float *samples, int n) {
  if (n <= 0) return 0.0f;
  double acc = 0.0;
  for (int i = 0; i < n; i++) acc += (double)samples[i];
  return (float)sqrt(acc / (double)n);
}

float estimateVelocityRmsMmS(const float *ax, const float *ay, const float *az,
                             int n, float dt_s) {
  if (n < 2 || dt_s <= 0.0f) return 0.0f;
  double vx = 0.0, vy = 0.0, vz = 0.0;
  double sumSq = 0.0;
  for (int i = 1; i < n; i++) {
    vx += 0.5 * ((double)ax[i - 1] + (double)ax[i]) * (double)dt_s;
    vy += 0.5 * ((double)ay[i - 1] + (double)ay[i]) * (double)dt_s;
    vz += 0.5 * ((double)az[i - 1] + (double)az[i]) * (double)dt_s;
    double vmag = sqrt(vx * vx + vy * vy + vz * vz);
    sumSq += vmag * vmag;
  }
  return (float)(sqrt(sumSq / (double)(n - 1)) * 1000.0);
}

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
  if (elapsed < RPM_WINDOW_MS) return;

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

void diagnoseMotor() {
  anomalyHint = "";
  bool rpmLow  = (rpm < cfg_rpm_min);
  bool rpmHigh = (rpm > cfg_rpm_max);
  bool rpmOk   = !rpmLow && !rpmHigh;
  bool motorRunning = (rpm > 50.0f);

  int vibLevel = 0;
  if (motorRunning) {
    if (vibration_rms_mms >= cfg_vib_critique || a_rms_ms2 >= cfg_a_rms_critique) vibLevel = 3;
    else if (vibration_rms_mms >= cfg_vib_alerte || a_rms_ms2 >= cfg_a_rms_alerte) vibLevel = 2;
    else if (vibration_rms_mms >= cfg_vib_normal || a_rms_ms2 >= cfg_a_rms_normal) vibLevel = 1;
  }

  int rpmLevel = 0;
  if (!motorRunning) {
    motorStatus = "ARRET";
    alertLevel  = "INFO";
    diagnosticMsg = "Moteur a l'arret ou vitesse < 50 tr/min.";
    anomalyHint = "Verifier si l'arret est volontaire.";
    return;
  }
  if (rpmLow || rpmHigh) rpmLevel = 2;

  int severity = vibLevel > rpmLevel ? vibLevel : rpmLevel;
  switch (severity) {
    case 0:
      motorStatus = "NORMAL";
      alertLevel = "NORMAL";
      diagnosticMsg = "Fonctionnement dans les plages configurees.";
      break;
    case 1:
      motorStatus = "SURVEILLANCE";
      alertLevel = "SURVEILLANCE";
      diagnosticMsg = "Vibrations legerement elevees. Surveiller l'evolution.";
      break;
    case 2:
      motorStatus = "AVERTISSEMENT";
      alertLevel = "AVERTISSEMENT";
      diagnosticMsg = "Anomalie probable. Intervention recommandee.";
      break;
    default:
      motorStatus = "ALARME";
      alertLevel = "ALARME";
      diagnosticMsg = "Seuil critique atteint. Controler le moteur.";
      break;
  }

  if (vibLevel >= 2 && rpmOk) {
    anomalyHint = "Anomalie probable : desequilibre, roulement ou fixation.";
  } else if (vibLevel >= 2 && !rpmOk) {
    anomalyHint = "Anomalie probable : mecanique et/ou entrainement / surcharge.";
  } else if (vibLevel == 0 && rpmLow) {
    anomalyHint = "Anomalie probable : sous-vitesse (charge, glissement, tension).";
  } else if (vibLevel == 0 && rpmHigh) {
    anomalyHint = "Anomalie probable : survitesse.";
  } else if (vibLevel == 1) {
    anomalyHint = "Tendance vibratoire a surveiller.";
  } else {
    anomalyHint = "Aucun signe d'anomalie selon les seuils actuels.";
  }
}

void setRelay(bool on) {
  relayState = on;
#if RELAY_ACTIVE_LOW
  digitalWrite(PIN_RELAY, on ? LOW : HIGH);
#else
  digitalWrite(PIN_RELAY, on ? HIGH : LOW);
#endif
}

void setBuzzerRaw(bool on) {
  buzzerState = on;
  digitalWrite(PIN_BUZZER, on ? HIGH : LOW);
}

void updateBuzzerPattern() {
  if (!cfg_buzzer_enable || buzzerMute) {
    setBuzzerRaw(false);
    return;
  }

  unsigned long onMs = 0, offMs = 0;
  if (alertLevel == "ALARME" || motorStatus == "ALARME") {
    onMs = BUZZ_ALARM_ON_MS; offMs = BUZZ_ALARM_OFF_MS;
  } else if (alertLevel == "AVERTISSEMENT" || motorStatus == "AVERTISSEMENT") {
    onMs = BUZZ_WARN_ON_MS; offMs = BUZZ_WARN_OFF_MS;
  } else {
    setBuzzerRaw(false);
    buzzerPhaseOn = false;
    buzzerPhaseMs = millis();
    return;
  }

  unsigned long now = millis();
  unsigned long phaseLen = buzzerPhaseOn ? onMs : offMs;
  if (now - buzzerPhaseMs >= phaseLen) {
    buzzerPhaseOn = !buzzerPhaseOn;
    buzzerPhaseMs = now;
    setBuzzerRaw(buzzerPhaseOn);
  }
}

void updateActuators() {
  if (cfg_auto_stop && (motorStatus == "ALARME" || alertLevel == "ALARME")) {
    relayDesired = false;
    setRelay(false);
  } else {
    setRelay(relayDesired);
  }
  updateBuzzerPattern();
}

String sanitizeField(const String &s) {
  String out = s;
  out.replace(',', '_');
  out.replace('|', '_');
  out.replace('\n', '_');
  out.replace('\r', '_');
  out.replace(' ', '_');
  return out;
}

/**
 * Trame vers ESP32 (9600 bauds) :
 * MEAS,ax,ay,az,a_rms,vib_rms,rpm,status,alert,relay,buzzer,mute,diagnostic,hint
 */
void sendToEsp32() {
  String line = "MEAS,";
  line += String(ax_g, 4); line += ',';
  line += String(ay_g, 4); line += ',';
  line += String(az_g, 4); line += ',';
  line += String(a_rms_ms2, 4); line += ',';
  line += String(vibration_rms_mms, 4); line += ',';
  line += String(rpm, 2); line += ',';
  line += motorStatus; line += ',';
  line += alertLevel; line += ',';
  line += relayState ? '1' : '0'; line += ',';
  line += buzzerState ? '1' : '0'; line += ',';
  line += buzzerMute ? '1' : '0'; line += ',';
  line += sanitizeField(diagnosticMsg); line += ',';
  line += sanitizeField(anomalyHint);

  espSerial.println(line);
}

/**
 * Commandes reçues de l'ESP32 :
 *   CMD,relay,mute
 *   CFG,rpm_nom,rpm_min,rpm_max,vib_n,vib_a,vib_c,a_n,a_a,a_c,auto_stop,buzz_en
 */
void pollEsp32Commands() {
  static String buf;
  while (espSerial.available()) {
    char c = (char)espSerial.read();
    if (c == '\n' || c == '\r') {
      if (buf.length() == 0) continue;
      buf.trim();

      if (buf.startsWith("CMD,")) {
        /* CMD,relay,mute */
        int p1 = buf.indexOf(',');
        int p2 = buf.indexOf(',', p1 + 1);
        if (p1 > 0 && p2 > p1) {
          int r = buf.substring(p1 + 1, p2).toInt();
          int m = buf.substring(p2 + 1).toInt();
          relayDesired = (r != 0);
          buzzerMute = (m != 0);
          Serial.print(F("[CMD] relay="));
          Serial.print(relayDesired);
          Serial.print(F(" mute="));
          Serial.println(buzzerMute);
        }
      } else if (buf.startsWith("CFG,")) {
        /* 12 champs numériques après CFG */
        float vals[12];
        int idx = 0;
        int start = 4; /* après "CFG," */
        while (idx < 12 && start < (int)buf.length()) {
          int comma = buf.indexOf(',', start);
          String part = (comma < 0) ? buf.substring(start) : buf.substring(start, comma);
          vals[idx++] = part.toFloat();
          if (comma < 0) break;
          start = comma + 1;
        }
        if (idx >= 12) {
          cfg_rpm_nominal = vals[0];
          cfg_rpm_min = vals[1];
          cfg_rpm_max = vals[2];
          cfg_vib_normal = vals[3];
          cfg_vib_alerte = vals[4];
          cfg_vib_critique = vals[5];
          cfg_a_rms_normal = vals[6];
          cfg_a_rms_alerte = vals[7];
          cfg_a_rms_critique = vals[8];
          cfg_auto_stop = (vals[9] >= 0.5f);
          cfg_buzzer_enable = (vals[10] >= 0.5f);
          /* vals[11] reserve */
          if (cfg_rpm_min >= cfg_rpm_max) {
            cfg_rpm_min = cfg_rpm_nominal * 0.8f;
            cfg_rpm_max = cfg_rpm_nominal * 1.2f;
          }
          Serial.println(F("[CFG] Seuils mis a jour depuis ESP32/Firebase"));
        }
      }
      buf = "";
    } else {
      if (buf.length() < 220) buf += c;
    }
  }
}
