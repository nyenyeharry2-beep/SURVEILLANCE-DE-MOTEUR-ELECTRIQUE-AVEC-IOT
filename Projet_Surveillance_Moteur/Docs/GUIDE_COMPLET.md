# GUIDE COMPLET — Système de maintenance prédictive IoT

Ce document suit l’ordre demandé : **ÉTAPE 1 → ÉTAPE 16**, puis renvoie vers la checklist.

---

## ÉTAPE 1 — Architecture générale

### Chaîne vibrations

```
MOTEUR ÉLECTRIQUE
    ↓ (accélérations mécaniques transmises au carter)
ADXL345 (accéléromètre 3 axes, I2C)
    ↓
ESP32 (acquisition, RMS, estimation mm/s, diagnostic)
    ↓ Wi-Fi
Firebase Realtime Database
    ↓
INTERFACE WEB (cartes, graphiques, alertes)
    ↓
SURVEILLANCE TEMPS RÉEL → ANALYSE / DIAGNOSTIC / ALERTE
```

### Chaîne vitesse

```
MOTEUR (arbre / ventilateur / poulie)
    ↓
AIMANT + CAPTEUR HALL (1 impulsion / tour)
    ↓ interruption GPIO
ESP32 (comptage, calcul RPM)
    ↓
Firebase → Interface Web
```

### Rôle de chaque élément

| Élément | Rôle |
|--------|------|
| Moteur électrique | Organe surveillé (charge, balourd, roulements, fixation…). |
| ADXL345 | Convertit les vibrations en accélérations Ax, Ay, Az. |
| Capteur Hall + aimant | Convertit la rotation en impulsions TTL 3,3 V. |
| ESP32 | Cœur embarqué : mesure, calcul, diagnostic local, IoT. |
| Wi-Fi | Liaison vers Internet / Firebase. |
| Firebase RTDB | Stockage temps réel + config des seuils + historique court. |
| Interface Web | Supervision humaine : valeurs, tendances, alertes, réglages. |
| Module diagnostic | Interprète RPM + vibration → état + hypothèse de panne. |

---

## ÉTAPE 2 — Liste complète du matériel

| Qté | Matériel | Remarque |
|----:|----------|----------|
| 1 | ESP32 DevKit V1 (WROOM-32) | Wi-Fi intégré, 3,3 V |
| 1 | ADXL345 (module I2C) | Alimenter en **3,3 V** |
| 1 | Capteur Hall (ex. A3144 / KY-003 / module 3144) | Sortie compatible 3,3 V ou diviseur |
| 1 | Aimant néodyme petit | Collé sur arbre / ventilateur |
| 1 | Module relais 5 V (1 canal, optocoupleur) | Commande bobine contacteur BT |
| 1 | Buzzer actif 3,3/5 V | Alarme locale (+ transistor si besoin) |
| 1 | Breadboard + fils Dupont | Prototypage |
| 1 | Alimentation 5 V USB (ESP32) | Ou régulateur 5 V isolé |
| 1 | Support / boîtier | Fixation ADXL345 **rigide** sur carter |
| — | PC + Arduino IDE | Développement |
| — | Compte Firebase | Cloud |
| Option | Multimètre, stroboscope / tachymètre laser | Validation RPM |
| Option | Vibromètre de référence | Validation vibrations |
| Option | Relais + contacteur | Si commande marche/arrêt (jamais 230 V direct) |

**Consommables sécurité** : disjoncteur, contacteur, arrêt d’urgence côté puissance.

---

## ÉTAPE 3 — Schéma de câblage textuel

Voir aussi `CABLAGE.md`.

```
ADXL345 VCC  →  3V3 ESP32
ADXL345 GND  →  GND
ADXL345 SDA  →  GPIO 21
ADXL345 SCL  →  GPIO 22
ADXL345 CS   →  3V3   (force le mode I2C sur beaucoup de modules)
ADXL345 SDO  →  GND   (adresse I2C 0x53)  [selon module]

Hall VCC     →  3V3 (si module 3,3 V)  OU 5 V + adaptation de niveau
Hall GND     →  GND
Hall OUT     →  GPIO 18  (INPUT_PULLUP, front descendant)

Relais 5 V VCC → 5 V (VIN USB)
Relais 5 V GND → GND
Relais 5 V IN  → GPIO 26  (souvent active LOW)
Relais COM/NO  → bobine contacteur BT uniquement (pas le 230 V moteur)

Buzzer +       → GPIO 25 (transistor NPN si courant élevé)
Buzzer −       → GND

LED intégrée GPIO 2 → statut Wi-Fi (déjà sur DevKit)
```

**Règle** : jamais une sortie **5 V** directement sur une entrée ESP32.

---

## ÉTAPE 4 — Configuration ESP32

### Caractéristiques utiles

- Dual-core 240 MHz, Wi-Fi 802.11 b/g/n
- GPIO avec interruptions (RPM)
- I2C matériel (ADXL345)
- Alimentation USB 5 V → régulateur 3,3 V interne
- Logique **3,3 V**

### Tableau GPIO

| Fonction | GPIO | Direction | Notes |
|----------|------|-----------|-------|
| I2C SDA | 21 | Bidirectionnel | ADXL345 |
| I2C SCL | 22 | Sortie horloge | ADXL345 |
| Hall OUT | 18 | Entrée + ISR | Pull-up, debounce 3 ms |
| Relais IN | 26 | Sortie | Module 5 V, active LOW par défaut |
| Buzzer | 25 | Sortie | Alarme locale |
| LED statut | 2 | Sortie | Connexion Wi-Fi |

Adresse I2C ADXL345 typique : **0x53** (SDO au GND).

### Communication Firebase

Le firmware utilise **Firebase ESP Client (Mobizt)** avec :
- `FIREBASE_HOST` = URL RTDB (sans `https://`)
- `FIREBASE_AUTH` = secret de base de données (legacy) **ou** token

Écriture : `/moteur/live` chaque seconde ; lecture `/moteur/config` toutes les 5 s ; lecture `/moteur/command` chaque seconde (relais / mute) ; push `/moteur/historique` toutes les 10 s.

**Actionneurs** : en ALARME avec `auto_stop_on_alarm`, le relais est forcé OFF ; le buzzer bippe (lent en AVERTISSEMENT, rapide en ALARME) sauf mute.

---

## ÉTAPE 5 — Installation Arduino IDE et bibliothèques

1. Installer [Arduino IDE 2.x](https://www.arduino.cc/en/software).
2. Ajouter le support ESP32 :  
   Fichier → Préférences → URL cartes supplémentaires :  
   `https://espressif.github.io/arduino-esp32/package_esp32_index.json`  
   Puis Outils → Type de carte → Gestionnaire → **esp32 by Espressif**.
3. Carte : **ESP32 Dev Module**, vitesse upload 115200.

### Bibliothèques

| Bibliothèque | Où | Pourquoi |
|--------------|----|----------|
| **Adafruit ADXL345** | Gestionnaire de bibliothèques → « Adafruit ADXL345 » | Driver accéléromètre |
| **Adafruit Unified Sensor** | Installée comme dépendance | Abstraction capteurs Adafruit |
| **Firebase ESP Client** (Mobizt) | Gestionnaire → « Firebase ESP Client » | RTDB set/get JSON |
| **WiFi** (intégré ESP32) | Déjà fourni | Connexion réseau |
| **Wire** (intégré) | Déjà fourni | Bus I2C |

Ne pas mélanger d’anciennes libs `FirebaseESP32` et `Firebase ESP Client` dans le même sketch.

Ouvrir `ESP32/surveillance_moteur/surveillance_moteur.ino`, renseigner Wi-Fi + Firebase, compiler, téléverser.

---

## ÉTAPE 6 — Configuration Firebase

1. Aller sur [https://console.firebase.google.com](https://console.firebase.google.com).
2. **Ajouter un projet** → nom libre → désactiver Analytics si inutile.
3. **Ajouter une application Web** → copier `apiKey`, `authDomain`, `databaseURL`, `projectId`, `appId` dans `Web/firebase-config.js`.
4. Menu **Build → Realtime Database → Créer** → choisir une région → démarrer en mode test (puis durcir les règles).
5. Onglet **Données** → ⋮ → **Importer JSON** → `Firebase/seed_initial.json`.
6. Onglet **Règles** → coller `Firebase/database.rules.json` (mode mémoire) ou règles Auth.
7. Paramètres projet → **Comptes de service** / **Secrets de base de données** (si affiché) → copier le secret pour `FIREBASE_AUTH` dans le `.ino`.  
   *Note : les secrets legacy sont dépréciés ; pour un mémoire c’est encore courant. En production, préférer Auth + règles.*
8. Dans le `.ino`, `FIREBASE_HOST` = URL complète `databaseURL` (avec `https://`, sans `/` final).

Connexion ESP32 : `Firebase.begin` + `RTDB.setJSON` / `getJSON`.  
Lecture Web : SDK JS `firebase.database().ref('moteur/live').on('value', ...)`.

---

## ÉTAPE 7 — Structure Firebase

Voir `Firebase/database_structure.txt`. Résumé :

- `moteur/live` : dernière mesure
- `moteur/config` : seuils (Web ↔ ESP32)
- `moteur/historique/<id>` : journal

---

## ÉTAPE 8 — Code ESP32 COMPLET

Fichier : `ESP32/surveillance_moteur/surveillance_moteur.ino`  
(complet, compilable après configuration des macros Wi-Fi / Firebase).

Fonctions principales : `initSensor`, `readVibration`, `calculateRMS`, `estimateVelocityRmsMmS`, `calculateRPM`, `diagnoseMotor`, `updateActuators`, `setRelay`, `updateBuzzerPattern`, `connectWiFi`, `connectFirebase`, `handleConnection`, `sendDataFirebase`, `loadConfigFromFirebase`, `loadCommandFromFirebase`.

---

## ÉTAPE 9 — Explication détaillée du code

1. **setup** : pins, I2C ADXL345, ISR Hall, Wi-Fi, Firebase, config défaut.
2. **loop** : mesure vib → RPM → diagnostic → (périodique) config / envoi / historique ; `handleConnection` gère reconnexions.
3. **Vibrations** : N=64 échantillons ~500 Hz, retrait de moyenne (gravité), A_RMS vectoriel, estimation mm/s par intégration trapézoïdale.
4. **RPM** : `pulseCount` incrémenté en ISR avec debounce 3 ms ; chaque fenêtre 1 s :  
   `RPM = (Δimpulsions / PULSES_PER_REV) × (60000 / Δt_ms)`.
5. **Diagnostic** : croise seuils `vib_*` et `a_rms_*` avec plage RPM ; produit `status`, `diagnostic`, `anomaly_hint`.
6. **Firebase** : JSON live ; garde-fous si `rpm_min >= rpm_max` ; commandes `moteur/command/relay` et `buzzer_mute`.
7. **Relais / buzzer** : `updateActuators()` applique la consigne ; arrêt auto sur ALARME ; patterns buzzer non bloquants.

---

## ÉTAPE 10 — Interface Web COMPLÈTE

Fichiers `Web/index.html`, `style.css`, `app.js`, `firebase-config.js`.

Affiche : état, vibration estimée mm/s, A_RMS, Ax/Ay/Az, RPM, nominale, niveau, diagnostic, commande relais/buzzer, horodatage, graphiques Chart.js, historique + min/max/moyenne, alertes, paramètres.

Ouvrir via un serveur local recommandé :

```bash
cd Projet_Surveillance_Moteur/Web
python3 -m http.server 8080
```

Puis navigateur : `http://localhost:8080`.

---

## ÉTAPE 11 — Explication HTML / CSS / JavaScript

- **HTML** : structure sémantique des cartes supervision + formulaires seuils.
- **CSS** : thème industriel (ardoise / turquoise), grille responsive, indicateurs d’état, animations légères (apparition cartes, LED alarme).
- **JS** : init Firebase, listeners RTDB, Chart.js (3 graphes), stats, validation des seuils avant écriture, détection « ESP32 hors ligne » si pas de refresh < 8 s.

---

## ÉTAPE 12 — Diagnostic des anomalies

### Seuils vibrations (configurables — non universels)

| Condition | État |
|-----------|------|
| vib < normale et A_RMS < normal | NORMAL |
| entre normale et alerte | SURVEILLANCE |
| entre alerte et critique | AVERTISSEMENT |
| ≥ critique | ALARME |

Les valeurs dépendent du **type de moteur**, **puissance**, **montage**, **norme** (ex. ISO 10816). Les chiffres du seed (2,8 / 4,5 / 7,1 mm/s) sont des **exemples pédagogiques** à recalibrer.

### Vitesse

- proche de `rpm_nominal` et dans `[rpm_min, rpm_max]` → OK  
- trop bas / trop haut → anomalie possible

### Combinaisons (aide, pas certitude)

| Observation | Hypothèse affichée |
|-------------|-------------------|
| Vib haute + RPM OK | Déséquilibre, roulement, fixation… |
| Vib haute + RPM anormal | Mécanique + entraînement / surcharge… |
| Vib faible + RPM bas | Alimentation, charge, glissement… |
| Vib en hausse progressive | Anomalie à surveiller |

**Limite** : deux paramètres ne suffisent pas à identifier une panne avec certitude (pas d’analyse fréquentielle FFT, pas de courant, pas de température).

---

## ÉTAPE 13 — Tests

Voir `TESTS_ET_VALIDATION.md` (Tests 1 à 10).

---

## ÉTAPE 14 — Validation expérimentale

Voir `TESTS_ET_VALIDATION.md` (erreurs absolue / relative / %).

---

## ÉTAPE 15 — Partie scientifique du mémoire

Voir `PARTIE_SCIENTIFIQUE.md`.

---

## ÉTAPE 16 — Améliorations et perspectives

1. FFT embarquée (ArduinoFFT) pour détecter balourd (1×RPM), alignement (2×RPM), roulements.
2. Capteur IEPE / vibromètre industriel pour mm/s traçables ISO 10816.
3. Mesure courant (SCT-013) + température (DS18B20).
4. Auth Firebase + règles strictes ; Cloud Functions de purge historique.
5. Passage Firestore / InfluxDB pour long terme.
6. Boîtier IP54, alimentation isolée, CEM.
7. Notification email / Telegram sur ALARME.
8. Machine learning léger (seuils adaptatifs) après campagne de mesures.

---

Checklist finale : `CHECKLIST.md`.  
Équations : `EQUATIONS.md`.  
Câblage détaillé : `CABLAGE.md`.
