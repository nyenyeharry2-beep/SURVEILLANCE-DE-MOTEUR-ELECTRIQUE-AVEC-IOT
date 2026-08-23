# Invitations Moïse & Sarah

Application mobile Android pour générer et envoyer des invitations de mariage personnalisées à partir d'un **modèle d'affiche fixe**.

## Télécharger l'APK

**Lien direct (après push sur GitHub) :**

```
https://github.com/<votre-repo>/raw/cursor/invitation-generator-apk-06a4/releases/invitations-moise-sarah-v1.0.0.apk
```

**Fichier local dans ce dépôt :** [`releases/invitations-moise-sarah-v1.0.0.apk`](releases/invitations-moise-sarah-v1.0.0.apk)

### Installation sur téléphone Android

1. Téléchargez le fichier `.apk` sur votre téléphone.
2. Ouvrez le fichier et autorisez **« Sources inconnues »** si Android le demande.
3. Installez **Invitations Moïse & Sarah** (icône = affiche d'invitation florale violette).

## Fonctionnalités

| Module | Description |
|--------|-------------|
| **Configuration** | Date, lieu, message WhatsApp, téléversement de l'affiche HD |
| **Invités** | Nom, WhatsApp (`243XXXXXXXXX`), places, table/zone |
| **Contacts** | Import depuis le carnet / enregistrement dans le téléphone |
| **Génération** | Superposition du nom, placement et QR code unique sur le modèle |
| **WhatsApp** | Envoi via `wa.me` avec message personnalisé `{NAME}`, `{DATE}`, `{VENUE}` |
| **Dashboard** | Liste, recherche, filtres par table, export CSV |

## Stack technique

- **Expo SDK 57** + **React Native** + **expo-router**
- Rendu : `react-native-view-shot` + `react-native-qrcode-svg`
- Stockage local : `@react-native-async-storage/async-storage`
- Contacts : `expo-contacts`
- WhatsApp : lien `https://wa.me/243...`

## Lancer en développement

```bash
cd invitation-app
npm install --legacy-peer-deps
npm run generate-assets
npm start
```

Scannez le QR code avec **Expo Go** sur Android.

## Reconstruire l'APK

```bash
cd invitation-app
npm install --legacy-peer-deps
python3 scripts/generate_assets.py
npx expo prebuild --platform android
cd android && ./gradlew assembleRelease
```

L'APK se trouve dans :
`invitation-app/android/app/build/outputs/apk/release/app-release.apk`

Un workflow GitHub Actions (`.github/workflows/build-apk.yml`) reconstruit automatiquement l'APK à chaque push.

## Structure

```
invitation-app/
├── app/              # Écrans (config, invités, aperçu, dashboard)
├── components/       # InvitationCanvas, GuestListItem
├── lib/              # Types, stockage, WhatsApp, export
├── assets/           # Modèle d'invitation + icône de l'app
└── scripts/          # Génération des assets visuels
```

## Personnalisation du modèle

1. Allez dans **Configurer l'événement**.
2. Appuyez sur l'affiche pour téléverser votre PNG/JPG HD.
3. Les zones de superposition (nom, QR, placement) sont définies dans `lib/types.ts` → `DEFAULT_TEMPLATE_CONFIG`.

---

*Mariage civil de Moïse NKUBA & Sarah KASONGO — Kipushi, 11 Septembre 2026*
