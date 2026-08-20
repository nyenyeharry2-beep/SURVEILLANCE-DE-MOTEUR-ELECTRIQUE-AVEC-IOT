# KYRIOS — Déploiement sur InfinityFree

InfinityFree supporte **PHP + MySQL** (pas Node.js). Ce dossier contient l'API PHP.

## Étapes

### 1. Créer un compte InfinityFree
- Allez sur https://infinityfree.com
- Créez un compte et un site (ex: `kyrios.infinityfreeapp.com`)

### 2. Créer la base MySQL
- Panneau InfinityFree → **MySQL Databases** → Create Database
- Notez : **Host**, **Database name**, **Username**, **Password**

### 3. Importer la base de données
- Panneau → **phpMyAdmin**
- Sélectionnez votre base → **Import**
- Importez le fichier : `../database/kyrios_mysql.sql`

### 4. Configurer l'API
- Éditez `config.php` avec vos identifiants MySQL InfinityFree
- Uploadez tout le dossier `infinityfree/` dans `htdocs/` via **File Manager** ou FTP :
  ```
  htdocs/
  ├── .htaccess
  ├── config.php
  └── api/
      └── index.php
  ```

### 5. Tester l'API
Ouvrez dans le navigateur :
```
https://VOTRE-SITE.infinityfreeapp.com/api/health
```
Réponse attendue : `{"status":"ok","app":"KYRIOS","host":"InfinityFree"}`

### 6. Configurer l'app mobile
Dans `mobile/src/api/client.ts`, changez :
```typescript
export const API_URL = 'https://VOTRE-SITE.infinityfreeapp.com'
```
Puis reconstruisez l'APK.

## Compte demo

| Email | Mot de passe |
|-------|-------------|
| me@kyrios.app | Kyrios2026! |

## Structure API

| Route | Méthode | Description |
|-------|---------|-------------|
| `/api/health` | GET | Test connexion |
| `/api/auth/login` | POST | Connexion |
| `/api/auth/register` | POST | Inscription |
| `/api/auth/me` | GET | Profil connecté |
| `/api/conversations` | GET | Liste des chats |
| `/api/conversations/{id}/messages` | GET/POST | Messages |
| `/api/posts` | GET | Fil Discover |
| `/api/stories` | GET | Stories |
| `/api/communities` | GET | Communautés |
| `/api/calls` | GET | Appels |
