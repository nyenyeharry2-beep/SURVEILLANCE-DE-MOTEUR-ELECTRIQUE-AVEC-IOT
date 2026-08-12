# SURVEILLANCE DE MOTEUR ÉLECTRIQUE AVEC IoT

Système de **maintenance prédictive** : surveillance temps réel des **vibrations** (ADXL345) et de la **vitesse** (capteur Hall) d’un moteur électrique via **ESP32 → Firebase Realtime Database → interface Web**.

## Contenu du dépôt

Tout le projet se trouve dans [`Projet_Surveillance_Moteur/`](Projet_Surveillance_Moteur/) :

| Dossier | Contenu |
|---------|---------|
| `ESP32/` | Firmware Arduino complet |
| `Web/` | Interface HTML/CSS/JS + Chart.js |
| `Firebase/` | Structure JSON, seed, règles |
| `Docs/` | Guide étapes 1–16, câblage, équations, tests, partie scientifique, checklist |

**Point d’entrée** : [`Projet_Surveillance_Moteur/README.md`](Projet_Surveillance_Moteur/README.md) puis [`Docs/GUIDE_COMPLET.md`](Projet_Surveillance_Moteur/Docs/GUIDE_COMPLET.md).

## Point technique important

L’ADXL345 mesure une **accélération**. **A_RMS (m/s²)** est la grandeur fiable. La **vibration RMS en mm/s** du firmware est une **estimation** (intégration), adaptée à un mémoire / démonstration, pas à une certification ISO 10816.
