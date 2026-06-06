# POS Pro — Guide de Test avec Terminal TC-TOUCH-D1

## Matériel testé

| Composant | Spécification |
|---|---|
| Modèle | TC-TOUCH-D1 (A9-P2-Android) |
| SoC | Rockchip RK3288 (ARM Cortex-A17 Quad-Core 1.8GHz) |
| RAM | 2 Go |
| Storage | 8 Go eMMC |
| OS | Android |
| Écran | Double écran tactile capacitif |
| Imprimante | Thermique 58mm intégrée (USB interne) |
| Connectique | 2x USB-A, 1x USB latéral, RJ11, 2x RS232, RJ45, Audio 3.5mm, MicroSD, MSR/RFID |
| Alimentation | 12V/5A DC |

---

## ÉTAPE 1 : Connecter le terminal au réseau

### Option A : Ethernet direct (PC ↔ Terminal)

```
   ┌──────────────────┐         ┌──────────────────────┐
   │  PC Windows      │─────────│  TC-TOUCH-D1         │
   │  (Câble RJ45)    │  RJ45   │  Port LAN (RJ45)     │
   └──────────────────┘         └──────────────────────┘
```

**Configuration PC Windows :**
1. Branchez le câble RJ45 entre le PC et le terminal
2. Windows → Paramètres → Réseau → Ethernet → Modifier les options
3. Clic droit sur l'adaptateur → Propriétés → TCP/IPv4
4. Configurez :
   ```
   Adresse IP :      192.168.1.1
   Masque :          255.255.255.0
   Passerelle :      (vide)
   ```

**Configuration Terminal Android :**
1. Allumez le terminal (bouton POWER sur la base)
2. Android → Paramètres → Réseau → Ethernet
3. Mode : IP statique
   ```
   Adresse IP :      192.168.1.100
   Masque :          255.255.255.0
   Passerelle :      192.168.1.1
   DNS :             8.8.8.8
   ```

### Option B : Via routeur/switch (les 2 appareils sur le même réseau)

1. Branchez le terminal au routeur en RJ45
2. Le terminal obtient une IP automatiquement (DHCP)
3. Trouvez l'IP : Android → Paramètres → À propos → Adresse IP
4. Notez cette IP (ex: `192.168.0.50`)

---

## ÉTAPE 2 : Configurer le serveur Laravel

Le serveur Laravel tourne sur WSL. Le terminal Android doit pouvoir y accéder.

### Vérifier l'IP du serveur

```bash
# Dans WSL :
hostname -I
# Résultat : 172.30.112.168 (ou similaire)
```

### Lancer le serveur

```bash
cd /home/suicide/pos-system
php artisan serve --host=0.0.0.0 --port=8000
```

### Vérifier l'accessibilité

Depuis un navigateur sur le PC Windows :
```
http://172.30.112.168:8000/login
```

Si la page de connexion s'affiche → le serveur est accessible.

### Configurer l'URL de l'application

Dans le fichier `.env` du projet, assurez-vous que l'URL pointe vers l'IP accessible :

```bash
cd /home/suicide/pos-system
# Éditer .env
APP_URL=http://172.30.112.168:8000
```

Puis vider le cache :
```bash
php artisan config:clear
```

---

## ÉTAPE 3 : Accéder au POS depuis le terminal Android

### Méthode 1 : Via le navigateur Android (Chrome)

1. Sur le terminal Android, ouvrez **Chrome**
2. Tapez l'URL : `http://172.30.112.168:8000/login`
3. La page de connexion s'affiche
4. Connectez-vous avec un compte caissier :
   ```
   Email : caisse1.demo@msec-pos.com
   Mot de passe : Caisse2026!
   ```
5. Le POS tactile s'affiche (interface caissier)

### Méthode 2 : Via PWA (recommandée pour usage POS)

1. Ouvrez Chrome sur le terminal
2. Allez sur `http://172.30.112.168:8000`
3. Menu Chrome (3 points) → **"Ajouter à l'écran d'accueil"**
4. L'icône POS Pro apparaît sur l'écran d'accueil Android
5. Lancez-la → elle s'ouvre en plein écran (mode kiosque)

### Méthode 3 : Mode kiosque (verrouillage sur l'app)

Pour empêcher l'utilisateur de quitter l'app :
1. Android → Paramètres → Sécurité → Épinglage d'écran → Activer
2. Ouvrez l'app POS Pro
3. Appuyez sur Aperçu des applications → Épingler
4. L'app est verrouillée (impossible de sortir sans code)

---

## ÉTAPE 4 : Tester le flux complet

### Test 1 : Connexion caissier

1. Sur le terminal, ouvrez l'app POS Pro
2. Connectez-vous : `caisse1.demo@msec-pos.com` / `Caisse2026!`
3. Vous êtes redirigé vers le POS (écran caissier)

### Test 2 : Sélection de table

1. L'écran affiche les tables en grille (Salle 1-6, Terrasse 1-4, VIP 1-2)
2. Appuyez sur une table **libre** (verte)
3. La table passe à l'état **occupée** (rouge)
4. Le panier s'ouvre

### Test 3 : Ajout de produits

1. Les produits sont affichés par catégorie (Plats, Boissons, Desserts, Entrées)
2. Appuyez sur un produit → il s'ajoute au panier
3. Modifiez les quantités (+/-)
4. Le total se met à jour en temps réel

### Test 4 : Envoi en cuisine

1. Validez la commande → elle part vers le KDS
2. Sur un 2ème terminal (ou tablette), connectez-vous en cuisinier :
   ```
   Email : cuisine1.demo@msec-pos.com
   Mot de passe : Cuisine2026!
   ```
3. Le KDS affiche la commande dans la colonne **"En attente"**
4. Appuyez **"Commencer"** → passe à "En préparation"
5. Appuyez **"Prêt!"** → passe à "Prêt"

### Test 5 : Encaissement

1. De retour sur le caissier, sélectionnez la commande
2. Choisissez le mode de paiement :
   - **Espèces** : Saisissez le montant reçu → calcul automatique de la monnaie
   - **Mobile Money** : Saisissez la référence
   - **Crédit** : Marquer comme crédit
3. Validez → La commande passe à l'état "payée"
4. La table redevient **libre**

### Test 6 : Impression du reçu

1. Après paiement, cliquez **"Imprimer"**
2. Le reçu s'imprime sur l'imprimante thermique 58mm intégrée
3. Format : texte structuré avec en-tête restaurant, articles, total, footer

**Note** : L'imprimante intégrée est connectée en USB interne au terminal Android. Pour l'impression depuis le navigateur Android, il faut un pont logiciel (voir ÉTAPE 6).

### Test 7 : Cash shift (caisse)

1. Au début du service : **Ouvrir la caisse**
   - Saisissez le fond de caisse (ex: 50 000 FC)
2. Pendant le service : consultez l'état de la caisse
3. En fin de service : **Fermer la caisse**
   - Saisissez le montant compté
   - Le système calcule l'écart (attendu vs compté)

### Test 8 : Mode hors-ligne (PWA)

1. Désactivez le Wi-Fi/Ethernet du terminal
2. L'app continue de fonctionner (PWA + IndexedDB)
3. Les commandes sont stockées localement
4. Reconnectez le réseau → synchronisation automatique

---

## ÉTAPE 5 : Tester avec 2 parcs de terminaux

### Configuration

Vous avez 2 terminaux TC-TOUCH-D1. Configurez-les ainsi :

**Terminal 1 (Parc 1 — Caisse principale) :**
```
IP : 192.168.1.100
Rôle : Caissier
Compte : caisse1.demo@msec-pos.com
```

**Terminal 2 (Parc 2 — Cuisine ou 2ème caisse) :**
```
IP : 192.168.1.101
Rôle : Cuisinier (KDS) OU Caissier
Compte : cuisine1.demo@msec-pos.com (KDS)
     OU caisse1.demo@msec-pos.com (caisse)
```

### Test multi-terminaux

1. **Terminal 1** : Caissier prend une commande → envoie cuisine
2. **Terminal 2** : Cuisinier reçoit la commande en temps réel
3. **Terminal 2** : Cuisinier marque "Prêt"
4. **Terminal 1** : Caissier voit la notification → sert le client

### Test de charge

1. Prenez 5 commandes simultanées sur le Terminal 1
2. Vérifiez que le Terminal 2 les reçoit toutes
3. Testez l'impression de 5 reçus consécutifs

---

## ÉTAPE 6 : Impression thermique (avancé)

L'imprimante thermique 58mm est intégrée au terminal Android. Pour l'utiliser depuis le navigateur :

### Option A : Impression via le serveur (recommandée)

Le serveur Laravel génère le reçu en texte ESC/POS et l'envoie à une imprimante réseau :

1. Connectez une imprimante thermique USB au PC Windows
2. Partagez l'imprimante sur le réseau
3. Configurez l'URL de l'imprimante dans Laravel :
   ```
   THERMAL_PRINTER_URL=192.168.1.1:9100
   ```
4. Le serveur envoie les travaux d'impression via socket TCP

### Option B : Impression directe depuis Android

1. Installez une app d'impression ESC/POS sur le terminal Android
   (ex: "ESC/POS Printer" sur Google Play)
2. Configurez l'app pour utiliser l'imprimante USB interne
3. Depuis le navigateur, utilisez le partage Android pour imprimer

### Option C : Impression PDF (fallback)

1. Le reçu s'affiche en HTML
2. Utilisez le partage Android → "Imprimer"
3. Sélectionnez une imprimante PDF ou réseau

---

## ÉTAPE 7 : Périphériques supplémentaires

### Tiroir-caisse (RJ11)

1. Branchez le tiroir-caisse au port **RJ11** de la base
2. Le tiroir s'ouvre automatiquement à chaque encaissement
3. Configuration dans Laravel : le signal est envoyé via le port série

### Lecteur de code-barres (USB)

1. Branchez le scanner USB sur un des ports **USB Type-A**
2. Le scanner fonctionne comme un clavier (HID)
3. Dans le POS, cliquez sur le champ de recherche → scannez un code-barres
4. Le produit est automatiquement ajouté au panier

### Lecteur MSR/RFID (module latéral)

1. Glissez la carte dans le lecteur latéral
2. Le numéro de carte est capturé
3. Utilisable pour : paiement carte, identification client

### Balance (RS232)

1. Branchez la balance sur le port **TTYS4** (DB9 Mâle)
2. Configuration : 9600 baud, 8N1
3. Le poids est lu automatiquement pour les produits au poids

### Afficheur client externe (RS232)

1. Branchez l'afficheur sur **TTYS1** (DB9 Femelle)
2. Le prix total s'affiche en temps réel sur l'écran client

---

## ÉTAPE 8 : Tests de performance

### Benchmarks à vérifier

| Test | Objectif |
|---|---|
| Temps de chargement POS | < 2 secondes |
| Ajout produit au panier | < 500ms |
| Envoi cuisine → réception KDS | < 1 seconde |
| Impression reçu | < 3 secondes |
| Connexion/déconnexion PIN | < 1 seconde |
| Mode hors-ligne → sync | < 5 secondes |

### Test de stabilité

1. Laissez l'app ouverte 8 heures consécutives
2. Prenez 50 commandes
3. Vérifiez la consommation mémoire (Android → Paramètres → Mémoire)
4. Redémarrez le terminal → vérifiez la sync des données

---

## ÉTAPE 9 : Sécurité

### Test de contrôle d'accès

1. Connectez-vous en caissier → essayez d'accéder à `/admin/dashboard`
   → **Doit être refusé (403)**
2. Connectez-vous en manager → essayez d'accéder à `/superadmin/restaurants`
   → **Doit être refusé (403)**
3. Connectez-vous en super-admin → accédez à tout
   → **Doit fonctionner**

### Test de validation PIN

1. Caissier essaie d'annuler une commande
   → **Demande le PIN du manager**
2. Saisissez PIN `1111` (manager)
   → **Action autorisée**
3. Saisissez PIN incorrect
   → **Action refusée**

### Test de période de grâce

1. Désactivez le réseau pendant 7+ jours
2. L'app se bloque avec un message
3. Reconnectez → la sync reprend

---

## RÉSUMÉ DES IDENTIFIANTS DE TEST

| Rôle | Email | Mot de passe | PIN | URL |
|---|---|---|---|---|
| Super-Admin | superadmin@msec-pos.com | SuperSecurise2026! | — | /superadmin/dashboard |
| Manager | manager.demo@msec-pos.com | Manager2026! | 1111 | /admin/dashboard |
| Caissier | caisse1.demo@msec-pos.com | Caisse2026! | 2222 | /pos |
| Cuisinier | cuisine1.demo@msec-pos.com | Cuisine2026! | 3333 | /kds |

---

*Guide de test — POS Pro v1.0 — 2026-06-06*
