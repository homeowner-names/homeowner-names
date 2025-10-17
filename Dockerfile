# frontend assets
FROM node:20 AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js .
RUN npm run build

# PHP deps
FROM composer:2 AS vendor
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./

# composer
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader --no-scripts

# runtime
FROM php:8.3-cli
WORKDIR /app

# PHP extensions
RUN curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions zip pdo_sqlite

# copy application source
COPY . .
COPY --from=assets /app/public/build /app/public/build
COPY --from=vendor /app/vendor /app/vendor

# permissions for caches
RUN mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# production defaults
ENV APP_ENV=production \
    APP_DEBUG=0 \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync

EXPOSE 8080

# clear caches, then serve
CMD ["sh","-lc","php artisan package:discover --ansi || true; php artisan config:clear; php artisan route:clear; php artisan view:clear; php -S 0.0.0.0:8080 -t public"]
