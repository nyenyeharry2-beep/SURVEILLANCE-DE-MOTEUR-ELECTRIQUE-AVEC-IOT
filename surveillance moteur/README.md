# Surveillance de moteur électrique avec IoT

Système de **supervision d’un moteur électrique** basé sur **Arduino Uno** (capteurs + relais), **ESP32** (Wi‑Fi) et **Telegram** (commandes & alertes).

```
Capteurs ──► Arduino Uno ──UART──► ESP32 ──Wi‑Fi──► Bot Telegram
                 │                      ▲
                 └── Relais moteur ─────┘ (commandes /on /off)
```

## Fonctionnalités

- Mesure **ax / ay / az**, **RMS**, **vRMS**, **RPM**, **impulsions**, **fréquence**, **urgence**, **alerte**
- Capteurs : ACS712, LM35, **IR 3 pins**, **ADXL345**
- **Tableau de bord Telegram admin** : boutons **ON / OFF**, **Historique**, **URGENCE STOP**, Actualiser
- **Observateur** (chat optionnel) : mêmes métriques, sans commandes moteur
- Coupure de sécurité locale Uno si `urg=2`

## Structure du dossier

```
surveillance moteur/
├── README.md
├── arduino_uno/motor_monitor/     # Firmware Arduino Uno
├── esp32/motor_telegram/          # Firmware ESP32 + Telegram
│   ├── motor_telegram.ino
│   ├── config.example.h
│   └── config.h                   # Secrets locaux (gitignoré)
├── docs/
│   ├── schema-cablage.md
│   ├── protocole.md
│   ├── mise-en-service.md
│   └── bom.md
└── tools/test_protocol.py
```

## Démarrage rapide

1. Lire [docs/mise-en-service.md](docs/mise-en-service.md)
2. Câbler selon [docs/schema-cablage.md](docs/schema-cablage.md)
3. Créer un bot via @BotFather, renseigner `esp32/motor_telegram/config.h`
4. Flasher l’Uno puis l’ESP32
5. Sur Telegram : `/start` puis `/status`

## Commandes Telegram

### Administrateur

| Commande / bouton | Action |
|-------------------|--------|
| `/dashboard` | Panneau + boutons inline |
| **ON** / **OFF** | Démarrer / arrêter le moteur |
| **Historique** | Dernières alertes / commandes |
| **URGENCE STOP** | Arrêt immédiat |
| `/status` | Métriques texte |

### Observateur (optionnel)

Mêmes métriques : ax ay az rms vrms rpm impulsions fréquence urgence alerte — **sans** ON/OFF ni historique.

## Sécurité

- Alimentation **moteur séparée** ; l’Arduino ne pilote que le **relais**.
- Diviseur de tension sur TX Uno → RX ESP32 (niveaux 5 V / 3.3 V).
- Ne publiez jamais `config.h` avec de vrais tokens sur un dépôt public.

## Licence

Projet éducatif / prototypage — à adapter avant usage industriel.
