#pragma once

// ============================================================
//  CONFIG — copier vers config.h (gitignoré)
// ============================================================

#define WIFI_SSID        "VOTRE_SSID"
#define WIFI_PASSWORD    "VOTRE_MOT_DE_PASSE"

#define TELEGRAM_BOT_TOKEN  "123456:ABC-DEF_votre_token"

// Chat administrateur : tableau de bord + ON/OFF + historique + urgence
#define TELEGRAM_ADMIN_CHAT_ID  "123456789"

// Chat observateur (optionnel) : mêmes métriques, sans commandes moteur
// Laisser vide "" pour désactiver
#define TELEGRAM_VIEWER_CHAT_ID  ""

// Seuils (référence / docs — les seuils runtime sont aussi côté Uno)
#define TEMP_MAX_C        70.0f
#define CURRENT_MAX_A     8.0f
#define VOLTAGE_MIN_V     180.0f
#define VIB_ALERT_ENABLE  1
#define VIB_MAG_MAX_G     0.35f
