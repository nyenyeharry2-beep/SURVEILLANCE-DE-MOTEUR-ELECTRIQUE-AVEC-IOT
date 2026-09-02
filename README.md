# Nouvelle Eve — Gestion de pharmacie

Application web de gestion de pharmacie développée en **PHP + MySQL**, optimisée pour un déploiement gratuit sur **[InfinityFree](https://www.infinityfree.com/)**.

## Fonctionnalités

- **Tableau de bord** — statistiques, alertes stock et ventes
- **Médicaments** — CRUD, recherche, gestion des prix et du stock
- **Catégories** — classification des produits
- **Fournisseurs** — carnet d'adresses fournisseurs
- **Achats / Entrées** — réapprovisionnement automatique du stock
- **Ventes** — enregistrement des ventes avec déduction du stock
- **Alertes stock** — stock faible et dates d'expiration
- **Utilisateurs** — gestion des comptes (admin, pharmacien, caissier)

## Prérequis

- Compte [InfinityFree](https://www.infinityfree.com/) (gratuit)
- PHP 7.4+ (InfinityFree propose PHP 8.x)
- Base de données MySQL

## Déploiement sur InfinityFree

### 1. Créer un compte et un site

1. Inscrivez-vous sur [infinityfree.com](https://www.infinityfree.com/)
2. Créez un nouveau compte d'hébergement
3. Ajoutez un site (sous-domaine gratuit ou domaine personnalisé)

### 2. Créer la base de données MySQL

1. Dans le **Control Panel** InfinityFree → **MySQL Databases**
2. Créez une nouvelle base de données
3. Notez :
   - **Hôte MySQL** (ex. `sql123.infinityfree.com`)
   - **Nom de la base** (ex. `if0_12345678_pharma`)
   - **Utilisateur** (ex. `if0_12345678`)
   - **Mot de passe**

### 3. Importer le schéma SQL

1. Ouvrez **phpMyAdmin** depuis le Control Panel
2. Sélectionnez votre base de données
3. Allez dans l'onglet **Importer**
4. Uploadez le fichier `database/schema.sql`
5. Cliquez sur **Exécuter**

### 4. Uploader les fichiers

Via le **File Manager** ou **FTP** (FileZilla) :

1. Connectez-vous à votre hébergement
2. Uploadez **tous les fichiers** du projet dans le dossier `htdocs`
3. La structure doit ressembler à :

```
htdocs/
├── index.php
├── login.php
├── dashboard.php
├── medicaments.php
├── ...
├── assets/
├── config/
├── includes/
└── database/
```

### 5. Configurer l'application

1. Dans `config/`, copiez `config.example.php` vers `config.php`
2. Modifiez `config.php` avec vos identifiants MySQL :

```php
define('DB_HOST', 'sql123.infinityfree.com');
define('DB_NAME', 'if0_12345678_pharma');
define('DB_USER', 'if0_12345678');
define('DB_PASS', 'votre_mot_de_passe');
define('APP_URL', 'https://votre-site.infinityfreeapp.com');
define('TIMEZONE', 'Africa/Abidjan');
```

### 6. Se connecter

Ouvrez votre site et connectez-vous avec le compte administrateur par défaut :

| Champ | Valeur |
|-------|--------|
| Email | `admin@pharmagest.local` |
| Mot de passe | `admin123` |

> **Important** : changez ce mot de passe immédiatement via **Utilisateurs → Réinitialiser le mot de passe**.

## Structure du projet

```
├── assets/css/app.css      # Styles personnalisés
├── assets/js/app.js        # Scripts JavaScript
├── config/
│   ├── config.example.php  # Modèle de configuration
│   └── config.php          # Configuration (non versionné)
├── database/schema.sql     # Schéma + données de démo
├── includes/
│   ├── auth.php            # Authentification
│   ├── db.php              # Connexion PDO
│   ├── helpers.php         # Fonctions utilitaires
│   ├── header.php          # En-tête + menu
│   └── footer.php          # Pied de page
├── index.php               # Redirection
├── login.php               # Page de connexion
├── dashboard.php           # Tableau de bord
├── medicaments.php         # Gestion des médicaments
├── categories.php          # Catégories
├── fournisseurs.php        # Fournisseurs
├── achats.php              # Entrées de stock
├── ventes.php              # Ventes
├── stock.php               # Alertes
├── utilisateurs.php        # Gestion utilisateurs (admin)
└── .htaccess               # Sécurité Apache
```

## Rôles utilisateurs

| Rôle | Permissions |
|------|-------------|
| **admin** | Accès complet + gestion des utilisateurs |
| **pharmacien** | Médicaments, achats, ventes, stock |
| **caissier** | Ventes uniquement (selon configuration) |

## Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes préparées PDO (protection SQL injection)
- Tokens CSRF sur tous les formulaires
- Dossiers `config/`, `includes/` et `database/` protégés par `.htaccess`

## Notes InfinityFree

- Les exécutions PHP sont limitées à **30 secondes**
- Pas de cron jobs natifs (planifiez manuellement si besoin)
- Supprimez les fichiers inutilisés pour économiser l'espace (limite ~5 Mo)
- Si `php_flag` dans `.htaccess` cause une erreur 500, commentez ces lignes

## Développement local

Pour tester en local avec XAMPP/WAMP/Laragon :

1. Copiez le projet dans `htdocs`
2. Créez une base MySQL locale et importez `database/schema.sql`
3. Copiez et configurez `config/config.php`
4. Accédez à `http://localhost/pharma-app/login.php`

## Licence

Projet libre d'utilisation pour la gestion de pharmacie.
