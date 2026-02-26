FROM php:8.2-fpm

# System deps needed for GD + Zip + common tooling
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev unzip git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Install composer deps first (better cache)
COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy app
COPY . .

# Laravel permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

