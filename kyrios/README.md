# KYRIOS

Application mobile de messagerie et réseau social — **Connecte-toi. Partage. Sois toi-même.**

## Structure du projet

```
kyrios/
├── android/          # Application Android (Kotlin + Jetpack Compose)
├── api/              # API REST PHP
├── database/         # Schéma MySQL
└── docs/             # Cahier des charges et documentation
```

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Mobile | Kotlin, Jetpack Compose, Retrofit |
| Backend | PHP 8+, REST JSON |
| Base de données | MySQL 8+ |
| Hébergement prototype | InfinityFree |

## Démarrage rapide

### 1. Base de données

```bash
mysql -u root -p < kyrios/database/schema.sql
```

### 2. API PHP

```bash
cd kyrios/api
cp .env.example .env   # puis éditer les variables
php -S localhost:8080 -t public
```

Variables d'environnement :

| Variable | Description |
|----------|-------------|
| `KYRIOS_DB_HOST` | Hôte MySQL |
| `KYRIOS_DB_NAME` | Nom de la base (`kyrios`) |
| `KYRIOS_DB_USER` | Utilisateur MySQL |
| `KYRIOS_DB_PASS` | Mot de passe MySQL |
| `KYRIOS_BASE_URL` | URL publique de l'API |

### 3. Application Android

1. Ouvrir `kyrios/android` dans Android Studio
2. Modifier `API_BASE_URL` dans `app/build.gradle.kts`
3. Lancer sur émulateur ou appareil

## MVP (V1) — Fonctionnalités implémentées

- [x] Inscription / connexion (token Bearer)
- [x] Profil utilisateur
- [x] Recherche d'utilisateurs
- [x] Messagerie texte (conversations directes)
- [x] Publications publiques
- [x] Likes et commentaires
- [x] Schéma MySQL complet (17 tables + tokens)

## Versions futures

- **V2** : messages vocaux, médias, stories, notifications, communautés
- **V3** : appels audio/vidéo, push notifications, scalabilité

## Documentation

- [Cahier des charges](docs/Cahier_des_charges_KYRIOS.md)
- [Documentation API](docs/API.md)
- [Guide de déploiement](docs/DEPLOYMENT.md)
