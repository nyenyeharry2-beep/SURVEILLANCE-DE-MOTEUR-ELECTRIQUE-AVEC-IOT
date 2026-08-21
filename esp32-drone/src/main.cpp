/*
 * Firmware drone quadrirotor ESP32
 * -----------------------------------
 * Stabilisation IMU (filtre de Kalman) + PID
 * Reception FS-iA6b (PWM) + GPS Home Point + atterrissage VL53L0X
 *
 * Modes: MANUEL | AUTO_LAND (commutateur CH5)
 */

#include <Arduino.h>

#include "config.h"
#include "flight_controller.h"
#include "rc_receiver.h"

FlightController flightController;
RcReceiver rcReceiver;

unsigned long lastControlMicros = 0;
unsigned long lastRcPollMs = 0;
unsigned long lastTelemetryMs = 0;

void printTelemetry() {
  Serial.print("[TELEM] mode=");
  Serial.print(flightController.mode() == FlightMode::AUTO_LAND ? "AUTO_LAND" : "MANUAL");
  Serial.print(" roll=");
  Serial.print(flightController.rollDeg(), 1);
  Serial.print(" pitch=");
  Serial.print(flightController.pitchDeg(), 1);
  Serial.print(" yaw=");
  Serial.print(flightController.yawDeg(), 1);
  Serial.print(" alt=");
  Serial.print(flightController.altitudeM(), 2);
  Serial.print(" home=");
  Serial.println(flightController.homeLocked() ? "OK" : "NO");
}

void setup() {
  Serial.begin(115200);
  delay(500);

  Serial.println();
  Serial.println("========================================");
  Serial.println("  ESP32 Quadrirotor — Firmware v1.0");
  Serial.println("  Kalman + PID | GPS | VL53L0X | RC");
  Serial.println("========================================");

  rcReceiver.begin();
  flightController.begin();

  lastControlMicros = micros();
  lastRcPollMs = millis();
  lastTelemetryMs = millis();

  Serial.println("[SYS] Initialisation terminee");
  Serial.println("[SYS] Retirer les helices pour les tests au banc !");
}

void loop() {
  const unsigned long nowMs = millis();

  // Lecture des canaux radio (~100 Hz)
  if (nowMs - lastRcPollMs >= (1000UL / RC_POLL_HZ)) {
    rcReceiver.poll();
    lastRcPollMs = nowMs;
  }

  // Boucle de stabilisation haute frequence (400 Hz)
  const unsigned long nowMicros = micros();
  const float dtSeconds = (nowMicros - lastControlMicros) / 1000000.0f;

  if (dtSeconds >= (1.0f / CONTROL_LOOP_HZ)) {
    flightController.update(rcReceiver.input(), dtSeconds);
    lastControlMicros = nowMicros;
  }

  // Telemetrie serie (10 Hz)
  if (nowMs - lastTelemetryMs >= (1000UL / TELEMETRY_HZ)) {
    printTelemetry();
    lastTelemetryMs = nowMs;
  }
}
