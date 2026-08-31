# Guide de mise en service

## Matériel requis

Voir [bom.md](bom.md) et [schema-cablage.md](schema-cablage.md).

## 1. Créer le bot Telegram

1. Ouvrir Telegram et chercher **@BotFather**.
2. Envoyer `/newbot`, choisir un nom et un username.
3. Copier le **token** (ex. `7123456789:AAH...`).
4. Démarrer une conversation avec votre bot (`/start`).
5. Ouvrir dans le navigateur :
   `https://api.telegram.org/bot<TOKEN>/getUpdates`
6. Repérer `"chat":{"id": 123456789}` → c’est votre **CHAT_ID**.

Pour un groupe : ajoutez le bot au groupe, envoyez un message, et relisez `getUpdates` (l’id est souvent négatif).

## 2. Logiciel Arduino IDE

1. Installer [Arduino IDE 2.x](https://www.arduino.cc/en/software).
2. Ajouter le support ESP32 :
   - Fichier → Préférences → URL cartes supplémentaires :
     `https://espressif.github.io/arduino-esp32/package_esp32_index.json`
   - Outils → Type de carte → Gestionnaire → installer **esp32** by Espressif.
3. Bibliothèques (Outils → Gérer les bibliothèques) :
   - **UniversalTelegramBot** (Brian Lough)
   - **ArduinoJson** v6 (Benoit Blanchon)
4. Côté **Arduino Uno** : seule la lib **Wire** (intégrée) est nécessaire pour l’ADXL345.

## 3. Configurer l’ESP32

```bash
cp esp32/motor_telegram/config.example.h esp32/motor_telegram/config.h
```

Éditer `config.h` :
- `WIFI_SSID` / `WIFI_PASSWORD`
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_ADMIN_CHAT_ID` — chat admin (boutons ON/OFF + historique)
- `TELEGRAM_VIEWER_CHAT_ID` — chat observateur (optionnel, `""` pour désactiver)

## 4. Flasher l’Arduino Uno

1. Ouvrir `arduino_uno/motor_monitor/motor_monitor.ino`.
2. Carte : **Arduino Uno**.
3. Port série USB correspondant.
4. Téléverser.
5. Ouvrir le moniteur série à **9600** baud → message `{"evt":"UNO_READY"}`.

> UART ESP32 sur **D3/D4** (SoftwareSerial) : le flash USB sur D0/D1 n’est plus bloqué.

## 5. Flasher l’ESP32

1. Ouvrir `esp32/motor_telegram/motor_telegram.ino`.
2. Carte : **ESP32 Dev Module**.
3. Téléverser.
4. Moniteur série **115200** → IP Wi‑Fi + confirmation bot.

Vous devez recevoir sur Telegram : `ESP32 connecte...`.

## 6. Câbler Uno ↔ ESP32

| Arduino Uno | ESP32   | Note                          |
|-------------|---------|-------------------------------|
| TX (**D4**) | GPIO16  | Via diviseur 1k/2k (5 V→3.3 V)|
| RX (**D3**) | GPIO17  | Direct                         |
| GND         | GND     | Masse commune                 |

Puis alimenter les deux cartes et tester `/ping` puis `/status` sur Telegram.

## 7. Calibrage ACS712

1. Moteur arrêté → noter la tension au repos sur A0 (idéalement ~2.5 V).
2. Ajuster `ACS712_VREF` dans le sketch Uno.
3. Vérifier `ACS712_SENSITIVITY` selon le modèle (5A/20A/30A).

## 8. Réglage capteur IR 3 pins (RPM)

1. Câbler **VCC→5V**, **GND→GND**, **OUT→D2**.
2. Coller une marque réfléchissante sur l’arbre.
3. Tourner le potentiomètre du module : la LED doit clignoter à chaque tour.
4. Ajuster `PULSES_PER_REV` si plusieurs marques.
5. Si RPM reste à 0 : inverser ISR `FALLING` ↔ `RISING` dans le sketch.

## 9. Vérification ADXL345

1. Câbler I2C : **SDA→A4**, **SCL→A5**, **VCC→3.3V**, **GND→GND**, **SDO→GND** (addr `0x53`).
2. Au boot, le message `UNO_READY` doit contenir `"adxl":1`.
3. Au repos, `/status` montre `az ≈ 1 g` (ou un autre axe ≈ 1 selon l’orientation) et `mag` faible.
4. Secouer le module → `mag` monte, éventuellement `vib: ALARME`.

## 10. Tests fonctionnels

| Test              | Attendu                                      |
|-------------------|----------------------------------------------|
| `/ping`           | « Uno repond : PONG »                        |
| `/status`         | c, t, v, rpm IR, ax/ay/az/mag ADXL345        |
| Rotation arbre    | `rpm` non nul                                |
| Secousse ADXL345  | `mag` ↑ / alerte vibration                   |
| `/on` / `/off`    | Relais + LED D13                             |

## Dépannage

| Symptôme                    | Piste                                      |
|-----------------------------|--------------------------------------------|
| Pas de Wi‑Fi                | SSID/mdp, bande 2.4 GHz                    |
| Bot ne répond pas           | Token, chat id, `/start` au bot            |
| Pas de données Uno          | Baud 9600, TX/RX croisés, GND commun       |
| `adxl:0` au boot            | 3.3 V, SDA/A4 SCL/A5, adresse 0x53/0x1D    |
| RPM toujours 0              | Pot. IR, distance, FALLING/RISING, D2      |
| Certificat SSL Telegram     | `securedClient.setInsecure();` en test     |
| Flash Uno échoue            | Débrancher UART vers ESP32                 |
