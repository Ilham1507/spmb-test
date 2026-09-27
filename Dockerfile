FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.3-cli-alpine
WORKDIR /var/www/html
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo_sqlite
COPY --from=dependencies /app/vendor ./vendor
COPY . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 10000
CMD ["sh", "-c", "touch database/database.sqlite && php artisan migrate --force && php -S 0.0.0.0:${PORT:-10000} -t public"]
