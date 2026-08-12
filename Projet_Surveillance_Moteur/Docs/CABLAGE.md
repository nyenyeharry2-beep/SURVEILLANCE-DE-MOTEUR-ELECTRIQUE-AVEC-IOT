# CÂBLAGE DÉTAILLÉ — ESP32 + ADXL345 + HALL

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
| LED statut | (intégrée DevKit) | GPIO 2 | — |

Si le module Hall n’existe qu’en version **5 V** : alimenter en 5 V et placer un **diviseur résistif** (ex. 10 kΩ + 20 kΩ) ou un translateur de niveau sur OUT → GPIO 18.

## Montage mécanique

1. Fixer l’ADXL345 **rigidement** sur le carter moteur (vis / colle époxy / support usiné). Un montage mou fausse les mesures.
2. Orienter les axes et noter X/Y/Z dans le mémoire (photo).
3. Coller **un** aimant sur une pièce tournante accessible (ventilateur, poulie).  
   → **1 impulsion = 1 tour**.
4. Positionner le Hall à 2–5 mm de l’aimant, fixe par rapport au stator.
5. Chemin de câbles loin des phases moteur ; torsader SDA/SCL si long.

## Séparation puissance / commande

```
[ RÉSEAU 230 V ]──disjoncteur──contacteur──MOTEUR
                      │
                      └── bobine contacteur via interface BT
                          (relais / opto)  ← commandée éventuellement
                                             par ESP32 (option)

[ 5 V / 3,3 V ]──ESP32──ADXL345──HALL
       ↑
   Alim USB / alimentation isolée
```

### Règles de sécurité

- L’ESP32 **ne se connecte jamais** au 230 V.
- Travaux puissance : consignation, EPI, compétence électrique.
- Si commande marche/arrêt : ESP32 → transistor/relais **basse tension** → bobine contacteur ; pas de commutation directe de la charge moteur par un petit relais.
- Terre / liaison équipotentielle selon normes locales.
- Capotages et distances d’isolement respectés.

## Vérifications avant mise sous tension commande

1. Continuité GND commune ESP32 ↔ capteurs.
2. VCC ADXL345 = 3,3 V (pas 5 V).
3. OUT Hall ≤ 3,3 V.
4. Aucun fil puissance dans la breadboard de commande.
