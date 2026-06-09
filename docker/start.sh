#!/bin/bash
set -e

cd /var/www

# Create .env from example if missing
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || touch .env
fi

# Force SQLite and file-based sessions
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env

# Create SQLite database
touch /var/www/database/database.sqlite
chmod 666 /var/www/database/database.sqlite

echo "=== Starting supervisord ==="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
