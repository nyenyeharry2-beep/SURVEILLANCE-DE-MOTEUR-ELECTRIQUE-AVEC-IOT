#!/usr/bin/env python3
"""Valide les trames JSON (sans impt/c/t/v)."""
import json
import sys

SAMPLES = [
    '{"ax":0.02,"ay":-0.01,"az":0.98,"rms":0.12,"vrms":2.45,"rpm":1450,"imp":24,"freq":24.10,"urg":0,"alerte":0,"m":1}',
    '{"ax":0.50,"ay":0.40,"az":0.90,"rms":0.55,"vrms":9.10,"rpm":0,"imp":0,"freq":0.0,"urg":2,"alerte":1,"m":0}',
    '{"evt":"UNO_READY","adxl":1,"ir":1}',
    '{"evt":"SAFE_STOP","urg":2}',
]

REQUIRED = {"ax", "ay", "az", "rms", "vrms", "rpm", "imp", "freq", "urg", "alerte", "m"}
FORBIDDEN = {"impt", "c", "t", "v"}


def check(line: str) -> None:
    data = json.loads(line)
    if "evt" in data:
        print(f"OK evt : {data['evt']}")
        return
    missing = REQUIRED - set(data)
    assert not missing, missing
    bad = FORBIDDEN & set(data)
    assert not bad, f"champs interdits presents: {bad}"
    assert data["urg"] in (0, 1, 2)
    assert data["alerte"] in (0, 1)
    assert data["m"] in (0, 1)
    print(f"OK dash: ax={data['ax']} rms={data['rms']} rpm={data['rpm']} urg={data['urg']}")


def main() -> int:
    for s in SAMPLES:
        check(s)
    print("Protocole OK (sans impt/c/t/v).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
