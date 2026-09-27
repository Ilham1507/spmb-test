FROM php:8.3-cli-alpine AS base
WORKDIR /var/www/html
RUN apk add --no-cache sqlite-dev mariadb-client libzip-dev icu-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite pdo_mysql zip intl gd

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
CMD ["sh", "-c", "if ! MYSQL_PWD=\"$DB_PASSWORD\" mysql -h \"$DB_HOST\" -P \"$DB_PORT\" -u \"$DB_USERNAME\" \"$DB_DATABASE\" -Nse \"SHOW TABLES LIKE 'users'\" | grep -q users; then MYSQL_PWD=\"$DB_PASSWORD\" mysql -h \"$DB_HOST\" -P \"$DB_PORT\" -u \"$DB_USERNAME\" \"$DB_DATABASE\" < database/schema/mysql-schema.sql; fi && php artisan migrate --force --path=database/migrations/2026_09_28_090000_add_payment_handover_fields.php && php -S 0.0.0.0:${PORT:-10000} -t public"]
