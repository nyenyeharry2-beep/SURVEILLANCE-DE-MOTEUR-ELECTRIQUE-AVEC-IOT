# Firmware ESP32

Sketch Arduino : `surveillance_moteur.ino` + `config.h`.

## Bibliothèques

Croquis → Inclure une bibliothèque → Gérer les bibliothèques :

- **PubSubClient** (Nick O’Leary)
- **ArduinoJson** (Benoit Blanchon)
- **OneWire**
- **DallasTemperature**

Carte : ESP32 Dev Module, vitesse 115200.

## Configuration minimale

Dans `config.h` :

- `WIFI_SSID` / `WIFI_PASSWORD`
- `MQTT_BROKER` : adresse IPv4 du PC qui lance Mosquitto (pas `127.0.0.1` depuis l’ESP32)
- Calibration ACS712 / ZMPT après essai au multimètre
