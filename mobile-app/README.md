# Intelligence Platform — APK Mobile

Application Android empaquetée avec **Capacitor**, basée sur vos fichiers originaux :

- `src/modules/DarkWebScannerModule.tsx` — module Dark Web (inchangé)
- `src/data/app_views.txt` — liste des 59 modules (inchangé)

## Installation sur téléphone

1. Transférez `android/app/build/outputs/apk/debug/app-debug.apk` sur votre téléphone
2. Activez **Sources inconnues** dans les paramètres Android
3. Ouvrez le fichier APK et installez

## Développement

```bash
cd mobile-app
npm install
npm run build
npx cap sync android
cd android && ./gradlew assembleDebug
```

## Structure

- **Écran d'accueil** : grille des modules depuis `app_views.txt`
- **Module darkweb** : ouvre `DarkWebScannerModule` tel quel
- **Autres modules** : placeholder « à venir »
