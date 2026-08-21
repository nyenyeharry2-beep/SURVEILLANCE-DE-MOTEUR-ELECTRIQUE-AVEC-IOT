#include <Arduino.h>

#include "config.h"
#include "flight_controller.h"
#include "wifi_telemetry.h"

FlightController flightController;
WifiTelemetry telemetry;

unsigned long lastControlMicros = 0;
unsigned long lastTelemetryMs = 0;

ControlInput buildControlInput() {
  ControlInput input = telemetry.readControlInput();

  // Armement via bouton physique (broche ARM_PIN à GND)
  pinMode(ARM_PIN, INPUT_PULLUP);
  const bool buttonArmed = digitalRead(ARM_PIN) == LOW;

  input.armRequest = input.armRequest && buttonArmed;
  return input;
}

void setup() {
  Serial.begin(115200);
  delay(500);

  Serial.println();
  Serial.println("=== ESP32 Drone Firmware ===");

  flightController.begin();
  telemetry.begin();

  lastControlMicros = micros();
  lastTelemetryMs = millis();

  Serial.println("[SYS] Demarrage termine — retirer les helices pour les tests");
}

void loop() {
  const unsigned long nowMicros = micros();
  const float dtSeconds = (nowMicros - lastControlMicros) / 1000000.0f;

  if (dtSeconds >= (1.0f / CONTROL_LOOP_HZ)) {
    telemetry.poll();
    const ControlInput input = buildControlInput();
    flightController.update(input, dtSeconds);
    lastControlMicros = nowMicros;
  }

  const unsigned long nowMs = millis();
  if (nowMs - lastTelemetryMs >= (1000UL / TELEMETRY_HZ)) {
    telemetry.sendTelemetry(flightController);
    lastTelemetryMs = nowMs;
  }
}
