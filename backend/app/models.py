"""Seuils, score de sante et detection d'alertes du moteur."""

from __future__ import annotations

from dataclasses import dataclass
from typing import Any


@dataclass
class Seuils:
    tension_min: float = 200.0
    tension_max: float = 240.0
    courant_alarme: float = 8.0
    courant_defaut: float = 12.0
    temp_alarme: float = 70.0
    temp_defaut: float = 85.0
    vib_alarme: float = 60.0
    vib_defaut: float = 85.0
    puissance_nominale: float = 750.0


DEFAULT_SEUILS = Seuils()


@dataclass
class Alerte:
    niveau: str
    code: str
    message: str


def evaluer(mesure: dict[str, Any], seuils: Seuils | None = None) -> dict[str, Any]:
    """Calcule sante, statut et liste d'alertes a partir d'une mesure."""
    s = seuils or DEFAULT_SEUILS
    voltage = float(mesure.get("voltage") or 0)
    current = float(mesure.get("current") or 0)
    temperature = float(mesure.get("temperature") or 0)
    vibration = float(mesure.get("vibration") or 0)
    power = float(mesure.get("power") or voltage * current * 0.85)
    relay = bool(mesure.get("relay", True))

    alertes: list[Alerte] = []
    health = 100
    niveau = "ok"

    if voltage and (voltage < s.tension_min or voltage > s.tension_max):
        health -= 15
        alertes.append(
            Alerte(
                "warning",
                "VOLTAGE",
                f"Tension hors plage: {voltage:.1f} V (attendu {s.tension_min:.0f}-{s.tension_max:.0f} V)",
            )
        )

    if current >= s.courant_defaut:
        health -= 40
        niveau = "fault"
        alertes.append(
            Alerte("fault", "OVERCURRENT", f"Surcharge critique: {current:.2f} A")
        )
    elif current >= s.courant_alarme:
        health -= 20
        niveau = "alarm"
        alertes.append(
            Alerte("warning", "OVERCURRENT", f"Courant eleve: {current:.2f} A")
        )

    if temperature >= s.temp_defaut:
        health -= 40
        niveau = "fault"
        alertes.append(
            Alerte(
                "fault",
                "OVERTEMP",
                f"Surchauffe critique: {temperature:.1f} °C",
            )
        )
    elif temperature >= s.temp_alarme:
        health -= 20
        if niveau != "fault":
            niveau = "alarm"
        alertes.append(
            Alerte("warning", "OVERTEMP", f"Temperature elevee: {temperature:.1f} °C")
        )

    if vibration >= s.vib_defaut:
        health -= 35
        niveau = "fault"
        alertes.append(
            Alerte(
                "fault",
                "VIBRATION",
                f"Vibration anormale (roulement/desequilibre): {vibration:.1f} %",
            )
        )
    elif vibration >= s.vib_alarme:
        health -= 18
        if niveau != "fault":
            niveau = "alarm"
        alertes.append(
            Alerte("warning", "VIBRATION", f"Vibration elevee: {vibration:.1f} %")
        )

    if not relay:
        if niveau != "fault":
            niveau = "stopped"
        health = min(health, 50)

    health = max(0, min(100, health))
    if health < 40:
        niveau = "fault"
    elif health < 70 and niveau == "ok":
        niveau = "alarm"

    status = mesure.get("status") or (
        "fault"
        if niveau == "fault"
        else "alarm"
        if niveau == "alarm"
        else "stopped"
        if not relay
        else "running"
    )

    return {
        "voltage": round(voltage, 2),
        "current": round(current, 2),
        "power": round(power, 1),
        "temperature": round(temperature, 1),
        "vibration": round(vibration, 1),
        "rpm": round(float(mesure.get("rpm") or 0), 0),
        "health": health,
        "status": status,
        "alertes": [a.__dict__ for a in alertes],
    }


def seuils_to_dict(s: Seuils | None = None) -> dict[str, float]:
    s = s or DEFAULT_SEUILS
    return s.__dict__.copy()
