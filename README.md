# SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT

Système de surveillance de moteur avec IoT (Lumen + Firebase).

## Tableau de bord Firebase ne reçoit pas les données ?

1. **Lumen ESP32** envoie vers `mesure.php` (MySQL), pas directement Firebase.
2. Renseignez `FIREBASE_DB_URL` et `FIREBASE_AUTH` dans `config.php` → miroir auto vers `moteur/live`.
3. Collez la même config dans `webtableau-de-bord-firebase/firebase-config.js` (`databaseURL` obligatoire).
4. Importez `surveillance motor/Firebase/seed_initial.json` dans Firebase Console.

Package prêt : `webtableau-de-bord-firebase-LUMEN.zip`

Déploiement InfinityFree (PHP Lumen) : `LIRE.txt` → `/install.php`

Connexion tableau de bord PHP : e-mail `nyenyeharry2@gmail.com` (mot de passe dans `config.php`).
