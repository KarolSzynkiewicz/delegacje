#!/bin/sh
set -e

echo "[START] Laravel application startup on Railway"
echo "[INFO] Port: ${PORT:-8000}"

# Validate APP_KEY (fail fast if missing)
echo "[CHECK] Validating APP_KEY..."
if [ -z "$APP_KEY" ]; then
    echo "[ERROR] APP_KEY environment variable is not set"
    echo "[HELP] Generate locally: php artisan key:generate --show"
    echo "[HELP] Then add to Railway Variables"
    exit 1
fi
echo "[OK] APP_KEY validated"

# Remove .env to force Railway environment variables
echo "[STEP] Removing .env file (Railway Variables take priority)..."
rm -f .env
echo "[OK] .env removed - using Railway environment variables"

# Railway Volume setup (if mounted at /data)
if [ -d "/data" ] && [ -w "/data" ]; then
    echo "[VOLUME] Railway Volume detected at /data"
    
    echo "[STEP] Preparing volume directory structure..."
    mkdir -p /data/storage/app/public
    
    # Initialize volume on first run (preserve existing files)
    if [ ! -f "/data/storage/.initialized" ]; then
        echo "[INIT] First run - copying existing files to volume..."
        cp -r storage/app/public/* /data/storage/app/public/ 2>/dev/null || true
        touch /data/storage/.initialized
        echo "[OK] Volume initialized with existing files"
    else
        echo "[INFO] Volume already initialized"
    fi
    
    # Setup symlinks
    echo "[STEP] Creating symlinks to volume..."
    rm -rf storage/app/public
    ln -sf /data/storage/app/public storage/app/public
    ln -sf /data/storage/app/public public/storage
    echo "[OK] Symlinks created"
    
    # Fix permissions
    echo "[STEP] Setting volume permissions..."
    chmod -R 777 /data/storage 2>/dev/null || true
    echo "[OK] Volume configured for persistent storage"
else
    echo "[INFO] No Railway Volume detected - using ephemeral storage"
fi

# Fix local permissions
echo "[STEP] Setting local storage permissions..."
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
echo "[OK] Permissions set"

# Clear all caches (Railway env vars may change between deploys)
echo "[CACHE] Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "[OK] All caches cleared"

# Optional migrations (controlled by RUN_MIGRATIONS env var)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "[MIGRATE] Running database migrations..."
    php artisan migrate --force --no-interaction
    echo "[OK] Migrations complete"
else
    echo "[INFO] Migrations skipped (RUN_MIGRATIONS not set to 'true')"
fi

# Passport keys for HTTP MCP (ChatGPT / Grok).
# Prefer PASSPORT_* env vars; otherwise keep one key pair in the database so
# Railway restarts do not invalidate existing Grok/ChatGPT tokens.
echo "[STEP] Ensuring Passport OAuth keys..."
if php artisan mcp:ensure-passport-keys --no-interaction; then
    echo "[OK] Passport OAuth keys ready"
else
    echo "[WARN] mcp:ensure-passport-keys failed; falling back to passport:keys"
    php artisan passport:keys --no-interaction || true
fi

# Keep procedure waits ticking without an external cron.
echo "[SCHEDULER] Starting schedule:work in background..."
php artisan schedule:work >> storage/logs/scheduler.log 2>&1 &

# Start server
echo "[START] Starting Laravel server..."
echo "[INFO] Listening on 0.0.0.0:${PORT:-8000}"
echo "[READY] Application ready - waiting for requests"
echo ""

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
