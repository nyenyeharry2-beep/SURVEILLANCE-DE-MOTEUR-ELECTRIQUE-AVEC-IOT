#pragma once

#include <Arduino.h>
#include <cstdint>

// Broches moteurs (signaux PWM vers ESC) — avant-gauche, avant-droit, arrière-droit, arrière-gauche
constexpr uint8_t MOTOR_FL_PIN = 25;
constexpr uint8_t MOTOR_FR_PIN = 26;
constexpr uint8_t MOTOR_RR_PIN = 27;
constexpr uint8_t MOTOR_RL_PIN = 14;

// I2C pour MPU6050
constexpr uint8_t I2C_SDA_PIN = 21;
constexpr uint8_t I2C_SCL_PIN = 22;

// Broche d'armement (mettre à GND pour armer via bouton)
constexpr uint8_t ARM_PIN = 32;

// Paramètres PWM ESC (microsecondes)
constexpr uint16_t ESC_MIN_US = 1000;
constexpr uint16_t ESC_MAX_US = 2000;
constexpr uint16_t ESC_IDLE_US = 1000;

// Boucle de contrôle
constexpr uint32_t CONTROL_LOOP_HZ = 250;
constexpr uint32_t TELEMETRY_HZ = 20;

// Filtre complémentaire (0.0–1.0, plus haut = plus de confiance en le gyro)
constexpr float COMPLEMENTARY_ALPHA = 0.98f;

// Gains PID — à ajuster selon votre châssis
struct PidGains {
  float kp;
  float ki;
  float kd;
};

constexpr PidGains ROLL_GAINS  = {4.5f, 0.0f, 0.6f};
constexpr PidGains PITCH_GAINS = {4.5f, 0.0f, 0.6f};
constexpr PidGains YAW_GAINS   = {3.0f, 0.0f, 0.2f};

// Limites de consigne (degrés / throttle 0–1)
constexpr float MAX_ANGLE_DEG = 25.0f;
constexpr float MAX_YAW_RATE_DEG_S = 120.0f;

// WiFi — modifier avant compilation
constexpr char WIFI_SSID[] = "DroneControl";
constexpr char WIFI_PASS[] = "drone1234";
constexpr uint16_t TELEMETRY_PORT = 4210;
