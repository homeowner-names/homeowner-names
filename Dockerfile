FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json vite.config.js ./
COPY resources ./resources
RUN npm ci && npm run build

FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction
COPY . .
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

FROM dunglas/frankenphp:1.2-php8.3

RUN install-php-extensions zip pdo_sqlite

WORKDIR /app

COPY . .
COPY --from=vendor /app/vendor /app/vendor
COPY --from=assets /app/public/build /app/public/build

ENV APP_ENV=production
ENV APP_DEBUG=false

ENV SERVER_NAME=":8080"
ENV FRANKENPHP_CONFIG="worker /app/public/index.php"

RUN cp -n .env.example .env \
 && php artisan key:generate \
 && php artisan optimize \
 && chown -R www-data:www-data storage bootstrap/cache

RUN chmod -R ug+rw storage bootstrap/cache

EXPOSE 8080
