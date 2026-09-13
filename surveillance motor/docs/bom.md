# Nomenclature (BOM)

| Qté | Référence                    | Rôle                         | Notes                                      |
|-----|------------------------------|------------------------------|--------------------------------------------|
| 1   | Arduino Uno R3               | Capteurs + relais            | Clone OK                                   |
| 1   | ESP32 DevKit                 | Wi‑Fi + Telegram             |                                            |
| 1   | **Capteur IR 3 pins**        | **RPM**                      | VCC / GND / OUT + pot. sensibilité         |
| 1   | **ADXL345** (GY-291…)        | **Vibration / accélération** | I2C, alimenter en **3.3 V**                |
| 1   | ACS712 (20A recommandé)      | Courant moteur               | 100 mV/A                                   |
| 1   | LM35                         | Température                  |                                            |
| 1   | Module tension 0–25 V        | Tension (optionnel)          |                                            |
| 1   | Module relais 5 V            | ON/OFF moteur                | Contact adapté au courant moteur           |
| 2   | Résistances 1 kΩ, 2 kΩ       | Diviseur TX 5 V → 3.3 V      |                                            |
| —   | Bande réfléchissante / disque| Pour le RPM IR               | 1 marque = `PULSES_PER_REV 1`              |
| 1   | Alim. moteur séparée         | Puissance                    | Avec protection                            |

## Remplacé par rapport à l’ancienne nomenclature

| Ancien        | Nouveau              |
|---------------|----------------------|
| SW-420        | **ADXL345** (I2C)    |
| Capteur IR vague / hall | **IR 3 broches** (VCC, GND, OUT) |
