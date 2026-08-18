# Plan de rapport / mémoire (trame)

Titre proposé : **Système de surveillance de moteur électrique avec IoT**

1. **Introduction** — enjeux de la maintenance préventive, pannes courantes (surcharge, surchauffe, roulements, sous-tension).
2. **État de l’art** — relais thermiques vs capteurs IoT, protocoles MQTT, tableaux SCADA.
3. **Cahier des charges** — grandeurs mesurées, seuils, autonomie réseau, coupure de sécurité.
4. **Conception matérielle** — ESP32, ACS712, ZMPT101B, DS18B20, SW-420, relais, alimentation, schéma.
5. **Conception logicielle** — firmware, broker, API, base SQLite, interface web, simulateur.
6. **Réalisation** — photos du banc, calibration ADC, essais à vide / en charge.
7. **Résultats** — courbes de démarrage (courant d’appel), montée en température, injection de défauts.
8. **Sécurité** — double protection locale (ESP32) et distante (SCADA), arrêt d’urgence.
9. **Limites et perspectives** — triphasé, FFT vibration, LoRa, notification SMS/e-mail.
10. **Conclusion et bibliographie**

Les codes sources de ce dépôt constituent les annexes logicielles du dossier.
