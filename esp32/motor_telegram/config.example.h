#pragma once

// ============================================================
//  CONFIGURATION — à remplir avant flash de l'ESP32
//  Copiez vers config.h (gitignoré) et renseignez vos valeurs.
// ============================================================

// ---- Wi-Fi ----
#define WIFI_SSID        "VOTRE_SSID"
#define WIFI_PASSWORD    "VOTRE_MOT_DE_PASSE"

// ---- Telegram ----
// @BotFather → /newbot → token
// Puis https://api.telegram.org/bot<TOKEN>/getUpdates → chat.id
#define TELEGRAM_BOT_TOKEN  "123456:ABC-DEF_votre_token"
#define TELEGRAM_CHAT_ID    "123456789"

// ---- Seuils d'alerte (côté ESP32) ----
#define TEMP_MAX_C        70.0f   // °C
#define CURRENT_MAX_A     8.0f    // A
#define VOLTAGE_MIN_V     180.0f  // V
#define VIB_ALERT_ENABLE  1       // 1 = alerte vibration ADXL345
#define VIB_MAG_MAX_G     0.35f   // |a|-1g au-delà → alerte (g)
