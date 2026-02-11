# Dockerfile - Laravel on Railway
# Uses php artisan serve (single process on dynamic $PORT)

# Build stage
FROM php:8.3-fpm AS base

ENV DEBIAN_FRONTEND=noninteractive

# Install build dependencies
RUN echo "[STEP] Installing system libraries for PHP extensions..." && \
    apt-get update && apt-get install -y \
    git curl libpng-dev libzip-dev libjpeg-dev libfreetype6-dev \
    libonig-dev zip unzip nodejs npm && \
    echo "[OK] System libraries installed"

RUN echo "[STEP] Installing PHP extensions (pdo_mysql, gd, zip, etc)..." && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip && \
    echo "[OK] PHP extensions installed"

RUN echo "[STEP] Cleaning up apt cache..." && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    echo "[OK] Cache cleaned"

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Cache dependencies (changes less frequently than source code)
RUN echo "[STEP] Preparing for dependency installation..."
COPY composer.json composer.lock package.json package-lock.json ./

RUN echo "[STEP] Installing PHP dependencies (composer)..." && \
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts && \
    echo "[OK] Composer dependencies installed"

RUN echo "[STEP] Installing frontend dependencies (npm)..." && \
    npm ci --prefer-offline --no-audit && \
    echo "[OK] Node modules installed"

# Copy application source
RUN echo "[STEP] Copying application source code..."
COPY . .
RUN echo "[OK] Source code copied"

# Setup storage directories (BEFORE package:discover!)
RUN echo "[STEP] Creating storage and cache directories..." && \
    mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views && \
    chmod -R 775 bootstrap/cache storage && \
    echo "[OK] Directories created"

# Copy server.php for php artisan serve static file handling
RUN echo "[STEP] Preparing server.php for static file serving..." && \
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php server.php && \
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php public/server.php && \
    echo "[OK] server.php ready"

# Build frontend
RUN echo "[STEP] Optimizing Laravel (package discovery + autoload)..." && \
    php artisan package:discover --ansi || true && \
    composer dump-autoload --optimize --classmap-authoritative && \
    echo "[OK] Laravel optimized"

RUN echo "[STEP] Building frontend assets (npm run build)..." && \
    npm run build && \
    echo "[OK] Frontend assets built"

RUN echo "[STEP] Cleaning up node_modules..." && \
    rm -rf node_modules && \
    echo "[OK] node_modules removed"

# Production stage
FROM php:8.3-cli

ENV DEBIAN_FRONTEND=noninteractive

# Install runtime dependencies
RUN echo "[STEP] Installing runtime dependencies for production..." && \
    apt-get update && apt-get install -y \
    libpng-dev libzip-dev libjpeg-dev libfreetype6-dev libonig-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    echo "[OK] Runtime dependencies installed"

WORKDIR /var/www/html

# Copy entrypoint first (cleaner than overwriting)
RUN echo "[STEP] Preparing entrypoint script..."
COPY docker/entrypoint-railway.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    echo "[OK] Entrypoint ready"

# Copy application from build stage
RUN echo "[STEP] Copying built application from build stage..."
COPY --from=base /var/www/html /var/www/html
RUN echo "[OK] Application copied"

# Fix permissions
RUN echo "[STEP] Setting final permissions..." && \
    chmod -R 777 storage bootstrap/cache && \
    echo "[OK] Permissions set"

RUN echo "[BUILD] Docker image build complete - ready to run"

ENTRYPOINT ["/entrypoint.sh"]
