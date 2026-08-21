#pragma once

#include <Arduino.h>
#include "config.h"

class MotorController {
 public:
  void begin();
  void disarm();
  void setOutputs(float throttle01, float rollCmd, float pitchCmd, float yawCmd);
  bool isArmed() const { return armed_; }

 private:
  bool armed_ = false;
  uint16_t mixMotor(float throttle01, float roll, float pitch, float yaw, float signRoll,
                    float signPitch, float signYaw) const;
  void writePwm(uint8_t channel, uint16_t pulseUs);
};
