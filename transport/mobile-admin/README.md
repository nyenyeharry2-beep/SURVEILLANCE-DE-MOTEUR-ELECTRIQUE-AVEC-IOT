# Super Genies Admin (APK Android)

Application Android **administration uniquement** pour le transport scolaire C.S LES SUPER GENIES.

- Ouvre : `https://genies.free.je/admin/login.php`
- Logo école au démarrage (écran splash)
- Accès limité aux pages `/admin/*`

## Reconstruire l'APK

```bash
python3 scripts/generate_icons.py
./gradlew assembleRelease
```

APK généré : `app/build/outputs/apk/release/app-release-unsigned.apk`
