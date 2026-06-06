# POS Pro — Guide de Déploiement et Test

## Table des matières

1. [Identifiants](#1-identifiants)
2. [Lancer l'application](#2-lancer-lapplication)
3. [Cloner sur un autre ordinateur](#3-cloner-sur-un-autre-ordinateur)
4. [Créer un repo GitHub privé](#4-créer-un-repo-github-privé)
5. [Tester avec 2 parcs de terminaux](#5-tester-avec-2-parcs-de-terminaux)
6. [Build .exe et .apk](#6-build-exe-et-apk)

---

## 1. Identifiants

### Comptes par défaut (alignés sur vos specs)

| Rôle | Email | Mot de passe | PIN | Interface |
|---|---|---|---|---|
| **Super-Admin** | `superadmin@msec-pos.com` | `SuperSecurise2026!` | — | Web Desktop |
| **Manager** | `manager.demo@msec-pos.com` | `Manager2026!` | `1111` | Hybride |
| **Caissier** | `caisse1.demo@msec-pos.com` | `Caisse2026!` | `2222` | Tactile POS |
| **Cuisinier** | `cuisine1.demo@msec-pos.com` | `Cuisine2026!` | `3333` | Tactile KDS |

### Restaurant de démo

- **Nom** : MSEC Restaurant Démo
- **Tables** : 12 (Salle 1-6, Terrasse 1-4, VIP 1-2)
- **Terminaux** : Caisse Principale, Bar
- **Produits** : 12 (Plats, Boissons, Desserts, Entrées)

---

## 2. Lancer l'application

### Méthode A : Serveur Web (développement)

```bash
# 1. Aller dans le projet
cd /home/suicide/pos-system

# 2. Lancer le serveur Laravel
php artisan serve --host=0.0.0.0 --port=8000

# 3. Ouvrir dans le navigateur
# Depuis WSL :     http://127.0.0.1:8000
# Depuis Windows :  http://127.0.0.1:8000
# Depuis le réseau : http://172.30.112.168:8000
```

### Méthode B : Serveur Web (production avec Apache/Nginx)

```bash
# Option 1 : Apache
sudo apt install apache2 libapache2-mod-php
sudo cp /home/suicide/pos-system/public/.htaccess /var/www/html/pos-system/
sudo systemctl restart apache2

# Option 2 : Nginx
sudo apt install nginx
# Configurer un vhost pointant vers /home/suicide/pos-system/public
sudo nginx -t && sudo systemctl restart nginx
```

### Méthode C : PWA (installation sur mobile/desktop)

```bash
# 1. Lancer le serveur (méthode A)
# 2. Ouvrir http://127.0.0.1:8000 dans Chrome/Edge
# 3. Cliquer sur l'icône "Installer" dans la barre d'adresse
# 4. L'app s'installe comme une application native
```

### Méthode D : Application Desktop .exe (Tauri)

```bash
cd /home/suicide/pos-system

# Installer Rust (si pas déjà fait)
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
source $HOME/.cargo/env

# Installer Tauri CLI
npm install -g @tauri-apps/cli

# Build
npm run tauri build

# Résultat :
# Windows : src-tauri/target/release/bundle/msi/POS Pro_1.0.0_x64.msi
# Linux   : src-tauri/target/release/bundle/deb/pos-pro_1.0.0_amd64.deb
```

### Méthode E : Application Mobile .apk (Capacitor)

```bash
cd /home/suicide/pos-system

# Prérequis : Android Studio installé
# Configurer ANDROID_HOME dans ~/.bashrc :
# export ANDROID_HOME=/home/suicide/Android/Sdk

# Build
npm run build
npx cap sync android
cd android
./gradlew assembleDebug

# Résultat : android/app/build/outputs/apk/debug/app-debug.apk
# Copier sur le téléphone et installer
```

---

## 3. Cloner sur un autre ordinateur

### Option A : Via GitHub (recommandé)

```bash
# Sur le nouvel ordinateur :
git clone https://github.com/VOTRE_USER/pos-system.git
cd pos-system

# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Créer la base de données
touch database/database.sqlite
php artisan migrate --force

# Lancer
php artisan serve --host=0.0.0.0 --port=8000
```

### Option B : Via copie directe (USB/réseau)

```bash
# Sur l'ordinateur source (WSL) :
cd /home/suicide
tar -czf pos-system-backup.tar.gz pos-system/ \
  --exclude='pos-system/node_modules' \
  --exclude='pos-system/vendor' \
  --exclude='pos-system/public/build'

# Copier le fichier sur USB ou réseau
cp pos-system-backup.tar.gz /mnt/c/Users/VOTRE_USER/Desktop/

# Sur le nouvel ordinateur (WSL) :
cd /home/suicide
tar -xzf /mnt/c/Users/VOTRE_USER/Desktop/pos-system-backup.tar.gz

# Installer les dépendancies
cd pos-system
composer install
npm install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
```

### Option C : Via SSH (entre deux machines)

```bash
# Sur l'ordinateur source :
cd /home/suicide
tar -czf pos-system.tar.gz pos-system/ --exclude='node_modules' --exclude='vendor'

# Transférer
scp pos-system.tar.gz user@autre-ordinateur:/home/user/

# Sur l'autre ordinateur :
tar -xzf pos-system.tar.gz
cd pos-system
composer install && npm install
php artisan key:generate
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 4. Créer un repo GitHub privé

### Étape 1 : Installer gh CLI

```bash
# Sur Ubuntu/WSL :
curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
sudo apt update
sudo apt install gh
```

### Étape 2 : S'authentifier

```bash
gh auth login
# Suivre les instructions (token ou navigateur)
```

### Étape 3 : Initialiser le repo et pousser

```bash
cd /home/suicide/pos-system

# Initialiser git
git init
git add .
git commit -m "Initial commit - POS Pro v1.0"

# Créer le repo privé sur GitHub
gh repo create pos-system --private --source=. --push

# Vérification
git remote -v
```

### Étape 4 : Workflow futur

```bash
# Après chaque modification :
git add .
git commit -m "Description des changements"
git push origin main

# Sur un autre ordinateur :
git pull origin main
composer install
npm install
php artisan migrate --force
```

---

## 5. Tester avec 2 parcs de terminaux

### Scénario de test

Vous avez **2 parcs de terminaux POS** (ex: 2 restaurants ou 2 zones distinctes).

### Étape 1 : Créer un 2ème restaurant

1. Se connecter en Super-Admin : `superadmin@msec-pos.com` / `SuperSecurise2026!`
2. Aller à : http://127.0.0.1:8000/superadmin/restaurants
3. Cliquer **"Nouveau Restaurant"**
4. Remplir :
   - Nom : `Restaurant Parc 2`
   - Adresse : `Avenue du 30 Juin, Kinshasa`
   - Type : Permanent
   - Statut : Actif
5. Cliquer **"Créer"**

### Étape 2 : Créer les tables du 2ème parc

1. Sur la fiche du restaurant, cliquer **"Gérer les tables"**
2. Utiliser le formulaire de création en masse :
   - Zone : `Salle` / De : `1` / À : `10` / Capacité : `4`
   - Zone : `Terrasse` / De : `1` / À : `6` / Capacité : `2`
3. Cliquer **"Créer les tables"**

### Étape 3 : Créer les terminaux du 2ème parc

1. Sur la fiche du restaurant, section **"Terminaux POS"**
2. Ajouter : `Caisse Parc 2`, `Bar Parc 2`

### Étape 4 : Créer un manager pour le 2ème parc

1. Se connecter en Manager du Parc 2 (ou Super-Admin)
2. Aller à : http://127.0.0.1:8000/admin/users
3. Cliquer **"Ajouter un utilisateur"**
4. Remplir :
   - Nom : `Manager Parc 2`
   - Email : `manager.parc2@msec-pos.com`
   - Téléphone : `+243 82 345 6789`
   - Adresse : `Avenue du 30 Juin, Kinshasa`
   - Mot de passe : `ManagerParc2!`
   - PIN : `4444`
   - Rôle : Manager
5. Cliquer **"Créer l'employé"**

### Étape 5 : Tester le flux complet

#### Test 1 : Caissier prend une commande

1. Se connecter : `caisse1.demo@msec-pos.com` / `Caisse2026!`
2. Redirigé vers le POS
3. Sélectionner une table (ex: `Salle 1`)
4. Ajouter des produits au panier
5. Valider la commande → Envoi cuisine
6. Encaisser (espèces/mobile money)
7. Imprimer le reçu

#### Test 2 : Cuisinier reçoit la commande

1. Se connecter : `cuisine1.demo@msec-pos.com` / `Cuisine2026!`
2. Redirigé vers le KDS
3. Voir la commande dans la colonne **"En attente"**
4. Cliquer **"Commencer"** → Passe à "En préparation"
5. Cliquer **"Prêt!"** → Passe à "Prêt"

#### Test 3 : Manager consulte le dashboard

1. Se connecter : `manager.demo@msec-pos.com` / `Manager2026!`
2. Redirigé vers le Dashboard
3. Voir : CA du jour, top produits, alertes stock
4. Aller à Gestion des Stocks → Ajuster un produit
5. Aller à Rapports → Exporter

#### Test 4 : Super-Admin supervise les 2 parcs

1. Se connecter : `superadmin@msec-pos.com` / `SuperSecurise2026!`
2. Dashboard global : voir les 2 restaurants
3. Cliquer sur un restaurant → Voir détails, tables, terminaux
4. Générer une licence pour un restaurant
5. Suspendre/Activer un restaurant

### Étape 6 : Test de la période de grâce (hors-ligne)

1. Ouvrir l'app en mode PWA
2. Désactiver le réseau (mode avion)
3. Passer une commande → Stockée en IndexedDB
4. Réactiver le réseau → Sync automatique
5. Vérifier que la commande apparaît sur le serveur

### Étape 7 : Test de la réinitialisation de PIN

1. Se connecter en Manager
2. Aller à Gestion des Utilisateurs
3. Cliquer sur un employé
4. Cliquer **[Modifier le Code PIN]**
5. Saisir un nouveau PIN
6. L'employé se connecte avec le nouveau PIN

---

## 6. Build .exe et .apk

### Build .exe (Tauri)

```bash
cd /home/suicide/pos-system

# Installer Rust
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
source $HOME/.cargo/env

# Build
npm run tauri build

# Résultat :
# src-tauri/target/release/bundle/msi/POS Pro_1.0.0_x64_en-US.msi
```

### Build .apk (Capacitor)

```bash
cd /home/suicide/pos-system

# Prérequis : Android Studio + SDK
export ANDROID_HOME=/home/suicide/Android/Sdk
export PATH=$PATH:$ANDROID_HOME/platform-tools

# Build
npm run build
npx cap sync android
cd android
./gradlew assembleDebug

# Résultat :
# android/app/build/outputs/apk/debug/app-debug.apk
```

### Build .ipa (Capacitor — nécessite macOS)

```bash
cd /home/suicide/pos-system
npm run build
npx cap sync ios
npx cap open ios
# Dans Xcode : Product → Archive → Distribute App
```

---

*Guide généré le 2026-06-06 — POS Pro v1.0*
