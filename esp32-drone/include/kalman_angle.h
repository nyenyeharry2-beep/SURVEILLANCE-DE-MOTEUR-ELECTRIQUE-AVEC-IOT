#pragma once

// Filtre de Kalman 1D pour fusion gyroscope / accéléromètre (roll ou pitch).
class KalmanAngle {
 public:
  KalmanAngle(float qAngle, float qBias, float rMeasure);

  float update(float measuredAngleDeg, float gyroRateDegS, float dtSeconds);
  void reset(float angleDeg = 0.0f);

 private:
  float qAngle_;
  float qBias_;
  float rMeasure_;

  float angleDeg_;
  float biasDegS_;
  float p00_;
  float p01_;
  float p10_;
  float p11_;
};
