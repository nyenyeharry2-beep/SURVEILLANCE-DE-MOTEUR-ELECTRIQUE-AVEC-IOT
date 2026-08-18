"""Simulateur de moteur asynchrone monophase — donnees realistes sans ESP32."""

from __future__ import annotations

import math
import random
import time
from dataclasses import dataclass
from typing import Literal

FaultKind = Literal["none", "overload", "overheat", "bearing", "undervoltage"]


@dataclass
class SimState:
    running: bool = True
    fault: FaultKind = "none"
    t0: float = 0.0
    energy_kwh: float = 0.0
    last_ts: float = 0.0
    temperature: float = 28.0


class MotorSimulator:
    def __init__(self) -> None:
        self.state = SimState(t0=time.time(), last_ts=time.time())

    def start(self) -> None:
        self.state.running = True
        self.state.fault = "none"
        self.state.t0 = time.time()

    def stop(self) -> None:
        self.state.running = False
        self.state.fault = "none"

    def inject_fault(self, kind: FaultKind) -> None:
        self.state.fault = kind
        if kind != "none":
            self.state.running = True

    def step(self) -> dict:
        now = time.time()
        dt = max(0.2, now - self.state.last_ts)
        self.state.last_ts = now
        elapsed = now - self.state.t0

        voltage = 220.0 + random.uniform(-2.5, 2.5) + 1.2 * math.sin(now / 7.0)

        if not self.state.running:
            self.state.temperature += (26.0 - self.state.temperature) * 0.02
            return self._pack(
                voltage=voltage,
                current=0.05 + random.uniform(0, 0.03),
                vibration=2.0 + random.uniform(0, 1.5),
                rpm=0,
                status="stopped",
                relay=False,
            )

        # Courant de demarrage (appel) pendant ~2.5 s
        if elapsed < 2.5:
            inrush = 7.2 * math.exp(-elapsed / 1.1)
            current = 3.1 + inrush
            rpm = 1450 * (1 - math.exp(-elapsed / 0.7))
            status = "starting"
        else:
            load = 3.15 + 0.25 * math.sin(now / 11.0) + random.uniform(-0.08, 0.08)
            current = load
            rpm = 1455 + random.uniform(-12, 12)
            status = "running"

        vibration = 12.0 + 3.0 * abs(math.sin(now / 5.0)) + random.uniform(0, 2)

        if self.state.fault == "overload":
            current = 9.4 + random.uniform(0, 1.6)
            vibration += 8
            status = "alarm"
        elif self.state.fault == "overheat":
            self.state.temperature += 0.45
            current += 0.6
            status = "alarm"
        elif self.state.fault == "bearing":
            vibration = 72 + 10 * abs(math.sin(now * 3)) + random.uniform(0, 8)
            status = "alarm"
        elif self.state.fault == "undervoltage":
            voltage = 178 + random.uniform(-4, 4)
            current += 1.1
            rpm -= 80
            status = "alarm"

        cible = 38.0 + current * 4.2
        self.state.temperature += (cible - self.state.temperature) * min(0.08, dt / 8)

        if self.state.temperature >= 86 or current >= 12.2 or vibration >= 88:
            status = "fault"

        power = voltage * current * 0.85
        return self._pack(
            voltage=voltage,
            current=current,
            vibration=vibration,
            rpm=max(0, rpm),
            status=status,
            relay=True,
            power=power,
            dt=dt,
        )

    def _pack(
        self,
        voltage: float,
        current: float,
        vibration: float,
        rpm: float,
        status: str,
        relay: bool,
        power: float | None = None,
        dt: float = 1.0,
    ) -> dict:
        if power is None:
            power = voltage * current * 0.85
        self.state.energy_kwh += (power * dt) / 3_600_000.0
        return {
            "device_id": "moteur-01",
            "ts": int(time.time()),
            "voltage": round(voltage, 2),
            "current": round(max(0.0, current), 2),
            "power": round(max(0.0, power), 1),
            "temperature": round(self.state.temperature, 1),
            "vibration": round(max(0.0, min(100.0, vibration)), 1),
            "rpm": round(rpm, 0),
            "energy_kwh": round(self.state.energy_kwh, 5),
            "relay": relay,
            "status": status,
            "source": "simulator",
        }
