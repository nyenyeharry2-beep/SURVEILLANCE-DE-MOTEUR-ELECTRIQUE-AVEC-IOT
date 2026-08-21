#pragma once

#include <Arduino.h>

struct ImuState {
  float rollDeg;
  float pitchDeg;
  float yawRateDegS;
  float accelZ;
};

class ImuSensor {
 public:
  bool begin();
  bool update(float dtSeconds);
  const ImuState& state() const { return state_; }

 private:
  ImuState state_{};
  bool ready_ = false;
  unsigned long lastMicros_ = 0;
};
