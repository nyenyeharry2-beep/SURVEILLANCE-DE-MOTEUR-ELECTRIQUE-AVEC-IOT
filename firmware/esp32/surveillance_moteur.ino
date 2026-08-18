/*
  Surveillance de moteur electrique — firmware ESP32
  Publie tension, courant, puissance, temperature et vibration via MQTT.
  Recoit les commandes: {"action":"stop"} | {"action":"start"} | {"action":"reset"}
*/

#include <WiFi.h>
#include <PubSubClient.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>
#include "config.h"

WiFiClient wifiClient;
PubSubClient mqtt(wifiClient);
OneWire oneWire(PIN_ONEWIRE);
DallasTemperature sensors(&oneWire);

bool moteurAutorise = true;
unsigned long lastPublish = 0;

void setLeds(bool ok, bool alarme) {
  digitalWrite(PIN_LED_OK, ok ? HIGH : LOW);
  digitalWrite(PIN_LED_ALARME, alarme ? HIGH : LOW);
}

float lireTensionRms(int pin, int echantillons = 400) {
  float sommeCarres = 0;
  for (int i = 0; i < echantillons; i++) {
    float v = (analogRead(pin) / ADC_RESOLUTION) * ADC_VREF;
    float centre = v - (ADC_VREF / 2.0f);
    sommeCarres += centre * centre;
  }
  float vRms = sqrt(sommeCarres / echantillons);
  return vRms * ZMPT_CALIBRATION;
}

float lireCourantRms(int pin, int echantillons = 400) {
  float sommeCarres = 0;
  for (int i = 0; i < echantillons; i++) {
    float v = (analogRead(pin) / ADC_RESOLUTION) * ADC_VREF;
    float amp = (v - ACS712_OFFSET_V) / ACS712_SENSITIVITY;
    sommeCarres += amp * amp;
  }
  return sqrt(sommeCarres / echantillons);
}

float lireTemperature() {
  sensors.requestTemperatures();
  float t = sensors.getTempCByIndex(0);
  if (t == DEVICE_DISCONNECTED_C) {
    return NAN;
  }
  return t;
}

float lireVibration() {
  // SW-420: HIGH = vibration detectee. On moyenne sur 50 ms.
  int hits = 0;
  const int n = 50;
  for (int i = 0; i < n; i++) {
    if (digitalRead(PIN_VIBRATION) == HIGH) hits++;
    delay(1);
  }
  return (hits * 100.0f) / n;
}

void appliquerRelais() {
  digitalWrite(PIN_RELAIS, moteurAutorise ? HIGH : LOW);
}

void onMqttMessage(char* topic, byte* payload, unsigned int length) {
  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, payload, length);
  if (err) return;

  const char* action = doc["action"] | "";
  if (strcmp(action, "stop") == 0) {
    moteurAutorise = false;
    digitalWrite(PIN_BUZZER, HIGH);
    delay(120);
    digitalWrite(PIN_BUZZER, LOW);
  } else if (strcmp(action, "start") == 0 || strcmp(action, "reset") == 0) {
    moteurAutorise = true;
  }
  appliquerRelais();
}

void connecterWifi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
    delay(400);
  }
}

void connecterMqtt() {
  while (!mqtt.connected()) {
    if (mqtt.connect(MQTT_CLIENT_ID, MQTT_USER, MQTT_PASS)) {
      mqtt.subscribe(MQTT_TOPIC_CMD);
      mqtt.publish(MQTT_TOPIC_STATUS, "{\"online\":true}", true);
    } else {
      delay(2000);
    }
  }
}

void setup() {
  pinMode(PIN_VIBRATION, INPUT);
  pinMode(PIN_RELAIS, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  pinMode(PIN_LED_OK, OUTPUT);
  pinMode(PIN_LED_ALARME, OUTPUT);
  analogReadResolution(12);
  sensors.begin();
  appliquerRelais();
  setLeds(false, false);

  connecterWifi();
  mqtt.setServer(MQTT_BROKER, MQTT_PORT);
  mqtt.setCallback(onMqttMessage);
  mqtt.setBufferSize(512);
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connecterWifi();
  }
  if (!mqtt.connected()) {
    connecterMqtt();
  }
  mqtt.loop();

  if (millis() - lastPublish < PUBLISH_INTERVAL_MS) {
    return;
  }
  lastPublish = millis();

  float voltage = lireTensionRms(PIN_VOLTAGE);
  float current = moteurAutorise ? lireCourantRms(PIN_CURRENT) : 0.0f;
  float temperature = lireTemperature();
  float vibration = lireVibration();
  float power = voltage * current * 0.85f;

  bool alarme = current > 8.0f || temperature > 70.0f || vibration > 60.0f;
  bool defaut = current > 12.0f || temperature > 85.0f || vibration > 85.0f;
  if (defaut) {
    moteurAutorise = false;
    appliquerRelais();
  }
  setLeds(!alarme && moteurAutorise, alarme || defaut);

  StaticJsonDocument<384> doc;
  doc["device_id"] = DEVICE_ID;
  doc["ts"] = (long)(millis() / 1000);
  doc["voltage"] = round(voltage * 10) / 10.0;
  doc["current"] = round(current * 100) / 100.0;
  doc["power"] = round(power * 10) / 10.0;
  doc["temperature"] = isnan(temperature) ? 0 : round(temperature * 10) / 10.0;
  doc["vibration"] = round(vibration * 10) / 10.0;
  doc["rpm"] = moteurAutorise && current > 0.4f ? 1450 : 0;
  doc["relay"] = moteurAutorise;
  doc["status"] = defaut ? "fault" : (alarme ? "alarm" : (moteurAutorise ? "running" : "stopped"));

  char buffer[384];
  size_t n = serializeJson(doc, buffer);
  mqtt.publish(MQTT_TOPIC_TELEMETRY, buffer, n);
}
