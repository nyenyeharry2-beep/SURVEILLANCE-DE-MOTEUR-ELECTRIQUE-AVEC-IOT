# Guide d'installation — Nouvelle Eve sur InfinityFree

Site : **https://mapharmaciepk.xo.je**

---

## Ce qu'il faut installer

Sur InfinityFree, **rien à installer côté serveur** : PHP et MySQL sont déjà inclus.

Sur votre PC, il vous faut seulement :

| Outil | Obligatoire ? | Usage |
|-------|---------------|--------|
| Navigateur web | Oui | Panneau InfinityFree, phpMyAdmin |
| FileZilla (FTP) | Non | Alternative au File Manager pour uploader les fichiers |
| Git | Non | Pour cloner le projet depuis GitHub |

---

## Étape 1 — Récupérer les fichiers du projet

**Option A — GitHub (recommandé)**

1. Allez sur la PR ou la branche `cursor/pharmacie-infinityfree-fdac`
2. Téléchargez le ZIP du code
3. Décompressez-le sur votre PC

**Option B — Cloner avec Git**

```bash
git clone https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT.git
cd SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT
git checkout cursor/pharmacie-infinityfree-fdac
```

---

## Étape 2 — Créer la base de données (déjà fait)

Votre base existe déjà :

| Paramètre | Valeur |
|-----------|--------|
| Hôte | `sql211.infinityfree.com` |
| Base | `if0_42810781_mapharmacieEve` |
| Utilisateur | `if0_42810781` |
| Mot de passe | *(celui défini dans InfinityFree)* |
| Port | `3306` |

---

## Étape 3 — Importer les tables SQL

1. Panneau InfinityFree → **MySQL Databases**
2. Cliquez **phpMyAdmin** à côté de `if0_42810781_mapharmacieEve`
3. Sélectionnez la base à gauche
4. Onglet **Importer**
5. Choisissez le fichier : `database/schema.sql`
6. Cliquez **Exécuter**

Tables créées : `utilisateurs`, `medicaments`, `categories`, `fournisseurs`, `achats`, `ventes`, etc.

---

## Étape 4 — Uploader les fichiers dans `htdocs`

1. Panneau InfinityFree → **File Manager**
2. Ouvrez le dossier **`htdocs`**
3. Uploadez **tous** les fichiers et dossiers listés ci-dessous

### Structure à uploader

```
htdocs/
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── medicaments.php
├── categories.php
├── fournisseurs.php
├── achats.php
├── ventes.php
├── stock.php
├── utilisateurs.php
├── .htaccess
├── assets/
│   ├── css/app.css
│   └── js/app.js
├── config/
│   └── config.php          ← à créer (étape 5)
├── includes/
│   ├── auth.php
│   ├── db.php
│   ├── footer.php
│   ├── header.php
│   └── helpers.php
└── database/
    └── schema.sql          (optionnel après import)
```

---

## Étape 5 — Créer `config/config.php`

Dans `htdocs/config/`, créez le fichier **`config.php`** avec ce contenu :

```php
<?php
define('DB_HOST', 'sql211.infinityfree.com');
define('DB_NAME', 'if0_42810781_mapharmacieEve');
define('DB_USER', 'if0_42810781');
define('DB_PASS', 'VOTRE_MOT_DE_PASSE_MYSQL');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Nouvelle Eve');
define('APP_URL', 'https://mapharmaciepk.xo.je');
define('TIMEZONE', 'Africa/Abidjan');

date_default_timezone_set(TIMEZONE);
```

Remplacez `VOTRE_MOT_DE_PASSE_MYSQL` par votre mot de passe MySQL InfinityFree.

---

## Étape 6 — Tester l'application

1. Ouvrez : **https://mapharmaciepk.xo.je/login.php**
2. Connectez-vous :

| Champ | Valeur |
|-------|--------|
| Email | `admin@pharmagest.local` |
| Mot de passe | `admin123` |

3. Changez le mot de passe admin : menu **Utilisateurs** → icône clé

---

## Checklist rapide

- [ ] Fichiers uploadés dans `htdocs`
- [ ] `database/schema.sql` importé dans phpMyAdmin
- [ ] `config/config.php` créé avec les bons identifiants
- [ ] Connexion sur https://mapharmaciepk.xo.je/login.php OK
- [ ] Mot de passe admin changé

---

## Dépannage

| Erreur | Solution |
|--------|----------|
| Page blanche / Erreur 500 | Supprimez ou commentez les lignes `php_flag` dans `.htaccess` |
| « Configuration manquante » | Vérifiez que `config/config.php` existe |
| Erreur connexion BDD | Vérifiez hôte, base, user, mot de passe dans `config.php` |
| Site inaccessible | Attendez la propagation DNS (jusqu'à 72 h) ou utilisez **View Website** |

---

## Rôles utilisateurs

| Rôle | Accès |
|------|-------|
| **admin** | Tout + gestion utilisateurs |
| **pharmacien** | Médicaments, achats, ventes, stock |
| **caissier** | Ventes |

---

## Sécurité

- Ne partagez jamais `config.php` publiquement
- Changez le mot de passe MySQL si exposé
- Changez `admin123` dès la première connexion
