# Protocole UART Arduino Uno ↔ ESP32

**Lien physique :** Serial matériel  
**Baud :** 9600 8N1  
**Broches :** Uno TX/RX ↔ ESP32 GPIO17 (TX2) / GPIO16 (RX2)  
**Format :** une trame = une ligne terminée par `\n`

## 1. Télémétrie (Uno → ESP32)

Envoyée toutes les ~2 s, et aussi sur commande `STATUS`.

```json
{"c":1.23,"t":45.6,"v":220.1,"vib":0,"rpm":1450,"m":1}
```

| Champ | Type  | Unité | Description        |
|-------|-------|-------|--------------------|
| `c`   | float | A     | Courant moteur     |
| `t`   | float | °C    | Température        |
| `v`   | float | V     | Tension            |
| `vib` | int   | 0/1   | Vibration          |
| `rpm` | float | tr/min| Vitesse            |
| `m`   | int   | 0/1   | État relais moteur |

## 2. Événements (Uno → ESP32)

```json
{"evt":"UNO_READY"}
{"evt":"PONG"}
{"evt":"MOTOR_ON","ok":1}
{"evt":"MOTOR_OFF","ok":1}
{"evt":"SAFE_STOP"}
```

| Événement    | Signification                                      |
|--------------|----------------------------------------------------|
| `UNO_READY`  | Uno démarré                                        |
| `PONG`       | Réponse à `PING`                                   |
| `MOTOR_ON`   | Relais activé                                      |
| `MOTOR_OFF`  | Relais désactivé                                   |
| `SAFE_STOP`  | Coupure locale (température / courant hors seuil)  |

## 3. Commandes (ESP32 → Uno)

Lignes texte simples (sans JSON) :

| Commande     | Action                         |
|--------------|--------------------------------|
| `MOTOR_ON`   | Active le relais moteur        |
| `MOTOR_OFF`  | Coupe le relais                |
| `STATUS`     | Force un envoi de télémétrie   |
| `PING`       | Test de liaison → `PONG`       |

## 4. Flux Telegram

```
Utilisateur Telegram
        │  /status /on /off /ping
        ▼
     ESP32 (bot)
        │  UART commande
        ▼
   Arduino Uno
        │  JSON télémétrie / evt
        ▼
     ESP32
        │  message / alerte
        ▼
   Telegram chat
```
