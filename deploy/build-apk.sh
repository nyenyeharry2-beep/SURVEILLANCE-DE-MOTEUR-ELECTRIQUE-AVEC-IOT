#!/bin/bash
# Compile l'APK vendeur et le copie dans deploy/nouvelle-eve-mobile.apk
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/mobile-android"
chmod +x gradlew
./gradlew assembleDebug --quiet
cp app/build/outputs/apk/debug/app-debug.apk "$ROOT/deploy/nouvelle-eve-mobile.apk"
echo "OK: $ROOT/deploy/nouvelle-eve-mobile.apk"
ls -lh "$ROOT/deploy/nouvelle-eve-mobile.apk"
