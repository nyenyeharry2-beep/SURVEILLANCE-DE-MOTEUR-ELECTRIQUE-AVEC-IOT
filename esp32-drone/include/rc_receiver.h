#pragma once

#include <cstdint>

enum class FlightMode : uint8_t {
  MANUAL = 0,
  AUTO_LAND = 1,
};

struct RcInput {
  float rollSetDeg;
  float pitchSetDeg;
  float throttle01;
  float yawRateSetDegS;
  FlightMode mode;
  bool valid;
  bool failsafe;
};

class RcReceiver {
 public:
  void begin();
  void poll();
  const RcInput& input() const { return input_; }

 private:
  RcInput input_{};
  uint32_t lastValidMs_ = 0;

  uint16_t readPulseUs(uint8_t pin) const;
  float mapChannelToAngle(uint16_t pulseUs) const;
  float mapChannelToThrottle(uint16_t pulseUs) const;
  float mapChannelToYawRate(uint16_t pulseUs) const;
  FlightMode mapChannelToMode(uint16_t pulseUs) const;
  bool isPulseValid(uint16_t pulseUs) const;
};
