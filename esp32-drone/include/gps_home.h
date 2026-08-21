#pragma once

#include <Arduino.h>

struct HomePosition {
  double latitude;
  double longitude;
  double altitudeM;
  bool locked;
};

class GpsHome {
 public:
  void begin();
  void poll();
  bool waitForLock(uint32_t timeoutMs);
  const HomePosition& home() const { return home_; }

 private:
  HomePosition home_{};
  class HardwareSerial* gpsSerial_ = nullptr;
};
