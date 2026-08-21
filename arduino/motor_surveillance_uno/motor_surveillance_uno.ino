/*
 * Système de surveillance moteur électrique - Arduino Uno
 * Acquisition RPM (capteur IR), vibrations (ADXL345), pilotage relais
 * Communication avec ESP32 via SoftwareSerial (D3=RX, D4=TX) @ 9600 bauds
 */

#include <Wire.h>
#include <SoftwareSerial.h>
#include <math.h>

#ifndef PI
#define PI 3.141592653589793
#endif

// --- Broches ---
const uint8_t PIN_IR_SENSOR = 2;       // Interruption RPM
const uint8_t PIN_RELAY = 7;           // Relais moteur
const uint8_t PIN_ESP_RX = 3;          // RX Arduino <- TX ESP32
const uint8_t PIN_ESP_TX = 4;          // TX Arduino -> RX ESP32

// --- ADXL345 ---
const uint8_t ADXL345_ADDR = 0x53;
const uint8_t ADXL345_POWER_CTL = 0x2D;
const uint8_t ADXL345_DATA_FORMAT = 0x31;
const uint8_t ADXL345_DATAX0 = 0x32;

// --- Paramètres métier ---
const float RPM_CONSIGNE = 2900.0f;
const float SEUIL_ECART_VITESSE = 5.0f;      // % écart max
const float SEUIL_ARMS = 2.0f;               // m/s²
const float SEUIL_VRMS = 4.5f;               // mm/s
const float FREQ_VIBRATION = 50.0f;          // Hz
const uint8_t RELAY_ACTIVE_LOW = 1;          // 1 = actif bas

// --- Timing ---
const unsigned long INTERVAL_MESURE_MS = 1000UL;
const unsigned long INTERVAL_ECHANTILLON_MS = 5UL;   // 20 x 5ms = 100ms
const uint8_t NB_ECHANTILLONS_RMS = 20;
const unsigned long ESP_TIMEOUT_MS = 10000UL;

SoftwareSerial espSerial(PIN_ESP_RX, PIN_ESP_TX);

volatile unsigned long pulseCount = 0;
unsigned long lastMeasureMs = 0;
unsigned long lastEspActivityMs = 0;

bool relayOn = false;
bool espConnected = true;

String etatMoteur = "MOTEUR_ARRETE";
bool anomalieVibration = false;
bool anomalieVitesse = false;

float ax = 0.0f, ay = 0.0f, az = 0.0f;
float rpm = 0.0f, arms = 0.0f, vrms = 0.0f, ecart = 0.0f;

String espRxBuffer;

void onIrPulse() {
  pulseCount++;
}

void writeRegister(uint8_t reg, uint8_t value) {
  Wire.beginTransmission(ADXL345_ADDR);
  Wire.write(reg);
  Wire.write(value);
  Wire.endTransmission();
}

bool initAdxl345() {
  Wire.begin();
  writeRegister(ADXL345_POWER_CTL, 0x08);   // Mesure ON
  writeRegister(ADXL345_DATA_FORMAT, 0x0B); // +/-16g, pleine résolution
  delay(10);
  Wire.beginTransmission(ADXL345_ADDR);
  Wire.write(ADXL345_POWER_CTL);
  Wire.endTransmission(false);
  Wire.requestFrom(ADXL345_ADDR, (uint8_t)1);
  return Wire.available() && Wire.read() == 0x08;
}

bool readAcceleration(float &outAx, float &outAy, float &outAz) {
  Wire.beginTransmission(ADXL345_ADDR);
  Wire.write(ADXL345_DATAX0);
  if (Wire.endTransmission(false) != 0) {
    return false;
  }
  if (Wire.requestFrom(ADXL345_ADDR, (uint8_t)6) != 6) {
    return false;
  }

  int16_t rawX = (Wire.read() | (Wire.read() << 8));
  int16_t rawY = (Wire.read() | (Wire.read() << 8));
  int16_t rawZ = (Wire.read() | (Wire.read() << 8));

  // 3.9 mg/LSB en pleine résolution +/-16g -> m/s²
  const float scale = 0.0039f * 9.80665f;
  outAx = rawX * scale;
  outAy = rawY * scale;
  outAz = rawZ * scale;
  return true;
}

float computeArms() {
  float sumSq = 0.0f;
  float sx = 0.0f, sy = 0.0f, sz = 0.0f;

  for (uint8_t i = 0; i < NB_ECHANTILLONS_RMS; i++) {
    float lx, ly, lz;
    if (!readAcceleration(lx, ly, lz)) {
      return -1.0f;
    }
    sx += lx;
    sy += ly;
    sz += lz;
    delay(INTERVAL_ECHANTILLON_MS);
  }

  sx /= NB_ECHANTILLONS_RMS;
  sy /= NB_ECHANTILLONS_RMS;
  sz /= NB_ECHANTILLONS_RMS;

  for (uint8_t i = 0; i < NB_ECHANTILLONS_RMS; i++) {
    float lx, ly, lz;
    if (!readAcceleration(lx, ly, lz)) {
      return -1.0f;
    }
    float dx = lx - sx;
    float dy = ly - sy;
    float dz = lz - sz;
    sumSq += dx * dx + dy * dy + dz * dz;
    delay(INTERVAL_ECHANTILLON_MS);
  }

  return sqrt(sumSq / (NB_ECHANTILLONS_RMS * 3.0f));
}

float computeVrms(float armsValue) {
  // Vitesse vibratoire RMS (mm/s) = accélération RMS / (2*pi*f) * 1000
  if (armsValue < 0.0f) {
    return -1.0f;
  }
  return (armsValue / (2.0f * PI * FREQ_VIBRATION)) * 1000.0f;
}

void setRelayState(bool on) {
  relayOn = on;
  if (RELAY_ACTIVE_LOW) {
    digitalWrite(PIN_RELAY, on ? LOW : HIGH);
  } else {
    digitalWrite(PIN_RELAY, on ? HIGH : LOW);
  }
}

void sendConfirmation(bool on) {
  espSerial.print("CONFIRMATION=RELAY_");
  espSerial.println(on ? "ON" : "OFF");
}

void handleEspCommand(const String &cmd) {
  if (cmd == "RELAY=ON") {
    setRelayState(true);
    sendConfirmation(true);
    lastEspActivityMs = millis();
  } else if (cmd == "RELAY=OFF") {
    setRelayState(false);
    sendConfirmation(false);
    lastEspActivityMs = millis();
  }
}

void processEspSerial() {
  while (espSerial.available()) {
    char c = espSerial.read();
    if (c == '\n' || c == '\r') {
      if (espRxBuffer.length() > 0) {
        handleEspCommand(espRxBuffer);
        espRxBuffer = "";
      }
    } else {
      espRxBuffer += c;
      if (espRxBuffer.length() > 32) {
        espRxBuffer = "";
      }
    }
  }
}

void handleSerialMonitor() {
  if (Serial.available()) {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    cmd.toUpperCase();
    if (cmd == "ON" || cmd == "RELAY=ON") {
      setRelayState(true);
      Serial.println(F("Relais ON"));
    } else if (cmd == "OFF" || cmd == "RELAY=OFF") {
      setRelayState(false);
      Serial.println(F("Relais OFF"));
    }
  }
}

void updateMotorState() {
  if (!relayOn) {
    etatMoteur = "MOTEUR_ARRETE";
    anomalieVibration = false;
    anomalieVitesse = false;
    return;
  }

  if (arms < 0.0f || vrms < 0.0f) {
    etatMoteur = "ERREUR";
    return;
  }

  anomalieVibration = (arms > SEUIL_ARMS) || (vrms > SEUIL_VRMS);
  anomalieVitesse = relayOn && (fabs(ecart) > SEUIL_ECART_VITESSE);

  if (anomalieVibration || anomalieVitesse) {
    etatMoteur = "ANOMALIE";
  } else {
    etatMoteur = "NORMAL";
  }
}

void sendTelemetry() {
  espSerial.print("AX=");
  espSerial.print(ax, 3);
  espSerial.print(",AY=");
  espSerial.print(ay, 3);
  espSerial.print(",AZ=");
  espSerial.print(az, 3);
  espSerial.print(",RPM=");
  espSerial.print(rpm, 1);
  espSerial.print(",ARMS=");
  espSerial.print(arms, 3);
  espSerial.print(",VRMS=");
  espSerial.print(vrms, 3);
  espSerial.print(",ECART=");
  espSerial.print(ecart, 2);
  espSerial.print(",ETAT=");
  espSerial.print(etatMoteur);
  espSerial.print(",RELAY=");
  espSerial.println(relayOn ? "ON" : "OFF");
}

void performMeasurement() {
  noInterrupts();
  unsigned long pulses = pulseCount;
  pulseCount = 0;
  interrupts();

  rpm = (pulses * 60.0f) / (INTERVAL_MESURE_MS / 1000.0f);

  if (!readAcceleration(ax, ay, az)) {
    arms = -1.0f;
    vrms = -1.0f;
  } else {
    arms = computeArms();
    vrms = computeVrms(arms);
  }

  if (RPM_CONSIGNE > 0.0f) {
    ecart = ((rpm - RPM_CONSIGNE) / RPM_CONSIGNE) * 100.0f;
  } else {
    ecart = 0.0f;
  }

  updateMotorState();
  sendTelemetry();

  if (millis() - lastEspActivityMs > ESP_TIMEOUT_MS) {
    espConnected = false;
  } else {
    espConnected = true;
  }
}

void setup() {
  Serial.begin(115200);
  espSerial.begin(9600);

  pinMode(PIN_IR_SENSOR, INPUT_PULLUP);
  pinMode(PIN_RELAY, OUTPUT);
  attachInterrupt(digitalPinToInterrupt(PIN_IR_SENSOR), onIrPulse, FALLING);

  setRelayState(false);

  if (!initAdxl345()) {
    etatMoteur = "ERREUR";
    Serial.println(F("Erreur initialisation ADXL345"));
  } else {
    Serial.println(F("ADXL345 OK"));
  }

  lastMeasureMs = millis();
  lastEspActivityMs = millis();
  Serial.println(F("Systeme surveillance moteur pret"));
}

void loop() {
  processEspSerial();
  handleSerialMonitor();

  unsigned long now = millis();
  if (now - lastMeasureMs >= INTERVAL_MESURE_MS) {
    lastMeasureMs = now;
    performMeasurement();
  }
}
