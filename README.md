# Système de surveillance de moteur électrique avec IoT

Supervision en temps réel d’un moteur asynchrone : tension, courant, puissance, température, vibration, alertes et arrêt de protection.

Ce dépôt contient **tout le dossier projet** : firmware ESP32, backend, tableau de bord SCADA, simulateur (fonctionne sans carte), schéma, nomenclature, Docker et documentation.

## Architecture

```
Capteurs (ACS712, ZMPT101B, DS18B20, SW-420)
        │
     ESP32 (Wi-Fi)
        │ MQTT
   Mosquitto broker
        │
 Backend FastAPI  ── SQLite ── WebSocket
        │
 Tableau de bord SCADA (navigateur)
```

Sans matériel, le **simulateur Python** alimente le même tableau de bord (courant d’appel au démarrage, dérive thermique, défauts injectables).

## Démarrage rapide (sans ESP32)

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r backend/requirements.txt
./scripts/start.sh
```

Ouvrir [http://127.0.0.1:8000](http://127.0.0.1:8000)

Commandes du tableau de bord :

- Démarrer / Arrêter / Arrêt d’urgence / Réarmer
- Injecter un défaut : surcharge, surchauffe, roulement, sous-tension

## Démarrage avec MQTT (ESP32 réel)

```bash
docker compose up -d mosquitto
MQTT_HOST=127.0.0.1 USE_SIMULATOR=0 ./scripts/start.sh
```

Configurer `firmware/esp32/config.h` (SSID, mot de passe Wi-Fi, IP du broker) puis flasher `firmware/esp32/surveillance_moteur.ino` avec l’IDE Arduino ou PlatformIO.

## Contenu du dossier

| Chemin | Rôle |
| --- | --- |
| `firmware/esp32/` | Programme ESP32 (MQTT, relais, capteurs) |
| `backend/` | API, WebSocket, SQLite, simulateur |
| `dashboard/` | Interface SCADA |
| `hardware/` | Nomenclature et câblage |
| `docs/` | Architecture, installation, rapport |
| `mosquitto/` | Configuration du broker MQTT |
| `scripts/start.sh` | Lancement local |
| `tests/` | Tests des seuils et de la santé moteur |

## Seuils par défaut (moteur ~0,75 kW / 220 V)

| Grandeur | Alarme | Défaut |
| --- | --- | --- |
| Tension | hors 200–240 V | < 180 V |
| Courant | ≥ 8 A | ≥ 12 A |
| Température | ≥ 70 °C | ≥ 85 °C |
| Vibration | ≥ 60 % | ≥ 85 % |

En défaut, le firmware ouvre le relais (coupure moteur).

## API

- `GET /api/live` — dernière mesure
- `GET /api/history` — historique
- `GET /api/alerts` — journal d’alertes
- `POST /api/command/{action}` — `start`, `stop`, `emergency`, `reset`, `overload`, `overheat`, `bearing`, `undervoltage`, `none`
- `WS /ws` — télémétrie temps réel

## Tests

```bash
source .venv/bin/activate
python -m pytest -q
```
