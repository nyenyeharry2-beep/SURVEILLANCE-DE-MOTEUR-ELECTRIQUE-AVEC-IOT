# Kyrios My Boutique

Marketplace sociale avec fil d'actualité, messagerie intégrée et gestion multi-rôles (Client, Vendeur, Livreur).

![Kyrios My Boutique](public/assets/img/logo.svg)

## Fonctionnalités

- **Fil d'actualité** style réseau social (publications, likes, commentaires, partage)
- **Marketplace** avec catégories, fiches produits et commandes
- **Messagerie instantanée** entre utilisateurs
- **3 types de comptes** : Client, Vendeur, Livreur
- **Connexion Google OAuth** + inscription classique email/mot de passe
- **Interface responsive** adaptée mobile et desktop

## Stack technique

- PHP 8+ (compatible hébergement mutualisé InfinityFree)
- MySQL / MariaDB
- HTML5, CSS3, JavaScript vanilla
- PDO pour la base de données

## Structure du projet

```
kyrios-my-boutique/
├── config/           # Configuration (.env)
├── database/         # Schéma SQL
├── public/           # Racine web (htdocs)
│   ├── api/          # Endpoints AJAX
│   ├── assets/       # CSS, JS, images
│   ├── auth/google/  # Callback OAuth Google
│   └── *.php         # Pages de l'application
└── src/              # Classes PHP (Auth, Feed, Messaging...)
```

## Installation sur InfinityFree

### 1. Base de données

1. Connectez-vous à votre panneau InfinityFree
2. Ouvrez **phpMyAdmin** pour la base `if0_42727746_kyriosboutique`
3. Importez le fichier `database/schema.sql` (dans le dossier uploadé)

### 2. Fichiers

1. Uploadez **tout le contenu** du dossier `public/` à la racine de votre site (`htdocs`)
2. Copiez `.env.example` vers `public/.env` et configurez vos identifiants :

```env
APP_NAME="Kyrios My Boutique"
APP_URL=https://kyriosboutique.page.gd
APP_DEBUG=false

DB_HOST=sql301.infinityfree.com
DB_PORT=3306
DB_NAME=if0_42727746_kyriosboutique
DB_USER=if0_42727746
DB_PASS=votre_mot_de_passe

GOOGLE_CLIENT_ID=votre_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_secret
GOOGLE_REDIRECT_URI=https://kyriosboutique.page.gd/auth/google/callback.php
```

### 3. Google OAuth

1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créez un projet → **APIs & Services** → **Credentials**
3. Créez un **OAuth 2.0 Client ID** (type: Web application)
4. URI de redirection autorisée : `https://kyriosboutique.page.gd/auth/google/callback.php`
5. Copiez Client ID et Secret dans `.env`

### 4. Comptes de démonstration

Après import du schéma SQL, ces comptes sont disponibles (mot de passe : `password`) :

| Email | Rôle |
|-------|------|
| demo.client@kyrios.local | Client |
| demo.vendeur@kyrios.local | Vendeur |
| demo.livreur@kyrios.local | Livreur |

## Pages principales

| Page | URL | Description |
|------|-----|-------------|
| Accueil | `/index.php` | Fil d'actualité social |
| Marketplace | `/marketplace.php` | Catalogue produits |
| Messages | `/messages.php` | Messagerie intégrée |
| Inscription | `/register.php` | Création de compte |
| Connexion | `/login.php` | Authentification |
| Mes produits | `/seller.php` | Gestion vendeur |
| Livraisons | `/delivery.php` | Espace livreur |

## Rôles utilisateur

| Rôle | Capacités |
|------|-----------|
| **Client** | Acheter, commenter, messagerie, commander |
| **Vendeur** | Tout client + publier produits, gérer boutique |
| **Livreur** | Accepter et gérer les livraisons |

## Logo

Le logo SVG personnalisé se trouve dans `public/assets/img/logo.svg` — un sac boutique violet avec couronne dorée et initiale « K ».

## Licence

Projet privé — Kyrios My Boutique © 2026
