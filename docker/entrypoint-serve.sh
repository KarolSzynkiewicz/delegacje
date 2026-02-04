#!/bin/sh
# Entrypoint dla Railway - php artisan serve
# Railway oczekuje jednego procesu HTTP na $PORT

set -e

echo "Starting Laravel application on port: ${PORT:-8000}"

# Upewnij się, że storage link istnieje
php artisan storage:link || true

# Cache config (opcjonalne, przyspiesza start)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Uruchom php artisan serve na $PORT (Railway wstrzykuje tę zmienną)
# --host=0.0.0.0 - nasłuchuj na wszystkich interfejsach (wymagane dla Railway)
# --port=$PORT - użyj portu z Railway
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
