FROM php:8.3-cli-alpine AS base
WORKDIR /var/www/html
RUN apk add --no-cache sqlite-dev libzip-dev icu-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite zip intl gd

FROM base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM base
WORKDIR /var/www/html
COPY --from=dependencies /app/vendor ./vendor
COPY . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database/database.sqlite
EXPOSE 10000
CMD ["sh", "-c", "php artisan migrate --force && php -S 0.0.0.0:${PORT:-10000} -t public"]
