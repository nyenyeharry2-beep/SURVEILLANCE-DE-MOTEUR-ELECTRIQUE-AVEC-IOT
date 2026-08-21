#pragma once

#include <cstdint>

class KalmanAngle;

struct ImuState {
  float rollDeg;
  float pitchDeg;
  float yawDeg;
  float rollRateDegS;
  float pitchRateDegS;
  float yawRateDegS;
};

class ImuSensor {
 public:
  bool begin();
  bool update(float dtSeconds);
  const ImuState& state() const { return state_; }

 private:
  ImuState state_{};
  bool ready_ = false;
  class KalmanAngle* rollFilter_ = nullptr;
  class KalmanAngle* pitchFilter_ = nullptr;
};
