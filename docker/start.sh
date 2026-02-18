#!/bin/sh

# Ensure storage directories exist
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/logs
chmod -R 775 storage bootstrap/cache

# Clear stale bootstrap cache (host may have cached dev packages)
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php

# Rediscover packages (without dev dependencies)
php artisan package:discover --ansi 2>/dev/null || true

# Run migrations if DB is available
php artisan migrate --force 2>/dev/null || echo "Migration skipped (DB not ready)"

# Don't cache config/routes when using volume mounts (shared with host)
php artisan config:clear
php artisan route:clear
php artisan view:cache

# Start services
nginx
exec php-fpm