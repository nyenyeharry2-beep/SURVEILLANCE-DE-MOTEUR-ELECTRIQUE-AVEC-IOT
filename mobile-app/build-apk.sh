#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"
npm install
npm run build
npx cap sync android
cd android
./gradlew assembleDebug
echo ""
echo "APK: android/app/build/outputs/apk/debug/app-debug.apk"
