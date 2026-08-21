#include "imu_sensor.h"

#include <Adafruit_MPU6050.h>
#include <Adafruit_Sensor.h>
#include <Wire.h>

#include "config.h"

namespace {
Adafruit_MPU6050 mpu;
float rollDeg_ = 0.0f;
float pitchDeg_ = 0.0f;
}  // namespace

bool ImuSensor::begin() {
  Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
  Wire.setClock(400000);

  if (!mpu.begin(0x68, &Wire)) {
    Serial.println("[IMU] MPU6050 introuvable");
    ready_ = false;
    return false;
  }

  mpu.setAccelerometerRange(MPU6050_RANGE_4_G);
  mpu.setGyroRange(MPU6050_RANGE_500_DEG);
  mpu.setFilterBandwidth(MPU6050_BAND_94_HZ);

  ready_ = true;
  lastMicros_ = micros();
  Serial.println("[IMU] MPU6050 initialise");
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

  const float accelRoll = atan2f(accel.acceleration.y, accel.acceleration.z) * 57.2957795f;
  const float accelPitch =
      atan2f(-accel.acceleration.x,
             sqrtf(accel.acceleration.y * accel.acceleration.y +
                   accel.acceleration.z * accel.acceleration.z)) *
      57.2957795f;

  const float gyroRollRate = gyro.gyro.x * 57.2957795f;
  const float gyroPitchRate = gyro.gyro.y * 57.2957795f;

  rollDeg_ = (COMPLEMENTARY_ALPHA * (rollDeg_ + gyroRollRate * dtSeconds)) +
             ((1.0f - COMPLEMENTARY_ALPHA) * accelRoll);
  pitchDeg_ = (COMPLEMENTARY_ALPHA * (pitchDeg_ + gyroPitchRate * dtSeconds)) +
              ((1.0f - COMPLEMENTARY_ALPHA) * accelPitch);

  state_.rollDeg = rollDeg_;
  state_.pitchDeg = pitchDeg_;
  state_.yawRateDegS = gyro.gyro.z * 57.2957795f;
  state_.accelZ = accel.acceleration.z;

  (void)lastMicros_;
  return true;
}
