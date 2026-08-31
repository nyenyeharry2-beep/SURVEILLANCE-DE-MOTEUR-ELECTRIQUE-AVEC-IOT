/*
 * Surveillance moteur électrique — Arduino Uno
 * Lit courant, tension, température, vibration, RPM
 * Envoie les mesures à l'ESP32 via UART (protocole JSON ligne)
 * Reçoit les commandes MOTOR_ON / MOTOR_OFF
 *
 * Bibliothèques : aucune obligatoire (LM35). DHT optionnel (voir USE_DHT).
 */

#include <Arduino.h>
#include <math.h>

// ============== BROCHES ==============
const int PIN_CURRENT   = A0;   // ACS712
const int PIN_TEMP      = A1;   // LM35
const int PIN_VOLTAGE   = A2;   // Module tension 0-25V
const int PIN_RPM       = 2;    // Capteur IR / hall (INT0 — obligatoire pour ISR)
const int PIN_VIBRATION = 3;    // SW-420 DO
const int PIN_RELAY     = 8;    // Relais moteur (actif HIGH)
const int PIN_LED       = 13;

// ============== ACS712 ==============
// Choisir selon le module : 5A=185, 20A=100, 30A=66 (mV/A)
const float ACS712_SENSITIVITY = 100.0; // mV/A (20A)
const float ACS712_VREF        = 2.5;   // V au repos (5V/2) — calibrer !

// ============== MODULE TENSION ==============
// Diviseur typique 0-25V : R1=30k, R2=7.5k → facteur ≈ 5.0
const float VOLTAGE_DIVIDER = 5.0;

// ============== SEUILS (copie locale pour LED / sécurité) ==============
const float TEMP_MAX_C      = 70.0;
const float CURRENT_MAX_A   = 8.0;
const float VIBRATION_ALARM = 1; // 1 = détectée

// ============== RPM ==============
volatile unsigned long rpmPulses = 0;
const int PULSES_PER_REV = 1; // nombre de marques / tour
unsigned long lastRpmMs  = 0;
float lastRpm            = 0;

// ============== ÉTAT ==============
bool motorOn = false;
unsigned long lastSendMs = 0;
const unsigned long SEND_INTERVAL_MS = 2000;

void rpmIsr() {
  rpmPulses++;
}

float readCurrentA() {
  // Moyenne de 20 échantillons
  long sum = 0;
  for (int i = 0; i < 20; i++) {
    sum += analogRead(PIN_CURRENT);
    delay(2);
  }
  float adc = sum / 20.0;
  float volts = (adc / 1023.0) * 5.0;
  float amps = (volts - ACS712_VREF) * 1000.0 / ACS712_SENSITIVITY;
  if (fabs(amps) < 0.05) amps = 0; // bruit
  return fabs(amps);
}

float readTempC() {
  int raw = analogRead(PIN_TEMP);
  float volts = (raw / 1023.0) * 5.0;
  // LM35 : 10 mV/°C
  return volts * 100.0;
}

float readVoltageV() {
  int raw = analogRead(PIN_VOLTAGE);
  float volts = (raw / 1023.0) * 5.0;
  return volts * VOLTAGE_DIVIDER;
}

int readVibration() {
  // SW-420 : HIGH = vibration détectée (selon câblage ; inverser si besoin)
  return digitalRead(PIN_VIBRATION) == HIGH ? 1 : 0;
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
  float current = readCurrentA();
  float temp    = readTempC();
  float voltage = readVoltageV();
  int vib       = readVibration();
  float rpm     = computeRpm();

  // Une ligne JSON compacte (ESP32 parse ligne par ligne)
  // Format: {"c":1.23,"t":45.6,"v":220.1,"vib":0,"rpm":1450,"m":1}
  Serial.print(F("{\"c\":"));
  Serial.print(current, 2);
  Serial.print(F(",\"t\":"));
  Serial.print(temp, 1);
  Serial.print(F(",\"v\":"));
  Serial.print(voltage, 1);
  Serial.print(F(",\"vib\":"));
  Serial.print(vib);
  Serial.print(F(",\"rpm\":"));
  Serial.print(rpm, 0);
  Serial.print(F(",\"m\":"));
  Serial.print(motorOn ? 1 : 0);
  Serial.println(F("}"));

  // Coupure locale de sécurité
  if (temp > TEMP_MAX_C || current > CURRENT_MAX_A) {
    if (motorOn) {
      setMotor(false);
      Serial.println(F("{\"evt\":\"SAFE_STOP\"}"));
    }
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
  pinMode(PIN_VIBRATION, INPUT);
  pinMode(PIN_RPM, INPUT_PULLUP);
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_LED, OUTPUT);
  setMotor(false);

  attachInterrupt(digitalPinToInterrupt(PIN_RPM), rpmIsr, FALLING);

  Serial.begin(9600);
  delay(500);
  Serial.println(F("{\"evt\":\"UNO_READY\"}"));
  lastRpmMs = millis();
  lastSendMs = millis();
}

void loop() {
  // Commandes depuis ESP32
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
