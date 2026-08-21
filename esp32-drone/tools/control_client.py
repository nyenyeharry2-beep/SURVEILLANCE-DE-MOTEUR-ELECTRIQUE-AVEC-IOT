#!/usr/bin/env python3
"""Script de controle WiFi pour le drone ESP32."""

import socket
import sys
import time

DRONE_IP = "192.168.4.1"
DRONE_PORT = 4210


def send_command(throttle: float, roll: float, pitch: float, yaw: float, arm: bool) -> None:
    payload = f"T:{throttle:.2f},R:{roll:.1f},P:{pitch:.1f},Y:{yaw:.1f},A:{1 if arm else 0}"
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.sendto(payload.encode(), (DRONE_IP, DRONE_PORT))
    sock.close()


def main() -> None:
    print("Controle drone ESP32 — connectez-vous au WiFi 'DroneControl'")
    print("Commandes: w/s gaz, a/d roll, i/k pitch, j/l yaw, espace armer, q quitter")

    throttle = 0.0
    roll = 0.0
    pitch = 0.0
    yaw = 0.0
    armed = False

    try:
        import tty
        import termios

        fd = sys.stdin.fileno()
        old = termios.tcgetattr(fd)

        while True:
            tty.setraw(fd)
            ch = sys.stdin.read(1)
            termios.tcsetattr(fd, termios.TCSADRAIN, old)

            if ch == "q":
                break
            if ch == " ":
                armed = not armed
            elif ch == "w":
                throttle = min(1.0, throttle + 0.05)
            elif ch == "s":
                throttle = max(0.0, throttle - 0.05)
            elif ch == "a":
                roll = max(-25.0, roll - 2.0)
            elif ch == "d":
                roll = min(25.0, roll + 2.0)
            elif ch == "i":
                pitch = min(25.0, pitch + 2.0)
            elif ch == "k":
                pitch = max(-25.0, pitch - 2.0)
            elif ch == "j":
                yaw = max(-120.0, yaw - 10.0)
            elif ch == "l":
                yaw = min(120.0, yaw + 10.0)

            send_command(throttle, roll, pitch, yaw, armed)
            print(f"T={throttle:.2f} R={roll:.1f} P={pitch:.1f} Y={yaw:.1f} ARM={armed}")
            time.sleep(0.05)

    except ImportError:
        # Mode simple sans clavier interactif
        for _ in range(10):
            send_command(0.0, 0.0, 0.0, 0.0, False)
            time.sleep(0.1)
        print("Envoi de test termine (pas de mode interactif sur cette plateforme)")


if __name__ == "__main__":
    main()
