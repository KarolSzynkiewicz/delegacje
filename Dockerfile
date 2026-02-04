# Dockerfile - Laravel on Railway
# Railway oczekuje jednego procesu HTTP na $PORT
# Używamy php artisan serve zamiast nginx + php-fpm

# Build argument to force cache invalidation
ARG CACHEBUST=1

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

# Kopiowanie plików
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

COPY . .

# Setup .env jeśli nie istnieje
RUN test -f .env || cp .env.example .env || touch .env

# Tworzenie katalogów storage
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 bootstrap/cache storage

# Package discover i autoload
RUN php artisan package:discover --ansi || true \
    && composer dump-autoload --optimize --classmap-authoritative

# Build frontend
RUN npm ci && npm run build && rm -rf node_modules

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
RUN echo "Build cache bust: ${CACHEBUST}" > /tmp/entrypoint-build.txt && cat /tmp/entrypoint-build.txt
COPY docker/entrypoint-railway.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && \
    echo "=== Entrypoint verification ===" && \
    head -20 /usr/local/bin/entrypoint.sh && \
    grep -q "Clearing caches" /usr/local/bin/entrypoint.sh && \
    echo "✓ Entrypoint has cache clearing" || \
    echo "✗ ERROR: Entrypoint missing cache clearing!"

# Uprawnienia
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Railway używa zmiennej PORT - nie ustawiamy EXPOSE
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
