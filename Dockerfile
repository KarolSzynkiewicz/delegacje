# Dockerfile - tylko buduje obraz, nie konfiguruje serwera
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

WORKDIR /var/www/html

# Kopiowanie i instalacja zależności
COPY composer.json composer.lock ./
COPY . .

# Setup dla artisan
RUN test -f .env || cp .env.example .env || touch .env
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 bootstrap/cache storage

# Instalacja zależności
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts \
    && php artisan package:discover --ansi || true \
    && composer dump-autoload --optimize --classmap-authoritative \
    && npm ci && npm run build && rm -rf node_modules

# Stage 2: Production
FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive

# Runtime dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    gettext-base \
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

# Konfiguracja Nginx (tylko reverse proxy)
# Railway auto-detected port 9000 - Nginx listens on 9000 and proxies to PHP-FPM
COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN echo "Nginx configured for port 9000" && head -3 /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Konfiguracja PHP-FPM (daemon mode)
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Entrypoint (setup + start)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Uprawnienia
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Railway auto-detected port 9000 - Nginx listens on 9000 and proxies to PHP-FPM
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
# CMD is not needed - entrypoint handles everything
