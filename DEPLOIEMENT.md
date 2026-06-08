# POS Pro — Guide de Déploiement Production
# © 2026 Oscar Mulume Izuba — M-SEC Technology Consulting

## ARCHITECTURE RECOMMANDÉE (100% GRATUIT)

```
┌─────────────────────────────────────────────────────────────┐
│                    ARCHITECTURE CLOUD                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  [GitHub repo privé]                                         │
│       │                                                      │
│       │ git push                                              │
│       ▼                                                      │
│  [Render.com] ←── Webhook GitHub                             │
│       │         (déploiement auto)                           │
│       │                                                      │
│       ├── Nginx + PHP-FPM (Laravel)                          │
│       ├── Node.js (build assets)                             │
│       │                                                      │
│       ▼                                                      │
│  [Supabase] ←── PostgreSQL (500MB gratuit)                   │
│                                                              │
│  TERMINAUX:                                                  │
│  [PC Windows .exe] ──┐                                       │
│  [Tablette Android] ──┼──→ https://pos-pro-msec.onrender.com │
│  [Navigateur Web] ────┘                                      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## ÉTAPE 1: Créer le projet Supabase

1. Allez sur https://supabase.com → Sign up (GitHub)
2. New Project:
   - Name: `pos-pro-msec`
   - Database Password: [choisissez un mot de passe fort]
   - Region: Europe (Frankfurt) — le plus proche de la RDC
3. Notez les informations de connexion:
   - Settings → Database → Connection string
   - Host: `db.[PROJECT_ID].supabase.co`
   - Port: 5432
   - Database: postgres
   - Username: postgres
   - Password: [votre mot de passe]

## ÉTAPE 2: Configurer le .env de production

1. Copiez `.env.production` vers `.env`
2. Remplacez les valeurs Supabase:
```
DB_HOST=db.[PROJECT_ID].supabase.co
DB_PASSWORD=[VOTRE_MOT_DE_PASSE]
```

## ÉTAPE 3: Pousser sur GitHub

```bash
cd /home/suicide/pos-system
git remote set-url origin https://[PAT]@github.com/OscarMulume/pos-by_os.git
git push origin main
```

## ÉTAPE 4: Déployer sur Render.com

1. Allez sur https://render.com → Sign up (GitHub)
2. New → Web Service
3. Connectez votre repo GitHub `pos-by_os`
4. Configuration:
   - Name: `pos-pro-msec`
   - Runtime: PHP
   - Plan: Free
   - Build Command: `bash deploy.sh`
   - Start Command: `php artisan serve --host=0.0.0.0 --port=$PORT`
5. Ajoutez les variables d'environnement:
   - APP_ENV=production
   - APP_DEBUG=false
   - DB_CONNECTION=pgsql
   - DB_HOST=[depuis Supabase]
   - DB_PASSWORD=[depuis Supabase]
   - DB_SSLMODE=require
6. Create Web Service

## ÉTAPE 5: Configurer le Webhook GitHub

1. Sur GitHub → repo pos-by_os → Settings → Webhooks → Add webhook
2. Payload URL: `https://pos-pro-msec.onrender.com/webhook/deploy`
3. Content type: `application/json`
4. Secret: `msec-pos-2026`
5. Events: Just the push event

## ÉTAPE 6: Tester les terminaux

### Option A: Navigateur Web (le plus simple)
- Ouvrez `https://pos-pro-msec.onrender.com` sur chaque terminal
- Fonctionne sur PC, tablette, smartphone

### Option B: Application Android (.apk)
```powershell
# Sur Windows
cd C:\Users\$env:USERNAME\Desktop\pos-system-build
npm run build
npx cap sync android
npx cap open android
# Build → Build APK → Copier sur tablette
```

### Option C: Application Windows (.exe)
```powershell
# Sur Windows
cd C:\Users\$env:USERNAME\Desktop\pos-system-build
npm run tauri build
# Le .exe est dans: src-tauri\target\release\bundle\nsis\
```

## OPTIMISATION ANTI-LATENCE

### 1. Utiliser un CDN pour les assets
Les assets (CSS, JS, images) sont servis par Render.
Pour améliorer, activez Cloudflare devant Render:
- Créez un compte Cloudflare gratuit
- Pointez `pos.msec-rdc.com` vers Render
- Activez la compression et le cache

### 2. Optimiser les requêtes
- Le polling KDS est à 5 secondes (bon équilibre)
- Le polling POS est à 10 secondes
- Les requêtes utilisent des agrégations SQL (pas de chargement massif)

### 3. Mode Offline (PWA)
L'app a un service worker. Si internet coupe:
- Les commandes sont stockées en local (IndexedDB)
- Synchronisation automatique quand internet revient

## COÛTS

| Service | Plan | Coût |
|---------|------|------|
| GitHub | Privé | Gratuit |
| Render | Free | Gratuit (750h/mois) |
| Supabase | Free | Gratuit (500MB) |
| Cloudflare | Free | Gratuit |
| **TOTAL** | | **0$/mois** |

## LIMITES DU GRATUIT

- Render Free: s'endort après 15 min d'inactivité (réveil en ~30s)
- Supabase Free: 500MB de BDD, 2GB de bande passante/mois
- Pour un restaurant avec ~500 commandes/jour: ~50MB/mois

## ÉVOLUTION (quand vous grandissez)

| Étape | Service | Coût |
|-------|---------|------|
| 1-3 restaurants | Render Free + Supabase Free | 0$/mois |
| 5-10 restaurants | Render Starter ($7) + Supabase Pro ($25) | ~32$/mois |
| 10+ restaurants | VPS DigitalOcean ($24) + Supabase Pro ($25) | ~49$/mois |

## SUPPORT

- Email: oscarmulume1612@gmail.com
- GitHub: https://github.com/OscarMulume/pos-by_os
