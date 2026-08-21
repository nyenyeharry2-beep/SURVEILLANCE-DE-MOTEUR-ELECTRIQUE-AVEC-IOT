#pragma once

#include <Arduino.h>
#include <cstdint>

// =============================================================================
// Mapping des broches — conforme au câblage FS-iA6b / ESC / GPS / I2C
// =============================================================================

// ESC (sorties PWM 50 Hz, impulsions 1000–2000 µs)
constexpr uint8_t MOTOR_FL_PIN = 18;  // Avant Gauche
constexpr uint8_t MOTOR_FR_PIN = 19;  // Avant Droit
constexpr uint8_t MOTOR_RL_PIN = 22;  // Arrière Gauche
constexpr uint8_t MOTOR_RR_PIN = 23;  // Arrière Droit

// Récepteur FS-iA6b (entrées PWM — largeur d'impulsion)
constexpr uint8_t RC_ROLL_PIN = 13;     // CH1 Aileron / Roll
constexpr uint8_t RC_PITCH_PIN = 12;    // CH2 Profondeur / Pitch
constexpr uint8_t RC_THROTTLE_PIN = 14; // CH3 Gaz / Throttle
constexpr uint8_t RC_YAW_PIN = 27;      // CH4 Lacet / Yaw
constexpr uint8_t RC_MODE_PIN = 26;     // CH5 sélecteur MANUEL / AUTO_LAND

// Bus I2C (MPU6050/9250 + VL53L0X)
constexpr uint8_t I2C_SDA_PIN = 2;
constexpr uint8_t I2C_SCL_PIN = 21;

// GPS NEO-6M (UART2)
constexpr uint8_t GPS_RX_PIN = 16;  // RX ESP32 ← TX GPS
constexpr uint8_t GPS_TX_PIN = 17;  // TX ESP32 → RX GPS
constexpr uint32_t GPS_BAUD = 9600;

// =============================================================================
// Paramètres ESC et boucle de contrôle
// =============================================================================

constexpr uint16_t ESC_MIN_US = 1000;
constexpr uint16_t ESC_MAX_US = 2000;
constexpr uint16_t ESC_IDLE_US = 1000;

constexpr uint32_t CONTROL_LOOP_HZ = 400;
constexpr uint32_t RC_POLL_HZ = 100;
constexpr uint32_t TELEMETRY_HZ = 10;

// =============================================================================
// Signaux radio — plages PWM standard (µs)
// =============================================================================

constexpr uint16_t RC_MIN_US = 1000;
constexpr uint16_t RC_MAX_US = 2000;
constexpr uint16_t RC_CENTER_US = 1500;
constexpr uint16_t RC_MODE_AUTO_THRESHOLD_US = 1500;

constexpr uint32_t RC_FAILSAFE_TIMEOUT_MS = 500;

// =============================================================================
// Gains PID — à ajuster selon le châssis et les hélices
// =============================================================================

struct PidGains {
  float kp;
  float ki;
  float kd;
};

constexpr PidGains ROLL_GAINS = {4.5f, 0.0f, 0.6f};
constexpr PidGains PITCH_GAINS = {4.5f, 0.0f, 0.6f};
constexpr PidGains YAW_GAINS = {3.0f, 0.0f, 0.2f};

constexpr float MAX_ANGLE_DEG = 25.0f;
constexpr float MAX_YAW_RATE_DEG_S = 120.0f;

// =============================================================================
// Atterrissage automatique (VL53L0X)
// =============================================================================

constexpr float LAND_DESCENT_RATE_M_S = 0.35f;
constexpr float LAND_GROUND_THRESHOLD_M = 0.05f;  // 5 cm
constexpr float LAND_HOVER_THROTTLE = 0.45f;
constexpr float LAND_MIN_THROTTLE = 0.15f;

// =============================================================================
// Filtre de Kalman — bruit processus / mesure (accéléromètre)
// =============================================================================

constexpr float KALMAN_Q_ANGLE = 0.001f;
constexpr float KALMAN_Q_BIAS = 0.003f;
constexpr float KALMAN_R_MEASURE = 0.03f;
