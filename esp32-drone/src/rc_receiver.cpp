#include "rc_receiver.h"

#include "config.h"

void RcReceiver::begin() {
  pinMode(RC_ROLL_PIN, INPUT);
  pinMode(RC_PITCH_PIN, INPUT);
  pinMode(RC_THROTTLE_PIN, INPUT);
  pinMode(RC_YAW_PIN, INPUT);
  pinMode(RC_MODE_PIN, INPUT);

  input_.valid = false;
  input_.failsafe = true;
  lastValidMs_ = millis();

  Serial.println("[RC] Recepteur FS-iA6b pret (CH1-13, CH2-12, CH3-14, CH4-27, CH5-26)");
}

uint16_t RcReceiver::readPulseUs(uint8_t pin) const {
  const uint32_t pulse = pulseIn(pin, HIGH, 25000);
  return static_cast<uint16_t>(pulse);
}

bool RcReceiver::isPulseValid(uint16_t pulseUs) const {
  return pulseUs >= 900 && pulseUs <= 2100;
}

float RcReceiver::mapChannelToAngle(uint16_t pulseUs) const {
  const float normalized = (static_cast<float>(pulseUs) - RC_CENTER_US) / (RC_MAX_US - RC_CENTER_US);
  return normalized * MAX_ANGLE_DEG;
}

float RcReceiver::mapChannelToThrottle(uint16_t pulseUs) const {
  if (pulseUs <= RC_MIN_US) {
    return 0.0f;
  }
  const float value = (static_cast<float>(pulseUs) - RC_MIN_US) / (RC_MAX_US - RC_MIN_US);
  if (value < 0.0f) {
    return 0.0f;
  }
  if (value > 1.0f) {
    return 1.0f;
  }
  return value;
}

float RcReceiver::mapChannelToYawRate(uint16_t pulseUs) const {
  const float normalized = (static_cast<float>(pulseUs) - RC_CENTER_US) / (RC_MAX_US - RC_CENTER_US);
  return normalized * MAX_YAW_RATE_DEG_S;
}

FlightMode RcReceiver::mapChannelToMode(uint16_t pulseUs) const {
  return (pulseUs >= RC_MODE_AUTO_THRESHOLD_US) ? FlightMode::AUTO_LAND : FlightMode::MANUAL;
}

void RcReceiver::poll() {
  const uint16_t rollUs = readPulseUs(RC_ROLL_PIN);
  const uint16_t pitchUs = readPulseUs(RC_PITCH_PIN);
  const uint16_t throttleUs = readPulseUs(RC_THROTTLE_PIN);
  const uint16_t yawUs = readPulseUs(RC_YAW_PIN);
  const uint16_t modeUs = readPulseUs(RC_MODE_PIN);

  const bool allValid = isPulseValid(rollUs) && isPulseValid(pitchUs) && isPulseValid(throttleUs) &&
                        isPulseValid(yawUs) && isPulseValid(modeUs);

  if (allValid) {
    input_.rollSetDeg = mapChannelToAngle(rollUs);
    input_.pitchSetDeg = mapChannelToAngle(pitchUs);
    input_.throttle01 = mapChannelToThrottle(throttleUs);
    input_.yawRateSetDegS = mapChannelToYawRate(yawUs);
    input_.mode = mapChannelToMode(modeUs);
    input_.valid = true;
    input_.failsafe = false;
    lastValidMs_ = millis();
  } else {
    input_.valid = false;
    if (millis() - lastValidMs_ > RC_FAILSAFE_TIMEOUT_MS) {
      input_.failsafe = true;
    }
  }
}
