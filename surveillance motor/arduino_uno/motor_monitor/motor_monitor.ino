/*
 * Arduino Uno — Surveillance moteur
 * Capteurs : ACS712, LM35, tension, IR 3 pins (D2), ADXL345 (I2C A4/A5)
 * UART ESP32 : SoftwareSerial RX=D3 TX=D4 @ 9600
 *
 * AFFICHAGE :
 *  - Moniteur série USB (D0/D1) @ 115200  ← visible dans l'IDE
 *  - SoftSerial vers ESP32 @ 9600         ← même JSON envoyé
 */

#include <Arduino.h>
#include <Wire.h>
#include <SoftwareSerial.h>
#include <math.h>

const int PIN_CURRENT = A0;
const int PIN_TEMP    = A1;
const int PIN_VOLTAGE = A2;
const int PIN_IR_OUT  = 2;
const int PIN_ESP_RX  = 3;   // ← ESP32 GPIO17
const int PIN_ESP_TX  = 4;   // → ESP32 GPIO16 (diviseur 1k/2k)
const int PIN_RELAY   = 8;
const int PIN_LED     = 13;

SoftwareSerial EspSerial(PIN_ESP_RX, PIN_ESP_TX);

const float ACS712_SENSITIVITY = 100.0;
const float ACS712_VREF        = 2.5;
const float VOLTAGE_DIVIDER    = 5.0;
const int PULSES_PER_REV = 1;
const unsigned long IR_DEBOUNCE_US = 2000;

const uint8_t ADXL345_ADDR = 0x53;
const float ADXL_SCALE = 0.0039f;

const float TEMP_MAX_C    = 70.0f;
const float CURRENT_MAX_A = 8.0f;
const float RMS_ALERT_G   = 0.25f;
const float RMS_URGENT_G  = 0.50f;
const float VRMS_ALERT    = 4.0f;
const float VRMS_URGENT   = 8.0f;

volatile unsigned long rpmPulses = 0;
volatile unsigned long lastIrUs  = 0;
volatile unsigned long totalImpulses = 0;

unsigned long lastRpmMs = 0;
float lastRpm = 0;
float lastFreqHz = 0;
unsigned long lastWindowImp = 0;

bool adxlOk = false;
float axG = 0, ayG = 0, azG = 0;
float rmsG = 0, vrmsMms = 0;
int urgLevel = 0;
int alerteFlag = 0;

bool motorOn = false;
unsigned long lastSendMs = 0;
const unsigned long SEND_INTERVAL_MS = 2000;
const int ADXL_SAMPLES = 16;

/** Envoie la même ligne sur USB (Serial) ET vers ESP32 (EspSerial) */
void sendBoth(const String& line) {
  Serial.println(line);      // moniteur série Uno USB @ 115200
  EspSerial.println(line);   // ESP32 SoftSerial @ 9600
}

void irRpmIsr() {
  unsigned long now = micros();
  if (now - lastIrUs < IR_DEBOUNCE_US) return;
  lastIrUs = now;
  rpmPulses++;
  totalImpulses++;
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
  if (!adxlWrite(0x31, 0x08)) return false;
  if (!adxlWrite(0x2C, 0x0A)) return false;
  if (!adxlWrite(0x2D, 0x08)) return false;
  delay(20);
  return true;
}

bool adxlReadAccel(float& x, float& y, float& z) {
  uint8_t raw[6];
  if (!adxlRead(0x32, raw, 6)) return false;
  int16_t xi = (int16_t)(raw[1] << 8 | raw[0]);
  int16_t yi = (int16_t)(raw[3] << 8 | raw[2]);
  int16_t zi = (int16_t)(raw[5] << 8 | raw[4]);
  x = xi * ADXL_SCALE;
  y = yi * ADXL_SCALE;
  z = zi * ADXL_SCALE;
  return true;
}

void updateVibrationRms() {
  if (!adxlOk) {
    axG = ayG = azG = rmsG = vrmsMms = 0;
    return;
  }
  float sx = 0, sy = 0, sz = 0;
  float samplesX[ADXL_SAMPLES];
  float samplesY[ADXL_SAMPLES];
  float samplesZ[ADXL_SAMPLES];
  for (int i = 0; i < ADXL_SAMPLES; i++) {
    float x, y, z;
    if (!adxlReadAccel(x, y, z)) { x = y = z = 0; }
    samplesX[i] = x; samplesY[i] = y; samplesZ[i] = z;
    sx += x; sy += y; sz += z;
    delay(2);
  }
  float mx = sx / ADXL_SAMPLES;
  float my = sy / ADXL_SAMPLES;
  float mz = sz / ADXL_SAMPLES;
  axG = mx; ayG = my; azG = mz;
  float acc2 = 0;
  for (int i = 0; i < ADXL_SAMPLES; i++) {
    float dx = samplesX[i] - mx;
    float dy = samplesY[i] - my;
    float dz = samplesZ[i] - mz;
    acc2 += dx * dx + dy * dy + dz * dz;
  }
  rmsG = sqrt(acc2 / ADXL_SAMPLES);
  float f = lastFreqHz;
  if (f < 0.5f) f = 1.0f;
  vrmsMms = (rmsG * 9806.65f) / (2.0f * 3.1415926f * f);
}

void computeRpmFreq() {
  unsigned long now = millis();
  unsigned long elapsed = now - lastRpmMs;
  if (elapsed < 1000) return;
  noInterrupts();
  unsigned long pulses = rpmPulses;
  rpmPulses = 0;
  interrupts();
  lastWindowImp = pulses;
  lastRpm = (pulses * 60000.0f) / (float)(elapsed * PULSES_PER_REV);
  lastFreqHz = (pulses * 1000.0f) / (float)elapsed;
  lastRpmMs = now;
}

void evaluateUrgency(float temp, float current) {
  urgLevel = 0;
  alerteFlag = 0;
  bool aRmsUrgent = rmsG >= RMS_URGENT_G;
  bool vRmsUrgent = vrmsMms >= VRMS_URGENT;
  bool overTemp = temp > TEMP_MAX_C;
  bool overCurr = current > CURRENT_MAX_A;
  bool aRmsAlert = rmsG >= RMS_ALERT_G;
  bool vRmsAlert = vrmsMms >= VRMS_ALERT;
  if (aRmsUrgent || vRmsUrgent || overTemp || overCurr) {
    urgLevel = 2; alerteFlag = 1;
  } else if (aRmsAlert || vRmsAlert) {
    urgLevel = 1; alerteFlag = 1;
  }
}

float readCurrentA() {
  long sum = 0;
  for (int i = 0; i < 20; i++) { sum += analogRead(PIN_CURRENT); delay(2); }
  float volts = ((sum / 20.0f) / 1023.0f) * 5.0f;
  float amps = (volts - ACS712_VREF) * 1000.0f / ACS712_SENSITIVITY;
  if (fabs(amps) < 0.05f) amps = 0;
  return fabs(amps);
}

float readTempC() {
  return (analogRead(PIN_TEMP) / 1023.0f) * 5.0f * 100.0f;
}

float readVoltageV() {
  return (analogRead(PIN_VOLTAGE) / 1023.0f) * 5.0f * VOLTAGE_DIVIDER;
}

void setMotor(bool on) {
  motorOn = on;
  digitalWrite(PIN_RELAY, on ? HIGH : LOW);
  digitalWrite(PIN_LED, on ? HIGH : LOW);
}

void sendTelemetry() {
  computeRpmFreq();
  updateVibrationRms();
  float current = readCurrentA();
  float temp = readTempC();
  float voltage = readVoltageV();
  evaluateUrgency(temp, current);

  noInterrupts();
  unsigned long impTotal = totalImpulses;
  interrupts();

  // Construit UNE ligne JSON → USB + ESP32
  String line = "{";
  line += "\"ax\":"; line += String(axG, 3);
  line += ",\"ay\":"; line += String(ayG, 3);
  line += ",\"az\":"; line += String(azG, 3);
  line += ",\"rms\":"; line += String(rmsG, 3);
  line += ",\"vrms\":"; line += String(vrmsMms, 2);
  line += ",\"rpm\":"; line += String(lastRpm, 0);
  line += ",\"imp\":"; line += String(lastWindowImp);
  line += ",\"impt\":"; line += String(impTotal);
  line += ",\"freq\":"; line += String(lastFreqHz, 2);
  line += ",\"urg\":"; line += String(urgLevel);
  line += ",\"alerte\":"; line += String(alerteFlag);
  line += ",\"c\":"; line += String(current, 2);
  line += ",\"t\":"; line += String(temp, 1);
  line += ",\"v\":"; line += String(voltage, 1);
  line += ",\"m\":"; line += String(motorOn ? 1 : 0);
  line += "}";

  sendBoth(line);

  // Affichage lisible aussi sur moniteur USB
  Serial.println(F("--- UNO MONITOR ---"));
  Serial.print(F("ax=")); Serial.print(axG, 3);
  Serial.print(F(" ay=")); Serial.print(ayG, 3);
  Serial.print(F(" az=")); Serial.println(azG, 3);
  Serial.print(F("RMS=")); Serial.print(rmsG, 3);
  Serial.print(F("g  vRMS=")); Serial.print(vrmsMms, 2);
  Serial.println(F(" mm/s"));
  Serial.print(F("RPM=")); Serial.print(lastRpm, 0);
  Serial.print(F("  freq=")); Serial.print(lastFreqHz, 2);
  Serial.print(F(" Hz  imp=")); Serial.println(lastWindowImp);
  Serial.print(F("Urgence=")); Serial.print(urgLevel);
  Serial.print(F("  Alerte=")); Serial.println(alerteFlag ? "OUI" : "NON");
  Serial.print(F("I=")); Serial.print(current, 2);
  Serial.print(F("A  U=")); Serial.print(voltage, 1);
  Serial.print(F("V  T=")); Serial.print(temp, 1);
  Serial.print(F("C  Moteur=")); Serial.println(motorOn ? "ON" : "OFF");
  Serial.println();

  if (urgLevel >= 2 && motorOn) {
    setMotor(false);
    sendBoth(F("{\"evt\":\"SAFE_STOP\",\"urg\":2}"));
    Serial.println(F("!!! SAFE_STOP — moteur coupe"));
  }
}

void processCommand(String line) {
  line.trim();
  if (line.length() == 0) return;

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
  }
}

void setup() {
  pinMode(PIN_IR_OUT, INPUT);
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_LED, OUTPUT);
  setMotor(false);

  attachInterrupt(digitalPinToInterrupt(PIN_IR_OUT), irRpmIsr, FALLING);

  Wire.begin();
  adxlOk = adxlBegin();

  Serial.begin(115200);   // MONITEUR 1 — USB Uno
  EspSerial.begin(9600);  // vers ESP32
  delay(500);

  String ready = "{\"evt\":\"UNO_READY\",\"adxl\":";
  ready += adxlOk ? "1" : "0";
  ready += ",\"ir\":1}";
  sendBoth(ready);

  Serial.println(F("=== MONITEUR UNO USB 115200 ==="));
  Serial.println(F("UART ESP32 : D3=RX D4=TX @9600"));
  Serial.print(F("ADXL345 : "));
  Serial.println(adxlOk ? "OK" : "ABSENT");

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
