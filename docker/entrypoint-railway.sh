#!/bin/sh
set -ex  # 'x' for debug output

echo "=== RAILWAY STARTUP DEBUG ==="
echo "PORT: ${PORT}"
echo "APP_KEY: ${APP_KEY:+SET}${APP_KEY:-NOT SET}"
echo "APP_KEY length: ${#APP_KEY}"
echo "APP_ENV: ${APP_ENV}"
echo "APP_DEBUG: ${APP_DEBUG}"

# Pre-flight checks
echo "🔍 Pre-flight checks..."
php -v || { echo "❌ PHP not found!"; exit 1; }
php artisan --version || { echo "❌ Artisan not found!"; exit 1; }

echo "🚀 Starting Laravel on Railway (Port: ${PORT:-8000})"
echo "🔧 Entrypoint version: 2026-02-04-railway-v2"

# Validate APP_KEY FIRST - before any config operations (HARD FAIL if missing)
if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY environment variable is not set!"
    echo ""
    echo "Generate locally with: php artisan key:generate --show"
    echo "Then add to Railway Variables: APP_KEY=base64:xxxxx"
    echo ""
    echo "Example:"
    echo "  APP_KEY=base64:ALdMPGMday9FaJ1OseM4DPksFmy7A2W0R8Zgtky4OSI="
    echo ""
    exit 1
fi

# Fix permissions for storage (Railway may run as different user)
echo "🔧 Fixing permissions..."
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

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
