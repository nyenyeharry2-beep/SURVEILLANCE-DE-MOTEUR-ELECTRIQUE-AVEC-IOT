# KYRIOS

Application mobile web de messagerie sociale (communautés, insights, fil Discover) — dérivée du cahier des charges et des maquettes UI.

## Démarrage

```bash
cd kyrios-app
npm install
npm run dev
```

Ouvrir l’URL locale affichée par Vite (port 5173 par défaut). L’interface est optimisée pour un viewport mobile (~430px).

## Documentation

- [Cahier des charges](docs/Cahier_des_charges.md) — synthèse fonctionnelle (le fichier `KYRIOS_Cahier_des_charges.zip` n’était pas présent dans l’environnement).

## Écrans

| Route | Description |
|-------|-------------|
| `/` | Messages (inbox) |
| `/chat/:id` | Conversation |
| `/communities` | Liste des communautés |
| `/insights` | Analytics membres |
| `/discover` | Fil Discover + stories |
| `/profile` | Profil utilisateur |

## Stack

- React 19 + TypeScript
- Vite 8
- React Router 7
