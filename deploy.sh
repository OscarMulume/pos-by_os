#!/bin/bash
# ═══════════════════════════════════════════════════════════
# POS Pro — Script de déploiement Render.com
# ═══════════════════════════════════════════════════════════

echo "🚀 Déploiement POS Pro v1.3..."

# Installer les dépendances PHP (optimisé production)
composer install --no-dev --optimize-autoloader --no-interaction

# Installer les dépendances Node
npm ci

# Build des assets frontend
npm run build

# Optimiser Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Migrations de base de données
php artisan migrate --force

# Créer le lien de stockage
php artisan storage:link

# Optimiser l'autoloader
composer dump-autoload --optimize

echo "✅ Déploiement terminé!"
