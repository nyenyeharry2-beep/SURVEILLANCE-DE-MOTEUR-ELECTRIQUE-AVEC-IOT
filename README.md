# Surveillance de moteur électrique avec IoT

Système de **supervision d’un moteur électrique** basé sur **Arduino Uno** (capteurs + relais), **ESP32** (Wi‑Fi) et **Telegram** (commandes & alertes).

```
Capteurs ──► Arduino Uno ──UART──► ESP32 ──Wi‑Fi──► Bot Telegram
                 │                      ▲
                 └── Relais moteur ─────┘ (commandes /on /off)
```

## Fonctionnalités

- Mesure **courant** (ACS712), **température** (LM35), **tension**, **vibration**, **RPM**
- Envoi périodique de télémétrie Uno → ESP32 (JSON sur UART)
- Bot Telegram : `/status`, `/on`, `/off`, `/ping`, `/help`
- Alertes automatiques (surchauffe, surintensité, vibration, sous-tension)
- Coupure de sécurité locale sur l’Uno si seuils critiques

## Structure du dépôt

```
├── README.md
├── arduino_uno/motor_monitor/     # Firmware Arduino Uno
├── esp32/motor_telegram/          # Firmware ESP32 + Telegram
│   ├── motor_telegram.ino
│   ├── config.example.h           # Modèle de configuration
│   └── config.h                   # Secrets locaux (gitignoré)
└── docs/
    ├── schema-cablage.md
    ├── protocole.md
    ├── mise-en-service.md
    └── bom.md
```

## Démarrage rapide

1. Lire [docs/mise-en-service.md](docs/mise-en-service.md)
2. Câbler selon [docs/schema-cablage.md](docs/schema-cablage.md)
3. Créer un bot via @BotFather, renseigner `esp32/motor_telegram/config.h`
4. Flasher l’Uno puis l’ESP32
5. Sur Telegram : `/start` puis `/status`

## Commandes Telegram

| Commande   | Action                    |
|------------|---------------------------|
| `/status`  | État des capteurs        |
| `/on`      | Démarrer le moteur        |
| `/off`     | Arrêter le moteur         |
| `/ping`    | Tester la liaison UART    |
| `/help`    | Aide                      |

## Sécurité

- Alimentation **moteur séparée** ; l’Arduino ne pilote que le **relais**.
- Diviseur de tension sur TX Uno → RX ESP32 (niveaux 5 V / 3.3 V).
- Ne publiez jamais `config.h` avec de vrais tokens sur un dépôt public.

## Licence

Projet éducatif / prototypage — à adapter avant usage industriel.
