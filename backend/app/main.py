"""API FastAPI + WebSocket + simulateur pour la supervision du moteur."""

from __future__ import annotations

import asyncio
import json
import os
from pathlib import Path
from typing import Any

from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles

from .database import (
    acquitter_alertes,
    fetch_alertes,
    fetch_historique,
    init_db,
    insert_alerte,
    insert_mesure,
)
from .models import DEFAULT_SEUILS, evaluer, seuils_to_dict
from .mqtt_client import MqttBridge
from .simulator import MotorSimulator

ROOT = Path(__file__).resolve().parents[2]
DASHBOARD = ROOT / "dashboard"

app = FastAPI(title="Surveillance moteur electrique IoT", version="1.0.0")
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

simulator = MotorSimulator()
clients: set[WebSocket] = set()
latest: dict[str, Any] = {}
last_alert_keys: set[str] = set()
mqtt_bridge: MqttBridge | None = None
use_simulator = os.environ.get("USE_SIMULATOR", "1") != "0"


class Hub:
    async def broadcast(self, payload: dict[str, Any]) -> None:
        dead: list[WebSocket] = []
        data = json.dumps(payload)
        for ws in list(clients):
            try:
                await ws.send_text(data)
            except Exception:
                dead.append(ws)
        for ws in dead:
            clients.discard(ws)


hub = Hub()


def ingest(raw: dict[str, Any]) -> dict[str, Any]:
    global latest, last_alert_keys
    evaluated = evaluer(raw)
    row = {**raw, **evaluated}
    if "energy_kwh" not in row:
        row["energy_kwh"] = latest.get("energy_kwh", 0)
    latest = row
    insert_mesure(row)

    current_keys = {a["code"] + a["niveau"] for a in evaluated["alertes"]}
    for alerte in evaluated["alertes"]:
        key = alerte["code"] + alerte["niveau"]
        if key not in last_alert_keys:
            insert_alerte(
                {
                    "ts": row["ts"],
                    "device_id": row.get("device_id", "moteur-01"),
                    "niveau": alerte["niveau"],
                    "code": alerte["code"],
                    "message": alerte["message"],
                }
            )
    last_alert_keys = current_keys
    return row


@app.on_event("startup")
async def startup() -> None:
    global mqtt_bridge
    init_db()

    def _from_mqtt(payload: dict[str, Any]) -> None:
        ingest(payload)

    mqtt_bridge = MqttBridge(_from_mqtt)
    mqtt_bridge.start()

    async def sim_loop() -> None:
        while True:
            if use_simulator:
                row = ingest(simulator.step())
                await hub.broadcast({"type": "telemetry", "data": row})
            elif latest:
                await hub.broadcast({"type": "telemetry", "data": latest})
            await asyncio.sleep(1.0)

    asyncio.create_task(sim_loop())


@app.get("/api/health")
def api_health() -> dict[str, Any]:
    return {
        "ok": True,
        "mqtt": bool(mqtt_bridge and mqtt_bridge.connected),
        "simulator": use_simulator,
        "clients": len(clients),
    }


@app.get("/api/live")
def api_live() -> dict[str, Any]:
    return latest or {"status": "idle"}


@app.get("/api/history")
def api_history(limit: int = 300) -> list[dict[str, Any]]:
    return fetch_historique(limit)


@app.get("/api/alerts")
def api_alerts(open_only: bool = False) -> list[dict[str, Any]]:
    return fetch_alertes(ouvertes=open_only)


@app.post("/api/alerts/ack")
def api_ack() -> dict[str, str]:
    acquitter_alertes()
    return {"status": "ok"}


@app.get("/api/seuils")
def api_seuils() -> dict[str, float]:
    return seuils_to_dict(DEFAULT_SEUILS)


@app.post("/api/command/{action}")
async def api_command(action: str) -> dict[str, str]:
    action = action.lower()
    if action == "start":
        simulator.start()
    elif action in ("stop", "emergency"):
        simulator.stop()
    elif action == "reset":
        simulator.start()
        acquitter_alertes()
    elif action in ("overload", "overheat", "bearing", "undervoltage", "none"):
        simulator.inject_fault(action)  # type: ignore[arg-type]
    else:
        return {"status": "unknown", "action": action}

    if mqtt_bridge:
        cmd = "stop" if action in ("stop", "emergency") else "start"
        if action in ("start", "stop", "emergency", "reset"):
            mqtt_bridge.publish_cmd(cmd)
    return {"status": "ok", "action": action}


@app.websocket("/ws")
async def ws_endpoint(ws: WebSocket) -> None:
    await ws.accept()
    clients.add(ws)
    if latest:
        await ws.send_text(json.dumps({"type": "telemetry", "data": latest}))
    try:
        while True:
            await ws.receive_text()
    except WebSocketDisconnect:
        clients.discard(ws)


if DASHBOARD.exists():
    app.mount("/static", StaticFiles(directory=DASHBOARD), name="static")

    @app.get("/")
    def index() -> FileResponse:
        return FileResponse(DASHBOARD / "index.html")
