#!/bin/sh
set -e

echo "🚀 Starting Laravel on Railway (Port: ${PORT:-8000})"
echo "🔧 Entrypoint version: 2026-02-04-railway-v2"

# Validate APP_KEY FIRST - before any config operations
if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY not set in Railway environment variables!"
    echo "Run locally: php artisan key:generate --show"
    echo "Then add APP_KEY to Railway dashboard"
    exit 1
fi

# Clear ALL caches FIRST (don't cache in container - env vars may change)
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# DO NOT cache config - Railway env vars may change
# Config cache blocks environment variables from being used

# Optional: Run migrations only if explicitly enabled
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🗄️ Running migrations..."
    php artisan migrate --force --no-interaction
fi

# Storage link (ignore if exists)
php artisan storage:link 2>/dev/null || true

# Start server
echo "✅ Server starting on 0.0.0.0:${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
