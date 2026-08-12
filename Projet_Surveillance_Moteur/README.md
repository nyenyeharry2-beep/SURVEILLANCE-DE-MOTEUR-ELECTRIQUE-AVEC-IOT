# CONCEPTION ET MISE EN ŒUVRE D’UN SYSTÈME DE MAINTENANCE PRÉDICTIVE IoT

Surveillance temps réel des **vibrations** et de la **vitesse de rotation** d’un moteur électrique.

## Architecture actuelle

```
Moteur → ADXL345 + Capteur IR → Arduino Uno (relais D8, buzzer)
                                      ↓ UART 9600
                                    ESP32 (Wi-Fi)
                                      ↓
                              Firebase Realtime Database
                                      ↓
                                 Interface Web
```

| Carte | Rôle |
|-------|------|
| **Arduino Uno** | ADXL345 (I2C A4/A5), **capteur IR** (D2) pour RPM, **relais D8**, buzzer D9, diagnostic local |
| **ESP32** | Passerelle Firebase uniquement (Serial2 GPIO16/17) |

## Structure

```
Projet_Surveillance_Moteur/
├── Arduino_Uno/surveillance_moteur_uno/   ← capteurs + relais + buzzer
├── ESP32/passerelle_firebase/             ← Wi-Fi + Firebase
├── Web/                                   ← interface
├── Firebase/
└── Docs/                                  ← GUIDE_COMPLET, CABLAGE, …
```

## Démarrage rapide

1. `Docs/GUIDE_COMPLET.md` + `Docs/CABLAGE.md`
2. Flasher l’**Uno** puis l’**ESP32** (credentials Wi-Fi / Firebase)
3. Diviseur 5 V→3,3 V sur TX Uno → RX ESP32
4. Importer `Firebase/seed_initial.json` ; configurer `Web/firebase-config.js`

## Point scientifique

**A_RMS (m/s²)** = grandeur fiable. **Vibration RMS mm/s** = estimation. Voir `Docs/EQUATIONS.md`.
