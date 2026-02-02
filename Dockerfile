# Multi-stage build dla Laravel
FROM php:8.3-fpm-alpine AS base

# Instalacja zależności systemowych
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    mysql-client \
    nodejs \
    npm

# Instalacja rozszerzeń PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ustawienie katalogu roboczego
WORKDIR /var/www/html

# Kopiowanie plików composer
COPY composer.json composer.lock ./

# Kopiowanie minimalnych plików potrzebnych do artisan (przed composer install)
COPY artisan ./
COPY bootstrap/app.php bootstrap/
COPY app/Providers app/Providers
COPY config/app.php config/

# Instalacja zależności PHP (teraz artisan będzie dostępny)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Kopiowanie reszty plików aplikacji
COPY . .

# Regeneracja autoloadera z wszystkimi plikami
RUN composer dump-autoload --optimize --classmap-authoritative

# Instalacja zależności Node.js i build assets
COPY package.json package-lock.json ./
RUN npm ci
RUN npm run build
RUN rm -rf node_modules

# Ustawienie uprawnień
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Stage produkcyjny
FROM php:8.3-fpm-alpine

# Instalacja minimalnych zależności
RUN apk add --no-cache \
    libpng \
    libzip \
    oniguruma \
    mysql-client \
    nginx \
    supervisor

# Instalacja rozszerzeń PHP
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apk del .build-deps

# Kopiowanie plików z poprzedniego stage
COPY --from=base /var/www/html /var/www/html

# Konfiguracja Nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Konfiguracja PHP-FPM
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Konfiguracja Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Skrypt startowy
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Ustawienie uprawnień
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Port
EXPOSE 80

# Start script
CMD ["/usr/local/bin/start.sh"]
