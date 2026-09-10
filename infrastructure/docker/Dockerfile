FROM node:22-alpine AS assets
WORKDIR /build
COPY apps/web/package*.json ./
RUN npm ci
COPY apps/web/resources ./resources
COPY apps/web/vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY apps/web/composer.json apps/web/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

FROM php:8.4-fpm-alpine AS runtime
RUN apk add --no-cache postgresql-client libpq-dev libzip-dev oniguruma-dev icu-dev freetype-dev libpng-dev libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql zip mbstring intl gd bcmath opcache \
    && wget -qO /usr/local/bin/php-fpm-healthcheck https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck
WORKDIR /var/www/html
COPY apps/web .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /build/public/build ./public/build
RUN mkdir -p storage/framework/{cache,sessions,views} storage/app/private storage/logs bootstrap/cache /backups \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache /backups
USER www-data
EXPOSE 9000

FROM nginx:1.27-alpine AS nginx
COPY apps/web/public /var/www/html/public
COPY --from=assets /build/public/build /var/www/html/public/build
COPY infrastructure/nginx/default.conf /etc/nginx/conf.d/default.conf
