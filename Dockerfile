# syntax=docker/dockerfile:1

# =========================================================
# 1) Compilar frontend con Vite
# =========================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci --no-audit --no-fund

COPY . .

RUN npm run build


# =========================================================
# 2) PHP para Laravel
# =========================================================
FROM php:8.2-cli-bookworm AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_PROCESS_TIMEOUT=900 \
    COMPOSER_MAX_PARALLEL_HTTP=4

# Dependencias Linux y extensiones PHP
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        pkg-config \
        libcurl4-openssl-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        gd \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html


# =========================================================
# 3) Instalar dependencias de Composer
# =========================================================
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

# Intenta Composer hasta 3 veces por si falla Packagist/GitHub
RUN set -eux; \
    attempt=1; \
    while true; do \
        composer install \
            --no-dev \
            --no-interaction \
            --prefer-dist \
            --optimize-autoloader \
            --no-scripts \
            --no-progress \
            --no-ansi \
            -vv \
        && break; \
        if [ "$attempt" -ge 3 ]; then \
            echo "Composer falló después de 3 intentos"; \
            exit 1; \
        fi; \
        echo "Composer falló. Reintentando en 10 segundos..."; \
        attempt=$((attempt + 1)); \
        sleep 10; \
    done


# =========================================================
# 4) Copiar Laravel y archivos compilados
# =========================================================
COPY . .

COPY --from=frontend /app/public/build ./public/build


# =========================================================
# 5) Crear carpetas y establecer permisos
# =========================================================
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache

EXPOSE 8080

USER www-data

CMD ["/bin/sh", "-lc", "php artisan package:discover --ansi && php artisan config:cache && php artisan view:cache && exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]