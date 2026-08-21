#pragma once

#include "config.h"
#include "imu_sensor.h"
#include "motor_controller.h"
#include "pid_controller.h"

struct ControlInput {
  float throttle01;   // 0.0 = moteurs au ralenti, 1.0 = plein gaz
  float rollSetDeg;   // inclinaison latérale demandée
  float pitchSetDeg;  // inclinaison avant/arrière demandée
  float yawRateSet;   // vitesse de rotation (deg/s)
  bool armRequest;
};

class FlightController {
 public:
  void begin();
  void update(const ControlInput& input, float dtSeconds);
  const ImuState& imuState() const { return imu_.state(); }
  bool isArmed() const { return motors_.isArmed(); }

 private:
  ImuSensor imu_;
  MotorController motors_;
  PidController rollPid_{ROLL_GAINS.kp, ROLL_GAINS.ki, ROLL_GAINS.kd, -500.0f, 500.0f};
  PidController pitchPid_{PITCH_GAINS.kp, PITCH_GAINS.ki, PITCH_GAINS.kd, -500.0f, 500.0f};
  PidController yawPid_{YAW_GAINS.kp, YAW_GAINS.ki, YAW_GAINS.kd, -500.0f, 500.0f};
  bool imuReady_ = false;
};
