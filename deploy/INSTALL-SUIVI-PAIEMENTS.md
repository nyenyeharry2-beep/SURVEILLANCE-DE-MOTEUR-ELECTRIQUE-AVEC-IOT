# Suivi paiements — Web + APK v1.1.0

## Problème résolu

L'ancienne app Paiements utilisait des appels API (OkHttp) bloqués par InfinityFree → « Service momentanément indisponible » et bouton Envoyer grisé.

**Solution :** portail web `suivi.php` + app Android en WebView (comme l'Admin v1.1.1).

## 1. Déployer sur InfinityFree (File Manager → htdocs)

Télécharger et extraire dans `htdocs/` :

- [supergenies-patch-suivi-paiements.zip](https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/raw/cursor/super-genies-paiements-019d/deploy/supergenies-patch-suivi-paiements.zip)

Le fichier `suivi.php` doit être à la racine de `htdocs/` (à côté de `index.php`).

## 2. Tester dans le navigateur (avant l'APK)

Ouvrir sur téléphone ou PC :

```
http://supergenies2026.site.je/suivi.php
```

- **Accueil** : rechercher un matricule (ex. CSLSG-2026-2027-00167)
- **Messagerie** : remplir nom, téléphone, matricule, message (≥ 10 caractères) → Envoyer

Si « Aucun élève trouvé », importer les PDF via l'Admin (portail web).

## 3. Installer les nouvelles APK

[Télécharger supergenies-apk-v1.1.0.zip](https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/raw/cursor/super-genies-paiements-019d/supergenies-apk-v1.1.0.zip)

- `SuperGenies-Paiements-v1.1.0.apk` — parents (WebView → suivi.php)
- `SuperGenies-Admin-v1.1.1.apk` — administration (WebView → connexion.php)

Désinstaller l'ancienne app Paiements avant d'installer v1.1.0.
