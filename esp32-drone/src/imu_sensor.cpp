#include "imu_sensor.h"

#include <Adafruit_MPU6050.h>
#include <Adafruit_Sensor.h>
#include <Wire.h>

#include "config.h"
#include "kalman_angle.h"

namespace {
Adafruit_MPU6050 mpu;
KalmanAngle rollKalman(KALMAN_Q_ANGLE, KALMAN_Q_BIAS, KALMAN_R_MEASURE);
KalmanAngle pitchKalman(KALMAN_Q_ANGLE, KALMAN_Q_BIAS, KALMAN_R_MEASURE);

float accelToRollDeg(float ax, float ay, float az) {
  return atan2f(ay, az) * 180.0f / PI;
}

float accelToPitchDeg(float ax, float ay, float az) {
  return atan2f(-ax, sqrtf(ay * ay + az * az)) * 180.0f / PI;
}
}  // namespace

bool ImuSensor::begin() {
  rollFilter_ = &rollKalman;
  pitchFilter_ = &pitchKalman;

  Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
  Wire.setClock(400000);

  if (!mpu.begin(0x68, &Wire)) {
    Serial.println("[IMU] ERREUR: MPU6050 introuvable sur I2C");
    ready_ = false;
    return false;
  }

  mpu.setAccelerometerRange(MPU6050_RANGE_8_G);
  mpu.setGyroRange(MPU6050_RANGE_500_DEG);
  mpu.setFilterBandwidth(MPU6050_BAND_94_HZ);

  sensors_event_t accel;
  sensors_event_t gyro;
  sensors_event_t temp;
  mpu.getEvent(&accel, &gyro, &temp);

  const float initRoll = accelToRollDeg(accel.acceleration.x, accel.acceleration.y, accel.acceleration.z);
  const float initPitch = accelToPitchDeg(accel.acceleration.x, accel.acceleration.y, accel.acceleration.z);
  rollFilter_->reset(initRoll);
  pitchFilter_->reset(initPitch);

  state_.yawDeg = 0.0f;
  ready_ = true;
  Serial.println("[IMU] MPU6050 initialise — filtre de Kalman actif");
  return true;
}

bool ImuSensor::update(float dtSeconds) {
  if (!ready_) {
    return false;
  }

  sensors_event_t accel;
  sensors_event_t gyro;
  sensors_event_t temp;
  mpu.getEvent(&accel, &gyro, &temp);

  const float accelRoll = accelToRollDeg(accel.acceleration.x, accel.acceleration.y, accel.acceleration.z);
  const float accelPitch = accelToPitchDeg(accel.acceleration.x, accel.acceleration.y, accel.acceleration.z);

  const float gyroRollRate = gyro.gyro.x * 180.0f / PI;
  const float gyroPitchRate = gyro.gyro.y * 180.0f / PI;
  const float gyroYawRate = gyro.gyro.z * 180.0f / PI;

  state_.rollDeg = rollFilter_->update(accelRoll, gyroRollRate, dtSeconds);
  state_.pitchDeg = pitchFilter_->update(accelPitch, gyroPitchRate, dtSeconds);
  state_.yawDeg += gyroYawRate * dtSeconds;

  if (state_.yawDeg > 180.0f) {
    state_.yawDeg -= 360.0f;
  } else if (state_.yawDeg < -180.0f) {
    state_.yawDeg += 360.0f;
  }

  state_.rollRateDegS = gyroRollRate;
  state_.pitchRateDegS = gyroPitchRate;
  state_.yawRateDegS = gyroYawRate;

  return true;
}
