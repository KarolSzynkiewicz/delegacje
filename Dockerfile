# Prostszy Dockerfile bazujący na Ubuntu (jak Laravel Sail)
# Multi-stage build dla Laravel Production

# Stage 1: Build
FROM php:8.3-fpm AS base

ENV DEBIAN_FRONTEND=noninteractive

# Instalacja zależności systemowych i rozszerzeń PHP
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

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Kopiowanie plików composer (dla cache Docker)
COPY composer.json composer.lock ./

# Kopiowanie WSZYSTKICH plików aplikacji przed composer install
COPY . .

# Utworzenie minimalnego .env jeśli nie istnieje (dla artisan)
RUN test -f .env || cp .env.example .env || touch .env

# Utworzenie katalogów cache przed composer install
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 bootstrap/cache storage

# Instalacja zależności PHP (bez skryptów - uruchomimy później)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Uruchomienie package:discover ręcznie
RUN php artisan package:discover --ansi || true

# Regeneracja autoloadera
RUN composer dump-autoload --optimize --classmap-authoritative

# Instalacja zależności Node.js i build assets
RUN npm ci && npm run build && rm -rf node_modules

# Stage 2: Production
FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive

# Instalacja runtime dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libpng-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip \
    && docker-php-ext-enable pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Kopiowanie plików z build stage
COPY --from=base /var/www/html /var/www/html

# Konfiguracja Nginx
COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && echo "daemon off;" >> /etc/nginx/nginx.conf

# Konfiguracja PHP-FPM (Ubuntu używa www-data)
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Utworzenie katalogu dla socketu PHP-FPM
RUN mkdir -p /var/run/php && chown www-data:www-data /var/run/php

# Konfiguracja Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Skrypt startowy
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Ustawienie uprawnień (Ubuntu używa www-data)
# Nginx i PHP-FPM działają jako www-data, więc muszą mieć dostęp do plików
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
