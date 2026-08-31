/*
 * Arduino Uno — Surveillance moteur
 * Capteurs : IR 3 pins (D2), ADXL345 (I2C A4/A5)
 * UART ESP32 : SoftwareSerial RX=D3 TX=D4 @ 9600
 *
 * Envoi JSON : ax ay az rms vrms rpm imp freq urg alerte m
 * (plus de impt / courant / température / tension)
 *
 * Affichage : Serial USB 115200 + SoftSerial ESP32
 */

#include <Arduino.h>
#include <Wire.h>
#include <SoftwareSerial.h>
#include <math.h>

const int PIN_IR_OUT = 2;
const int PIN_ESP_RX = 3;   // ← ESP32 GPIO17
const int PIN_ESP_TX = 4;   // → ESP32 GPIO16 (diviseur 1k/2k)
const int PIN_RELAY  = 8;
const int PIN_LED    = 13;

SoftwareSerial EspSerial(PIN_ESP_RX, PIN_ESP_TX);

const int PULSES_PER_REV = 1;
const unsigned long IR_DEBOUNCE_US = 2000;

const uint8_t ADXL345_ADDR = 0x53;
const float ADXL_SCALE = 0.0039f;

const float RMS_ALERT_G  = 0.25f;
const float RMS_URGENT_G = 0.50f;
const float VRMS_ALERT   = 4.0f;
const float VRMS_URGENT  = 8.0f;

volatile unsigned long rpmPulses = 0;
volatile unsigned long lastIrUs  = 0;

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

void sendBoth(const String& line) {
  Serial.println(line);
  EspSerial.println(line);
}

void irRpmIsr() {
  unsigned long now = micros();
  if (now - lastIrUs < IR_DEBOUNCE_US) return;
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
  float f = lastFreqHz < 0.5f ? 1.0f : lastFreqHz;
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
}

void sendTelemetry() {
  computeRpmFreq();
  updateVibrationRms();
  evaluateUrgency();

  // JSON : ax ay az rms vrms rpm imp freq urg alerte m
  String line = "{";
  line += "\"ax\":"; line += String(axG, 3);
  line += ",\"ay\":"; line += String(ayG, 3);
  line += ",\"az\":"; line += String(azG, 3);
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
  Serial.print(F("  Alerte=")); Serial.print(alerteFlag ? "OUI" : "NON");
  Serial.print(F("  Moteur=")); Serial.println(motorOn ? "ON" : "OFF");
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

  Serial.begin(115200);
  EspSerial.begin(9600);
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
