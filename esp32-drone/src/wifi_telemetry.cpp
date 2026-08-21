#include "wifi_telemetry.h"

#include <cstring>

namespace {
constexpr uint32_t CONTROL_TIMEOUT_MS = 500;

float clampFloat(float value, float minValue, float maxValue) {
  if (value < minValue) {
    return minValue;
  }
  if (value > maxValue) {
    return maxValue;
  }
  return value;
}
}  // namespace

void WifiTelemetry::begin() {
  WiFi.mode(WIFI_AP);
  WiFi.softAP(WIFI_SSID, WIFI_PASS);

  udp_.begin(TELEMETRY_PORT);

  input_.throttle01 = 0.0f;
  input_.rollSetDeg = 0.0f;
  input_.pitchSetDeg = 0.0f;
  input_.yawRateSet = 0.0f;
  input_.armRequest = false;

  Serial.print("[WIFI] AP actif: ");
  Serial.println(WiFi.softAPIP());
  Serial.printf("[WIFI] Port UDP: %u\n", TELEMETRY_PORT);
}

ControlInput WifiTelemetry::readControlInput() const {
  return input_;
}

void WifiTelemetry::sendTelemetry(const FlightController& fc) {
  const ImuState& imu = fc.imuState();

  char payload[128];
  snprintf(payload, sizeof(payload), "R:%.2f,P:%.2f,Y:%.2f,T:%.2f,A:%d\n", imu.rollDeg,
           imu.pitchDeg, imu.yawRateDegS, input_.throttle01, fc.isArmed() ? 1 : 0);

  if (remotePort_ != 0) {
    udp_.beginPacket(remoteIp_, remotePort_);
    udp_.write(reinterpret_cast<const uint8_t*>(payload), strlen(payload));
    udp_.endPacket();
  }
}

void WifiTelemetry::poll() {
  const int packetSize = udp_.parsePacket();
  if (packetSize <= 0) {
    if (millis() - lastPacketMs_ > CONTROL_TIMEOUT_MS) {
      input_.throttle01 = 0.0f;
      input_.armRequest = false;
    }
    return;
  }

  remoteIp_ = udp_.remoteIP();
  remotePort_ = udp_.remotePort();

  char buffer[128];
  const int length = udp_.read(buffer, sizeof(buffer) - 1);
  if (length <= 0) {
    return;
  }

  parsePacket(buffer, length);
}

void WifiTelemetry::parsePacket(const char* buffer, int length) {
  // Format attendu: "T:0.35,R:0,P:0,Y:0,A:1"
  input_.throttle01 = 0.0f;
  input_.rollSetDeg = 0.0f;
  input_.pitchSetDeg = 0.0f;
  input_.yawRateSet = 0.0f;
  input_.armRequest = false;

  char localBuffer[128];
  if (length >= static_cast<int>(sizeof(localBuffer))) {
    length = sizeof(localBuffer) - 1;
  }
  memcpy(localBuffer, buffer, length);
  localBuffer[length] = '\0';

  char* token = strtok(localBuffer, ",");
  while (token != nullptr) {
    if (token[0] == 'T' && token[1] == ':') {
      input_.throttle01 = clampFloat(atof(token + 2), 0.0f, 1.0f);
    } else if (token[0] == 'R' && token[1] == ':') {
      input_.rollSetDeg = clampFloat(atof(token + 2), -MAX_ANGLE_DEG, MAX_ANGLE_DEG);
    } else if (token[0] == 'P' && token[1] == ':') {
      input_.pitchSetDeg = clampFloat(atof(token + 2), -MAX_ANGLE_DEG, MAX_ANGLE_DEG);
    } else if (token[0] == 'Y' && token[1] == ':') {
      input_.yawRateSet = clampFloat(atof(token + 2), -MAX_YAW_RATE_DEG_S, MAX_YAW_RATE_DEG_S);
    } else if (token[0] == 'A' && token[1] == ':') {
      input_.armRequest = (atoi(token + 2) == 1);
    }
    token = strtok(nullptr, ",");
  }

  lastPacketMs_ = millis();
}
