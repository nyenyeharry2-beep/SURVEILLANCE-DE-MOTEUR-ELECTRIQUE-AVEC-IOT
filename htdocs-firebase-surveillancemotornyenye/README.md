# Tableau de bord Firebase — Surveillance Moteur

Interface web temps réel (Firebase Realtime Database) pour **surveillancemotornyenye.xo.je**.

## Déploiement InfinityFree

1. File Manager → `htdocs` → upload & unzip
2. Configurer `firebase-config.js`
3. Ouvrir `http://surveillancemotornyenye.xo.je/`

## Chemins Firebase

| Chemin | Rôle |
|--------|------|
| `moteur/live` | Mesures temps réel |
| `moteur/config` | Seuils |
| `moteur/command` | Relais / buzzer |
| `moteur/historique/` | Journal |

## ESP32

Utiliser `passerelle_firebase.ino` — publication directe vers Firebase (sans PHP/MySQL).
