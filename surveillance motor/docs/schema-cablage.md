# Schéma de câblage — Surveillance moteur IoT

## Vue d'ensemble

```
                    ┌─────────────────────────────────────┐
                    │           ARDUINO UNO               │
                    │                                     │
  ACS712 ──────────►│ A0  (courant)                       │
  LM35 ────────────►│ A1  (température)                   │
  Module tension ──►│ A2  (tension)                       │
                    │                                     │
  IR 3 pins OUT ───►│ D2  (RPM, INT0)                     │
  IR VCC ──────────►│ 5V                                  │
  IR GND ──────────►│ GND                                 │
                    │                                     │
  ADXL345 SDA ─────►│ A4  (I2C)                           │
  ADXL345 SCL ─────►│ A5  (I2C)                           │
  ADXL345 VCC ─────►│ 3.3V  (pas 5V si module sans régul.)│
  ADXL345 GND ─────►│ GND                                 │
                    │                                     │
  Relais moteur ◄───│ D8                                  │
  LED statut ◄──────│ D13                                 │
                    │                                     │
                    │ TX (D4) ──diviseur──► GPIO16 ESP32  │
                    │ RX (D3) ◄──────────── GPIO17 ESP32  │
                    │ GND ───────────────── GND           │
                    └─────────────────────────────────────┘
                                      │ UART 9600 (SoftSerial)
                                      ▼
                                 ESP32 → Telegram
```

## Capteur IR 3 broches (RPM)

Module obstacle / réflexion typique (souvent bleu) :

| Broche module | Arduino Uno | Rôle                          |
|---------------|-------------|-------------------------------|
| **VCC**       | 5V          | Alimentation                  |
| **GND**       | GND         | Masse                         |
| **OUT**       | **D2**      | Signal digital (interruption) |

### Montage mécanique RPM

1. Coller **1 bande réfléchissante** (ou plus) sur l’arbre / un disque.
2. Placer le module IR face à la zone (distance ~1–5 cm).
3. Régler le **potentiomètre** du module jusqu’à ce que la LED bascule clairement à chaque passage.
4. Dans le code : `PULSES_PER_REV` = nombre de marques par tour.

> Polarité : beaucoup de modules sortent **LOW** à la détection → ISR en `FALLING`. Si le vôtre est actif HIGH, passer l’ISR en `RISING` dans `motor_monitor.ino`.

## Accéléromètre ADXL345 (vibration)

Bus **I2C** (Uno) :

| Broche ADXL345 | Arduino Uno | Notes                                      |
|----------------|-------------|--------------------------------------------|
| **VCC**        | **3.3V**    | Obligatoire si pas de régulateur sur le module |
| **GND**        | GND         |                                            |
| **SDA**        | **A4**      |                                            |
| **SCL**        | **A5**      |                                            |
| **SDO / ALT**  | GND         | Adresse I2C **0x53** (défaut du sketch)    |
| **CS**         | 3.3V        | Mode I2C (sur certains breakouts)          |

Adresse alternative `0x1D` si SDO → VCC : modifier `ADXL345_ADDR` dans le sketch.

Mesure utilisée : `mag = ||a|| − 1g` (écart à la gravité). Si `mag ≥ VIB_MAG_ALARM` → `vib=1` + éventuel `SAFE_STOP`.

## Autres broches

| Broche | Signal     | Composant              |
|--------|------------|------------------------|
| A0     | CURRENT    | ACS712                 |
| A1     | TEMP       | LM35                   |
| A2     | VOLTAGE    | Module 0–25 V          |
| D3     | ESP_RX     | ← ESP32 GPIO17 (TX2)   |
| D4     | ESP_TX     | → ESP32 GPIO16 (RX2) via diviseur |
| D8     | RELAY      | Module relais          |
| D13    | STATUS_LED | LED intégrée           |

## ESP32

| Broche  | Connexion                              |
|---------|----------------------------------------|
| GPIO16  | ← Uno **D4** (TX) via diviseur 1k + 2k |
| GPIO17  | → Uno **D3** (RX)                      |
| GND     | Masse commune                          |

```
Arduino D4 TX (5 V) ───[1 kΩ]───┬─── ESP32 GPIO16 RX (≈3.3 V)
                                │
                              [2 kΩ]
                                │
                               GND

Arduino D3 RX (5 V tolère 3.3 V) ◄── ESP32 GPIO17 TX
Arduino GND ────────────────────────── ESP32 GND
```

## Sécurité

1. Moteur alimenté **via le contact du relais**, jamais directement depuis l’Arduino.
2. Fusible / disjoncteur sur l’alimentation moteur.
3. ADXL345 en **3.3 V** sauf module explicitement 5 V tolérant.
