# =========================================================
# 1) Build de frontend con Vite
# =========================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build


# =========================================================
# 2) Runtime PHP para Laravel
# =========================================================
FROM php:8.2-cli-bookworm

ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBIAN_FRONTEND=noninteractive

# Dependencias del sistema y extensiones PHP.
# -j$(nproc) compila usando todos los núcleos disponibles.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html


# =========================================================
# 3) Dependencias PHP
# =========================================================
COPY composer.json composer.lock ./

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-progress


# =========================================================
# 4) Código de Laravel y archivos compilados de Vite
# =========================================================
COPY . .

COPY --from=frontend /app/public/build ./public/build


# =========================================================
# 5) Carpetas y permisos de Laravel
# =========================================================
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

USER www-data

# Se utiliza si Railway no tiene Custom Start Command.
CMD ["/bin/sh", "-lc", "php artisan package:discover --ansi && php artisan optimize && exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]