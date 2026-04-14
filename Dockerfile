# 1) Build frontend assets (Vite)
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# 2) PHP runtime for Railway
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev unzip git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Install PHP dependencies first (better layer cache)
COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy application code and built frontend assets
COPY . .
COPY --from=assets /app/public/build /var/www/html/public/build

# Runtime optimizations / permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
 && php artisan config:clear \
 && php artisan route:clear \
 && php artisan view:clear

ENV APP_ENV=production
EXPOSE 8080

# Railway injects PORT; fall back to 8080 for local docker run
CMD ["sh", "-lc", "php artisan migrate --force --no-interaction && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
