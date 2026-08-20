# KYRIOS

Application mobile de messagerie et réseau social — développée avec **React Native** et **Expo**.

## Fonctionnalités

| Module | Description |
|--------|-------------|
| **Chats** | Liste des conversations, stories, filtres (All, Favorites, Work, Groups) |
| **Conversation** | Messages texte, images empilées, réactions emoji, statut en ligne |
| **Calls** | Historique des appels (audio/vidéo) |
| **Explore** | Amis actifs, fil de découverte, accès aux communautés et insights |
| **Communities** | Groupes thématiques avec nombre de membres |
| **Insights** | Analytics : membres totaux, localisations, démographie par âge |
| **Profile** | Profil utilisateur, statistiques, galerie photos |

## Design

Interface **dark mode** avec accents dégradés orange-rose, inspirée des maquettes UI du cahier des charges KYRIOS.

## Démarrage

```bash
cd kyrios
npm install
npm start
```

Puis choisir :
- `w` — Web
- `a` — Android (émulateur ou appareil)
- `i` — iOS (macOS requis)

## Structure

```
kyrios/
├── app/                  # Écrans (Expo Router)
│   ├── (tabs)/           # Navigation principale
│   ├── chat/[id].tsx     # Conversation
│   ├── communities.tsx   # Communautés
│   └── insights.tsx      # Analytics
├── components/           # Composants réutilisables
├── constants/            # Thème et données mock
└── types/                # Types TypeScript
```

## Stack technique

- React Native 0.86 + Expo SDK 57
- Expo Router (navigation file-based)
- TypeScript
- expo-linear-gradient, expo-image

## Note

Le fichier `KYRIOS_Cahier_des_charges.zip` n'était pas présent dans l'environnement de travail. Cette implémentation est basée sur les maquettes UI fournies. Si vous disposez du cahier des charges complet, veuillez le partager pour affiner les fonctionnalités.
