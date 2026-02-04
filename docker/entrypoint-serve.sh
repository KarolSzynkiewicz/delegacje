#!/bin/sh
set -e

echo "🚀 Starting Laravel on Railway (Port: ${PORT:-8000})"

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo "⚙️ Generating APP_KEY..."
    php artisan key:generate --force --no-interaction
fi

# Clear and cache config
echo "📦 Caching configuration..."
php artisan config:clear
php artisan config:cache

# Run migrations (optional - remove if you prefer manual migrations)
echo "🗄️ Running migrations..."
php artisan migrate --force --no-interaction || echo "⚠️ Migrations failed (might be intentional)"

# Storage link
php artisan storage:link || true

# Start PHP built-in server
echo "✅ Starting server on 0.0.0.0:${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
