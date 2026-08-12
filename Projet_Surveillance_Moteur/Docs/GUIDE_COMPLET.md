# GUIDE COMPLET — Système de maintenance prédictive IoT

Ce document suit l’ordre demandé : **ÉTAPE 1 → ÉTAPE 16**, puis renvoie vers la checklist.

**Architecture retenue** : **Arduino Uno** = tous les capteurs + relais (D8) + buzzer ; **ESP32** = passerelle Wi-Fi / Firebase ; **capteur IR** pour le RPM (plus de Hall).

---

## ÉTAPE 1 — Architecture générale

### Chaîne vibrations

```
MOTEUR ÉLECTRIQUE
    ↓
ADXL345 (I2C → Uno A4/A5)
    ↓
Arduino Uno (RMS, estimation mm/s, diagnostic)
    ↓ UART 9600
ESP32 (passerelle)
    ↓ Wi-Fi
Firebase Realtime Database
    ↓
INTERFACE WEB → SURVEILLANCE / DIAGNOSTIC / ALERTE
```

### Chaîne vitesse

```
MOTEUR (marque contrastée sur arbre / ventilateur)
    ↓
CAPTEUR IR (1 impulsion / tour) → Uno D2 (INT0)
    ↓
Arduino Uno (calcul RPM)
    ↓ UART
ESP32 → Firebase → Interface Web
```

### Chaîne actionneurs

```
Interface Web / Firebase command
    ↓
ESP32 → UART → Arduino Uno
    ├─ Relais D8 → bobine contacteur BT
    └─ Buzzer D9 → alerte locale
```

### Rôle de chaque élément

| Élément | Rôle |
|--------|------|
| Moteur électrique | Organe surveillé. |
| ADXL345 | Vibrations → Ax, Ay, Az. |
| Capteur IR | Rotation → impulsions (1 marque = 1 tour). |
| Arduino Uno | Acquisition, RMS, RPM, diagnostic, relais D8, buzzer. |
| ESP32 | Passerelle Wi-Fi / Firebase uniquement. |
| Firebase RTDB | Live, config, commandes, historique. |
| Interface Web | Supervision, graphiques, Marche/Arrêt, seuils. |

---

## ÉTAPE 2 — Liste complète du matériel

| Qté | Matériel | Remarque |
|----:|----------|----------|
| 1 | **Arduino Uno** | Capteurs + relais D8 + buzzer |
| 1 | **ESP32 DevKit V1** | Passerelle Wi-Fi / Firebase |
| 1 | ADXL345 (module I2C) | 3,3 V (ou module 5 V régulé) |
| 1 | **Capteur IR** (TCRT5000 / KY-033 / obstacle IR) | RPM — sortie digitale |
| 1 | Marque réfléchissante / bande noire-blanche | 1 marque / tour |
| 1 | Module relais 5 V | **IN → D8 Uno** |
| 1 | Buzzer actif | D9 Uno |
| 2 | Résistances (ex. 2,2 kΩ + 3,3 kΩ) | Diviseur TX Uno → RX ESP32 |
| 1 | Breadboard + fils | Prototypage |
| — | PC + Arduino IDE + Firebase | — |

**Consommables sécurité** : disjoncteur, contacteur, AU côté puissance.

---

## ÉTAPE 3 — Schéma de câblage textuel

Voir `CABLAGE.md` (référence complète).

```
--- Arduino Uno ---
ADXL345 VCC/GND/SDA/SCL → 3V3/GND/A4/A5
IR VCC/GND/OUT          → 5V/GND/D2
Relais VCC/GND/IN       → 5V/GND/D8
Buzzer +/-              → D9/GND
Uno D11 (TX) --diviseur--> ESP32 GPIO16 (RX)
Uno D10 (RX) <----------- ESP32 GPIO17 (TX)
GND Uno ---------------- GND ESP32

--- ESP32 ---
GPIO2 LED Wi-Fi
(pas de capteurs sur l'ESP32)
```

**Règle** : jamais 5 V direct sur une entrée ESP32 ; jamais 230 V sur Uno/ESP32.

---

## ÉTAPE 4 — Configuration microcontrôleurs

### Arduino Uno

| Fonction | Broche | Notes |
|----------|--------|-------|
| I2C SDA | A4 | ADXL345 |
| I2C SCL | A5 | ADXL345 |
| IR OUT | D2 | INT0, debounce 3 ms |
| Relais IN | **D8** | Active LOW par défaut |
| Buzzer | D9 | Alarme locale |
| UART→ESP32 TX | D11 | SoftwareSerial 9600 |
| UART←ESP32 RX | D10 | SoftwareSerial 9600 |

### ESP32 (passerelle)

| Fonction | GPIO | Notes |
|----------|------|-------|
| Serial2 RX | 16 | Depuis Uno (via diviseur) |
| Serial2 TX | 17 | Vers Uno |
| LED Wi-Fi | 2 | — |

### Protocole UART

- Uno → ESP32 : `MEAS,ax,ay,az,a_rms,vib,rpm,status,alert,relay,buzzer,mute,diag,hint`
- ESP32 → Uno : `CMD,relay,mute` et `CFG,rpm_nom,...`

Firebase : `passerelle_firebase.ino` (`FIREBASE_HOST` avec `https://`, secret legacy).

---

## ÉTAPE 5 — Installation Arduino IDE et bibliothèques

1. Installer Arduino IDE 2.x.
2. Support ESP32 : URL `https://espressif.github.io/arduino-esp32/package_esp32_index.json`.
3. Flasher **deux** sketches :
   - Carte **Arduino Uno** → `Arduino_Uno/surveillance_moteur_uno/`
   - Carte **ESP32 Dev Module** → `ESP32/passerelle_firebase/`

### Bibliothèques

| Bibliothèque | Carte | Pourquoi |
|--------------|-------|----------|
| Adafruit ADXL345 | Uno | Vibrations |
| Adafruit Unified Sensor | Uno | Dépendance |
| SoftwareSerial | Uno | Lien ESP32 |
| Firebase ESP Client (Mobizt) | ESP32 | RTDB |
| WiFi (core) | ESP32 | Réseau |

Détails : `Arduino_Uno/BIBLIOTHEQUES.txt` et `ESP32/BIBLIOTHEQUES.txt`.

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

- `moteur/live` : dernière mesure (dont `speed_sensor=IR`, `controller=Arduino_Uno`)
- `moteur/config` : seuils (Web → ESP32 → Uno)
- `moteur/command` : relais / mute (Web → ESP32 → Uno D8 / buzzer)
- `moteur/historique/<id>` : journal

---

## ÉTAPE 8 — Codes COMPLETS

- Uno : `Arduino_Uno/surveillance_moteur_uno/surveillance_moteur_uno.ino`
- ESP32 : `ESP32/passerelle_firebase/passerelle_firebase.ino`

---

## ÉTAPE 9 — Explication détaillée du code

1. **Uno setup** : ADXL345, IR INT0, relais D8 OFF, SoftwareSerial ESP32.
2. **Uno loop** : vibration → RPM IR → diagnostic → commandes ESP32 → actionneurs → trame `MEAS`.
3. **ESP32** : Wi-Fi/Firebase ; parse `MEAS` ; publie `live` ; lit `config`/`command` ; envoie `CFG`/`CMD` à l’Uno.
4. **RPM IR** : `RPM = (Δimpulsions / PULSES_PER_REV) × (60000 / Δt_ms)` avec 1 marque/tour.
5. **Relais D8** : consigne Web ; force OFF si ALARME + auto-stop.

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
