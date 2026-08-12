# CHECKLIST DE MISE EN SERVICE

## Matériel
- [ ] ESP32 DevKit V1 alimenté en USB / 5 V stable
- [ ] ADXL345 câblé en **3,3 V** (VCC, GND, SDA21, SCL22, CS→3V3 si besoin)
- [ ] Capteur Hall câblé ; OUT ≤ 3,3 V ; aimant fixé (1 pulse/tour)
- [ ] Fixation rigide de l’ADXL345 sur le carter
- [ ] Séparation physique puissance 230 V / commande BT vérifiée

## Logiciel embarqué
- [ ] Carte « ESP32 Dev Module » sélectionnée dans Arduino IDE
- [ ] Libs : Adafruit ADXL345 (+ Unified Sensor), Firebase ESP Client (Mobizt)
- [ ] `WIFI_SSID` / `WIFI_PASSWORD` renseignés
- [ ] `FIREBASE_HOST` / `FIREBASE_AUTH` renseignés
- [ ] Compilation sans erreur ; téléversement OK
- [ ] Moniteur série : ADXL345 OK, Wi-Fi IP, Firebase OK

## Firebase
- [ ] Projet créé + RTDB activée
- [ ] `seed_initial.json` importé
- [ ] Application Web créée ; clés copiées dans `firebase-config.js`
- [ ] Nœud `moteur/live` se met à jour depuis l’ESP32
- [ ] Nœud `moteur/config` modifiable
- [ ] Historique qui se remplit (~10 s)

## Interface Web
- [ ] `firebase-config.js` personnalisé
- [ ] Page ouverte (serveur local recommandé)
- [ ] Badge « ESP32 en ligne »
- [ ] Cartes État / Vibration / Vitesse / Diagnostic / Dernière mesure
- [ ] 3 graphiques qui défilent
- [ ] Statistiques min/max/moyenne
- [ ] Bannière alerte si seuils dépassés
- [ ] Alerte visuelle si liaison perdue (> 8 s)
- [ ] Formulaire Paramètres : validation min < nom < max ; enregistrement OK
- [ ] Affichage correct smartphone

## Mesures
- [ ] À l’arrêt : orientation cohérente (≈ 1 g sur un axe)
- [ ] En rotation : RPM non nul et stable
- [ ] Secousse / balourd test : A_RMS augmente
- [ ] Diagnostic change d’état selon seuils calibrés

## Sécurité & mémoire
- [ ] Aucun fil ESP32 sur 230 V
- [ ] Limites mm/s estimé expliquées dans le rapport
- [ ] Seuils présentés comme calibrables, non universels
- [ ] Plan de tests 1–10 exécuté et consignés
- [ ] Calculs d’erreur RPM documentés

## Critères « système fonctionne »
1. Données live rafraîchies ≤ 2 s dans Firebase et sur le Web  
2. RPM cohérent avec un tachymètre (erreur relative faible)  
3. A_RMS sensible à une excitation mécanique  
4. Passage volontaire en ALARME via seuils ou défaut simulé  
5. Reconnexion Wi-Fi automatique après coupure  
