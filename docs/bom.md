# Nomenclature (BOM)

| Qté | Référence              | Rôle                              | Notes                          |
|-----|------------------------|-----------------------------------|--------------------------------|
| 1   | Arduino Uno R3         | Acquisition capteurs + relais     | Clone OK                       |
| 1   | ESP32 DevKit (30/38p)  | Wi‑Fi + bot Telegram              | Dual core, 3.3 V               |
| 1   | ACS712 (20A recommandé)| Courant moteur                    | Sensibilité 100 mV/A           |
| 1   | LM35                   | Température                       | Ou DHT22 (adapter le code)     |
| 1   | Module tension 0–25 V  | Tension (optionnel)               | Diviseur intégré               |
| 1   | SW-420                 | Vibration                         | Sortie digitale                |
| 1   | Capteur IR / hall      | RPM                               | + disque à fente / aimant      |
| 1   | Module relais 5 V      | Coupure / démarrage moteur        | Contact dimensionné au moteur  |
| 2   | Résistances 1 kΩ, 2 kΩ | Diviseur TX 5 V → 3.3 V           | Obligatoire pour long terme    |
| —   | Fils Dupont, breadboard| Prototypage                       | —                              |
| 1   | Alimentation moteur    | Séparée                           | Fusible / disjoncteur          |

## Variantes

- **Sans RPM :** laisser D4 libre ; la valeur restera à 0.
- **Sans module tension :** A2 flottant → filtrer ou ignorer `v` côté alertes (`VOLTAGE_MIN_V = 0` côté logique métier).
- **Moteur triphasé / forte puissance :** utiliser un contacteur + transformateur de courant (CT) à la place de l’ACS712 hobby.
