#!/bin/sh
set -ex  # 'x' for debug output

echo "=== RAILWAY STARTUP DEBUG ==="
echo "PORT: ${PORT}"
echo "APP_KEY: ${APP_KEY:+SET}${APP_KEY:-NOT SET}"
echo "APP_KEY length: ${#APP_KEY}"
echo "APP_ENV: ${APP_ENV}"
echo "APP_DEBUG: ${APP_DEBUG}"

# #region agent log - Hypothesis A, B, C, D: Check .env file and APP_KEY sources
LOG_FILE="/tmp/debug.log"
if [ -f .env ]; then
    echo "DEBUG: .env file EXISTS"
    ENV_APP_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2- | head -1)
    if [ -n "$ENV_APP_KEY" ]; then
        echo "DEBUG: .env contains APP_KEY: ${ENV_APP_KEY:0:30}..."
        echo "{\"id\":\"log_$(date +%s)_env_key\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:10\",\"message\":\".env APP_KEY found\",\"data\":{\"env_key_preview\":\"${ENV_APP_KEY:0:30}...\",\"env_key_length\":${#ENV_APP_KEY}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A\"}" >> "$LOG_FILE"
    else
        echo "DEBUG: .env does NOT contain APP_KEY"
        echo "{\"id\":\"log_$(date +%s)_env_no_key\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:12\",\"message\":\".env exists but no APP_KEY\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A\"}" >> "$LOG_FILE"
    fi
else
    echo "DEBUG: .env file DOES NOT EXIST"
    echo "{\"id\":\"log_$(date +%s)_env_missing\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:14\",\"message\":\".env file missing\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"B\"}" >> "$LOG_FILE"
fi

echo "DEBUG: Railway env var APP_KEY: ${APP_KEY:0:30}..."
echo "{\"id\":\"log_$(date +%s)_railway_key\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:16\",\"message\":\"Railway env var APP_KEY\",\"data\":{\"railway_key_preview\":\"${APP_KEY:0:30}...\",\"railway_key_length\":${#APP_KEY}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"C\"}" >> "$LOG_FILE"

if [ -n "$ENV_APP_KEY" ] && [ -n "$APP_KEY" ] && [ "$ENV_APP_KEY" != "$APP_KEY" ]; then
    echo "DEBUG: CONFLICT! .env APP_KEY differs from Railway APP_KEY"
    echo "{\"id\":\"log_$(date +%s)_conflict\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:18\",\"message\":\"APP_KEY conflict detected\",\"data\":{\"env_key_preview\":\"${ENV_APP_KEY:0:30}...\",\"railway_key_preview\":\"${APP_KEY:0:30}...\"},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"D\"}" >> "$LOG_FILE"
fi
# #endregion

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

# #region agent log - Hypothesis C: Check what Laravel sees after config:clear
LOG_FILE="/tmp/debug.log"
LARAVEL_APP_KEY=$(php artisan tinker --execute="echo config('app.key') ?: 'NULL';" 2>/dev/null | tail -1 | tr -d '\n')
echo "DEBUG: Laravel config('app.key') after config:clear: ${LARAVEL_APP_KEY:0:30}..."
echo "{\"id\":\"log_$(date +%s)_laravel_key\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:45\",\"message\":\"Laravel sees APP_KEY\",\"data\":{\"laravel_key_preview\":\"${LARAVEL_APP_KEY:0:30}...\",\"laravel_key_length\":${#LARAVEL_APP_KEY}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"C\"}" >> "$LOG_FILE"
# #endregion

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
