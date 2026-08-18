# Architecture du système

## Objectif

Surveiller en continu un moteur électrique (typiquement asynchrone monophasé 220 V / 0,75 kW) et déclencher une alarme ou une coupure si un paramètre sort des limites.

## Chaîne d’acquisition

1. **Tension** : capteur ZMPT101B → ADC ESP32 (GPIO34), calcul RMS.
2. **Courant** : ACS712 20 A → ADC ESP32 (GPIO35), calcul RMS.
3. **Température** : DS18B20 (OneWire, GPIO4) posé sur carcasse ou palier.
4. **Vibration** : SW-420 (GPIO32) — taux de bascules sur 50 ms, échelle 0–100 %.
5. **Actionneur** : module relais 5 V / 10 A (GPIO26) pour coupure d’alimentation moteur.
6. **Signalisation** : LED OK, LED alarme, buzzer.

La puissance active approximée est `P = U × I × 0,85` (facteur de puissance typique).

## Communications

- Publication MQTT `moteur/01/telemetry` toutes les 1 s (JSON).
- Commandes `moteur/01/cmd` : `{"action":"stop"}`, `{"action":"start"}`, `{"action":"reset"}`.
- Le backend FastAPI souscrit au broker, stocke SQLite, pousse le navigateur via WebSocket.
- Si le broker est absent, le simulateur local continue d’alimenter le SCADA.

## Décision

Le module `backend/app/models.py` calcule un **score de santé** 0–100 et un statut `running | starting | alarm | fault | stopped`. Les mêmes règles sont dupliquées côté firmware pour la protection locale (coupure même si le Wi-Fi tombe).

## Données JSON

```json
{
  "device_id": "moteur-01",
  "ts": 1710000000,
  "voltage": 221.4,
  "current": 3.21,
  "power": 638.2,
  "temperature": 42.1,
  "vibration": 18.5,
  "rpm": 1450,
  "relay": true,
  "status": "running"
}
```
