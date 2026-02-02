#!/bin/sh
set -e

echo "=== Starting application setup ==="

# Utwórz link do storage jeśli nie istnieje
echo "Creating storage link..."
php artisan storage:link || true

# Wyczyść cache przed migracjami
echo "Clearing cache..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Uruchom migracje jeśli baza jest gotowa (w tle, żeby nie blokować startu)
echo "Checking database connection..."
max_attempts=15
attempt=0
db_ready=false

while [ $attempt -lt $max_attempts ]; do
    if php artisan migrate:status > /dev/null 2>&1; then
        echo "Database is ready!"
        db_ready=true
        break
    fi
    attempt=$((attempt + 1))
    echo "Database not ready, waiting... (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ "$db_ready" = true ]; then
    echo "Running migrations..."
    php artisan migrate --force || echo "Migration failed, continuing..."
else
    echo "Database connection timeout, skipping migrations (will retry on next request)"
fi

# Wyczyść i zbuilduj cache po migracjach
echo "Building cache..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "=== Starting services ==="
# Uruchom supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
