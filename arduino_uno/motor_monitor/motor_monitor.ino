/*
 * Surveillance moteur électrique — Arduino Uno
 * Capteurs :
 *   - ACS712 (courant), LM35 (temp), module tension
 *   - Capteur IR 3 broches (VCC/GND/OUT) → RPM sur D2 (INT0)
 *   - ADXL345 (I2C A4/A5) → vibration / accélération
 * UART → ESP32 : télémétrie JSON + commandes MOTOR_ON / MOTOR_OFF
 *
 * Bibliothèques : Wire (intégrée). Aucune lib externe requise.
 */

#include <Arduino.h>
#include <Wire.h>
#include <math.h>

// ============== BROCHES ==============
const int PIN_CURRENT = A0;  // ACS712
const int PIN_TEMP    = A1;  // LM35
const int PIN_VOLTAGE = A2;  // Module tension 0-25V
const int PIN_IR_OUT  = 2;   // Capteur IR 3 pins — OUT → D2 (INT0)
const int PIN_RELAY   = 8;   // Relais moteur (actif HIGH)
const int PIN_LED     = 13;

// I2C Uno : SDA = A4, SCL = A5  (ADXL345)

// ============== ACS712 ==============
const float ACS712_SENSITIVITY = 100.0; // mV/A (20A) — 5A=185, 30A=66
const float ACS712_VREF        = 2.5;   // V au repos — calibrer !

// ============== MODULE TENSION ==============
const float VOLTAGE_DIVIDER = 5.0;

// ============== CAPTEUR IR 3 PINS (RPM) ==============
// Module typique : VCC, GND, OUT (+ pot. sensibilité)
// OUT souvent LOW quand réflexion détectée → front FALLING = 1 pulse
const int PULSES_PER_REV     = 1;     // bandes réfléchissantes / tour
const unsigned long IR_DEBOUNCE_US = 2000; // anti-rebond ISR

// ============== ADXL345 ==============
// Adresse I2C : 0x53 si SDO/ALT → GND (modules GY-291 courants)
//              0x1D si SDO → VCC
const uint8_t ADXL345_ADDR = 0x53;
const float ADXL_SCALE     = 0.0039; // g/LSB en ±2g (full-res)
const float VIB_MAG_ALARM  = 0.35;   // g — écart à 1g (gravité) → alarme

// ============== SEUILS SÉCURITÉ LOCALE ==============
const float TEMP_MAX_C    = 70.0;
const float CURRENT_MAX_A = 8.0;

// ============== RPM ==============
volatile unsigned long rpmPulses = 0;
volatile unsigned long lastIrUs  = 0;
unsigned long lastRpmMs = 0;
float lastRpm           = 0;

// ============== ADXL état ==============
bool adxlOk = false;
float axG = 0, ayG = 0, azG = 0, magG = 0;
int vibFlag = 0;

// ============== ÉTAT ==============
bool motorOn = false;
unsigned long lastSendMs = 0;
const unsigned long SEND_INTERVAL_MS = 2000;

// ---------- ISR IR ----------
void irRpmIsr() {
  unsigned long now = micros();
  if (now - lastIrUs < IR_DEBOUNCE_US) return;
  lastIrUs = now;
  rpmPulses++;
}

// ---------- ADXL345 (registre bas niveau) ----------
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
  uint8_t n = Wire.requestFrom(ADXL345_ADDR, len);
  if (n != len) return false;
  for (uint8_t i = 0; i < len; i++) buf[i] = Wire.read();
  return true;
}

bool adxlBegin() {
  uint8_t id = 0;
  if (!adxlRead(0x00, &id, 1)) return false; // DEVID
  if (id != 0xE5) return false;

  // DATA_FORMAT : full-res ±2g (bit FULL_RES=1 → 0x08) ; ±16g full-res = 0x0B
  if (!adxlWrite(0x31, 0x08)) return false;
  // BW_RATE : 100 Hz
  if (!adxlWrite(0x2C, 0x0A)) return false;
  // POWER_CTL : measure mode
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

void updateVibration() {
  if (!adxlOk) {
    axG = ayG = azG = magG = 0;
    vibFlag = 0;
    return;
  }
  float x, y, z;
  if (!adxlReadAccel(x, y, z)) return;
  axG = x;
  ayG = y;
  azG = z;
  float mag = sqrt(x * x + y * y + z * z);
  // Écart par rapport à ~1 g (repos) = composante vibratoire
  magG = fabs(mag - 1.0);
  vibFlag = (magG >= VIB_MAG_ALARM) ? 1 : 0;
}

// ---------- Capteurs analogiques ----------
float readCurrentA() {
  long sum = 0;
  for (int i = 0; i < 20; i++) {
    sum += analogRead(PIN_CURRENT);
    delay(2);
  }
  float adc = sum / 20.0;
  float volts = (adc / 1023.0) * 5.0;
  float amps = (volts - ACS712_VREF) * 1000.0 / ACS712_SENSITIVITY;
  if (fabs(amps) < 0.05) amps = 0;
  return fabs(amps);
}

float readTempC() {
  int raw = analogRead(PIN_TEMP);
  float volts = (raw / 1023.0) * 5.0;
  return volts * 100.0; // LM35
}

float readVoltageV() {
  int raw = analogRead(PIN_VOLTAGE);
  float volts = (raw / 1023.0) * 5.0;
  return volts * VOLTAGE_DIVIDER;
}

float computeRpm() {
  unsigned long now = millis();
  unsigned long elapsed = now - lastRpmMs;
  if (elapsed < 1000) return lastRpm;

  noInterrupts();
  unsigned long pulses = rpmPulses;
  rpmPulses = 0;
  interrupts();

  float rpm = (pulses * 60000.0) / (float)(elapsed * PULSES_PER_REV);
  lastRpmMs = now;
  lastRpm = rpm;
  return rpm;
}

void setMotor(bool on) {
  motorOn = on;
  digitalWrite(PIN_RELAY, on ? HIGH : LOW);
  digitalWrite(PIN_LED, on ? HIGH : LOW);
}

void sendTelemetry() {
  updateVibration();
  float current = readCurrentA();
  float temp    = readTempC();
  float voltage = readVoltageV();
  float rpm     = computeRpm();

  // {"c":..,"t":..,"v":..,"vib":0|1,"ax":..,"ay":..,"az":..,"mag":..,"rpm":..,"m":0|1}
  Serial.print(F("{\"c\":"));
  Serial.print(current, 2);
  Serial.print(F(",\"t\":"));
  Serial.print(temp, 1);
  Serial.print(F(",\"v\":"));
  Serial.print(voltage, 1);
  Serial.print(F(",\"vib\":"));
  Serial.print(vibFlag);
  Serial.print(F(",\"ax\":"));
  Serial.print(axG, 3);
  Serial.print(F(",\"ay\":"));
  Serial.print(ayG, 3);
  Serial.print(F(",\"az\":"));
  Serial.print(azG, 3);
  Serial.print(F(",\"mag\":"));
  Serial.print(magG, 3);
  Serial.print(F(",\"rpm\":"));
  Serial.print(rpm, 0);
  Serial.print(F(",\"m\":"));
  Serial.print(motorOn ? 1 : 0);
  Serial.println(F("}"));

  bool overTemp = temp > TEMP_MAX_C;
  bool overCurr = current > CURRENT_MAX_A;
  bool overVib  = vibFlag == 1;
  if ((overTemp || overCurr || overVib) && motorOn) {
    setMotor(false);
    Serial.println(F("{\"evt\":\"SAFE_STOP\"}"));
  }
}

void processCommand(String line) {
  line.trim();
  if (line.length() == 0) return;

  if (line == "MOTOR_ON") {
    setMotor(true);
    Serial.println(F("{\"evt\":\"MOTOR_ON\",\"ok\":1}"));
  } else if (line == "MOTOR_OFF") {
    setMotor(false);
    Serial.println(F("{\"evt\":\"MOTOR_OFF\",\"ok\":1}"));
  } else if (line == "STATUS") {
    sendTelemetry();
  } else if (line == "PING") {
    Serial.println(F("{\"evt\":\"PONG\"}"));
  }
}

void setup() {
  pinMode(PIN_IR_OUT, INPUT); // module IR a souvent sa propre pull-up
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_LED, OUTPUT);
  setMotor(false);

  // Front descendant : OUT passe à LOW à chaque passage de la marque
  // Si votre module est actif HIGH, passer à RISING
  attachInterrupt(digitalPinToInterrupt(PIN_IR_OUT), irRpmIsr, FALLING);

  Wire.begin();
  adxlOk = adxlBegin();

  Serial.begin(9600);
  delay(500);
  if (adxlOk) {
    Serial.println(F("{\"evt\":\"UNO_READY\",\"adxl\":1,\"ir\":1}"));
  } else {
    Serial.println(F("{\"evt\":\"UNO_READY\",\"adxl\":0,\"ir\":1}"));
  }
  lastRpmMs = millis();
  lastSendMs = millis();
}

void loop() {
  while (Serial.available()) {
    String line = Serial.readStringUntil('\n');
    processCommand(line);
  }

  unsigned long now = millis();
  if (now - lastSendMs >= SEND_INTERVAL_MS) {
    lastSendMs = now;
    sendTelemetry();
  }
}
