"""Client MQTT optionnel — relais vers le backend si Mosquitto est disponible."""

from __future__ import annotations

import json
import os
import threading
from typing import Callable

import paho.mqtt.client as mqtt

MQTT_HOST = os.environ.get("MQTT_HOST", "127.0.0.1")
MQTT_PORT = int(os.environ.get("MQTT_PORT", "1883"))
MQTT_TOPIC = os.environ.get("MQTT_TOPIC", "moteur/+/telemetry")
MQTT_CMD_TOPIC = os.environ.get("MQTT_CMD_TOPIC", "moteur/01/cmd")


def _make_client() -> mqtt.Client:
    if hasattr(mqtt, "CallbackAPIVersion"):
        return mqtt.Client(
            mqtt.CallbackAPIVersion.VERSION2,
            client_id="backend-scada",
        )
    return mqtt.Client(client_id="backend-scada")


class MqttBridge:
    def __init__(self, on_telemetry: Callable[[dict], None]) -> None:
        self.on_telemetry = on_telemetry
        self.client = _make_client()
        self.client.on_connect = self._on_connect
        self.client.on_message = self._on_message
        self.connected = False

    def _on_connect(self, client, userdata, flags, reason_code, properties=None):
        if reason_code == 0 or getattr(reason_code, "value", 1) == 0:
            self.connected = True
            client.subscribe(MQTT_TOPIC)

    def _on_message(self, client, userdata, msg):
        try:
            payload = json.loads(msg.payload.decode("utf-8"))
            payload["source"] = "mqtt"
            self.on_telemetry(payload)
        except (json.JSONDecodeError, UnicodeDecodeError):
            return

    def start(self) -> None:
        def _run():
            try:
                self.client.connect(MQTT_HOST, MQTT_PORT, 30)
                self.client.loop_forever()
            except Exception:
                self.connected = False

        threading.Thread(target=_run, daemon=True).start()

    def publish_cmd(self, action: str) -> None:
        body = json.dumps({"action": action})
        try:
            self.client.publish(MQTT_CMD_TOPIC, body, qos=1)
        except Exception:
            pass
