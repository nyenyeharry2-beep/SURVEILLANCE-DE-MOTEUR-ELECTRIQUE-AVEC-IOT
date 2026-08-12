# CONCEPTION ET MISE EN ŒUVRE D’UN SYSTÈME DE MAINTENANCE PRÉDICTIVE BASÉ SUR L’IoT POUR LA SURVEILLANCE D’UN MOTEUR ÉLECTRIQUE

Projet complet (matériel + firmware ESP32 + Firebase + interface Web) pour surveiller en temps réel **les vibrations** et **la vitesse de rotation** d’un moteur électrique.

## Structure des fichiers

```
Projet_Surveillance_Moteur/
├── ESP32/
│   └── surveillance_moteur/
│       └── surveillance_moteur.ino
├── Web/
│   ├── index.html
│   ├── style.css
│   ├── app.js
│   └── firebase-config.js
├── Firebase/
│   ├── database_structure.txt
│   ├── seed_initial.json
│   └── database.rules.json
└── Docs/
    ├── GUIDE_COMPLET.md          ← Étapes 1 à 16 du cahier des charges
    ├── EQUATIONS.md
    ├── CABLAGE.md
    ├── TESTS_ET_VALIDATION.md
    ├── PARTIE_SCIENTIFIQUE.md
    └── CHECKLIST.md
```

## Démarrage rapide

1. Lire `Docs/GUIDE_COMPLET.md` (architecture → checklist).
2. Câbler selon `Docs/CABLAGE.md` (3,3 V uniquement côté ESP32).
3. Créer le projet Firebase, importer `Firebase/seed_initial.json`.
4. Renseigner Wi-Fi + Firebase dans le `.ino` et `Web/firebase-config.js`.
5. Installer les bibliothèques Arduino (Adafruit ADXL345 + Firebase ESP Client).
6. Flasher l’ESP32, ouvrir `Web/index.html` (ou héberger les fichiers Web).

## Avertissement scientifique important

L’**ADXL345** mesure une **accélération**. La grandeur fiable affichée est **A_RMS (m/s²)**.  
La **vibration RMS en mm/s** fournie par le firmware est une **estimation** par intégration numérique : utile pour un mémoire / démonstration, **pas** une mesure certifiée ISO 10816. Voir `Docs/EQUATIONS.md`.

## Sécurité

Séparer strictement **partie puissance (230 V)** et **partie commande (3,3 / 5 V)**. L’ESP32 ne doit jamais être relié au réseau 230 V.
