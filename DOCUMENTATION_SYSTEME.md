# POS Pro — Documentation Système

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture technique](#2-architecture-technique)
3. [Rôles et accès](#3-rôles-et-accès)
4. [Initialisation et comptes par défaut](#4-initialisation-et-comptes-par-défaut)
5. [Guide d'utilisation par rôle](#5-guide-dutilisation-par-rôle)
6. [Fonctionnalités détaillées](#6-fonctionnalités-détaillées)
7. [API et intégrations](#7-api-et-intégrations)
8. [Build multi-plateforme](#8-build-multi-plateforme)
9. [Sécurité](#9-sécurité)
10. [Dépannage](#10-dépannage)

---

## 1. Vue d'ensemble

POS Pro est un système de point de vente (POS) multi-plateforme conçu pour les restaurants. Il supporte :

- **Web/PWA** — Depuis tout navigateur (Phase 1)
- **Mobile .apk/.ipa** — Via Capacitor (Phase 2)
- **Desktop .exe/.deb** — Via Tauri (Phase 3)

### URLs d'accès

| Environnement | URL |
|---|---|
| Local (WSL) | http://127.0.0.1:8000 |
| Local (IP WSL) | http://172.30.112.168:8000 |
| Production | https://app.pospro.com |

---

## 2. Architecture technique

### Stack technologique

| Couche | Technologie |
|---|---|
| Backend | Laravel 11 (PHP 8.2+) |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Base de données | SQLite (dev) / PostgreSQL (prod) |
| Mobile | Capacitor 6 |
| Desktop | Tauri 2 |
| PWA | Service Worker + Manifest |
| Offline | IndexedDB + Background Sync |
| Auth | Session + PIN + WebAuthn (FIDO2) |
| Impression | ESC/POS (thermique) / HTML print |
| Messaging | WhatsApp Business API |

### Structure du projet

```
pos-system/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/        → DG/Manager (dashboard, users, products, inventory, settings)
│   │   ├── Api/          → PIN, WebAuthn, WhatsApp, Licence
│   │   ├── Auth/         → Login, Register, Password Reset
│   │   ├── Kds/          → Écran cuisine (Kanban)
│   │   └── Pos/          → Caisse (POS), Cash Shifts, Receipts
│   ├── Models/           → 16 modèles
│   └── Services/         → Order, WhatsApp, Reports
├── config/               → Configuration Laravel
├── database/
│   ├── migrations/       → 14 migrations
│   └── seeders/          → Données de démo
├── public/               → Assets publics, PWA, CSS tactile
├── resources/views/
│   ├── admin/            → Vues DG/Manager
│   ├── superadmin/       → Vues Super-Admin (SaaS)
│   ├── pos/              → Vues POS (caisse)
│   ├── kds/              → Vues cuisine
│   ├── auth/             → Vues authentification
│   └── layouts/          → Layouts (app, pos, superadmin, guest)
├── routes/web.php        → ~100 routes
├── capacitor.config.json → Config mobile
├── tauri.conf.json       → Config desktop
├── build-mobile.sh       → Script build .apk/.ipa
└── build-desktop.sh      → Script build .exe/.deb
```

### Modèles de données (16)

| Modèle | Rôle |
|---|---|
| User | Utilisateurs (super_admin, admin, manager, cashier, cook) |
| Restaurant | Restaurants clients (multi-tenant) |
| PosTerminal | Terminaux de caisse |
| RestaurantTable | Tables (liées aux terminaux) |
| Category | Catégories de produits |
| Product | Produits du menu |
| ProductVariant | Variantes de produit (ex: taille, accompagnement) |
| ProductVariantOption | Options de variante (ex: Frites, Alloco) |
| Order | Commandes |
| OrderItem | Lignes de commande |
| StockMovement | Historique des mouvements de stock |
| CashShift | Shifts de caisse (ouverture/fermeture) |
| Licence | Licences par restaurant |
| AuditLog | Journal d'audit immuable |
| SiteSetting | Paramètres globaux |
| Category | Catégories |

---

## 3. Rôles et accès

### Matrice des rôles

| Niveau | Rôle | Interface | Terminaux |
|---|---|---|---|
| 1 | **Super-Admin** | Panneau SaaS (tous les restaurants) | PC / Tablette |
| 2 | **Admin / Manager** | Dashboard complet du restaurant | PC / Tablette |
| 3 | **Caissier / Serveur** | POS tactile (caisse) | Smartphone / Tablette |
| 4 | **Cuisinier** | KDS cuisine (Kanban) | Tablette / Écran TV |

### Règles d'accès

- Le **Super-Admin** voit TOUS les restaurants de la plateforme
- Le **Manager** ne voit que SON restaurant (isolation par restaurant_id)
- Le **Caissier** n'a accès qu'au POS et aux cash shifts
- Le **Cuisinier** n'a accès qu'au KDS cuisine
- Toute action critique (annulation, remise, ouverture tiroir) nécessite validation PIN/Biométrie

---

## 4. Initialisation et comptes par défaut

### Comptes créés automatiquement

| Rôle | Email | Mot de passe | PIN | Interface |
|---|---|---|---|---|
| Super-Admin | superadmin@pos.local | Abccccvvv | — | Web Desktop |
| Manager | manager.demo@msec-pos.com | Manager2026! | 1111 | Hybride |
| Caissier | caisse1.demo@msec-pos.com | Caisse2026! | 2222 | Tactile POS |
| Cuisinier | cuisine1.demo@msec-pos.com | Cuisine2026! | 3333 | Tactile KDS |

### Restaurant de démo

- **Nom** : MSEC Restaurant Démo
- **Adresse** : Avenue Kenda, Commune de Gombe, Kinshasa
- **Tables** : 12 (Salle 1-6, Terrasse 1-4, VIP 1-2)
- **Terminaux** : Caisse Principale, Bar
- **Produits** : 12 produits répartis en 4 catégories

---

## 5. Guide d'utilisation par rôle

### 5.1 Super-Connexion : http://127.0.0.1:8000/login
- Email : superadmin@pos.local
- Mot de passe : Abccccvvv

Actions disponibles :
- Dashboard global (stats de tous les restaurants)
- CRUD Restaurants (créer, modifier, suspendre, supprimer)
- Gestion des tables par restaurant (création en masse, numérotation)
- Gestion des licences (génération, activation, expiration)
- Visualiser les terminaux POS

### 5.2 Manager / DG

Connexion : http://127.0.0.1:8000/login
- Email : manager.demo@msec-pos.com
- Mot de passe : Manager2026!

Actions disponibles :
- Dashboard analytique (CA jour, % vs hier, top produits, graphique 7 jours)
- Gestion des produits et catégories (avec variantes et options)
- Gestion des stocks (inventaire, ajustements, alertes)
- Gestion des employés (création, modification, reset PIN)
- Rapports et exports
- Paramètres (taux de change, devise, reçus)
- Transactions et annulations (avec validation PIN)

#### Créer un employé (RH)

Depuis Gestion des Utilisateurs → Ajouter :
1. **Nom complet** : Jean Mukendi (génère automatiquement le user_id)
2. **Téléphone** : +243 81 234 5678
3. **Adresse** : Avenue Kenda, Commune de Gombe, Kinshasa
4. **Email** : jean.mukendi@restaurant.com (identifiant de connexion)
5. **Mot de passe initial** : Généré ou saisi
6. **Code PIN** : 4921 (outil de travail quotidien)
7. **Rôle** : Caissier / Manager / Cuisinier
8. **Date d'embedding** : Enregistrée automatiquement au clic sur "Créer"

#### Réinitialiser un PIN employé

Depuis Gestion des Utilisateurs → Cliquer sur l'employé → [Modifier le Code PIN] :
1. Le manager saisit un nouveau PIN provisoire sur l'écran tactile
2. L'employé peut reprendre le travail immédiatement
3. Action enregistrée dans l'audit log

### 5.3 Caissier / Serveur

Connexion : http://127.0.0.1:8000/login
- Email : caisse1.demo@msec-pos.com
- Mot de passe : Caisse2026!

Après connexion, le caissier est redirigé vers le POS :
- **Ouvrir un cash shift** avec le fond de caisse
- **Sélectionner une table** (visuel Kanban : libre/occupée/réservée)
- **Ajouter des produits** au panier (recherche tactile)
- **Valider la commande** (envoi cuisine géré)
- **Encaisser** (espèces, mobile money, crédit)
- **Imprimer le reçu** (HTML ou texte ESC/POS)
- **Envoyer via WhatsApp** au client

Actions nécessitant validation manager :
- Annulation de commande → PIN manager requis
- Application de remise → PIN manager requis
- Ouverture tiroir-caisse → PIN manager requis

### 5.4 Cuisinier

Connexion : http://127.0.0.1:8000/login
- Email : cuisine1.demo@msec-pos.com
- Mot de passe : Cuisine2026!

Interface KDS (Kitchen Display System) :
- **Vue Kanban** en 3 colonnes : En attente → En préparation → Prêt
- **Minuteur coloré** : vert (<5min) / orange (5-15min) / rouge (>15min)
- **Bouton "Commencer"** la préparation
- **Bouton "Prêt!"** pour notifier la mise à disposition
- Rafraîchissement automatique (polling)

---

## 6. Fonctionnalités détaillées

### 6.1 Dashboard analytique (Manager)

Calculs SQL en temps réel :

```
CA du Jour = SUM(total_amount) WHERE status='payee' AND date=AUJOURD'HUI
Évolution % = ((CA_Aujourd'hui - CA_Hier) / CA_Hier) × 100
Top Produits = GROUP BY product_id ORDER BY SUM(quantity) DESC LIMIT 5
Mode de paiement #1 = GROUP BY payment_method ORDER BY COUNT(*) DESC LIMIT 1
```

Cartes affichées :
- CA du jour + comparatif % vs hier
- Nombre de commandes + comparatif % vs hier
- Top 5 des produits (quantité vendue + chiffre)
- Mode de paiement le plus utilisé
- Graphique 7 jours (barres de revenus)
- Alertes stock bas (produits sous seuil)
- Dernières commandes (10)
- Shifts de caisse ouverts

### 6.2 Gestion des stocks

- **Déduction automatique** au passage à l'état "payee"
- **Désactivation automatique** du produit si rupture (stock = 0)
- **Ajustement manuel** avec motif et traçabilité
- **StockMovement** : historique complet (type, quantité, avant/après, raison, utilisateur)
- **Alertes** : produits sous le seuil configurable

### 6.3 Impression thermique

Deux formats disponibles :

**HTML** (pour navigateur/imprimante standard) :
- Route : GET /pos/order/{order}/receipt
- Format 80mm, CSS @page optimisé
- Logo restaurant, header/footer personnalisables

**ESC/POS** (pour imprimante thermique réseau/USB/Bluetooth) :
- Route : GET /pos/order/{order}/receipt/thermal
- Format texte 32 colonnes
- Caractères UTF-8
- À envoyer via socket TCP ou port série à l'imprimante

### 6.4 WhatsApp Business API

Configuration (.env) :
```
WHATSAPP_ACCESS_TOKEN=votre_token
WHATSAPP_PHONE_NUMBER_ID=votre_phone_id
WHATSAPP_BUSINESS_ID=votre_business_id
```

Envoi de reçu :
1. Au moment du paiement, saisir le numéro du client
2. Formattage automatique (+243...)
3. Envoi via API WhatsApp Business (v18.0)
4. Message structuré avec détail commande + total

### 6.5 WebAuthn / Biométrie (FIDO2)

Pour validation tactile rapide :
1. Le DG/Manager enregistre sa biométrie depuis son profil
2. Lors d'une action critique, le système propose "Valider par biométrie"
3. Vérification via WebAuthn API du navigateur
4. Fallback sur PIN si biométrie indisponible

### 6.6 Licence et période de grâce

Sécurité anti-fraude SaaS :
- Jeton signé HMAC-SHA256 avec expiration
- Vérification locale du jeton
- **Période de grâce 7 jours** : blocage si pas de synchronisation serveur > 7 jours
- Génération : Super-Admin → Licences → Générer
- Plans : Basic (20 tables, 5 terminaux), Pro (illimité)

### 6.7 Taux de change multi-devise

Paramétrable depuis Admin → Paramètres → Point de Vente :
- Taux du jour (ex: 1 USD = 2850 FC)
- Devise par défaut (FC)
- Devise secondaire (USD)
- Conversion automatique à la caisse

### 6.8 PWA et mode hors-ligne

- **Installation** : Depuis le navigateur → "Ajouter à l'écran d'accueil"
- **Service Worker** : Cache des assets statiques
- **IndexedDB** : Stockage local des commandes hors-ligne
- **Sync** : Synchronisation automatique quand la connexion revient
- **Page offline** : Message + file d'attente des commandes

### 6.9 Ergonomie tactile

Normes respectées :
- **Zone de clic** : 48x48px minimum (64x64px pour boutons caisse)
- **Espacement** : 8px minimum entre boutons
- **Feedback visuel** : Changement de couleur instantané au toucher (::after overlay)
- **Pavé numérique** : Plein écran pour saisie PIN et montants
- **Animations** : Scale(0.95) au toucher, toast de confirmation

---

## 7. API et intégrations

### Routes API (prefix /api)

| Méthode | Route | Description |
|---|---|---|
| GET | /api/license/verify | Vérifier la licence |
| POST | /api/license/generate/{restaurant} | Générer une licence |
| POST | /api/pin/verify | Vérifier un code PIN |
| POST | /api/webauthn/register | Enregistrer biométrie |
| POST | /api/webauthn/verify | Vérifier biométrie |
| DELETE | /api/webauthn | Supprimer biométrie |
| POST | /api/whatsapp/send-receipt | Envoyer reçu WhatsApp |

### Webhooks (optionnel)

Configuration dans config/services.php pour recevoir des événements externes.

---

## 8. Build multi-plateforme

### Phase 1 : Web/PWA (ACTUEL)

```bash
cd /home/suicide/pos-system
php artisan serve --host=0.0.0.0 --port=8000
# Ouvrir http://127.0.0.1:8000
```

### Phase 2 : Mobile .apk/.ipa (Capacitor)

```bash
cd /home/suicide/pos-system
chmod +x build-mobile.sh
./build-mobile.sh
```

Prérequis :
- Node.js 18+
- Android Studio (pour .apk)
- Xcode sur macOS (pour .ipa)

### Phase 3 : Desktop .exe/.deb (Tauri)

```bash
cd /home/suicide/pos-system
chmod +x build-desktop.sh
./build-desktop.sh
```

Prérequis :
- Node.js 18+
- Rust (installé automatiquement par Tauri)
- Tauri CLI : `npm install -g @tauri-apps/cli`

---

## 9. Sécurité

### Mesures implémentées

1. **Authentification** : Session Laravel + CSRF token
2. **RBAC** : Middleware CheckRole par route
3. **PIN code** : Hashé (bcrypt), 4-8 chiffres
4. **WebAuthn** : Biométrie FIDO2 pour actions critiques
5. **Audit Log** : Historique immuable de toutes les actions
6. **RLS** : Isolation des données par restaurant_id
7. **Licence** : Jeton signé HMAC-SHA256
8. **Période de grâce** : Blocage après 7 jours sans sync
9. **Validation** : Toutes les entrées validées côté serveur
10. **Hashage** : Mots de passe et PIN en bcrypt

### Journal d'audit

Chaque action critique est enregistrée avec :
- user_id (qui a fait l'action)
- action (type d'action)
- entity_type + entity_id (sur quoi)
- old_values + new_values (avant/après)
- ip_address
- timestamp

---

## 10. Dépannage

### Erreur 403 "Accès non autorisé"

Cause : Le rôle de l'utilisateur n'est pas dans la liste autorisée pour cette route.
Solution : Vérifier le middleware `role:` dans routes/web.php et le rôle de l'utilisateur.

### Le serveur ne démarre pas

```bash
# Vérifier le port
lsof -i :8000
# Tuer le processus si nécessaire
kill $(lsof -t -i :8000)
# Relancer
cd /home/suicide/pos-system && php artisan serve --host=0.0.0.0 --port=8000
```

### Erreurs de migration

```bash
php artisan migrate:fresh --seed
```

### Cache corrompu

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Build frontend échoue

```bash
rm -rf node_modules
npm install
npm run build
```

### WhatsApp ne fonctionne pas

Vérifier les variables d'environnement :
```bash
grep WHATSAPP .env
```

### Imprimante thermique

L'impression ESC/POS nécessite un pont logiciel (node-esc-pos ou python-escpos) pour envoyer le texte brut à l'imprimante via réseau/USB/Bluetooth.

---

## Commandes utiles

```bash
# Lancer le serveur
cd /home/suicide/pos-system && php artisan serve --host=0.0.0.0 --port=8000

# Voir toutes les routes
php artisan route:list

# Voir les routes d'un préfixe
php artisan route:list --path=admin

# Console interactive
php artisan tinker

# Créer un modèle + migration + contrôleur
php artisan make:model Nom -mcr

# Vider tous les caches
php artisan optimize:clear

# Sauvegarde manuelle
cd /home/suicide && tar -czf backups/pos-system-$(date +%Y%m%d_%H%M%S).tar.gz --exclude='node_modules' --exclude='vendor' --exclude='public/build' pos-system/
```

---

*Document généré le 2026-06-06 — POS Pro v1.0*
