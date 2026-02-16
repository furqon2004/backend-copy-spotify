FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    postgresql-dev \
    $PHPIZE_DEPS 

RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN apk del $PHPIZE_DEPS

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY ./docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]