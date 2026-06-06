#!/bin/bash
# ═══════════════════════════════════════════════════
# POS Pro — Script de build Capacitor (.apk / .ipa)
# ═══════════════════════════════════════════════════

set -e

echo "═══════════════════════════════════════════"
echo "  POS Pro — Build Mobile (Capacitor)"
echo "═══════════════════════════════════════════"

# 1. Build frontend
echo "[1/4] Build frontend (npm run build)..."
npm run build

# 2. Sync Capacitor
echo "[2/4] Sync Capacitor..."
npx cap sync

# 3. Build Android
echo "[3/4] Build Android..."
cd android
./gradlew assembleDebug
cd ..

# 4. Output
echo "[4/4] Build terminé!"
echo ""
echo "APK de debug: android/app/build/outputs/apk/debug/app-debug.apk"
echo ""
echo "Pour build release (signé):"
echo "  cd android && ./gradlew assembleRelease"
echo ""
echo "Pour iOS (nécessite macOS + Xcode):"
echo "  npx cap open ios"
echo "  → Build depuis Xcode → Product → Archive"
