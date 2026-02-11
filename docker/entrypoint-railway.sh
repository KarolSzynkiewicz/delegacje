#!/bin/sh
set -e

echo "Starting Laravel on Railway (Port: ${PORT:-8000})"

# Validate APP_KEY (fail fast if missing)
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY environment variable is not set"
    echo "Generate locally: php artisan key:generate --show"
    echo "Then add to Railway Variables"
    exit 1
fi

# Remove .env to force Railway environment variables
rm -f .env

# Railway Volume setup (if mounted at /data)
if [ -d "/data" ] && [ -w "/data" ]; then
    echo "Railway Volume detected at /data"
    mkdir -p /data/storage/app/public
    
    # Initialize volume on first run (preserve existing files)
    if [ ! -f "/data/storage/.initialized" ]; then
        echo "Initializing volume (first run)..."
        cp -r storage/app/public/* /data/storage/app/public/ 2>/dev/null || true
        touch /data/storage/.initialized
    fi
    
    # Setup symlinks
    rm -rf storage/app/public
    ln -sf /data/storage/app/public storage/app/public
    ln -sf /data/storage/app/public public/storage
    
    # Fix permissions
    chmod -R 777 /data/storage 2>/dev/null || true
    echo "Volume configured for persistent storage"
fi

# Fix local permissions
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Clear all caches (Railway env vars may change between deploys)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optional migrations (controlled by RUN_MIGRATIONS env var)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force --no-interaction
fi

# Start server
echo "Server starting on 0.0.0.0:${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
