# Installation APK — Lumen Reader

## Télécharger l'APK

Fichier généré localement :

```
releases/lumen-reader-debug.apk
```

Taille approximative : **~65 Mo** (OCR + PDF inclus, fonctionne hors ligne).

## Installer sur Android

1. Copiez `lumen-reader-debug.apk` sur votre téléphone (USB, WhatsApp, Google Drive…)
2. Ouvrez le fichier sur Android
3. Autorisez **Sources inconnues** si demandé
4. Installez **Lumen Reader**

## Reconstruire l'APK vous-même

```bash
cd pdf-reader
npm install
npm run android:build
```

APK produit :

```
android/app/build/outputs/apk/debug/app-debug.apk
```

## Prérequis (build local)

- Node.js 18+
- Java 17 ou 21
- Android SDK (ANDROID_HOME configuré)

## Fonctionnement

- **100 % hors ligne** après installation
- Import PDF, OCR, synthèse vocale, bibliothèque, progression
- Données stockées sur l'appareil

## GitHub Actions

Chaque push sur la branche déclenche aussi un build APK téléchargeable dans **Actions → Artifacts**.
