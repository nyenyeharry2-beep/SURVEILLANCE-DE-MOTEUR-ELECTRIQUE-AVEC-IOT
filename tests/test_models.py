from backend.app.models import evaluer


def test_moteur_sain():
    r = evaluer(
        {
            "voltage": 221,
            "current": 3.2,
            "temperature": 42,
            "vibration": 15,
            "rpm": 1450,
            "relay": True,
        }
    )
    assert r["health"] == 100
    assert r["status"] == "running"
    assert r["alertes"] == []


def test_surcharge_defaut():
    r = evaluer({"voltage": 220, "current": 13, "temperature": 40, "vibration": 10, "relay": True})
    assert r["status"] == "fault"
    assert r["health"] <= 60
    assert any(a["code"] == "OVERCURRENT" for a in r["alertes"])


def test_surchauffe_alarme():
    r = evaluer({"voltage": 220, "current": 3, "temperature": 75, "vibration": 10, "relay": True})
    assert r["status"] == "alarm"
    assert any(a["code"] == "OVERTEMP" for a in r["alertes"])


def test_vibration_roulement():
    r = evaluer({"voltage": 220, "current": 3, "temperature": 40, "vibration": 90, "relay": True})
    assert r["status"] == "fault"
    assert any(a["code"] == "VIBRATION" for a in r["alertes"])


def test_sous_tension():
    r = evaluer({"voltage": 185, "current": 3, "temperature": 40, "vibration": 10, "relay": True})
    assert any(a["code"] == "VOLTAGE" for a in r["alertes"])
    assert r["health"] == 85
