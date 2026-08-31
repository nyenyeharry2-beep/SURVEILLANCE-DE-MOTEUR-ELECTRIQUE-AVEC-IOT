# Schéma de câblage — Surveillance moteur IoT

## Vue d'ensemble

```
                    ┌─────────────────────────────────────┐
                    │           ARDUINO UNO               │
                    │                                     │
  ACS712 ──────────►│ A0  (courant)                       │
  LM35 ────────────►│ A1  (température)                   │
  Module tension ──►│ A2  (tension)                       │
  Capteur IR RPM ──►│ D2  (vitesse, INT0)                 │
  SW-420 ──────────►│ D3  (vibration)                     │
  Relais moteur ◄───│ D8  (commande ON/OFF)               │
  LED statut ◄──────│ D13                                 │
                    │                                     │
                    │ TX (D1) ──────► RX2 (GPIO16) ESP32  │
                    │ RX (D0) ◄────── TX2 (GPIO17) ESP32  │
                    │ GND ─────────── GND                 │
                    └─────────────────────────────────────┘
                                      │
                                      │ UART 9600 baud
                                      ▼
                    ┌─────────────────────────────────────┐
                    │              ESP32                  │
                    │  Wi-Fi ──► Internet ──► Telegram    │
                    └─────────────────────────────────────┘
```

## Alimentation

| Composant     | Alimentation      | Notes                                      |
|---------------|-------------------|--------------------------------------------|
| Arduino Uno   | USB 5 V ou jack   | Ne pas alimenter le moteur via l'Arduino   |
| ESP32         | USB 5 V / 3.3 V   | Niveau logique 3.3 V                       |
| Relais        | 5 V               | Bobine sur D8, **contact** vers le moteur  |
| Moteur        | Alim. séparée     | Via contact NO du relais                   |
| ACS712        | 5 V               | Sortie analogique vers A0                  |

> **Important :** Arduino (5 V) et ESP32 (3.3 V) — pour une liaison série durable, utiliser un **diviseur de tension** sur la ligne TX Arduino → RX ESP32 (résistances 1 kΩ + 2 kΩ), ou un module level-shifter.

## Broches détaillées

### Arduino Uno

| Broche | Signal        | Composant              |
|--------|---------------|------------------------|
| A0     | CURRENT       | ACS712 5A/20A/30A      |
| A1     | TEMP          | LM35                   |
| A2     | VOLTAGE       | Module détecteur 0–25 V|
| D2     | RPM_PULSE     | Capteur IR / hall (INT0)|
| D3     | VIBRATION     | SW-420 (sortie DO)     |
| D8     | RELAY         | Module relais (IN)     |
| D13    | STATUS_LED    | LED intégrée           |
| D1/TX  | SERIAL_TX     | → GPIO16 ESP32         |
| D0/RX  | SERIAL_RX     | ← GPIO17 ESP32         |
| GND    | GND           | Masse commune          |

### ESP32

| Broche   | Signal     | Connexion              |
|----------|------------|------------------------|
| GPIO16   | RX2        | ← TX Arduino (via diviseur) |
| GPIO17   | TX2        | → RX Arduino           |
| GND      | GND        | Masse commune          |
| 3V3/5V   | Alim.      | USB                    |

## Diviseur de tension (TX Uno → RX ESP32)

```
Arduino TX (5 V) ───[1 kΩ]───┬─── ESP32 RX (≈3.3 V)
                             │
                           [2 kΩ]
                             │
                            GND
```

## Sécurité électrique

1. Ne jamais brancher directement un moteur secteur sur une broche Arduino/ESP32.
2. Utiliser un **relais** (ou SSR) dimensionné pour le courant moteur.
3. Prévoir une **protection** (fusible, disjoncteur) sur l'alimentation moteur.
4. Masse commune entre Uno et ESP32 uniquement pour le signal série — pas pour la puissance moteur.
