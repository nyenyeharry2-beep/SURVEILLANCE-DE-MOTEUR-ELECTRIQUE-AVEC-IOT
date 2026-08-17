# SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT

Système de surveillance de moteur avec IoT (Lumen).

Déploiement InfinityFree : suivre `LIRE.txt`, puis ouvrir `/install.php`.

L’ESP32 envoie les mesures en **POST** vers `http://VOTRE-DOMAINE/mesure.php` (voir `Lumen_ESP32/`). Un 404 HTML nginx signifie que ce fichier n’est pas sur l’hébergeur, ou que l’URL pointe vers un site statique (Netlify) au lieu d’InfinityFree.

Connexion tableau de bord : e-mail `nyenyeharry2@gmail.com` (mot de passe dans `config.php`, constante `APP_PASSWORD`).
