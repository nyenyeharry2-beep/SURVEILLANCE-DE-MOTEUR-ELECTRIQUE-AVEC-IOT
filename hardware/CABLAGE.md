# Câblage détaillé

## Alimentation

- ESP32 via USB (développement) ou régulateur 5 V → pin VIN.
- Modules ACS712 / ZMPT / relais en 5 V. **Ne pas alimenter le ZMPT côté mesure depuis le 3,3 V.**
- Masse commune ESP32 ↔ capteurs bas niveau.

## OneWire DS18B20

```
3V3 ── 4,7 kΩ ── GPIO4 ── data DS18B20
GND ──────────── GND DS18B20
3V3 ──────────── VDD DS18B20 (mode alimenté)
```

## Relais

Le circuit moteur 220 V passe uniquement par COM / NO du relais. L’ESP32 ne voit que la borne IN.

Si le module est « active LOW », inverser `appliquerRelais()` dans le firmware.

## MQTT

Sujets :

- `moteur/01/telemetry` (ESP32 → broker)
- `moteur/01/cmd` (SCADA → ESP32)
- `moteur/01/status` (LWT / online)
