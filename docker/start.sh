#!/bin/sh

php artisan optimize
php artisan view:cache
php artisan icons:cache

nginx
exec php-fpm