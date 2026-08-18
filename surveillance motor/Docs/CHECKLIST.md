# CHECKLIST DE MISE EN SERVICE

## Matériel
- [ ] Arduino Uno alimenté (USB / 5 V)
- [ ] ESP32 alimenté (USB)
- [ ] ADXL345 sur Uno : SDA=A4, SCL=A5, alim 3,3 V (ou module 5 V régulé)
- [ ] Capteur IR sur Uno **D2** ; 1 marque contrastée / tour
- [ ] Module relais : IN → **D8**, VCC=5 V ; contacts = bobine BT contacteur seulement
- [ ] Buzzer sur **D9**
- [ ] UART : Uno **D4**→diviseur→ESP32 GPIO16 ; Uno **D3**←ESP32 GPIO17 ; GND commun
- [ ] Séparation 230 V / commande BT vérifiée

## Logiciel
- [ ] Sketch Uno flashé (`surveillance_moteur_uno.ino`)
- [ ] Sketch ESP32 flashé (`passerelle_firebase.ino`) avec Wi-Fi + Firebase
- [ ] Moniteur Uno : ADXL345 OK, IR OK, trames périodiques
- [ ] Moniteur ESP32 : Wi-Fi OK, `Uno=OK`, live Firebase

## Firebase / Web
- [ ] `seed_initial.json` importé
- [ ] `firebase-config.js` renseigné
- [ ] Badge en ligne ; RPM / vibrations / relais visibles
- [ ] Boutons Marche/Arrêt → relais D8
- [ ] Mute buzzer + auto-stop ALARME

## Critères « système fonctionne »
1. Trames `MEAS` reçues par l’ESP32  
2. RPM cohérent (tachymètre) avec marque IR  
3. A_RMS sensible à une excitation  
4. Relais D8 suit la commande Web ; OFF en ALARME si auto-stop  
5. Reconnexion Wi-Fi ESP32 après coupure  
