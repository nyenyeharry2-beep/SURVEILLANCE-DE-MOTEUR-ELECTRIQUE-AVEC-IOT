#include "flight_controller.h"

#include "config.h"

FlightController::FlightController()
    : rollPid_(ROLL_GAINS.kp, ROLL_GAINS.ki, ROLL_GAINS.kd, -500.0f, 500.0f),
      pitchPid_(PITCH_GAINS.kp, PITCH_GAINS.ki, PITCH_GAINS.kd, -500.0f, 500.0f),
      yawPid_(YAW_GAINS.kp, YAW_GAINS.ki, YAW_GAINS.kd, -500.0f, 500.0f) {}

void FlightController::begin() {
  imuReady_ = imu_.begin();
  rangefinderReady_ = rangefinder_.begin();
  motors_.begin();
  gps_.begin();

  rollPid_.reset();
  pitchPid_.reset();
  yawPid_.reset();

  // Attente du verrouillage GPS et enregistrement du Home Point au demarrage
  homeLocked_ = gps_.waitForLock(60000);
}

void FlightController::enforceFailsafe(const RcInput& rc) {
  if (rc.failsafe) {
    motors_.kill();
    rollPid_.reset();
    pitchPid_.reset();
    yawPid_.reset();
    autoLandActive_ = false;
  }
}

void FlightController::applyStabilization(float rollSetDeg, float pitchSetDeg, float yawRateSetDegS,
                                          float throttle01, float dtSeconds) {
  if (!imuReady_ || !imu_.update(dtSeconds)) {
    motors_.disarm();
    return;
  }

  const ImuState& imu = imu_.state();
  rollDeg_ = imu.rollDeg;
  pitchDeg_ = imu.pitchDeg;
  yawDeg_ = imu.yawDeg;

  if (throttle01 < 0.05f) {
    motors_.disarm();
    rollPid_.reset();
    pitchPid_.reset();
    yawPid_.reset();
    return;
  }

  float rollSet = rollSetDeg;
  float pitchSet = pitchSetDeg;
  float yawRateSet = yawRateSetDegS;

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

  motors_.setOutputs(throttle01, rollCmd, pitchCmd, yawCmd);
}

void FlightController::handleManualMode(const RcInput& rc, float dtSeconds) {
  autoLandActive_ = false;
  landThrottle_ = LAND_HOVER_THROTTLE;

  applyStabilization(rc.rollSetDeg, rc.pitchSetDeg, rc.yawRateSetDegS, rc.throttle01, dtSeconds);
}

void FlightController::handleAutoLandMode(const RcInput& rc, float dtSeconds) {
  if (!autoLandActive_) {
    autoLandActive_ = true;
    landThrottle_ = rc.throttle01 > LAND_HOVER_THROTTLE ? rc.throttle01 : LAND_HOVER_THROTTLE;
    Serial.println("[MODE] AUTO_LAND active — descente autonome");
  }

  if (rangefinderReady_) {
    rangefinder_.update();
    altitudeM_ = rangefinder_.altitudeM();

    if (altitudeM_ <= LAND_GROUND_THRESHOLD_M) {
      motors_.kill();
      rollPid_.reset();
      pitchPid_.reset();
      yawPid_.reset();
      Serial.println("[MODE] AUTO_LAND termine — sol detecte (< 5 cm)");
      return;
    }

    landThrottle_ -= (LAND_DESCENT_RATE_M_S * dtSeconds) / 2.0f;
    if (landThrottle_ < LAND_MIN_THROTTLE) {
      landThrottle_ = LAND_MIN_THROTTLE;
    }
  } else {
    landThrottle_ -= 0.05f * dtSeconds;
    if (landThrottle_ < LAND_MIN_THROTTLE) {
      landThrottle_ = LAND_MIN_THROTTLE;
    }
  }

  applyStabilization(0.0f, 0.0f, 0.0f, landThrottle_, dtSeconds);
}

void FlightController::update(const RcInput& rc, float dtSeconds) {
  gps_.poll();

  if (rc.failsafe || !rc.valid) {
    enforceFailsafe(rc);
    return;
  }

  activeMode_ = rc.mode;

  if (activeMode_ == FlightMode::AUTO_LAND) {
    handleAutoLandMode(rc, dtSeconds);
  } else {
    handleManualMode(rc, dtSeconds);
  }
}
