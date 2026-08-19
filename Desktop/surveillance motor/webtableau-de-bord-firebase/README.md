LUMEN + Firebase — déploiement
==============================

Ce zip / dossier contient le **tableau de bord Firebase** (MOTORGUARD IoT).

ÉTAPES
------
1. Configurer Firebase (Realtime Database + import seed_initial.json)
2. Remplir firebase-config.js (databaseURL obligatoire)
3. Déployer ces fichiers (Netlify, InfinityFree sous-dossier, ou local)
4. Si ESP32 = Lumen_ESP32.ino : renseigner FIREBASE_DB_URL + FIREBASE_AUTH
   dans config.php du site PHP Lumen pour miroir automatique vers Firebase

CHEMIN FIREBASE ATTENDU
-----------------------
moteur/live          ← mesures temps réel
moteur/config        ← seuils
moteur/command       ← relais / buzzer
moteur/historique/   ← journal

ERREURS FRÉQUENTES
------------------
- Mode démo / badge « Firebase non configuré » → PLACEHOLDER dans firebase-config.js
- Données vides → Lumen envoie vers PHP, pas Firebase (voir étape 4)
- Site_VoltWatch lisait motors/motor1 (corrigé → moteur/live)

Compte Lumen PHP (autre tableau de bord) : nyenyeharry2@gmail.com
