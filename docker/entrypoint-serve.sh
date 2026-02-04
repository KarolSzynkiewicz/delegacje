#!/bin/sh
set -e

echo "🚀 Starting Laravel on Railway (Port: ${PORT:-8000})"

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

# Verify APP_KEY is accessible after cache clear
if ! php artisan tinker --execute="echo config('app.key');" 2>/dev/null | grep -q "base64:"; then
    echo "⚠️ WARNING: APP_KEY not accessible after cache clear"
    echo "APP_KEY from env: ${APP_KEY:0:20}..."
fi

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
