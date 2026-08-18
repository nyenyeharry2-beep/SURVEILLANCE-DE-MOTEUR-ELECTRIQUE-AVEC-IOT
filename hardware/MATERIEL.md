# Matériel et câblage

## Nomenclature (BOM)

| Qté | Référence | Rôle |
| --- | --- | --- |
| 1 | ESP32 DevKit V1 | Acquisition + Wi-Fi/MQTT |
| 1 | ACS712 20 A | Courant RMS |
| 1 | ZMPT101B | Tension RMS 220 V |
| 1 | DS18B20 + résistance 4,7 kΩ | Température carcasse |
| 1 | SW-420 | Vibration / choc |
| 1 | Module relais 5 V 10 A | Coupure moteur |
| 1 | Buzzer actif 3,3 V | Alarme sonore |
| 2 | LED + résistances 220 Ω | OK / alarme |
| 1 | Alim 5 V 2 A | ESP32 + modules |
| — | Borniers, fils, boîtier IP54 | Câblage terrain |

Le moteur (ex. 0,75 kW / 220 V) n’est **jamais** alimenté depuis l’ESP32 : le relais commute le circuit de puissance, séparé du 3,3 V.

## Brochage ESP32

| Signal | GPIO | Notes |
| --- | --- | --- |
| ZMPT101B OUT | 34 | ADC1, entrée analogique |
| ACS712 OUT | 35 | ADC1 |
| SW-420 DO | 32 | Digital |
| DS18B20 DATA | 4 | Pull-up 4,7 kΩ vers 3,3 V |
| Relais IN | 26 | Vérifier logique HIGH/LOW du module |
| Buzzer | 27 | |
| LED OK | 14 | |
| LED alarme | 12 | |

## Schéma de principe

```
   220 VAC ── fusible ── [relais] ── moteur
                  │
              ZMPT101B ── GPIO34
                  │
              ACS712 ── GPIO35   (phase moteur)

   5 V ── ESP32 ── Wi-Fi ── MQTT ── PC/serveur SCADA
            │
         DS18B20, SW-420, LEDs, buzzer
```

## Calibration

1. Moteur à l’arrêt : noter la tension ACS712 (offset, souvent ~1,65 V). Mettre à jour `ACS712_OFFSET_V`.
2. Moteur alimenté, comparer U RMS multimètre vs valeur MQTT. Ajuster `ZMPT_CALIBRATION`.
3. Coller le DS18B20 sur la carcasse avec pâte thermique, loin du relais.
4. Fixer le SW-420 sur le palier ; régler le potentiomètre pour un repos à 0–5 % à vide.

## Consignes de sécurité

- Travailler hors tension pour le câblage 220 V.
- Terre du moteur et du boîtier.
- Ne jamais dépasser le calibre du relais ; pour moteurs plus gros, utiliser un contacteur industriel piloté par le relais.
