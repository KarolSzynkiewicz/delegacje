#!/bin/sh
set -e

echo "🚀 Starting Laravel on Railway (Port: ${PORT:-8000})"

# Validate APP_KEY
if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY not set!"
    echo "Generate with: php artisan key:generate --show"
    echo "Then add to Railway environment variables"
    exit 1
fi

# Clear caches (don't cache in container - env vars may change)
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

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
