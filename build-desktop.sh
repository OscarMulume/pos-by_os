#!/bin/bash
# ═══════════════════════════════════════════════════
# POS Pro — Script de build Tauri (.exe / .deb)
# ═══════════════════════════════════════════════════

set -e

echo "═══════════════════════════════════════════"
echo "  POS Pro — Build Desktop (Tauri)"
echo "═══════════════════════════════════════════"

# 1. Build frontend
echo "[1/3] Build frontend (npm run build)..."
npm run build

# 2. Build Tauri
echo "[2/3] Build Tauri..."
npm run tauri build

# 3. Output
echo "[3/3] Build terminé!"
echo ""
echo "Windows (.msi): src-tauri/target/release/bundle/msi/"
echo "Linux (.deb):  src-tauri/target/release/bundle/deb/"
echo "macOS (.dmg):  src-tauri/target/release/bundle/dmg/"
