# Dockerfile - Laravel on Railway
# Railway oczekuje jednego procesu HTTP na $PORT
# Używamy php artisan serve zamiast nginx + php-fpm
# FORCE_REBUILD: 2026-02-04-20:45:00 - FULL REBUILD NO CACHE

# Build argument to force cache invalidation
# Set CACHEBUST env var in Railway dashboard to force rebuild
# Or Railway will use default value 1
ARG CACHEBUST=20260204204500

# Stage 1: Build
FROM php:8.3-fpm AS base

ENV DEBIAN_FRONTEND=noninteractive

# Instalacja zależności build
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setup aplikacji
WORKDIR /var/www/html

# OPTIMIZATION: Copy dependency files FIRST for better caching
# Dependencies change less frequently than source code
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# OPTIMIZATION: Copy package files before source code
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline --no-audit

# OPTIMIZATION: Copy source code LAST (changes most frequently)
COPY . .

# Copy server.php to root AND public/ for php artisan serve to handle static files correctly
# php artisan serve looks for server.php in base_path() first, then uses framework default
RUN cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php server.php && \
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php public/server.php

# Setup .env jeśli nie istnieje
RUN test -f .env || cp .env.example .env || touch .env

# Tworzenie katalogów storage
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 bootstrap/cache storage

# Package discover i autoload
RUN php artisan package:discover --ansi || true \
    && composer dump-autoload --optimize --classmap-authoritative

# Build frontend (node_modules already installed above)
RUN npm run build && rm -rf node_modules

# Stage 2: Production
FROM php:8.3-cli

ENV DEBIAN_FRONTEND=noninteractive

# Runtime dependencies (tylko PHP extensions, bez nginx)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip \
    && docker-php-ext-enable pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Kopiowanie aplikacji
COPY --from=base /var/www/html /var/www/html

WORKDIR /var/www/html

# Entrypoint - php artisan serve na $PORT
# CRITICAL: Use CACHEBUST to force rebuild when entrypoint changes
RUN echo "Build cache bust: ${CACHEBUST}" > /tmp/entrypoint-build.txt && \
    echo "Entrypoint timestamp: $(date +%s)" >> /tmp/entrypoint-build.txt && \
    echo "Full rebuild: $(date)" >> /tmp/entrypoint-build.txt
COPY docker/entrypoint-railway.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    echo "=== Entrypoint verification ===" && \
    head -5 /usr/local/bin/entrypoint.sh && \
    echo "=== Entrypoint verification: NO config:cache ===" && \
    grep -q "DO NOT cache config" /usr/local/bin/entrypoint.sh && echo "✅ Entrypoint does NOT cache config" || echo "❌ WARNING: Entrypoint may cache config!" && \
    ! grep -q "config:cache" /usr/local/bin/entrypoint.sh && echo "✅ Entrypoint does NOT run config:cache" || echo "❌ ERROR: Entrypoint runs config:cache!"

# Uprawnienia (Railway może działać jako root, więc używamy 777 dla storage)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Railway używa zmiennej PORT - nie ustawiamy EXPOSE
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
