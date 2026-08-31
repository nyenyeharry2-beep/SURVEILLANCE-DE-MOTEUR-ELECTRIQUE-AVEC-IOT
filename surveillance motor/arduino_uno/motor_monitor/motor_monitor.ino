/*
 * Surveillance moteur — Arduino Uno
 * Capteurs : ACS712, LM35, tension, IR 3 pins (RPM), ADXL345 (I2C)
 *
 * Télémétrie JSON :
 *  ax,ay,az,rms,vrms,rpm (mpr),imp,freq,urg,alerte,c,t,v,m
 */

#include <Arduino.h>
#include <Wire.h>
#include <SoftwareSerial.h>
#include <math.h>

const int PIN_CURRENT = A0;
const int PIN_TEMP    = A1;
const int PIN_VOLTAGE = A2;
const int PIN_IR_OUT  = 2;   // IR OUT → D2 (INT0)
const int PIN_ESP_RX  = 3;   // ← ESP32 GPIO17 (TX2)
const int PIN_ESP_TX  = 4;   // → ESP32 GPIO16 (RX2) via diviseur 1k/2k
const int PIN_RELAY   = 8;
const int PIN_LED     = 13;

// UART logiciel vers ESP32 (laisse D0/D1 libres pour le flash USB)
SoftwareSerial EspSerial(PIN_ESP_RX, PIN_ESP_TX); // RX=D3, TX=D4

const float ACS712_SENSITIVITY = 100.0;
const float ACS712_VREF        = 2.5;
const float VOLTAGE_DIVIDER    = 5.0;

const int PULSES_PER_REV = 1;
const unsigned long IR_DEBOUNCE_US = 2000;

const uint8_t ADXL345_ADDR = 0x53;
const float ADXL_SCALE = 0.0039f;

// Seuils locaux
const float TEMP_MAX_C    = 70.0f;
const float CURRENT_MAX_A = 8.0f;
const float RMS_ALERT_G   = 0.25f;  // alerte
const float RMS_URGENT_G  = 0.50f;  // urgence
const float VRMS_ALERT    = 4.0f;   // mm/s approx
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
int urgLevel = 0;   // 0 OK, 1 ALERTE, 2 URGENCE
int alerteFlag = 0;

bool motorOn = false;
unsigned long lastSendMs = 0;
const unsigned long SEND_INTERVAL_MS = 2000;
const int ADXL_SAMPLES = 16;

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
  if (!adxlWrite(0x31, 0x08)) return false; // ±2g full-res
  if (!adxlWrite(0x2C, 0x0A)) return false; // 100 Hz
  if (!adxlWrite(0x2D, 0x08)) return false; // measure
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
    if (!adxlReadAccel(x, y, z)) {
      x = y = z = 0;
    }
    samplesX[i] = x;
    samplesY[i] = y;
    samplesZ[i] = z;
    sx += x; sy += y; sz += z;
    delay(2); // ~500 Hz burst
  }

  float mx = sx / ADXL_SAMPLES;
  float my = sy / ADXL_SAMPLES;
  float mz = sz / ADXL_SAMPLES;
  axG = mx;
  ayG = my;
  azG = mz;

  // RMS de l'accélération dynamique (gravité retirée)
  float acc2 = 0;
  for (int i = 0; i < ADXL_SAMPLES; i++) {
    float dx = samplesX[i] - mx;
    float dy = samplesY[i] - my;
    float dz = samplesZ[i] - mz;
    acc2 += dx * dx + dy * dy + dz * dz;
  }
  rmsG = sqrt(acc2 / ADXL_SAMPLES);

  // vRMS approx (mm/s) : v = a/(2πf) avec a en mm/s²
  // 1 g = 9806.65 mm/s²
  float f = lastFreqHz;
  if (f < 0.5f) f = 1.0f; // fallback basse fréquence
  float a_mms2 = rmsG * 9806.65f;
  vrmsMms = a_mms2 / (2.0f * 3.1415926f * f);
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
  lastFreqHz = (pulses * 1000.0f) / (float)elapsed; // Hz impulsions
  lastRpmMs = now;
}

void evaluateUrgency(float temp, float current) {
  urgLevel = 0;
  alerteFlag = 0;

  bool aRmsAlert = rmsG >= RMS_ALERT_G;
  bool aRmsUrgent = rmsG >= RMS_URGENT_G;
  bool vRmsAlert = vrmsMms >= VRMS_ALERT;
  bool vRmsUrgent = vrmsMms >= VRMS_URGENT;
  bool overTemp = temp > TEMP_MAX_C;
  bool overCurr = current > CURRENT_MAX_A;

  if (aRmsUrgent || vRmsUrgent || overTemp || overCurr) {
    urgLevel = 2;
    alerteFlag = 1;
  } else if (aRmsAlert || vRmsAlert) {
    urgLevel = 1;
    alerteFlag = 1;
  }
}

float readCurrentA() {
  long sum = 0;
  for (int i = 0; i < 20; i++) {
    sum += analogRead(PIN_CURRENT);
    delay(2);
  }
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

  // Champs tableau de bord :
  // ax ay az rms vrms rpm(mpr) imp freq urg alerte + c t v m
  EspSerial.print(F("{\"ax\":"));
  EspSerial.print(axG, 3);
  EspSerial.print(F(",\"ay\":"));
  EspSerial.print(ayG, 3);
  EspSerial.print(F(",\"az\":"));
  EspSerial.print(azG, 3);
  EspSerial.print(F(",\"rms\":"));
  EspSerial.print(rmsG, 3);
  EspSerial.print(F(",\"vrms\":"));
  EspSerial.print(vrmsMms, 2);
  EspSerial.print(F(",\"rpm\":"));
  EspSerial.print(lastRpm, 0);
  EspSerial.print(F(",\"imp\":"));
  EspSerial.print(lastWindowImp);
  EspSerial.print(F(",\"impt\":"));
  EspSerial.print(impTotal);
  EspSerial.print(F(",\"freq\":"));
  EspSerial.print(lastFreqHz, 2);
  EspSerial.print(F(",\"urg\":"));
  EspSerial.print(urgLevel);
  EspSerial.print(F(",\"alerte\":"));
  EspSerial.print(alerteFlag);
  EspSerial.print(F(",\"c\":"));
  EspSerial.print(current, 2);
  EspSerial.print(F(",\"t\":"));
  EspSerial.print(temp, 1);
  EspSerial.print(F(",\"v\":"));
  EspSerial.print(voltage, 1);
  EspSerial.print(F(",\"m\":"));
  EspSerial.print(motorOn ? 1 : 0);
  EspSerial.println(F("}"));

  if (urgLevel >= 2 && motorOn) {
    setMotor(false);
    EspSerial.println(F("{\"evt\":\"SAFE_STOP\",\"urg\":2}"));
  }
}

void processCommand(String line) {
  line.trim();
  if (line.length() == 0) return;

  if (line == "MOTOR_ON") {
    setMotor(true);
    EspSerial.println(F("{\"evt\":\"MOTOR_ON\",\"ok\":1}"));
  } else if (line == "MOTOR_OFF") {
    setMotor(false);
    EspSerial.println(F("{\"evt\":\"MOTOR_OFF\",\"ok\":1}"));
  } else if (line == "STATUS") {
    sendTelemetry();
  } else if (line == "PING") {
    EspSerial.println(F("{\"evt\":\"PONG\"}"));
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

  // USB debug optionnel (D0/D1 libres — pas de conflit flash)
  Serial.begin(115200);
  EspSerial.begin(9600);
  delay(500);
  EspSerial.print(F("{\"evt\":\"UNO_READY\",\"adxl\":"));
  EspSerial.print(adxlOk ? 1 : 0);
  EspSerial.println(F(",\"ir\":1}"));
  Serial.println(F("Uno pret — UART ESP32 sur D3(RX)/D4(TX) @9600"));

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
