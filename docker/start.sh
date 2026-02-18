#!/bin/sh

# Run migrations if DB is available
php artisan migrate --force 2>/dev/null || echo "Migration skipped (DB not ready)"

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start services
nginx
exec php-fpm