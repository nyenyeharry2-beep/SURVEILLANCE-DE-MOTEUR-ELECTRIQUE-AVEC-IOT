# Fichiers à uploader dans File Manager (InfinityFree)

## Destination : dossier `htdocs`

Uploadez **tout le contenu** du dossier `backend/` directement dans `htdocs/`.

Structure finale sur le serveur :

```
htdocs/
├── .htaccess
├── index.php
├── config/
│   ├── app.php
│   ├── bootstrap.php
│   └── database.php
├── api/
│   ├── student.php
│   ├── admin/
│   │   ├── login.php
│   │   ├── upload.php
│   │   ├── stats.php
│   │   └── messages.php
│   ├── info/
│   │   ├── inscriptions.php
│   │   └── trousseau.php
│   └── messages/
│       └── send.php
├── parser/
│   └── PdfParser.php
├── uploads/          ← dossier vide (pour les PDF importés)
├── assets/           ← images (optionnel sur serveur)
└── sql/              ← NE PAS laisser public en production (voir note)
```

## Étapes File Manager

1. InfinityFree → **Files** → **File Manager**
2. Ouvrir le dossier **`htdocs`**
3. Uploader les dossiers et fichiers un par un OU uploader le ZIP et extraire

## Fichiers obligatoires (22 fichiers)

| Fichier | Rôle |
|---------|------|
| `.htaccess` | Routes API |
| `index.php` | Page d'accueil API |
| `config/database.php` | Connexion MySQL |
| `config/app.php` | Configuration école |
| `config/bootstrap.php` | Fonctions communes |
| `api/student.php` | Recherche matricule |
| `api/messages/send.php` | Messagerie parents |
| `api/admin/login.php` | Connexion admin |
| `api/admin/ping.php` | Test API admin (optionnel) |
| `api/admin/upload.php` | Import PDF |
| `api/admin/stats.php` | Statistiques |
| `api/admin/messages.php` | Messages facturation |
| `api/info/inscriptions.php` | Infos inscriptions |
| `api/info/trousseau.php` | Infos trousseau |
| `parser/PdfParser.php` | Lecture PDF |

## Dossier uploads

Créez un dossier vide **`uploads`** dans `htdocs` (permissions 755).
Les PDF importés par l'app Admin y seront enregistrés.

## Note sécurité

Le dossier `sql/` contient les scripts de base de données.
**Ne l'uploadez PAS sur htdocs** — utilisez phpMyAdmin pour importer les `.sql`.

## Vérification

Après upload, ouvrez dans le navigateur :
`https://supergenies2026.site.je/`

Vous devez voir un JSON avec la liste des endpoints API.

Test matricule :
`https://supergenies2026.site.je/api/student.php?matricule=CSLSG-2026-2027-00167`
