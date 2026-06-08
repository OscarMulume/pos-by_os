# ═══════════════════════════════════════════════════════════
# POS Pro — Guide de Build .EXE et .APK
# © 2026 Oscar Mulume Izuba — M-SEC Technology Consulting
# ═══════════════════════════════════════════════════════════

## PRÉREQUIS SUR WINDOWS

Avant de builder, installez ces outils sur votre PC Windows:

### 1. Node.js (v18+)
- Téléchargez: https://nodejs.org/
- Installez la version LTS
- Vérifiez: `node -v` et `npm -v`

### 2. Rust (pour Tauri)
- Téléchargez: https://rustup.rs/
- Installez la version stable
- Vérifiez: `rustc -v`

### 3. WebView2 (pour Tauri)
- Déjà installé sur Windows 10/11
- Si erreur: https://developer.microsoft.com/en-us/microsoft-edge/webview2/

### 4. Android Studio (pour APK)
- Téléchargez: https://developer.android.com/studio
- Installez avec Android SDK
- Configurez JAVA_HOME

---

## ÉTAPE 1: Copier le projet sur Windows

Depuis WSL, le projet est déjà copié sur votre Bureau:
```
C:\Users\[VOTRE_USER]\Desktop\pos-system-build
```

Vérifiez que le dossier contient:
- `src-tauri\` (config Tauri)
- `android\` (config Capacitor)
- `package.json`

---

## ÉTAPE 2: Build .EXE (Tauri)

Ouvrez **PowerShell en tant qu'Administrateur**:

```powershell
# Aller dans le projet
cd C:\Users\$env:USERNAME\Desktop\pos-system-build

# Installer les dépendances Node
npm install

# Build Tauri (génère le .exe)
npm run tauri build
```

**Résultat:**
```
src-tauri\target\release\bundle\nsis\POS-Pro_1.0.0_x64-setup.exe
```

**Installation sur les PC du restaurant:**
1. Copiez le `.exe` sur chaque PC Windows
2. Double-cliquez pour installer
3. Lancez "POS Pro" depuis le menu Démarrer
4. Entrez l'URL du serveur: `https://pos-pro-msec.onrender.com`

---

## ÉTAPE 3: Build .APK (Capacitor)

```powershell
# Dans le même dossier
npm run build
npx cap sync android
npx cap open android
```

**Dans Android Studio:**
1. Attendez que Gradle finisse de synchroniser
2. **Build → Build Bundle(s) / APK(s) → Build APK(s)**
3. Attendez ~2-5 minutes

**Résultat:**
```
android\app\build\outputs\apk\debug\app-debug.apk
```

**Installation sur la tablette Android:**
1. Copiez l'APK sur la tablette (USB ou Bluetooth)
2. Autorisez "Sources inconnues" dans Paramètres → Sécurité
3. Ouvrez l'APK pour installer
4. Lancez "POS Pro" depuis l'écran d'accueil
5. Entrez l'URL du serveur: `https://pos-pro-msec.onrender.com`

---

## ÉTAPE 4: Configuration de l'URL du serveur

Par défaut, l'app essaie de se connecter à `http://127.0.0.1:8000`.
Pour changer vers le serveur en ligne:

### Sur le .EXE (Tauri):
L'URL est dans `src-tauri/tauri.conf.json`:
```json
{
  "build": {
    "beforeBuildCommand": "npm run build",
    "beforeDevCommand": "npm run dev",
    "devPath": "https://pos-pro-msec.onrender.com",
    "distDir": "../public"
  }
}
```

### Sur l'APK (Capacitor):
L'URL est dans `capacitor.config.json`:
```json
{
  "server": {
    "url": "https://pos-pro-msec.onrender.com",
    "cleartext": true
  }
}
```

**IMPORTANT:** Après avoir changé l'URL, rebuild l'app!

---

## ÉTAPE 5: Déploiement sur Render.com

### 5.1 Créer le compte Render
1. Allez sur https://render.com
2. Sign up avec GitHub
3. Vérifiez votre email

### 5.2 Créer le Web Service
1. Dashboard → **New → Web Service**
2. Connectez votre repo: `OscarMulume/pos-by_os`
3. Configuration:
   - **Name:** `pos-pro-msec`
   - **Runtime:** PHP
   - **Region:** Frankfurt (Europe)
   - **Plan:** Free
   - **Build Command:** `bash deploy.sh`
   - **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`
4. **Environment Variables:**
```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=db.cinzlzdfddvcjhmvnbtp.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=***n
DB_SSLMODE=require
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```
5. Cliquez **Create Web Service**

### 5.3 Créer la base Supabase
1. Allez sur https://supabase.com/dashboard
2. Projet `pos-pro-msec` → **Settings → Database**
3. Copiez le **Connection String**
4. Collez dans Render → Environment → DB_PASSWORD

### 5.4 Lancer les migrations
1. Sur Render → **Shell**
2. Exécutez:
```bash
php artisan migrate --force
php artisan storage:link
```

---

## ÉTAPE 6: Tester les terminaux

### Test 1: Navigateur Web
1. Ouvrez `https://pos-pro-msec.onrender.com`
2. Login: `superadmin@pos.local` / `Abccccvvr`
3. Testez le cycle complet

### Test 2: Application Windows (.exe)
1. Installez le .exe sur un PC
2. Lancez l'app
3. Entrez: `https://pos-pro-msec.onrender.com`
4. Testez le POS

### Test 3: Application Android (.apk)
1. Installez l'APK sur la tablette
2. Lancez l'app
3. Entrez: `https://pos-pro-msec.onrender.com`
4. Testez le POS

---

## RÉSOLUTION DES PROBLÈMES

### Erreur "Network is unreachable" (Supabase depuis WSL)
- WSL n'a pas IPv6 → c'est normal
- Render.com a IPv6 → ça fonctionnera en production
- Pour tester depuis WSL, utilisez le Transaction Pooler (port 6543)

### Erreur "could not find driver" (pgsql)
```bash
sudo apt-get install php-pgsql
```

### Build Tauri échoue
```powershell
# Installez les outils Visual Studio C++
# https://visualstudio.microsoft.com/visual-cpp-build-tools/
# Cochez "Desktop development with C++"
```

### Build APK échoue
```powershell
# Vérifiez JAVA_HOME
echo $env:JAVA_HOME
# Doit pointer vers: C:\Program Files\Android\Android Studio\jbr
```

---

## CHECKLIST FINALE

- [ ] Projet copié sur Windows Desktop
- [ ] Node.js installé
- [ ] Rust installé
- [ ] Android Studio installé
- [ ] .EXE buildé avec Tauri
- [ .APK buildé avec Capacitor
- [ ] Render.com configuré
- [ ] Supabase connecté
- [ ] Migrations exécutées
- [ ] Test navigateur OK
- [ ] Test .exe OK
- [ ] Test .apk OK

---

## SUPPORT

- GitHub: https://github.com/OscarMulume/pos-by_os
- Email: oscarmulume1612@gmail.com
