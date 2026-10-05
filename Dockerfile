FROM php:8.3-fpm-alpine AS base
WORKDIR /var/www/html
RUN apk add --no-cache nginx supervisor sqlite-dev mariadb-client libzip-dev icu-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite pdo_mysql zip intl gd

FROM base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm run build

FROM base
WORKDIR /var/www/html
COPY --from=dependencies /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-spmb.conf
COPY docker/supervisord.conf /etc/supervisord.conf
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database/database.sqlite \
    && php-fpm -t
EXPOSE 10000
CMD ["sh", "docker-entrypoint.sh"]
