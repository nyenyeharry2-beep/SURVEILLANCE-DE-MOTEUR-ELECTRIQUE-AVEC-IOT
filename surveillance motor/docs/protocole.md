# Protocole UART Arduino Uno ↔ ESP32

**Baud :** 9600 8N1 — Uno **D4/D3** (SoftwareSerial) ↔ ESP32 **GPIO16/17**

## Télémétrie Uno → ESP32

```json
{"ax":0.02,"ay":-0.01,"az":0.98,"rms":0.12,"vrms":2.45,"rpm":1450,"imp":24,"freq":24.10,"urg":0,"alerte":0,"m":1}
```

| Champ | Unité | Description |
|-------|-------|-------------|
| `ax` `ay` `az` | g | Accélération ADXL345 |
| `rms` | g | RMS vibration |
| `vrms` | mm/s | vRMS approximé |
| `rpm` | tr/min | Vitesse (IR) |
| `imp` | — | Impulsions (fenêtre) |
| `freq` | Hz | Fréquence IR |
| `urg` | 0/1/2 | OK / ALERTE / URGENCE |
| `alerte` | 0/1 | Drapeau alerte |
| `m` | 0/1 | Moteur OFF/ON |

Champs **retirés** : `impt`, `c` (courant), `t` (température), `v` (tension).
