#pragma once

#include <WiFi.h>
#include <WiFiUdp.h>
#include "flight_controller.h"

class WifiTelemetry {
 public:
  void begin();
  void poll();
  ControlInput readControlInput() const;
  void sendTelemetry(const FlightController& fc);

 private:
  WiFiUDP udp_;
  ControlInput input_{};
  IPAddress remoteIp_{};
  uint16_t remotePort_ = 0;
  unsigned long lastPacketMs_ = 0;

  void parsePacket(const char* buffer, int length);
};
