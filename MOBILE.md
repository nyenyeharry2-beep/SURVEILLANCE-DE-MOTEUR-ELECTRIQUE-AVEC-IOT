# Application mobile Nouvelle Eve (Android)

## APK

Téléchargez : `deploy/nouvelle-eve-mobile.apk`

## Fonctionnalités

- **Ventes** : enregistrer une vente (médicament, quantité, CDF ou USD)
- **Stock** : consulter les quantités en stock de tous les produits
- **Rapports** : rapport du jour et du mois + export PDF
- **Alertes** : stock faible, produits à écouler, expirés

## Important — hébergement InfinityFree

InfinityFree **bloque les applications mobiles** avec une page JavaScript (`aes.js`). Ce n'est **pas** un problème de dossier `api/` manquant.

L'APK **v1.3.0+** contourne cette protection automatiquement (cookie `__test` via WebView + déchiffrement AES).

**v1.3.2** corrige l'erreur « Connexion impossible » alors que le mot de passe est correct (requêtes réseau sur thread secondaire).

Si la connexion échoue encore, attendez 5 secondes et réessayez.

## Installation

1. **Désinstallez** l’ancienne version de l’app si elle est déjà installée
2. Sur le téléphone Android : autorisez **Sources inconnues**
3. Transférez et ouvrez `nouvelle-eve-mobile.apk` (version **1.3.2**)
4. Installez **Nouvelle Eve Vendeur** — vérifiez « Version 1.3.2 » en bas de l'écran de connexion

## Configuration

1. Uploadez le site web (ZIP) sur InfinityFree
2. Importez `database/migration_api_tokens.sql` dans phpMyAdmin
3. Installez l’APK sur le téléphone du vendeur
4. Connexion : **email + mot de passe** uniquement (pas d’URL à saisir)
5. L’app se connecte automatiquement à `https://mapharmaciepk.xo.je/api/`

Créez un compte **caissier** ou **pharmacien** dans le site web (Utilisateurs) pour chaque vendeur.

## Fonctionnalités (vendeurs uniquement)

- **Ventes** : enregistrer une vente
- **Stock** : voir les quantités disponibles
- **Rapports** : jour et mois + export PDF
- **Alertes** : stock faible et dates d’expiration

Aucune autre fonction (pas de gestion médicaments, utilisateurs, etc.) — réservée au site web admin.

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | auth/login | Connexion |
| GET | stock?q= | Stock complet (quantités) |
| POST | ventes | Créer une vente |
| GET | rapports/jour?date=YYYY-MM-DD | Rapport journalier |
| GET | rapports/mois?annee=2026&mois=9 | Rapport mensuel |
| GET | alertes?type=all\|stock\|ecouler\|expiration | Alertes |

Header : `Authorization: Bearer <token>`

## Développement

Projet Android : `mobile-android/`

```bash
cd mobile-android
./gradlew assembleDebug
```

APK généré : `app/build/outputs/apk/debug/app-debug.apk`
