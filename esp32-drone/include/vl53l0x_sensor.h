#pragma once

class Vl53l0xSensor {
 public:
  bool begin();
  bool update();
  float altitudeM() const { return altitudeM_; }
  bool isReady() const { return ready_; }

 private:
  bool ready_ = false;
  float altitudeM_ = 0.0f;
};
