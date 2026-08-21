#include "gps_home.h"

#include <HardwareSerial.h>
#include <TinyGPSPlus.h>

#include "config.h"

namespace {
TinyGPSPlus gpsParser;
HardwareSerial gpsSerial(2);
}  // namespace

void GpsHome::begin() {
  gpsSerial_ = &gpsSerial;
  gpsSerial_->begin(GPS_BAUD, SERIAL_8N1, GPS_RX_PIN, GPS_TX_PIN);
  home_.locked = false;

  Serial.println("[GPS] NEO-6M initialise (UART2 RX=16, TX=17)");
}

void GpsHome::poll() {
  while (gpsSerial_->available() > 0) {
    gpsParser.encode(gpsSerial_->read());
  }
}

bool GpsHome::waitForLock(uint32_t timeoutMs) {
  Serial.println("[GPS] Attente du verrouillage GPS pour enregistrer le Home Point...");

  const uint32_t startMs = millis();
  while (millis() - startMs < timeoutMs) {
    poll();

    if (gpsParser.location.isValid() && gpsParser.altitude.isValid()) {
      home_.latitude = gpsParser.location.lat();
      home_.longitude = gpsParser.location.lng();
      home_.altitudeM = gpsParser.altitude.meters();
      home_.locked = true;

      Serial.print("[GPS] Home Point enregistre: lat=");
      Serial.print(home_.latitude, 6);
      Serial.print(" lon=");
      Serial.print(home_.longitude, 6);
      Serial.print(" alt=");
      Serial.print(home_.altitudeM, 1);
      Serial.println(" m");
      return true;
    }

    delay(200);
    Serial.print('.');
  }

  Serial.println();
  Serial.println("[GPS] AVERTISSEMENT: pas de verrouillage GPS — vol sans reference position");
  return false;
}
