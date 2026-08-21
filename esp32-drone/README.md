# ESP32 Quadrirotor Drone Firmware

Firmware complet pour un drone quadrirotor stabilise base sur **ESP32** (Arduino Framework / PlatformIO).

## Fonctionnalites

- **Stabilisation IMU** : MPU6050 avec filtre de Kalman (roll/pitch) + integration gyro (yaw)
- **Regulation PID** : boucles roll, pitch et yaw rate
- **Reception radio FS-iA6b** : entrees PWM sur GPIO dedies
- **GPS NEO-6M** : enregistrement du Home Point au demarrage
- **Telemetre laser VL53L0X** : atterrissage automatique avec coupure moteurs
- **Modes de vol** : MANUEL et AUTO_LAND (commutateur CH5)
- **Failsafe** : coupure moteurs en cas de perte du signal radio

## Câblage

| Composant | Broche ESP32 |
|-----------|-------------|
| ESC Avant Gauche (FL) | GPIO 18 |
| ESC Avant Droit (FR) | GPIO 19 |
| ESC Arriere Gauche (RL) | GPIO 22 |
| ESC Arriere Droit (RR) | GPIO 23 |
| RC CH1 Roll | GPIO 13 |
| RC CH2 Pitch | GPIO 12 |
| RC CH3 Throttle | GPIO 14 |
| RC CH4 Yaw | GPIO 27 |
| RC CH5 Mode | GPIO 26 |
| I2C SDA | GPIO 2 |
| I2C SCL | GPIO 21 |
| GPS RX | GPIO 16 |
| GPS TX | GPIO 17 |

## Compilation

```bash
cd esp32-drone
pio run
pio run -t upload
pio device monitor
```

## Modes de vol

- **MANUEL** : CH5 < 1500 µs — controle direct roll/pitch/yaw/throttle avec stabilisation IMU
- **AUTO_LAND** : CH5 >= 1500 µs — descente autonome guidee par VL53L0X ; arret moteurs a < 5 cm du sol

## Securite

Retirer les helices pour tout test au banc. Ajuster les gains PID dans `include/config.h` selon votre chassis.
