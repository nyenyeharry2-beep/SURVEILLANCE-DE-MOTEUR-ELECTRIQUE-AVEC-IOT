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

## 3. Configurer l’ESP32

```bash
cp esp32/motor_telegram/config.example.h esp32/motor_telegram/config.h
```

Éditer `config.h` : SSID, mot de passe Wi‑Fi, token bot, chat id, seuils.

## 4. Flasher l’Arduino Uno

1. Ouvrir `arduino_uno/motor_monitor/motor_monitor.ino`.
2. Carte : **Arduino Uno**.
3. Port série USB correspondant.
4. Téléverser.
5. Ouvrir le moniteur série à **9600** baud → message `{"evt":"UNO_READY"}`.

> Débrancher temporairement les fils TX/RX vers l’ESP32 pendant le flash USB (conflit sur D0/D1).

## 5. Flasher l’ESP32

1. Ouvrir `esp32/motor_telegram/motor_telegram.ino`.
2. Carte : **ESP32 Dev Module**.
3. Téléverser.
4. Moniteur série **115200** → IP Wi‑Fi + confirmation bot.

Vous devez recevoir sur Telegram : `ESP32 connecte...`.

## 6. Câbler Uno ↔ ESP32

| Arduino Uno | ESP32   | Note                          |
|-------------|---------|-------------------------------|
| TX (D1)     | GPIO16  | Via diviseur 1k/2k (5 V→3.3 V)|
| RX (D0)     | GPIO17  | Direct                         |
| GND         | GND     | Masse commune                 |

Puis alimenter les deux cartes et tester `/ping` puis `/status` sur Telegram.

## 7. Calibrage ACS712

1. Moteur arrêté → noter la tension au repos sur A0 (idéalement ~2.5 V).
2. Ajuster `ACS712_VREF` dans le sketch Uno.
3. Vérifier `ACS712_SENSITIVITY` selon le modèle (5A/20A/30A).

## 8. Tests fonctionnels

| Test              | Attendu                                      |
|-------------------|----------------------------------------------|
| `/ping`           | « Uno repond : PONG »                        |
| `/status`         | Valeurs c, t, v, vib, rpm, m                 |
| `/on`             | Relais + LED D13                             |
| `/off`            | Relais coupe                                 |
| Chauffe simulée   | Alerte Telegram + éventuel `SAFE_STOP`       |

## Dépannage

| Symptôme                    | Piste                                      |
|-----------------------------|--------------------------------------------|
| Pas de Wi‑Fi                | SSID/mdp, bande 2.4 GHz                    |
| Bot ne répond pas           | Token, chat id, `/start` au bot            |
| Pas de données Uno          | Baud 9600, TX/RX croisés, GND commun       |
| Certificat SSL Telegram     | `securedClient.setInsecure();` en test     |
| Flash Uno échoue            | Débrancher UART vers ESP32                 |
