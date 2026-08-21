#include "vl53l0x_sensor.h"

#include <VL53L0X.h>
#include <Wire.h>

#include "config.h"

namespace {
VL53L0X rangefinder;
}  // namespace

bool Vl53l0xSensor::begin() {
  Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);

  rangefinder.setTimeout(500);
  if (!rangefinder.init()) {
    Serial.println("[VL53L0X] ERREUR: capteur laser introuvable");
    ready_ = false;
    return false;
  }

  rangefinder.startContinuous(50);
  ready_ = true;
  Serial.println("[VL53L0X] Telemetre laser initialise (mesure vers le sol)");
  return true;
}

bool Vl53l0xSensor::update() {
  if (!ready_) {
    return false;
  }

  const uint16_t distanceMm = rangefinder.readRangeContinuousMillimeters();
  if (rangefinder.timeoutOccurred()) {
    return false;
  }

  altitudeM_ = static_cast<float>(distanceMm) / 1000.0f;
  return true;
}
