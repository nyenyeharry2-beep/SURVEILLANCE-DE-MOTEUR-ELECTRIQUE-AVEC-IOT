#!/usr/bin/env python3
"""Valide le format des trames JSON (IR RPM + ADXL345).

Usage : python3 tools/test_protocol.py
"""
import json
import sys

SAMPLES = [
    '{"c":1.23,"t":45.6,"v":220.1,"vib":0,"ax":0.020,"ay":-0.015,"az":0.980,"mag":0.025,"rpm":1450,"m":1}',
    '{"c":0.00,"t":25.0,"v":0.0,"vib":1,"ax":0.500,"ay":0.400,"az":0.900,"mag":0.420,"rpm":0,"m":0}',
    '{"evt":"UNO_READY","adxl":1,"ir":1}',
    '{"evt":"SAFE_STOP"}',
    '{"evt":"PONG"}',
]

REQUIRED_TELEM = {"c", "t", "v", "vib", "ax", "ay", "az", "mag", "rpm", "m"}


def check(line: str) -> None:
    data = json.loads(line)
    if "evt" in data:
        assert isinstance(data["evt"], str) and data["evt"]
        print(f"OK evt  : {data['evt']}")
        return
    missing = REQUIRED_TELEM - set(data)
    assert not missing, f"champs manquants: {missing}"
    assert data["m"] in (0, 1)
    assert data["vib"] in (0, 1)
    print(
        f"OK telem: I={data['c']}A RPM={data['rpm']} "
        f"mag={data['mag']}g vib={data['vib']}"
    )


def main() -> int:
    for s in SAMPLES:
        check(s)
    print("Tous les échantillons du protocole sont valides.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
