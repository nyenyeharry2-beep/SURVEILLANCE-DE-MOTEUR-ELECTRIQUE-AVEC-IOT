# CÂBLAGE — Arduino Uno (capteurs + relais) + ESP32 (passerelle IoT)

## Architecture

```
MOTEUR
  ├─ ADXL345 (vibrations) ──┐
  └─ Capteur IR (RPM) ──────┼─► Arduino Uno ──UART──► ESP32 ──Wi-Fi──► Firebase ──► Web
                            │         │
                     Relais D8        Buzzer D9
                            │
                    bobine contacteur BT (pas le 230 V)
```

## Arduino Uno — tableau de connexions

| COMPOSANT | BROCHE | Arduino Uno | Tension |
|-----------|--------|-------------|---------|
| ADXL345 VCC | VCC | **3,3 V** (ou 5 V si module avec régulateur) | Voir module |
| ADXL345 GND | GND | GND | 0 V |
| ADXL345 SDA | SDA | **A4** | I2C |
| ADXL345 SCL | SCL | **A5** | I2C |
| ADXL345 CS | CS | 3,3 V (si présent, mode I2C) | — |
| ADXL345 SDO | SDO | GND → adresse **0x53** | — |
| Capteur IR VCC | VCC | **5 V** | 5 V |
| Capteur IR GND | GND | GND | 0 V |
| Capteur IR OUT | OUT | **D2** (INT0) | 5 V OK sur Uno |
| Module relais VCC | VCC | **5 V** | 5 V |
| Module relais GND | GND | GND | 0 V |
| Module relais IN | IN | **D8** | 5 V logique |
| Relais COM / NO | contacts | Bobine contacteur **BT uniquement** | — |
| Buzzer + | + | **D9** | 5 V / transistor si besoin |
| Buzzer − | − | GND | 0 V |
| Liaison ESP32 | TX | **D11** → diviseur → ESP32 RX | **5 V → 3,3 V** |
| Liaison ESP32 | RX | **D10** ← ESP32 TX | 3,3 V |

### Capteur IR (vitesse)

- Type : module réfléchissant (ex. **TCRT5000**, KY-033, capteur IR obstacle).
- Coller **une marque** contrastée (bande blanche/noire ou pastille réfléchissante) sur l’arbre / ventilateur.
- **1 marque = 1 impulsion = 1 tour** (`PULSES_PER_REV = 1`).
- Distance typique 2–10 mm ; éviter la lumière parasite.
- Sortie numérique vers **D2** ; anti-rebond logiciel 3 ms dans le firmware.

### Relais sur D8

- Active LOW par défaut (`RELAY_ACTIVE_LOW 1`).
- Au démarrage : relais **OFF**.

## ESP32 — passerelle uniquement

| Fonction | GPIO ESP32 |
|----------|------------|
| Serial2 RX (← Uno TX via diviseur) | **16** |
| Serial2 TX (→ Uno RX) | **17** |
| LED Wi-Fi | 2 |

### Diviseur de tension obligatoire (Uno TX 5 V → ESP32 RX)

```
Uno D11 (TX) ── 2,2 kΩ ──┬── ESP32 GPIO16 (RX)
                         │
                        3,3 kΩ
                         │
                        GND
```

(Rapport ≈ 3,3/5,5 ≈ 3,0 V — adapté. Variante classique : 1 kΩ + 2 kΩ.)

GND Uno et GND ESP32 **reliés**.

## Séparation puissance / commande

```
[ 230 V ]──disjoncteur──contacteur──MOTEUR
                │
                └── bobine BT ◄── contacts NO du relais (Uno D8)

[ 5 V ]── Arduino Uno ── ADXL345 / IR / Relais / Buzzer
[ 3,3/5 V USB ]── ESP32 (Wi-Fi seulement)
```

- L’ESP32 et l’Uno **ne touchent jamais** au 230 V.
- Le module relais ne commute **pas** le moteur 230 V directement.

## Alimentation ADXL345 sur Uno

Le circuit ADXL345 est en **3,3 V**. Préférer un module breakout avec :
- régulateur 3,3 V + translation I2C, alimenté en 5 V, **ou**
- alimentation 3,3 V Uno + vérification que les pull-ups I2C ne dépassent pas 3,3 V.

Ne jamais alimenter la puce nue en 5 V.
