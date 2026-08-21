# Guide d'installation - Surveillance moteur Harry

## Matériel requis

| Composant | Broche Arduino | Remarque |
|-----------|----------------|----------|
| Capteur IR RPM | D2 (INT0) | 1 impulsion par tour |
| ADXL345 SDA | A4 | I2C adresse 0x53 |
| ADXL345 SCL | A5 | |
| Module relais | D7 | Actif bas par défaut |
| ESP32 TX | D3 (RX Uno) | 9600 bauds |
| ESP32 RX | D4 (TX Uno) | 9600 bauds |

## Étapes InfinityFree

### Étape 1 : Créer les tables

1. Panneau InfinityFree → **phpMyAdmin**
2. Base : `if0_42713537_surveillancemoteurharry`
3. Onglet **SQL** → coller le contenu de `schema.sql` → Exécuter

### Étape 2 : Uploader les fichiers PHP

1. Panneau InfinityFree → **File Manager** → `htdocs`
2. Uploader :
   - config.php
   - insert_data.php
   - get_command.php
   - set_command.php
   - dashboard.php

### Étape 3 : Tester

- Ouvrir : http://surveillancemoteurharry.ct.ws/dashboard.php
- Tester commande : http://surveillancemoteurharry.ct.ws/set_command.php?cmd=ON

## Configuration ESP32

Dans `motor_surveillance_esp32.ino`, modifier :

```cpp
const char *WIFI_SSID = "VOTRE_SSID_WIFI";
const char *WIFI_PASSWORD = "VOTRE_MOT_DE_PASSE_WIFI";
const char *SERVER_BASE_URL = "http://surveillancemoteurharry.ct.ws";
const char *API_KEY = "harry_surveillance_2026";
```

## Protocole série

**Arduino → ESP32 (chaque seconde) :**
```
AX=0.123,AY=-0.456,AZ=9.789,RPM=2895.2,ARMS=0.567,VRMS=1.234,ECART=0.16,ETAT=NORMAL,RELAY=ON
```

**ESP32 → Arduino :**
```
RELAY=ON
RELAY=OFF
```

**Confirmation Arduino :**
```
CONFIRMATION=RELAY_ON
CONFIRMATION=RELAY_OFF
```

## Seuils d'anomalie (Arduino)

- Consigne RPM : 2900 tr/min
- Écart vitesse max : 5 %
- ARMS max : 2.0 m/s²
- VRMS max : 4.5 mm/s

## Dépannage

| Problème | Solution |
|----------|----------|
| Pas de données sur dashboard | Vérifier WiFi ESP32, URL serveur, tables SQL |
| Commande ignorée | ESP32 doit poller get_command.php toutes les 5 s |
| Erreur ADXL345 | Vérifier câblage I2C et adresse 0x53 |
| RPM = 0 | Vérifier capteur IR sur D2 |
