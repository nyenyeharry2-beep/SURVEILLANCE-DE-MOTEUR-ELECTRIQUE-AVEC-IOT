#include "flight_controller.h"

void FlightController::begin() {
  imuReady_ = imu_.begin();
  motors_.begin();
  rollPid_.reset();
  pitchPid_.reset();
  yawPid_.reset();
}

void FlightController::update(const ControlInput& input, float dtSeconds) {
  if (!imuReady_) {
    motors_.disarm();
    return;
  }

  if (!imu_.update(dtSeconds)) {
    motors_.disarm();
    return;
  }

  const ImuState& imu = imu_.state();

  if (!input.armRequest || input.throttle01 < 0.05f) {
    motors_.disarm();
    rollPid_.reset();
    pitchPid_.reset();
    yawPid_.reset();
    return;
  }

  float rollSet = input.rollSetDeg;
  float pitchSet = input.pitchSetDeg;
  float yawRateSet = input.yawRateSet;

  if (rollSet > MAX_ANGLE_DEG) {
    rollSet = MAX_ANGLE_DEG;
  } else if (rollSet < -MAX_ANGLE_DEG) {
    rollSet = -MAX_ANGLE_DEG;
  }

  if (pitchSet > MAX_ANGLE_DEG) {
    pitchSet = MAX_ANGLE_DEG;
  } else if (pitchSet < -MAX_ANGLE_DEG) {
    pitchSet = -MAX_ANGLE_DEG;
  }

  if (yawRateSet > MAX_YAW_RATE_DEG_S) {
    yawRateSet = MAX_YAW_RATE_DEG_S;
  } else if (yawRateSet < -MAX_YAW_RATE_DEG_S) {
    yawRateSet = -MAX_YAW_RATE_DEG_S;
  }

  const float rollCmd = rollPid_.update(rollSet, imu.rollDeg, dtSeconds);
  const float pitchCmd = pitchPid_.update(pitchSet, imu.pitchDeg, dtSeconds);
  const float yawCmd = yawPid_.update(yawRateSet, imu.yawRateDegS, dtSeconds);

  motors_.setOutputs(input.throttle01, rollCmd, pitchCmd, yawCmd);
}
