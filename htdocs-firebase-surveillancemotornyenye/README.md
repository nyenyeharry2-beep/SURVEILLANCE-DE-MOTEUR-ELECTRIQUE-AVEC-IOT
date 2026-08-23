# NYENYE — Tableau de bord complet (Firebase)

Interface **Surveillance du moteur** avec sidebar, graphiques, historique et commande — connectée à **Firebase** en temps réel.

## Fichiers htdocs

| Fichier | Rôle |
|---------|------|
| `index.html` | Page principale |
| `main.js` | Tableau de bord interactif |
| `firebase-api.js` | Connexion Firebase |
| `firebase-config.js` | Clés Firebase |
| `app.css` | Styles |
| `calculs.js` | Calculs vibration / RPM |

## Installation

1. InfinityFree → File Manager → `htdocs`
2. Supprimer les anciens fichiers PHP/Lumen
3. Upload de **tous** les fichiers ci-dessus
4. Ouvrir `http://surveillancemotornyenye.xo.je/`

## Firebase

- `motor/live` — données ESP32
- `motor/command` — ALLUMER / ÉTEINDRE
- Projet : `surveillance-moteur-f38e1`
