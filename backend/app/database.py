"""Persistance SQLite des mesures et alertes."""

from __future__ import annotations

import sqlite3
from pathlib import Path
from threading import Lock
from typing import Any

DB_PATH = Path(__file__).resolve().parents[2] / "data" / "moteur.db"
_lock = Lock()


def get_conn() -> sqlite3.Connection:
    DB_PATH.parent.mkdir(parents=True, exist_ok=True)
    conn = sqlite3.connect(DB_PATH, check_same_thread=False)
    conn.row_factory = sqlite3.Row
    return conn


def init_db() -> None:
    with _lock:
        conn = get_conn()
        try:
            conn.executescript(
                """
                CREATE TABLE IF NOT EXISTS mesures (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ts INTEGER NOT NULL,
                    device_id TEXT NOT NULL,
                    voltage REAL,
                    current REAL,
                    power REAL,
                    temperature REAL,
                    vibration REAL,
                    rpm REAL,
                    energy_kwh REAL,
                    health INTEGER,
                    status TEXT
                );
                CREATE TABLE IF NOT EXISTS alertes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ts INTEGER NOT NULL,
                    device_id TEXT NOT NULL,
                    niveau TEXT NOT NULL,
                    code TEXT NOT NULL,
                    message TEXT NOT NULL,
                    acquittee INTEGER NOT NULL DEFAULT 0
                );
                CREATE INDEX IF NOT EXISTS idx_mesures_ts ON mesures(ts);
                CREATE INDEX IF NOT EXISTS idx_alertes_ts ON alertes(ts);
                """
            )
            conn.commit()
        finally:
            conn.close()


def insert_mesure(row: dict[str, Any]) -> None:
    with _lock:
        conn = get_conn()
        try:
            conn.execute(
                """
                INSERT INTO mesures (
                    ts, device_id, voltage, current, power, temperature,
                    vibration, rpm, energy_kwh, health, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """,
                (
                    row["ts"],
                    row["device_id"],
                    row.get("voltage"),
                    row.get("current"),
                    row.get("power"),
                    row.get("temperature"),
                    row.get("vibration"),
                    row.get("rpm"),
                    row.get("energy_kwh"),
                    row.get("health"),
                    row.get("status"),
                ),
            )
            conn.commit()
        finally:
            conn.close()


def insert_alerte(row: dict[str, Any]) -> int:
    with _lock:
        conn = get_conn()
        try:
            cur = conn.execute(
                """
                INSERT INTO alertes (ts, device_id, niveau, code, message)
                VALUES (?, ?, ?, ?, ?)
                """,
                (
                    row["ts"],
                    row["device_id"],
                    row["niveau"],
                    row["code"],
                    row["message"],
                ),
            )
            conn.commit()
            return int(cur.lastrowid)
        finally:
            conn.close()


def fetch_historique(limit: int = 300) -> list[dict[str, Any]]:
    with _lock:
        conn = get_conn()
        try:
            rows = conn.execute(
                "SELECT * FROM mesures ORDER BY ts DESC LIMIT ?",
                (limit,),
            ).fetchall()
            return [dict(r) for r in reversed(rows)]
        finally:
            conn.close()


def fetch_alertes(limit: int = 80, ouvertes: bool = False) -> list[dict[str, Any]]:
    with _lock:
        conn = get_conn()
        try:
            if ouvertes:
                rows = conn.execute(
                    "SELECT * FROM alertes WHERE acquittee = 0 ORDER BY ts DESC LIMIT ?",
                    (limit,),
                ).fetchall()
            else:
                rows = conn.execute(
                    "SELECT * FROM alertes ORDER BY ts DESC LIMIT ?",
                    (limit,),
                ).fetchall()
            return [dict(r) for r in rows]
        finally:
            conn.close()


def acquitter_alertes() -> None:
    with _lock:
        conn = get_conn()
        try:
            conn.execute("UPDATE alertes SET acquittee = 1 WHERE acquittee = 0")
            conn.commit()
        finally:
            conn.close()
