FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash curl git zip unzip \
    libpng-dev oniguruma-dev libxml2-dev \
    postgresql-dev nginx supervisor

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS

RUN docker-php-ext-install \
    pdo pdo_pgsql pgsql mbstring xml bcmath pcntl opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN apk del .build-deps

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
