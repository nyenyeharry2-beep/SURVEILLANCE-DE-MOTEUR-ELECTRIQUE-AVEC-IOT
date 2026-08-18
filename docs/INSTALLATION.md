# Installation

## Prérequis

- Python 3.10+
- Navigateur moderne
- Optionnel : Docker (broker MQTT), IDE Arduino + carte ESP32

## Mode démonstration (recommandé)

Depuis la racine du projet :

```bash
python3 -m venv .venv
source .venv/bin/activate   # Windows : .venv\Scripts\activate
pip install -r backend/requirements.txt
chmod +x scripts/start.sh
./scripts/start.sh
```

Puis ouvrir http://127.0.0.1:8000

## Broker MQTT

```bash
docker compose up -d mosquitto
```

Port 1883 (MQTT) et 9001 (WebSocket MQTT, debug).

Pour n’utiliser que l’ESP32 (pas le simulateur) :

```bash
USE_SIMULATOR=0 MQTT_HOST=127.0.0.1 ./scripts/start.sh
```

## Firmware ESP32

Bibliothèques Arduino :

- PubSubClient
- ArduinoJson
- OneWire
- DallasTemperature

1. Copier `firmware/esp32/` dans un sketch.
2. Éditer `config.h` : SSID, mot de passe, IP du PC qui héberge Mosquitto.
3. Régler `ZMPT_CALIBRATION` et l’offset ACS712 sur un multimètre.
4. Téléverser, ouvrir le moniteur série 115200 bauds si besoin.

## Windows / dossier Bureau

Copier tout le dossier du dépôt dans `Bureau\surveillance motor` (ou laisser le raccourci créé sur la machine de développement). Lancer `scripts/start.sh` dans Git Bash, ou :

```bat
.venv\Scripts\python -m uvicorn backend.app.main:app --host 0.0.0.0 --port 8000
```
