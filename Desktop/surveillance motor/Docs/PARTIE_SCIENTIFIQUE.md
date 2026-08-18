# PARTIE SCIENTIFIQUE (rédaction type mémoire Bac+3)

## 1. Problématique

Les moteurs électriques constituent des organes critiques des systèmes de production. Une panne non anticipée entraîne des arrêts de chaîne, des coûts de réparation élevés et des risques sécuritaires. La maintenance corrective classique intervient après la défaillance ; la maintenance préventive systématique impose des interventions parfois inutiles. La **maintenance prédictive** vise à suivre l’état réel de la machine afin d’intervenir au bon moment. Dans un contexte pédagogique et industriel léger, l’IoT permet de rendre cette surveillance **accessible, distante et temps réel**.

**Question de recherche** : comment concevoir et mettre en œuvre un système embarqué IoT capable de surveiller simultanément les vibrations et la vitesse d’un moteur électrique, de transmettre les mesures vers une base cloud, et d’assister l’opérateur par un diagnostic préliminaire ?

## 2. Hypothèses

- H1 : l’accélération mesurée sur le carter contient une information corrélée à l’état mécanique du moteur.
- H2 : la vitesse de rotation (RPM) constitue un indicateur complémentaire (charge, glissement, dérive).
- H3 : le croisement vibration + RPM améliore la pertinence d’une alerte par rapport à un seul paramètre.
- H4 : une architecture ESP32 + Firebase + interface Web est suffisante pour une démonstration de supervision temps réel de niveau mémoire.

## 3. Objectif général

Concevoir et réaliser un système réel de maintenance prédictive basé sur l’IoT pour la surveillance à distance d’un moteur électrique, centré sur les vibrations et la vitesse de rotation.

## 4. Objectifs spécifiques

1. Sélectionner et câbler un Arduino Uno (ADXL345, capteur IR, relais D8) et un ESP32 passerelle.
2. Acquérir Ax, Ay, Az et calculer A_RMS ; estimer une vitesse vibratoire RMS.
3. Mesurer le régime en tr/min par comptage d’impulsions **IR**.
4. Transmettre les données Uno → ESP32 → Firebase Realtime Database.
5. Développer une interface Web de supervision, d’historique et d’alertes.
6. Proposer une logique de diagnostic configurable et en exposer les limites.
7. Valider expérimentalement la mesure de vitesse IR et la cohérence vibratoire.

## 5. Méthodologie

Approche expérimentale en V : analyse du besoin → architecture → réalisation matérielle → firmware → cloud → IHM → tests unitaires → validation croisée → rédaction. Les seuils d’alarme sont **calibrés** sur machine saine puis confrontés à des défauts simulés (balourd léger, variation de régime), sans prétendre à une certification normative.

## 6. Architecture du système

Le système comporte trois couches : (i) **perception** (ADXL345, capteur IR sur Arduino Uno), (ii) **traitement embarqué** (Uno : filtrage DC, RMS, RPM, diagnostic, relais/buzzer) + **passerelle IoT** (ESP32), (iii) **supervision** (Firebase + Web). La séparation galvanique entre puissance 230 V et commande est une contrainte de conception.

## 7. Fonctionnement

À périodicité fixe, l’ESP32 échantillonne les accélérations, calcule les indicateurs, met à jour le RPM sur fenêtre glissante, évalue l’état, publie un objet JSON `live`, archive un point d’historique, et relit la configuration des seuils. L’opérateur visualise l’évolution et peut ajuster les paramètres sans recompiler le firmware.

## 8. Acquisition des données

- Vibrations : I2C Uno A4/A5, plage ±16 g, fenêtre de 32 points (contrainte SRAM).
- Vitesse : interruption IR sur D2, 1 marque → 1 pulse/tour, anti-rebond logiciel.

## 9. Traitement des données

Retrait de moyenne, calcul de \(A_{RMS}\), estimation de \(V_{RMS}\) par intégration. Le diagnostic combine seuils de vibration (mm/s estimés et A_RMS) et plage RPM. Les messages d’« anomalie probable » sont heuristiques.

## 10. Transmission IoT et stockage Firebase

Wi-Fi station → Realtime Database. Avantages : latence faible, synchronisation native Web. Limite : historique massif coûteux ; pour une usine, un time-series DB serait préférable.

## 11. Diagnostic

Quatre niveaux : NORMAL, SURVEILLANCE, AVERTISSEMENT, ALARME, plus ARRET. Les seuils ne sont pas universels ; ils dépendent du moteur et des normes de référence (ex. familles ISO 10816). Le système **assiste** ; il **n’identifie pas** une panne avec certitude à partir de deux grandeurs globales.

## 12. Résultats attendus

- Chaîne de mesure opérationnelle bout-en-bout.
- Erreur relative de vitesse faible à régime stable.
- Détection d’écarts vibratoires relatifs par rapport à une baseline.
- Interface utilisable PC/smartphone.

## 13. Limites du système

- ADXL345 ≠ instrument de métrologie vibratoire ISO.
- Pas d’analyse fréquentielle (FFT) dans la version de base.
- Pas de mesure de courant, tension, température.
- Règles Firebase ouvertes en mode mémoire (risque sécurité).
- Historique RTDB limité.
- Dépendance à la qualité du montage capteur et au Wi-Fi.

## 14. Perspectives

FFT temps réel, capteurs IEPE, multi-moteurs, notifications push, apprentissage de seuils, edge computing, conformité cybersécurité industrielle, intégration GMAO.

---

*Texte destiné à être intégré / adapté au style imposé par l’établissement (citations, normes locales, références bibliographiques à compléter : ISO 10816, documentations Analog Devices ADXL345, Espressif, Firebase).*
