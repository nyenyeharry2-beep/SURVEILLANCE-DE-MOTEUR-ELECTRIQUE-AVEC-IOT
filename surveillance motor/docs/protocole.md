# Protocole UART + Tableau de bord Telegram

**Baud :** 9600 8N1 — Uno ↔ ESP32 GPIO16/17

## Télémétrie Uno → ESP32

```json
{"ax":0.02,"ay":-0.01,"az":0.98,"rms":0.12,"vrms":2.45,"rpm":1450,"imp":24,"impt":12040,"freq":24.10,"urg":0,"alerte":0,"c":1.2,"t":42.0,"v":220.0,"m":1}
```

| Champ | Unité | Description |
|-------|-------|-------------|
| `ax` `ay` `az` | g | Accélération ADXL345 (moyenne) |
| `rms` | g | RMS accélération dynamique |
| `vrms` | mm/s | vRMS approximé `a/(2πf)` |
| `rpm` | tr/min | Vitesse (IR) — MPR |
| `imp` | — | Impulsions sur la fenêtre |
| `impt` | — | Impulsions totales |
| `freq` | Hz | Fréquence d’impulsions IR |
| `urg` | 0/1/2 | 0=OK, 1=ALERTE, 2=URGENCE |
| `alerte` | 0/1 | Drapeau alerte |
| `c` `t` `v` `m` | A/°C/V/0-1 | Courant, temp, tension, moteur |

## Rôles Telegram

### Administrateur (`TELEGRAM_ADMIN_CHAT_ID`)

Commandes et **boutons inline** :

| Action | Bouton / commande |
|--------|-------------------|
| Tableau de bord | `/dashboard` |
| ON / OFF moteur | boutons **ON** **OFF** ou `/on` `/off` |
| Historique | bouton **Historique** ou `/historique` |
| Stop urgence | bouton **URGENCE STOP** ou `/urgence` |
| Actualiser / Alertes | boutons dédiés |

### Observateur (`TELEGRAM_VIEWER_CHAT_ID`, optionnel)

Mêmes métriques affichées :

`ax ay az rms vrms rpm impulsions frequence urgence alerte`

**Sans** ON/OFF, sans historique, sans urgence.
