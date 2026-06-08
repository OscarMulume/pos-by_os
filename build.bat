@echo off
REM ═══════════════════════════════════════════════════════════
REM POS Pro v1.3 — Script de build Windows
REM © 2026 Oscar Mulume Izuba — M-SEC Technology Consulting
REM ═══════════════════════════════════════════════════════════

echo.
echo ═══════════════════════════════════════════════════════════
echo   POS Pro v1.3 — Build Launchers
echo ═══════════════════════════════════════════════════════════
echo.

REM ── Vérifier les prérequis ──
echo [1/5] Verification des prerequis...
where node >nul 2>&1 || (echo Node.js non trouve! Installez-le depuis nodejs.org && exit /b 1)
where npm >nul 2>&1 || (echo npm non trouve! && exit /b 1)
echo   Node.js OK

where rustc >nul 2>&1 || (echo Rust non trouve! Installez-le depuis rustup.rs && exit /b 1)
where cargo >nul 2>&1 || (echo Cargo non trouve! && exit /b 1)
echo   Rust OK

echo.
echo [2/5] Installation des dependances npm...
call npm install
if %ERRORLEVEL% neq 0 (echo Erreur npm install && exit /b 1)

echo.
echo [3/5] Build des assets frontend...
call npm run build
if %ERRORLEVEL% neq 0 (echo Erreur build frontend && exit /b 1)

echo.
echo [4/5] Build Tauri (.exe)...
echo   Cela peut prendre 5-10 minutes...
call npm run tauri build
if %ERRORLEVEL% neq 0 (echo Erreur build Tauri && exit /b 1)

echo.
echo [5/5] Build Capacitor (.apk)...
call npx cap sync android
if %ERRORLEVEL% neq 0 (echo Erreur cap sync && exit /b 1)

echo.
echo ═══════════════════════════════════════════════════════════
echo   BUILD TERMINE!
echo ═══════════════════════════════════════════════════════════
echo.
echo   .EXE: src-tauri\target\release\bundle\nsis\POS-Pro_1.3.0_x64-setup.exe
echo   .APK: android\app\build\outputs\apk\debug\app-debug.apk
echo.
pause
