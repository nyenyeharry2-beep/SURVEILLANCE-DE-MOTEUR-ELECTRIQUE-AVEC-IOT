#pragma once

// ============================================================
//  CONFIGURATION — à remplir avant flash de l'ESP32
//  Ne committez PAS de vrais secrets sur un dépôt public.
//  Copiez ce fichier vers config.h et renseignez vos valeurs
//  (config.example.h est le modèle versionné).
// ============================================================

// ---- Wi-Fi ----
#define WIFI_SSID        "VOTRE_SSID"
#define WIFI_PASSWORD    "VOTRE_MOT_DE_PASSE"

// ---- Telegram ----
// 1) Ouvrir Telegram → @BotFather → /newbot → récupérer le token
// 2) Écrire au bot, puis ouvrir :
//    https://api.telegram.org/bot<TOKEN>/getUpdates
//    pour lire votre chat.id (nombre, parfois négatif pour un groupe)
#define TELEGRAM_BOT_TOKEN  "123456:ABC-DEF_votre_token"
#define TELEGRAM_CHAT_ID    "123456789"

// ---- Seuils d'alerte (côté ESP32) ----
#define TEMP_MAX_C        70.0f   // °C
#define CURRENT_MAX_A     8.0f    // A
#define VOLTAGE_MIN_V     180.0f  // V (0 = désactiver si pas de secteur)
#define VIB_ALERT_ENABLE  1       // 1 = alerte vibration
