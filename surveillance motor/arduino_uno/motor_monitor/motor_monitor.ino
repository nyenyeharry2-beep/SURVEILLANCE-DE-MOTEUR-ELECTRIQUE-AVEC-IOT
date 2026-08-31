/*
 * Arduino Uno — mesures RÉELLES (IR + ADXL345)
 *
 * Corrections vs version précédente :
 *  - IR : anti-rebond fort + plafond RPM + ignore si moteur OFF
 *  - ADXL345 : calibration gravité au boot, RMS dynamique réel
 *  - Filtrage EMA pour ax/ay/az affichés
 *
 * Câblage :
 *  IR OUT → D2 | SoftSerial RX=D3 TX=D4 → ESP32
 *  ADXL345 SDA=A4 SCL=A5 VCC=3.3V
 *  Relais D8
 */

#include <Arduino.h>
#include <Wire.h>
#include <SoftwareSerial.h>
#include <math.h>

const int PIN_IR_OUT = 2;
const int PIN_ESP_RX = 3;
const int PIN_ESP_TX = 4;
const int PIN_RELAY  = 8;
const int PIN_LED    = 13;

SoftwareSerial EspSerial(PIN_ESP_RX, PIN_ESP_TX);

// ---- IR / RPM ----
// 1 marque réfléchissante par tour. Augmenter si plusieurs bandes.
const int PULSES_PER_REV = 1;
// Anti-rebond : ignore tout pulse < 8 ms (~7500 RPM max théorique)
const unsigned long IR_DEBOUNCE_US = 8000;
// RPM max crédible pour ton moteur (au-delà = bruit → ignoré)
const float RPM_MAX_PLAUSIBLE = 4000.0f;
// Fenêtre de mesure RPM (ms)
const unsigned long RPM_WINDOW_MS = 1000;

// ---- ADXL345 ----
const uint8_t ADXL345_ADDR = 0x53;
const float ADXL_SCALE = 0.0039f; // g/LSB ±2g full-res
const int ADXL_SAMPLES = 25;
const float EMA_ALPHA = 0.25f;    // lissage ax/ay/az affichés

// Seuils vibration (g dynamique / mm/s)
const float RMS_ALERT_G  = 0.15f;
const float RMS_URGENT_G = 0.40f;
const float VRMS_ALERT   = 2.5f;
const float VRMS_URGENT  = 6.0f;

volatile unsigned long rpmPulses = 0;
volatile unsigned long lastIrUs  = 0;
volatile bool lastIrLevel = HIGH;

unsigned long lastRpmMs = 0;
float lastRpm = 0;
float lastFreqHz = 0;
unsigned long lastWindowImp = 0;

bool adxlOk = false;
float g0x = 0, g0y = 0, g0z = 1.0f; // gravité calibrée au repos
float axG = 0, ayG = 0, azG = 0;     // accélération totale filtrée
float axDyn = 0, ayDyn = 0, azDyn = 0; // sans gravité
float rmsG = 0, vrmsMms = 0;
int urgLevel = 0;
int alerteFlag = 0;

bool motorOn = false;
unsigned long lastSendMs = 0;
const unsigned long SEND_INTERVAL_MS = 2000;

void sendBoth(const String& line) {
  Serial.println(line);
  EspSerial.println(line);
}

// ISR IR : front descendant propre uniquement
void irRpmIsr() {
  int level = digitalRead(PIN_IR_OUT);
  // On compte seulement HIGH→LOW (réflexion typique modules IR)
  if (!(lastIrLevel == HIGH && level == LOW)) {
    lastIrLevel = level;
    return;
  }
  lastIrLevel = level;

  unsigned long now = micros();
  if ((now - lastIrUs) < IR_DEBOUNCE_US) return;
  lastIrUs = now;
  rpmPulses++;
}

bool adxlWrite(uint8_t reg, uint8_t val) {
  Wire.beginTransmission(ADXL345_ADDR);
  Wire.write(reg);
  Wire.write(val);
  return Wire.endTransmission() == 0;
}

bool adxlRead(uint8_t reg, uint8_t* buf, uint8_t len) {
  Wire.beginTransmission(ADXL345_ADDR);
  Wire.write(reg);
  if (Wire.endTransmission(false) != 0) return false;
  if (Wire.requestFrom(ADXL345_ADDR, len) != len) return false;
  for (uint8_t i = 0; i < len; i++) buf[i] = Wire.read();
  return true;
}

bool adxlBegin() {
  uint8_t id = 0;
  if (!adxlRead(0x00, &id, 1) || id != 0xE5) return false;
  adxlWrite(0x31, 0x08); // full-res ±2g
  adxlWrite(0x2C, 0x0A); // 100 Hz
  adxlWrite(0x2D, 0x08); // measure
  delay(50);
  return true;
}

bool adxlReadRaw(float& x, float& y, float& z) {
  uint8_t raw[6];
  if (!adxlRead(0x32, raw, 6)) return false;
  int16_t xi = (int16_t)((raw[1] << 8) | raw[0]);
  int16_t yi = (int16_t)((raw[3] << 8) | raw[2]);
  int16_t zi = (int16_t)((raw[5] << 8) | raw[4]);
  x = xi * ADXL_SCALE;
  y = yi * ADXL_SCALE;
  z = zi * ADXL_SCALE;
  return true;
}

/** Calibre la gravité au repos (moteur OFF, carte immobile ~1 s) */
void calibrateGravity() {
  float sx = 0, sy = 0, sz = 0;
  const int N = 50;
  int ok = 0;
  for (int i = 0; i < N; i++) {
    float x, y, z;
    if (adxlReadRaw(x, y, z)) {
      sx += x; sy += y; sz += z;
      ok++;
    }
    delay(20);
  }
  if (ok > 0) {
    g0x = sx / ok;
    g0y = sy / ok;
    g0z = sz / ok;
  }
  Serial.println(F("Calibration gravite ADXL :"));
  Serial.print(F("  g0=(")); Serial.print(g0x, 3);
  Serial.print(','); Serial.print(g0y, 3);
  Serial.print(','); Serial.print(g0z, 3); Serial.println(')');
}

void updateVibrationRms() {
  if (!adxlOk) {
    axG = ayG = azG = axDyn = ayDyn = azDyn = rmsG = vrmsMms = 0;
    return;
  }

  float sumDyn2 = 0;
  float sx = 0, sy = 0, sz = 0;
  int nOk = 0;

  for (int i = 0; i < ADXL_SAMPLES; i++) {
    float x, y, z;
    if (!adxlReadRaw(x, y, z)) continue;
    // Accélération dynamique = total − gravité calibrée
    float dx = x - g0x;
    float dy = y - g0y;
    float dz = z - g0z;
    sumDyn2 += dx * dx + dy * dy + dz * dz;
    sx += x; sy += y; sz += z;
    nOk++;
    delay(4); // ~250 Hz d'échantillonnage
  }

  if (nOk == 0) return;

  float mx = sx / nOk;
  float my = sy / nOk;
  float mz = sz / nOk;

  // EMA pour affichage stable
  axG = EMA_ALPHA * mx + (1.0f - EMA_ALPHA) * axG;
  ayG = EMA_ALPHA * my + (1.0f - EMA_ALPHA) * ayG;
  azG = EMA_ALPHA * mz + (1.0f - EMA_ALPHA) * azG;

  axDyn = axG - g0x;
  ayDyn = ayG - g0y;
  azDyn = azG - g0z;

  // RMS dynamique réel (g)
  rmsG = sqrt(sumDyn2 / nOk);
  // Petit bruit électronique → forcer à 0
  if (rmsG < 0.02f) rmsG = 0;

  // Fréquence pour vRMS : privilégier freq IR si moteur tourne, sinon 50 Hz
  float f = lastFreqHz;
  if (!motorOn || f < 1.0f) f = 50.0f;
  // a (mm/s²) / (2πf) → mm/s
  vrmsMms = (rmsG * 9806.65f) / (2.0f * 3.1415926f * f);
  if (vrmsMms < 0.05f) vrmsMms = 0;
}

void computeRpmFreq() {
  unsigned long now = millis();
  unsigned long elapsed = now - lastRpmMs;
  if (elapsed < RPM_WINDOW_MS) return;

  noInterrupts();
  unsigned long pulses = rpmPulses;
  rpmPulses = 0;
  interrupts();

  // Moteur OFF → RPM réel = 0 (ignore bruit IR)
  if (!motorOn) {
    lastWindowImp = 0;
    lastRpm = 0;
    lastFreqHz = 0;
    lastRpmMs = now;
    return;
  }

  float rpm = (pulses * 60000.0f) / (float)(elapsed * PULSES_PER_REV);
  float freq = (pulses * 1000.0f) / (float)elapsed;

  // Rejette valeurs absurdes (bruit)
  if (rpm > RPM_MAX_PLAUSIBLE) {
    Serial.print(F("IR bruit ignore: rpm="));
    Serial.println(rpm, 0);
    pulses = 0;
    rpm = 0;
    freq = 0;
  }

  lastWindowImp = pulses;
  lastRpm = rpm;
  lastFreqHz = freq;
  lastRpmMs = now;
}

void evaluateUrgency() {
  urgLevel = 0;
  alerteFlag = 0;
  if (rmsG >= RMS_URGENT_G || vrmsMms >= VRMS_URGENT) {
    urgLevel = 2; alerteFlag = 1;
  } else if (rmsG >= RMS_ALERT_G || vrmsMms >= VRMS_ALERT) {
    urgLevel = 1; alerteFlag = 1;
  }
}

void setMotor(bool on) {
  motorOn = on;
  digitalWrite(PIN_RELAY, on ? HIGH : LOW);
  digitalWrite(PIN_LED, on ? HIGH : LOW);
  // Reset compteurs IR au changement d'état
  noInterrupts();
  rpmPulses = 0;
  interrupts();
  lastRpm = 0;
  lastFreqHz = 0;
  lastWindowImp = 0;
  lastRpmMs = millis();
}

void sendTelemetry() {
  computeRpmFreq();
  updateVibrationRms();
  evaluateUrgency();

  String line = "{";
  line += "\"ax\":"; line += String(axDyn, 3);  // dynamique (sans g)
  line += ",\"ay\":"; line += String(ayDyn, 3);
  line += ",\"az\":"; line += String(azDyn, 3);
  line += ",\"rms\":"; line += String(rmsG, 3);
  line += ",\"vrms\":"; line += String(vrmsMms, 2);
  line += ",\"rpm\":"; line += String(lastRpm, 0);
  line += ",\"imp\":"; line += String(lastWindowImp);
  line += ",\"freq\":"; line += String(lastFreqHz, 2);
  line += ",\"urg\":"; line += String(urgLevel);
  line += ",\"alerte\":"; line += String(alerteFlag);
  line += ",\"m\":"; line += String(motorOn ? 1 : 0);
  line += "}";
  sendBoth(line);

  Serial.println(F("--- UNO MONITOR (REEL) ---"));
  Serial.print(F("a_dyn x=")); Serial.print(axDyn, 3);
  Serial.print(F(" y=")); Serial.print(ayDyn, 3);
  Serial.print(F(" z=")); Serial.println(azDyn, 3);
  Serial.print(F("a_tot x=")); Serial.print(axG, 3);
  Serial.print(F(" y=")); Serial.print(ayG, 3);
  Serial.print(F(" z=")); Serial.print(azG, 3);
  Serial.print(F(" |a|=")); Serial.println(sqrt(axG*axG+ayG*ayG+azG*azG), 3);
  Serial.print(F("RMS=")); Serial.print(rmsG, 3);
  Serial.print(F(" g   vRMS=")); Serial.print(vrmsMms, 2);
  Serial.println(F(" mm/s"));
  Serial.print(F("RPM=")); Serial.print(lastRpm, 0);
  Serial.print(F("  freq=")); Serial.print(lastFreqHz, 2);
  Serial.print(F(" Hz  imp=")); Serial.println(lastWindowImp);
  Serial.print(F("Urgence=")); Serial.print(urgLevel);
  Serial.print(F("  Alerte=")); Serial.print(alerteFlag ? "OUI" : "NON");
  Serial.print(F("  Moteur=")); Serial.println(motorOn ? "ON" : "OFF");
  Serial.println();

  if (urgLevel >= 2 && motorOn) {
    setMotor(false);
    sendBoth(F("{\"evt\":\"SAFE_STOP\",\"urg\":2}"));
    Serial.println(F("!!! SAFE_STOP"));
  }
}

void processCommand(String line) {
  line.trim();
  if (!line.length()) return;
  Serial.print(F("[CMD ESP32] "));
  Serial.println(line);
  if (line == "MOTOR_ON") {
    setMotor(true);
    sendBoth(F("{\"evt\":\"MOTOR_ON\",\"ok\":1}"));
  } else if (line == "MOTOR_OFF") {
    setMotor(false);
    sendBoth(F("{\"evt\":\"MOTOR_OFF\",\"ok\":1}"));
  } else if (line == "STATUS") {
    sendTelemetry();
  } else if (line == "PING") {
    sendBoth(F("{\"evt\":\"PONG\"}"));
  } else if (line == "CALIB") {
    calibrateGravity();
    sendBoth(F("{\"evt\":\"CALIB_OK\"}"));
  }
}

void setup() {
  pinMode(PIN_IR_OUT, INPUT); // modules IR ont souvent pull-up interne
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_LED, OUTPUT);
  setMotor(false);

  lastIrLevel = digitalRead(PIN_IR_OUT);
  attachInterrupt(digitalPinToInterrupt(PIN_IR_OUT), irRpmIsr, CHANGE);

  Wire.begin();
  Serial.begin(115200);
  EspSerial.begin(9600);
  delay(300);

  adxlOk = adxlBegin();
  Serial.println(F("=== UNO MESURES REELLES ==="));
  Serial.print(F("ADXL345: "));
  Serial.println(adxlOk ? "OK" : "ABSENT");

  if (adxlOk) {
    Serial.println(F("Immobile 1s pour calibrer gravite..."));
    calibrateGravity();
    axG = g0x; ayG = g0y; azG = g0z;
  }

  String ready = "{\"evt\":\"UNO_READY\",\"adxl\":";
  ready += adxlOk ? "1" : "0";
  ready += ",\"ir\":1}";
  sendBoth(ready);

  lastRpmMs = millis();
  lastSendMs = millis();
}

void loop() {
  while (EspSerial.available()) {
    processCommand(EspSerial.readStringUntil('\n'));
  }
  unsigned long now = millis();
  if (now - lastSendMs >= SEND_INTERVAL_MS) {
    lastSendMs = now;
    sendTelemetry();
  }
}
