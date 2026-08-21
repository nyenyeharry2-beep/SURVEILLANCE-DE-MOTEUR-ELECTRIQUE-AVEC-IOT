#include "pid_controller.h"

PidController::PidController(float kp, float ki, float kd, float outputMin, float outputMax)
    : kp_(kp),
      ki_(ki),
      kd_(kd),
      integral_(0.0f),
      prevError_(0.0f),
      outputMin_(outputMin),
      outputMax_(outputMax) {}

void PidController::setGains(float kp, float ki, float kd) {
  kp_ = kp;
  ki_ = ki;
  kd_ = kd;
}

void PidController::reset() {
  integral_ = 0.0f;
  prevError_ = 0.0f;
}

float PidController::update(float setpoint, float measurement, float dtSeconds) {
  if (dtSeconds <= 0.0f) {
    return 0.0f;
  }

  const float error = setpoint - measurement;
  integral_ += error * dtSeconds;

  // Anti-windup simple
  constexpr float maxIntegral = 200.0f;
  if (integral_ > maxIntegral) {
    integral_ = maxIntegral;
  } else if (integral_ < -maxIntegral) {
    integral_ = -maxIntegral;
  }

  const float derivative = (error - prevError_) / dtSeconds;
  prevError_ = error;

  float output = (kp_ * error) + (ki_ * integral_) + (kd_ * derivative);

  if (output > outputMax_) {
    output = outputMax_;
  } else if (output < outputMin_) {
    output = outputMin_;
  }

  return output;
}
