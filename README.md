# Système de surveillance moteur électrique avec IoT

Projet complet : Arduino Uno + ESP32 + MySQL (InfinityFree).

## Structure du projet

```
├── arduino/motor_surveillance_uno/   → Code Arduino Uno
├── esp32/motor_surveillance_esp32/   → Code ESP32 (passerelle WiFi)
├── php/                              → Fichiers a uploader sur InfinityFree
│   ├── config.php
│   ├── insert_data.php
│   ├── get_command.php
│   ├── set_command.php
│   ├── dashboard.php
│   └── schema.sql
└── docs/installation.md
```

## Déploiement rapide

### 1. Base de données (phpMyAdmin)

1. Connectez-vous à phpMyAdmin depuis InfinityFree.
2. Sélectionnez la base `if0_42713537_surveillancemoteurharry`.
3. Exécutez le contenu de `php/schema.sql`.

### 2. Fichiers PHP (File Manager)

Uploadez **tous les fichiers `.php`** du dossier `php/` à la racine `htdocs/` :

- `config.php`
- `insert_data.php`
- `get_command.php`
- `set_command.php`
- `dashboard.php`

### 3. Arduino Uno

1. Ouvrez `arduino/motor_surveillance_uno/motor_surveillance_uno.ino` dans Arduino IDE.
2. Sélectionnez la carte **Arduino Uno**.
3. Téléversez le sketch.

### 4. ESP32

1. Ouvrez `esp32/motor_surveillance_esp32/motor_surveillance_esp32.ino`.
2. Modifiez `WIFI_SSID` et `WIFI_PASSWORD`.
3. Vérifiez `SERVER_BASE_URL` = `http://surveillancemoteurharry.ct.ws`
4. Sélectionnez votre carte ESP32 et téléversez.

## Câblage Arduino ↔ ESP32

| Arduino Uno | ESP32        |
|-------------|--------------|
| D3 (RX)     | GPIO17 (TX)  |
| D4 (TX)     | GPIO16 (RX)  |
| GND         | GND          |

## URLs utiles

- Dashboard : http://surveillancemoteurharry.ct.ws/dashboard.php
- Insertion données : http://surveillancemoteurharry.ct.ws/insert_data.php
- Commandes : http://surveillancemoteurharry.ct.ws/set_command.php?cmd=ON

## Clé API

La clé par défaut est `harry_surveillance_2026` (définie dans `config.php` et le code ESP32).
