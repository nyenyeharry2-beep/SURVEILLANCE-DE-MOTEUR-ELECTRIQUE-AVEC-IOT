# Firebase — Realtime Database

Base cloud du système de surveillance moteur.

## Contenu

| Fichier | Usage |
|---------|--------|
| `seed_initial.json` | Import initial dans Firebase Console |
| `database.rules.json` | Règles (ouvertes pour mémoire / proto) |
| `database_structure.txt` | Dictionnaire des champs |

## Démarrage

Suivre **`../Docs/SETUP_FIREBASE.md`**.

## Arbre minimal

```text
moteur/
├── live/         # mesures temps réel (ESP32)
├── config/       # seuils (Web → ESP32 → Uno)
├── command/      # relais + mute (Web → ESP32 → Uno)
└── historique/   # journal (créé par l’ESP32)
```
