#!/bin/sh

# Membersihkan dan mengatur cache Laravel untuk performa maksimal
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Menjalankan database migration jika diperlukan
# php artisan migrate --force

# Menjalankan Nginx di background dan PHP-FPM di foreground
nginx
php-fpm