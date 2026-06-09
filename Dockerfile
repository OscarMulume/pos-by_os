# ═══════════════════════════════════════════════════════════
# POS Pro v1.3 — Dockerfile Render.com
# PHP 8.4-FPM + Nginx + Supervisor
# ═══════════════════════════════════════════════════════════

FROM php:8.4-fpm

# ── System deps ──
RUN apt-get update && apt-get install -y \
    git curl unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev libonig-dev libxml2-dev \
    nginx supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip mbstring xml bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Composer ──
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ── App ──
WORKDIR /var/www
COPY . .

# ── PHP deps ──
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && composer dump-autoload --optimize

# ── Node deps (ignore errors) ──
RUN npm ci 2>/dev/null && npm run build 2>/dev/null || true

# ── Permissions ──
RUN chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# ── OPcache ──
RUN printf 'opcache.memory_consumption=128\nopcache.interned_strings_buffer=8\nopcache.max_accelerated_files=4000\nopcache.revalidate_freq=0\nopcache.validate_timestamps=0\nopcache.enable_cli=1\n' \
    > /usr/local/etc/php/conf.d/opcache.ini

# ── Nginx ──
COPY docker/nginx-default /etc/nginx/sites-available/default

# ── Supervisor ──
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Health check ──
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
