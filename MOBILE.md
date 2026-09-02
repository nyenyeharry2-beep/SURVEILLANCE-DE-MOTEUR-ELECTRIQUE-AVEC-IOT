# Application mobile Nouvelle Eve (Android)

## APK

Téléchargez : `deploy/nouvelle-eve-mobile.apk`

## Fonctionnalités

- **Ventes** : enregistrer une vente (médicament, quantité, CDF ou USD)
- **Rapports** : rapport du jour et du mois
- **Alertes** : stock faible, produits à écouler, expirés
- **PDF** : exporter le rapport en PDF et le partager (WhatsApp, email, etc.)

## Installation

1. Sur le téléphone Android : autorisez **Sources inconnues** / **Installer apps inconnues**
2. Transférez et ouvrez `nouvelle-eve-mobile.apk`
3. Installez l'application **Nouvelle Eve**

## Configuration serveur

1. Uploadez d'abord le site web (ZIP) sur InfinityFree
2. Importez `database/migration_api_tokens.sql` dans phpMyAdmin (table `api_tokens`)
3. Dans l'app, URL serveur par défaut :
   ```
   https://mapharmaciepk.xo.je/api/
   ```
4. Connexion : `admin@pharmagest.local` / `admin123`

## API REST

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | auth/login | Connexion |
| GET | medicaments | Liste des produits |
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
