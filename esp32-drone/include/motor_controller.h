#pragma once

#include <cstdint>

class MotorController {
 public:
  void begin();
  void disarm();
  void kill();  // Arrêt d'urgence — coupe immédiatement tous les ESC
  bool isArmed() const { return armed_; }
  void setOutputs(float throttle01, float rollCmd, float pitchCmd, float yawCmd);

 private:
  bool armed_ = false;

  uint16_t mixMotor(float throttle01, float roll, float pitch, float yaw, float signRoll,
                    float signPitch, float signYaw) const;
  void writePwm(uint8_t channel, uint16_t pulseUs);
};
