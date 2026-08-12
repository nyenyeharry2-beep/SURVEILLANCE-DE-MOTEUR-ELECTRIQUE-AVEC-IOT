# CÂBLAGE DÉTAILLÉ — ESP32 + ADXL345 + HALL + RELAIS 5 V + BUZZER

## Tableau de connexions

| COMPOSANT | BROCHE COMPOSANT | ESP32 / Alim | Tension |
|-----------|------------------|--------------|---------|
| ADXL345 | VCC | **3V3** | 3,3 V |
| ADXL345 | GND | GND | 0 V |
| ADXL345 | SDA | **GPIO 21** | 3,3 V I2C |
| ADXL345 | SCL | **GPIO 22** | 3,3 V I2C |
| ADXL345 | CS (si présent) | 3V3 | Force I2C |
| ADXL345 | SDO/SA0 (si présent) | GND | Adresse **0x53** |
| Capteur Hall | VCC | 3V3 (module 3,3 V) | 3,3 V |
| Capteur Hall | GND | GND | 0 V |
| Capteur Hall | OUT | **GPIO 18** | 3,3 V max |
| Module relais 5 V | VCC | **5 V** (pin VIN/5V ESP32 USB) | 5 V |
| Module relais 5 V | GND | GND | 0 V |
| Module relais 5 V | IN | **GPIO 26** | 3,3 V logique |
| Relais (contacts) | COM / NO | Bobine contacteur **basse tension** uniquement | Selon contacteur |
| Buzzer actif | + | **GPIO 25** (via transistor si >20 mA) | 3,3 V |
| Buzzer actif | − | GND | 0 V |
| LED statut | (intégrée DevKit) | GPIO 2 | — |

Si le module Hall n’existe qu’en version **5 V** : alimenter en 5 V et placer un **diviseur résistif** (ex. 10 kΩ + 20 kΩ) ou un translateur de niveau sur OUT → GPIO 18.

### Relais — polarité IN

La plupart des modules Songle / « Arduino relay » sont **active LOW** (IN à 0 V → relais collé). Le firmware utilise `RELAY_ACTIVE_LOW 1`. Si votre module est active HIGH, mettez `RELAY_ACTIVE_LOW 0` dans le `.ino`.

### Buzzer

- **Buzzer actif** (2 fils, bip fixe) : GPIO 25 → + , GND → −. Si le courant dépasse ~20 mA, passer par un NPN (ex. 2N2222) + résistance de base 1 kΩ.
- **Buzzer passif** : même câblage ; le firmware actuel utilise un tout-ou-rien (HIGH/LOW). Pour une fréquence fixe, on pourrait ajouter `tone()`, non requis ici.

## Montage mécanique

1. Fixer l’ADXL345 **rigidement** sur le carter moteur (vis / colle époxy / support usiné). Un montage mou fausse les mesures.
2. Orienter les axes et noter X/Y/Z dans le mémoire (photo).
3. Coller **un** aimant sur une pièce tournante accessible (ventilateur, poulie).  
   → **1 impulsion = 1 tour**.
4. Positionner le Hall à 2–5 mm de l’aimant, fixe par rapport au stator.
5. Chemin de câbles loin des phases moteur ; torsader SDA/SCL si long.
6. Placer le buzzer audible hors du carter ; isoler le module relais des vibrations fortes.

## Séparation puissance / commande

```
[ RÉSEAU 230 V ]──disjoncteur──contacteur──MOTEUR
                      │
                      └── bobine contacteur (ex. 24 V DC)
                              ▲
                              │ contacts NO du module relais 5 V
[ 5 V ]── module relais ◄── GPIO 26 ESP32
[ 3,3 V ]──ESP32──ADXL345──HALL──BUZZER
```

### Règles de sécurité

- L’ESP32 **ne se connecte jamais** au 230 V.
- Le **petit relais 5 V ne commute pas** directement un moteur 230 V / plusieurs ampères : il commande la **bobine** d’un contacteur dimensionné.
- Travaux puissance : consignation, EPI, compétence électrique.
- Terre / liaison équipotentielle selon normes locales.
- Au boot, le firmware force le relais **OFF**.

## Vérifications avant mise sous tension commande

1. Continuité GND commune ESP32 ↔ capteurs ↔ module relais.
2. VCC ADXL345 = 3,3 V (pas 5 V).
3. OUT Hall ≤ 3,3 V.
4. Module relais alimenté en **5 V**, IN sur GPIO 26 uniquement.
5. Contacts relais branchés uniquement sur circuit bobine BT du contacteur.
6. Aucun fil puissance dans la breadboard de commande.
