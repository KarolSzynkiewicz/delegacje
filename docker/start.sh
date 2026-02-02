#!/bin/sh

# Uruchom migracje jeśli baza jest gotowa
echo "Waiting for database connection..."
until php artisan migrate:status > /dev/null 2>&1; do
    echo "Database not ready, waiting..."
    sleep 2
done

echo "Database is ready!"

# Uruchom migracje
php artisan migrate --force

# Utwórz link do storage jeśli nie istnieje
php artisan storage:link || true

# Wyczyść cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Uruchom supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
