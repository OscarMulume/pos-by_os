#!/bin/bash
set -e

cd /var/www

# Create .env from example if missing
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || touch .env
fi

# Force SQLite and file-based sessions (no external DB needed)
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's/^DB_HOST=.*/# DB_HOST=/' .env
sed -i 's/^DB_PORT=.*/# DB_PORT=/' .env
sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/database/database.sqlite|' .env
sed -i 's/^DB_USERNAME=.*/# DB_USERNAME=/' .env
sed -i 's/^DB_PASSWORD=.*/# DB_PASSWORD=/' .env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env

# Create SQLite database if missing
touch /var/www/database/database.sqlite
chmod 666 /var/www/database/database.sqlite
chown www-data:www-data /var/www/database/database.sqlite

# Run migrations
php artisan migrate --force 2>/dev/null || true

echo "=== DB_CONNECTION: $(grep DB_CONNECTION .env | head -1) ==="
echo "=== Starting supervisord ==="

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
