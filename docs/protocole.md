# Protocole UART Arduino Uno ↔ ESP32

**Baud :** 9600 8N1 — Uno TX/RX ↔ ESP32 GPIO16/17 — une ligne JSON + `\n`

## 1. Télémétrie (Uno → ESP32)

Toutes les ~2 s, ou sur `STATUS`.

```json
{"c":1.23,"t":45.6,"v":220.1,"vib":0,"ax":0.020,"ay":-0.015,"az":0.980,"mag":0.025,"rpm":1450,"m":1}
```

| Champ | Type  | Unité | Source / description                |
|-------|-------|-------|-------------------------------------|
| `c`   | float | A     | ACS712                              |
| `t`   | float | °C    | LM35                                |
| `v`   | float | V     | Module tension                      |
| `vib` | int   | 0/1   | 1 si `mag` ≥ seuil ADXL345          |
| `ax`  | float | g     | ADXL345 axe X                       |
| `ay`  | float | g     | ADXL345 axe Y                       |
| `az`  | float | g     | ADXL345 axe Z                       |
| `mag` | float | g     | `‖a‖ − 1` (composante vibratoire)   |
| `rpm` | float | tr/min| Capteur **IR 3 pins** (pulses/s)    |
| `m`   | int   | 0/1   | Relais moteur                       |

## 2. Événements (Uno → ESP32)

```json
{"evt":"UNO_READY","adxl":1,"ir":1}
{"evt":"PONG"}
{"evt":"MOTOR_ON","ok":1}
{"evt":"MOTOR_OFF","ok":1}
{"evt":"SAFE_STOP"}
```

| Événement   | Signification                                                |
|-------------|--------------------------------------------------------------|
| `UNO_READY` | Démarrage ; `adxl` 1/0 = ADXL345 détecté ou non              |
| `PONG`      | Réponse à `PING`                                             |
| `SAFE_STOP` | Coupure locale (température, courant **ou vibration**)       |

## 3. Commandes (ESP32 → Uno)

| Commande   | Action                |
|------------|-----------------------|
| `MOTOR_ON` | Relais ON             |
| `MOTOR_OFF`| Relais OFF            |
| `STATUS`   | Force une télémétrie  |
| `PING`     | → `PONG`              |
