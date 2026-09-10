# Application de Gestion du Transport Scolaire

**C.S LES SUPER GENIES** — Fondation Eben Ezer, Lubumbashi

Application web PHP/MySQL compatible **InfinityFree** pour gérer les inscriptions au bus scolaire, les paiements mensuels (Septembre → Juin) et la fiche de contrôle papier numérisée.

## Fonctionnalités

- **Formulaire public mobile** (QR Code) : inscription en 5 étapes
- **Détection automatique des doublons** par nom d'élève
- **Tableau de contrôle** identique au modèle papier (N°, Nom, SEPT → JUIN)
- **Paiements** : complet, partiel, impayé avec calcul automatique du reste
- **Administration** : élèves, classes, arrêts, tarifs, années scolaires
- **Export CSV** et **Export PDF** (impression A4)
- **QR Code** généré pour affichage à l'école
- **Historique** de toutes les opérations
- **Sécurité** : CSRF, XSS, SQL injection, mots de passe hachés

## Installation sur InfinityFree

### 1. Base de données

1. Connectez-vous au panel InfinityFree
2. Allez dans **MySQL Databases**
3. Créez la base `if0_XXXXX_genies` (si pas déjà fait)
4. Cliquez **phpMyAdmin**
5. Sélectionnez votre base de données
6. Onglet **Importer** → choisissez `database/database.sql` → Exécuter

### 2. Configuration

Éditez `config/database.php` avec vos identifiants InfinityFree :

```php
define('DB_HOST', 'sql205.infinityfree.com');
define('DB_NAME', 'if0_42871659_genies');
define('DB_USER', 'if0_42871659');
define('DB_PASS', 'VOTRE_MOT_DE_PASSE');
define('BASE_URL', 'https://genies.free.je/transport');
```

### 3. Upload des fichiers

1. Panel InfinityFree → **Files** → **File Manager**
2. Ouvrez le dossier `htdocs`
3. Uploadez tout le contenu du dossier `transport/` dans `htdocs/transport/`

Structure finale :
```
htdocs/
└── transport/
    ├── index.php
    ├── inscription.php
    ├── admin/
    ├── config/
    ├── includes/
    ├── assets/
    └── database/
```

### 4. Accès

| Page | URL |
|------|-----|
| Formulaire public (QR) | `https://genies.free.je/transport/inscription.php` |
| Administration | `https://genies.free.je/transport/admin/login.php` |

**Identifiants par défaut :**
- Utilisateur : `admin`
- Mot de passe : `admin123`

> ⚠️ **Changez le mot de passe immédiatement** dans Paramètres → Changer le mot de passe.

## Utilisation

### Pour les parents
1. Scanner le QR Code affiché à l'école
2. Remplir le formulaire (élève, parent, transport, paiement)
3. Noter le numéro de dossier affiché

### Pour l'administration
1. Se connecter à l'espace admin
2. Vérifier les nouvelles inscriptions (marquer **OK**)
3. Consulter la **Fiche de contrôle** (tableau Septembre → Juin)
4. Exporter en PDF pour impression
5. Gérer les paiements manuellement si nécessaire

## Structure du projet

```
transport/
├── index.php              → Redirige vers inscription
├── inscription.php        → Formulaire public (QR Code)
├── confirmation.php       → Page de confirmation
├── api/
│   └── submit_inscription.php
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── students.php
│   ├── student.php
│   ├── payments.php
│   ├── control_sheet.php  → Fiche papier numérisée
│   ├── stops.php
│   ├── classes.php
│   ├── tariffs.php
│   ├── qr_code.php
│   ├── export.php
│   ├── history.php
│   └── settings.php
├── config/
│   ├── database.php
│   └── app.php
├── includes/
│   ├── db.php
│   ├── functions.php
│   ├── auth.php
│   └── header_*.php
├── assets/
│   ├── css/
│   └── js/
└── database/
    └── database.sql
```

## Technologies

- PHP 7.4+ / 8.x
- MySQL / MariaDB
- Bootstrap 5
- JavaScript (formulaire multi-étapes AJAX)
- Compatible hébergement mutualisé (pas de Node.js, Docker, etc.)

## Support

École : C.S LES SUPER GENIES — Lubumbashi  
Email : cslessupergenies@gmail.com
