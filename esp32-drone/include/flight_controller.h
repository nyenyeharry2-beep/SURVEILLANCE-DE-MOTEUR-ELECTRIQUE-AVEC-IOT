#pragma once

#include "config.h"
#include "gps_home.h"
#include "imu_sensor.h"
#include "motor_controller.h"
#include "pid_controller.h"
#include "rc_receiver.h"
#include "vl53l0x_sensor.h"

class FlightController {
 public:
  FlightController();

  void begin();
  void update(const RcInput& rc, float dtSeconds);

  float rollDeg() const { return rollDeg_; }
  float pitchDeg() const { return pitchDeg_; }
  float yawDeg() const { return yawDeg_; }
  FlightMode mode() const { return activeMode_; }
  float altitudeM() const { return altitudeM_; }
  bool homeLocked() const { return homeLocked_; }

 private:
  ImuSensor imu_;
  MotorController motors_;
  PidController rollPid_;
  PidController pitchPid_;
  PidController yawPid_;
  GpsHome gps_;
  Vl53l0xSensor rangefinder_;

  bool imuReady_ = false;
  bool rangefinderReady_ = false;
  bool homeLocked_ = false;

  FlightMode activeMode_ = FlightMode::MANUAL;
  bool autoLandActive_ = false;
  float landThrottle_ = LAND_HOVER_THROTTLE;

  float rollDeg_ = 0.0f;
  float pitchDeg_ = 0.0f;
  float yawDeg_ = 0.0f;
  float altitudeM_ = 0.0f;

  void handleManualMode(const RcInput& rc, float dtSeconds);
  void handleAutoLandMode(const RcInput& rc, float dtSeconds);
  void applyStabilization(float rollSetDeg, float pitchSetDeg, float yawRateSetDegS, float throttle01,
                          float dtSeconds);
  void enforceFailsafe(const RcInput& rc);
};
