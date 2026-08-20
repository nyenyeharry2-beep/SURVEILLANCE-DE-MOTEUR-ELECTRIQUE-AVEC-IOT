# KYRIOS

Application complète de messagerie et réseau social.

## Structure du projet

```
kyrios/
├── database/          # Base de données SQLite + schéma SQL
│   ├── schema.sql     # Schéma complet
│   ├── seed.sql       # Données demo
│   └── kyrios.db      # Base prête à l'emploi
├── backend/           # API REST + WebSocket
├── mobile/            # App Android (Expo/React Native)
│   └── android/app/build/outputs/apk/release/app-release.apk
└── src/               # App web (React + Vite)
```

## APK Android

**Télécharger :** [`releases/KYRIOS-v1.0.0.apk`](./releases/KYRIOS-v1.0.0.apk) (81 Mo)

Pour reconstruire l'APK, voir la section [Reconstruire l'APK](#reconstruire-lapk) en bas.

### Installation sur téléphone

1. Téléchargez `releases/KYRIOS-v1.0.0.apk`
2. Activez **Sources inconnues** dans Paramètres > Sécurité
3. Ouvrez le fichier APK et installez
4. Lancez le backend sur votre PC (voir ci-dessous) — l'app se connecte via votre IP réseau

### Compte demo

| Email | Mot de passe |
|-------|-------------|
| me@kyrios.app | Kyrios2026! |

## Démarrage complet

### 1. Backend + Base de données

```bash
cd backend
npm install
npm run init-db    # Crée kyrios.db
npm start          # API sur http://localhost:3001
```

### 2. App web

```bash
npm install
npm run dev        # http://localhost:5173
```

### 3. App mobile (développement)

```bash
cd mobile
npm install
npm run android
```

> **Important** : L'app mobile se connecte au backend via `http://10.0.2.2:3001` (émulateur Android).
> Sur un vrai téléphone, modifiez `mobile/src/api/client.ts` avec l'IP de votre PC :
> `export const API_URL = 'http://192.168.x.x:3001'`

## Fonctionnalités

- Authentification (inscription / connexion JWT)
- Messagerie temps réel (REST + WebSocket)
- Stories, filtres de chats, groupes
- Fil Discover (posts, likes, commentaires)
- Communautés thématiques
- Historique d'appels
- Profils utilisateurs
- Notifications

## Base de données

Voir [database/README.md](./database/README.md)

Tables : users, conversations, messages, stories, posts, communities, calls, notifications, follows

## Reconstruire l'APK

```bash
cd mobile
npx expo prebuild --platform android
cd android && ./gradlew assembleRelease
```

## Stack

| Composant | Technologie |
|-----------|------------|
| Web | React 19, Vite, Tailwind CSS v4 |
| Mobile | Expo 57, React Native |
| Backend | Node.js, Express, WebSocket |
| Base de données | SQLite (dev) / PostgreSQL (prod) |
