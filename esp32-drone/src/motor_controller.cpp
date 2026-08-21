#include "motor_controller.h"

#include "config.h"

namespace {
constexpr uint32_t ESC_PWM_FREQ_HZ = 50;
constexpr uint8_t ESC_PWM_RES_BITS = 14;
constexpr uint32_t ESC_PWM_MAX_DUTY = (1UL << ESC_PWM_RES_BITS) - 1UL;
constexpr uint32_t ESC_PWM_PERIOD_US = 20000UL;

uint8_t channelForPin(uint8_t pin) {
  static uint8_t nextChannel = 0;
  ledcSetup(nextChannel, ESC_PWM_FREQ_HZ, ESC_PWM_RES_BITS);
  ledcAttachPin(pin, nextChannel);
  return nextChannel++;
}

uint8_t flChannel = 0;
uint8_t frChannel = 0;
uint8_t rlChannel = 0;
uint8_t rrChannel = 0;
}  // namespace

void MotorController::begin() {
  flChannel = channelForPin(MOTOR_FL_PIN);
  frChannel = channelForPin(MOTOR_FR_PIN);
  rlChannel = channelForPin(MOTOR_RL_PIN);
  rrChannel = channelForPin(MOTOR_RR_PIN);

  disarm();
  Serial.println("[MOTOR] ESC initialises (FL=18, FR=19, RL=22, RR=23)");
}

void MotorController::disarm() {
  armed_ = false;
  writePwm(flChannel, ESC_IDLE_US);
  writePwm(frChannel, ESC_IDLE_US);
  writePwm(rlChannel, ESC_IDLE_US);
  writePwm(rrChannel, ESC_IDLE_US);
}

void MotorController::kill() {
  disarm();
  Serial.println("[MOTOR] KILL SWITCH — moteurs arretes");
}

void MotorController::setOutputs(float throttle01, float rollCmd, float pitchCmd, float yawCmd) {
  if (throttle01 < 0.05f) {
    disarm();
    return;
  }

  armed_ = true;

  if (throttle01 > 1.0f) {
    throttle01 = 1.0f;
  }

  // Mixage quadrirotor en X — ajuster les signes selon le sens de rotation des moteurs
  const uint16_t fl = mixMotor(throttle01, rollCmd, pitchCmd, yawCmd, -1.0f, +1.0f, +1.0f);
  const uint16_t fr = mixMotor(throttle01, rollCmd, pitchCmd, yawCmd, +1.0f, +1.0f, -1.0f);
  const uint16_t rr = mixMotor(throttle01, rollCmd, pitchCmd, yawCmd, +1.0f, -1.0f, +1.0f);
  const uint16_t rl = mixMotor(throttle01, rollCmd, pitchCmd, yawCmd, -1.0f, -1.0f, -1.0f);

  writePwm(flChannel, fl);
  writePwm(frChannel, fr);
  writePwm(rlChannel, rl);
  writePwm(rrChannel, rr);
}

uint16_t MotorController::mixMotor(float throttle01, float roll, float pitch, float yaw, float signRoll,
                                   float signPitch, float signYaw) const {
  const float base = ESC_MIN_US + (throttle01 * (ESC_MAX_US - ESC_MIN_US));
  const float mixed = base + (signRoll * roll) + (signPitch * pitch) + (signYaw * yaw);

  if (mixed < static_cast<float>(ESC_MIN_US)) {
    return ESC_MIN_US;
  }
  if (mixed > static_cast<float>(ESC_MAX_US)) {
    return ESC_MAX_US;
  }
  return static_cast<uint16_t>(mixed);
}

void MotorController::writePwm(uint8_t channel, uint16_t pulseUs) {
  const uint32_t duty = (static_cast<uint32_t>(pulseUs) * ESC_PWM_MAX_DUTY) / ESC_PWM_PERIOD_US;
  ledcWrite(channel, duty);
}
