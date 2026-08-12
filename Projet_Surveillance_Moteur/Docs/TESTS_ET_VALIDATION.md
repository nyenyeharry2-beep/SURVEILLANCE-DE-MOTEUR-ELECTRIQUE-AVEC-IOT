# PLAN DE TESTS ET VALIDATION EXPÉRIMENTALE

## Tests fonctionnels

### Test 1 — ESP32 seul
- **Objectif** : vérifier alimentation et port série.
- **Procédure** : flasher un `Blink` ou ce firmware ; ouvrir moniteur 115200 bauds.
- **Attendu** : messages de boot ; LED GPIO2 réagit à la tentative Wi-Fi.

### Test 2 — ESP32 + ADXL345
- **Objectif** : bus I2C opérationnel.
- **Procédure** : câbler SDA/SCL/3V3/GND ; démarrer firmware.
- **Attendu** : `[OK] ADXL345 initialisé` ; sinon vérifier adresse / CS / alimentation 3,3 V.

### Test 3 — Lecture vibrations
- **Objectif** : Ax, Ay, Az, A_RMS cohérents.
- **Procédure** : moteur à l’arrêt : Az ≈ ±1 g selon orientation ; secouer légèrement le capteur.
- **Attendu** : variation des axes ; A_RMS augmente pendant la secousse.

### Test 4 — Capteur de vitesse
- **Objectif** : impulsions Hall détectées.
- **Procédure** : passer l’aimant devant le Hall à la main ; observer `pulseCount` via RPM non nul après 1 s.
- **Attendu** : fronts propres ; pas de comptage fou (= rebond) grâce au debounce.

### Test 5 — Calcul RPM
- **Objectif** : formule RPM correcte.
- **Procédure** : tourner à vitesse connue (moteur + tachymètre) pendant ≥ 10 s.
- **Attendu** : RPM firmware ≈ tachymètre (voir validation).

### Test 6 — Connexion Wi-Fi
- **Objectif** : association AP.
- **Procédure** : SSID/mot de passe corrects ; redémarrer.
- **Attendu** : IP affichée ; LED allumée.

### Test 7 — Firebase
- **Objectif** : écriture RTDB.
- **Procédure** : host + secret ; console Firebase ouverte sur `moteur/live`.
- **Attendu** : champs mis à jour ~1 Hz ; `online: true`.

### Test 8 — Interface Web
- **Objectif** : lecture temps réel.
- **Procédure** : configurer `firebase-config.js` ; servir `Web/` ; ouvrir la page.
- **Attendu** : cartes et graphiques animés ; badge « en ligne ».

### Test 9 — Simulation vibration élevée
- **Objectif** : chaînes d’alerte.
- **Procédure** : baisser temporairement `vib_critique_mms` / `a_rms_critique_ms2` dans Paramètres **ou** vibrer fortement le support.
- **Attendu** : état AVERTISSEMENT/ALARME ; bannière ⚠ ; message diagnostic.

### Test 10 — Simulation vitesse anormale
- **Objectif** : surveillance RPM.
- **Procédure** : régler `rpm_min` proche de la valeur actuelle ou freiner/accélérer hors plage.
- **Attendu** : anomalie vitesse + hypothèse associée.

### Test reconnexion
- Couper le Wi-Fi 20 s puis rétablir → messages `[WiFi] Reconnexion...` puis reprise des envois.

### Test 11 — Relais 5 V
- **Objectif** : commande marche/arrêt via Firebase / Web.
- **Procédure** : sans 230 V ; LED du module relais ; boutons Marche/Arrêt de l’interface.
- **Attendu** : clic audible du relais ; `relay_state` suit la commande ; au boot = OFF.

### Test 12 — Buzzer
- **Objectif** : alerte sonore locale.
- **Procédure** : forcer AVERTISSEMENT puis ALARME (seuils abaissés) ; tester mute.
- **Attendu** : bip lent puis rapide ; mute coupe le son ; `buzzer_enabled=false` désactive.

### Test 13 — Arrêt auto sur ALARME
- **Objectif** : sécurité `auto_stop_on_alarm`.
- **Procédure** : relais ON, provoquer ALARME.
- **Attendu** : relais passe OFF ; `moteur/command/relay` remis à `false`.

---

## Validation expérimentale

### A. Vitesse

1. Mesurer \(RPM_{réel}\) au tachymètre laser / stroboscope.
2. Noter \(RPM_{mesuré}\) (interface ou série) sur 10 points.
3. Calculer \(E\), \(E_r\), \(E_{\%}\) (voir `EQUATIONS.md`).
4. Précision : \(\overline{E_{\%}}\) et écart-type.

### B. Vibrations

1. Si vibromètre dispo : comparer mm/s de référence à `vibration_rms` **et** noter que l’écart peut être large (méthode différente).
2. Validation **relative** réaliste sans instrument : établir une baseline \(A_{RMS,0}\) moteur sain ; vérifier que \(A_{RMS}\) augmente en cas de balourd volontaire (masse sur poulie).
3. Tracer tableau : condition | A_RMS | Vib_est | état.

### C. Critères d’acceptation (proposition mémoire)

| Mesure | Critère indicatif |
|--------|-------------------|
| RPM | \(E_{\%} < 3\%\) à régime stable (1 aimant, bon debounce) |
| A_RMS | Répétable ±10 % à condition identique |
| Vib mm/s estimée | Tendance cohérente (hausse/baisse), pas exigence ISO |

Documenter clairement dans le mémoire que la précision mm/s de l’ADXL345 intégré n’est **pas** celle d’un appareil de métrologie vibratoire.
