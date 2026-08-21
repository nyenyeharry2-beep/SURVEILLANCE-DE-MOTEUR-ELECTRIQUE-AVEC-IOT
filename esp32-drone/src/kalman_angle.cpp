#include "kalman_angle.h"

KalmanAngle::KalmanAngle(float qAngle, float qBias, float rMeasure)
    : qAngle_(qAngle),
      qBias_(qBias),
      rMeasure_(rMeasure),
      angleDeg_(0.0f),
      biasDegS_(0.0f),
      p00_(0.0f),
      p01_(0.0f),
      p10_(0.0f),
      p11_(0.0f) {}

void KalmanAngle::reset(float angleDeg) {
  angleDeg_ = angleDeg;
  biasDegS_ = 0.0f;
  p00_ = 0.0f;
  p01_ = 0.0f;
  p10_ = 0.0f;
  p11_ = 0.0f;
}

float KalmanAngle::update(float measuredAngleDeg, float gyroRateDegS, float dtSeconds) {
  if (dtSeconds <= 0.0f) {
    return angleDeg_;
  }

  // --- Étape de prédiction ---
  const float rate = gyroRateDegS - biasDegS_;
  angleDeg_ += dtSeconds * rate;

  p00_ += dtSeconds * (dtSeconds * p11_ - p01_ - p10_ + qAngle_);
  p01_ -= dtSeconds * p11_;
  p10_ -= dtSeconds * p11_;
  p11_ += qBias_ * dtSeconds;

  // --- Étape de correction (mesure accéléromètre) ---
  const float innovation = measuredAngleDeg - angleDeg_;
  const float s = p00_ + rMeasure_;
  const float k0 = p00_ / s;
  const float k1 = p10_ / s;

  angleDeg_ += k0 * innovation;
  biasDegS_ += k1 * innovation;

  const float p00Temp = p00_;
  p00_ -= k0 * p00Temp;
  p01_ -= k0 * p01_;
  p10_ -= k1 * p00Temp;
  p11_ -= k1 * p01_;

  return angleDeg_;
}
