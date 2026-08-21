#pragma once

#include "config.h"

class PidController {
 public:
  PidController(float kp, float ki, float kd, float outputMin, float outputMax);

  void setGains(float kp, float ki, float kd);
  void reset();
  float update(float setpoint, float measurement, float dtSeconds);

 private:
  float kp_;
  float ki_;
  float kd_;
  float integral_;
  float prevError_;
  float outputMin_;
  float outputMax_;
};
