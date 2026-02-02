#!/bin/sh

# Uruchom migracje jeśli baza jest gotowa
echo "Waiting for database connection..."
max_attempts=30
attempt=0
until php artisan migrate:status > /dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        echo "Database connection timeout after $max_attempts attempts"
        break
    fi
    echo "Database not ready, waiting... (attempt $attempt/$max_attempts)"
    sleep 2
done

echo "Database is ready!"

# Uruchom migracje
php artisan migrate --force

# Uruchom seedery (opcjonalnie - usuń jeśli nie chcesz seedować przy każdym starcie)
# php artisan db:seed --force

# Utwórz link do storage jeśli nie istnieje
php artisan storage:link || true

# Wyczyść cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Uruchom supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
