# KYRIOS — Guide de déploiement (InfinityFree)

## Prérequis

- Compte [InfinityFree](https://infinityfree.net/)
- Base de données MySQL créée dans le panneau
- Client FTP (FileZilla) ou gestionnaire de fichiers du panneau

## Étapes

### 1. Importer la base de données

1. Ouvrir **phpMyAdmin** depuis le panneau InfinityFree
2. Sélectionner votre base de données
3. Importer `kyrios/database/schema.sql`

### 2. Déployer l'API PHP

1. Uploader le dossier `kyrios/api/` vers `htdocs/kyrios/api/` sur le serveur
2. Créer les dossiers de stockage média :
   ```
   kyrios/api/storage/images/
   kyrios/api/storage/videos/
   kyrios/api/storage/audio/
   ```
3. Configurer les variables d'environnement dans le panneau ou via `.htaccess` :

```apache
SetEnv KYRIOS_DB_HOST sqlXXX.infinityfree.com
SetEnv KYRIOS_DB_NAME if0_XXXXXX_kyrios
SetEnv KYRIOS_DB_USER if0_XXXXXX
SetEnv KYRIOS_DB_PASS your_password
SetEnv KYRIOS_BASE_URL https://your-site.infinityfreeapp.com/kyrios/api/public
SetEnv KYRIOS_APP_DEBUG false
```

4. Vérifier que `public/index.php` est accessible :
   `https://your-site.infinityfreeapp.com/kyrios/api/public/`

### 3. Configurer l'application Android

Dans `android/app/build.gradle.kts`, mettre à jour :

```kotlin
buildConfigField("String", "API_BASE_URL", "\"https://your-site.infinityfreeapp.com/kyrios/api/public/\"")
```

Recompiler et installer l'APK.

### 4. HTTPS

InfinityFree fournit HTTPS gratuit. Activez-le dans le panneau et forcez HTTPS pour toutes les requêtes API.

## Migration vers une infrastructure de production

Avant un lancement à grande échelle, prévoir :

- Hébergement VPS ou cloud (DigitalOcean, AWS, etc.)
- CDN pour les médias (Cloudflare R2, AWS S3)
- WebSockets ou polling pour la messagerie temps réel
- Firebase Cloud Messaging pour les notifications push (V3)

## Sécurité

- Ne jamais committer les mots de passe MySQL
- Désactiver `KYRIOS_APP_DEBUG` en production
- Limiter la taille des uploads (`max_size_mb` dans config)
- Activer HTTPS obligatoire
