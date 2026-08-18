#ifndef CONFIG_H
#define CONFIG_H

// --- Wi-Fi ---
#define WIFI_SSID "VOTRE_WIFI"
#define WIFI_PASSWORD "VOTRE_MOT_DE_PASSE"

// --- MQTT ---
#define MQTT_BROKER "192.168.1.10"
#define MQTT_PORT 1883
#define MQTT_USER ""
#define MQTT_PASS ""
#define MQTT_CLIENT_ID "esp32-moteur-01"
#define MQTT_TOPIC_TELEMETRY "moteur/01/telemetry"
#define MQTT_TOPIC_CMD "moteur/01/cmd"
#define MQTT_TOPIC_STATUS "moteur/01/status"

// --- Materiel ---
#define PIN_VOLTAGE 34      // ZMPT101B (ADC)
#define PIN_CURRENT 35      // ACS712 20A (ADC)
#define PIN_VIBRATION 32    // SW-420 (digital) ou analog MPU
#define PIN_ONEWIRE 4       // DS18B20
#define PIN_RELAIS 26       // Relais de coupure (actif LOW selon module)
#define PIN_BUZZER 27
#define PIN_LED_OK 14
#define PIN_LED_ALARME 12

#define DEVICE_ID "moteur-01"
#define PUBLISH_INTERVAL_MS 1000

// Calibration analogique (a ajuster sur banc)
#define ADC_RESOLUTION 4095.0f
#define ADC_VREF 3.3f
#define ACS712_SENSITIVITY 0.100f   // 100 mV/A pour ACS712 20A
#define ACS712_OFFSET_V 1.65f
#define ZMPT_CALIBRATION 220.0f     // tension RMS de reference

#endif
