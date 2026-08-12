# PLAN DE TESTS ET VALIDATION EXPÉRIMENTALE

## Tests fonctionnels

### Test 2 — ESP32 + liaison Uno
- **Objectif** : UART Uno D3/D4 ↔ ESP32 GPIO16/17 @ 9600.
- **Procédure** : câbler diviseur ; flasher les deux cartes.
- **Attendu** : ESP32 affiche `Uno=OK` quand l’Uno envoie `MEAS`.

### Test 3 — ESP32 seul (Wi-Fi)
- **Objectif** : réseau + Firebase.
- **Procédure** : credentials corrects.
- **Attendu** : IP + `[OK] Firebase`.

### Test 4 — ADXL345 sur Uno
- **Objectif** : I2C A4/A5.
- **Attendu** : `[OK] ADXL345` ; axes cohérents à l’arrêt (~1 g).

### Test 5 — Capteur IR / RPM
- **Objectif** : impulsions optiques.
- **Procédure** : passer la marque devant l’IR à la main ; puis moteur.
- **Attendu** : RPM non nul après ~1 s ; 1 marque = 1 tour.

### Test 6 — Relais D8
- **Objectif** : commande Web → Uno D8.
- **Procédure** : boutons Marche/Arrêt (sans 230 V).
- **Attendu** : clic relais ; `relay_state` cohérent.

### Test 7 — Buzzer D9
- **Objectif** : alerte sonore.
- **Procédure** : forcer AVERTISSEMENT/ALARME ; tester mute.
- **Attendu** : bip lent/rapide ; mute OK.

### Test 8 — Firebase + interface
- **Attendu** : cartes et graphiques temps réel.

### Test 9 — Vibration élevée simulée
- **Attendu** : AVERTISSEMENT/ALARME + buzzer.

### Test 10 — Vitesse anormale
- **Attendu** : diagnostic RPM hors plage.

### Test 11 — Arrêt auto ALARME
- **Attendu** : relais D8 forcé OFF.

### Test reconnexion Wi-Fi
- Couper Wi-Fi 20 s → messages de reconnexion ESP32.

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
