# FULL GOSPEL SMS

Application mobile Android pour envoyer des SMS via l'API SMS du compte Full Gospel.

## Fonctionnalités

- **Accueil** : affichage du solde de crédits SMS restants
- **Envoi** : composer et envoyer un SMS à un ou plusieurs numéros
- **Contacts** : importer des numéros depuis le répertoire téléphone
- **Historique** : consulter les messages envoyés
- **Expéditeur** : `FULL GOSPEL` (Sender ID approuvé)

## API utilisée

| Paramètre | Valeur |
|-----------|--------|
| Base URL | `http://164.68.101.225:6005/api/v2` |
| Sender ID | `FULL GOSPEL` |

Endpoints intégrés :
- `GET /Balance` — solde crédits
- `POST /SendSMS` — envoi de messages
- `GET /GetSMS` — historique des envois

## Prérequis

- [Flutter SDK](https://flutter.dev/docs/get-started/install) (3.27+)
- Android SDK (pour compiler l'APK)

## Installation et lancement

```bash
cd full_gospel_sms
flutter pub get
flutter run
```

## Compiler l'APK

```bash
cd full_gospel_sms
flutter build apk --release
```

L'APK sera généré dans :
`build/app/outputs/flutter-apk/app-release.apk`

## Configuration

Les identifiants API sont dans `lib/config/api_config.dart`.

> **Sécurité** : en production, stockez les clés API dans un coffre sécurisé (Keychain/Keystore) et ne les committez pas dans le dépôt.

## Permissions Android

- `INTERNET` — communication avec l'API SMS
- `READ_CONTACTS` — import des contacts (optionnel)

## Structure du projet

```
lib/
├── config/api_config.dart      # Configuration API
├── models/sms_models.dart      # Modèles de données
├── services/sms_api_service.dart # Client API REST
├── screens/                    # Écrans de l'application
├── theme/app_theme.dart        # Thème visuel
└── utils/phone_utils.dart      # Normalisation des numéros
```
