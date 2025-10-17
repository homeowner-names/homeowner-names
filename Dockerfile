# frontend assets
FROM node:20 AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js ./
RUN npm run build

# Install PHP deps
FROM composer:2 AS vendor
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

# Copy the app and finish install
COPY . .
# bring built assets from the previous stage
COPY --from=assets /app/public/build /app/public/build

# composer
RUN composer install --no-dev --prefer-dist --no-interaction

# Runtime image
FROM php:8.3-cli
WORKDIR /app

# optional extensions
RUN curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions zip pdo_sqlite

# copy everything from the vendor stage
COPY --from=vendor /app /app

# production defaults
ENV APP_ENV=production \
    APP_DEBUG=0

EXPOSE 8080

# Use Laravel's router (server.php) with PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "server.php"]
