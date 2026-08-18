from backend.app.simulator import MotorSimulator


def test_arret_courant_nul():
    sim = MotorSimulator()
    sim.stop()
    row = sim.step()
    assert row["status"] == "stopped"
    assert row["current"] < 0.2
    assert row["rpm"] == 0


def test_defaut_surcharge():
    sim = MotorSimulator()
    sim.start()
    sim.inject_fault("overload")
    row = sim.step()
    assert row["current"] > 8
    assert row["source"] == "simulator"
