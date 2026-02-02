#!/bin/sh
set -e

# Przekieruj output do stdout/stderr żeby był widoczny w logach
exec 1>&1
exec 2>&2

echo "=== Starting application setup ==="

# Utwórz link do storage jeśli nie istnieje
echo "Creating storage link..."
php artisan storage:link || true

# Wyczyść cache przed migracjami
echo "Clearing cache..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Uruchom migracje jeśli baza jest gotowa
echo "Checking database connection..."
max_attempts=20
attempt=0
db_ready=false

# Sprawdź połączenie używając prostego testu
while [ $attempt -lt $max_attempts ]; do
    # Test połączenia przez php
    if php -r "
        try {
            \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$pdo->query('SELECT 1');
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; then
        echo "Database is ready!"
        db_ready=true
        break
    fi
    attempt=$((attempt + 1))
    echo "Database not ready, waiting... (attempt $attempt/$max_attempts)"
    sleep 3
done

if [ "$db_ready" = true ]; then
    echo "Running migrations..."
    php artisan migrate --force || echo "Migration failed: $?"
else
    echo "Database connection timeout after $max_attempts attempts"
    echo "DB_HOST: ${DB_HOST:-not set}"
    echo "DB_PORT: ${DB_PORT:-not set}"
    echo "DB_DATABASE: ${DB_DATABASE:-not set}"
    echo "Skipping migrations - application will start but may have database errors"
fi

# Wyczyść i zbuilduj cache po migracjach
echo "Building cache..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "=== Setup completed ==="
# Setup zakończony - supervisord uruchomi nginx i php-fpm
