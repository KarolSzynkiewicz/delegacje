# Dockerfile - Laravel on Railway
# Uses php artisan serve (single process on dynamic $PORT)

# Build stage
FROM php:8.3-fpm AS base

ENV DEBIAN_FRONTEND=noninteractive

# Install build dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libzip-dev libjpeg-dev libfreetype6-dev \
    libonig-dev zip unzip nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Cache dependencies (changes less frequently than source code)
COPY composer.json composer.lock package.json package-lock.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts
RUN npm ci --prefer-offline --no-audit

# Copy application source
COPY . .

# Copy server.php for php artisan serve static file handling
RUN cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php server.php && \
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php public/server.php

# Build frontend
RUN php artisan package:discover --ansi || true && \
    composer dump-autoload --optimize --classmap-authoritative && \
    npm run build && rm -rf node_modules

# Setup storage directories
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 bootstrap/cache storage

# Production stage
FROM php:8.3-cli

ENV DEBIAN_FRONTEND=noninteractive

# Install runtime dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libzip-dev libjpeg-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy entrypoint first (cleaner than overwriting)
COPY docker/entrypoint-railway.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copy application from build stage
COPY --from=base /var/www/html /var/www/html

# Fix permissions
RUN chmod -R 777 storage bootstrap/cache

ENTRYPOINT ["/entrypoint.sh"]
