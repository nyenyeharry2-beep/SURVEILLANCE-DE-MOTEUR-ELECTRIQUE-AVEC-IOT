# SURVEILLANCE DE MOTEUR ÉLECTRIQUE AVEC IOT

Système de maintenance prédictive IoT : vibrations (ADXL345) + vitesse (capteur IR) → Arduino Uno → ESP32 → **Firebase Realtime Database** → interface Web.

## Étape en cours : base de données Firebase

Guide complet :

→ [`Projet_Surveillance_Moteur/Docs/SETUP_FIREBASE.md`](Projet_Surveillance_Moteur/Docs/SETUP_FIREBASE.md)

Fichiers à utiliser dans la console Firebase :

| Fichier | Action |
|---------|--------|
| [`Firebase/seed_initial.json`](Projet_Surveillance_Moteur/Firebase/seed_initial.json) | Importer (onglet Données) |
| [`Firebase/database.rules.json`](Projet_Surveillance_Moteur/Firebase/database.rules.json) | Publier (onglet Règles) |
| [`Web/firebase-config.js`](Projet_Surveillance_Moteur/Web/firebase-config.js) | Remplir avec ta config Web |

## Architecture données

```text
moteur/live        ← ESP32 écrit les mesures (~1 s)
moteur/config      ← Web écrit les seuils ; ESP32 lit
moteur/command     ← Web commande relais/mute ; ESP32 lit
moteur/historique  ← ESP32 archive (~10 s)
```

## Suite (après Firebase)

1. Firmware ESP32 (Wi-Fi + publication)
2. Firmware Arduino Uno (capteurs + relais)
3. Interface Web
4. Câblage et tests
