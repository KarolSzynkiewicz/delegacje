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
        ENV_KEY_PREVIEW=$(echo "$ENV_APP_KEY" | cut -c1-30)
        echo "DEBUG: .env contains APP_KEY: ${ENV_KEY_PREVIEW}..."
        ENV_KEY_LEN=$(echo "$ENV_APP_KEY" | wc -c)
        echo "{\"id\":\"log_$(date +%s)_env_key\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:10\",\"message\":\".env APP_KEY found\",\"data\":{\"env_key_preview\":\"${ENV_KEY_PREVIEW}...\",\"env_key_length\":${ENV_KEY_LEN}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A\"}" >> "$LOG_FILE"
    else
        echo "DEBUG: .env does NOT contain APP_KEY"
        echo "{\"id\":\"log_$(date +%s)_env_no_key\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:12\",\"message\":\".env exists but no APP_KEY\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A\"}" >> "$LOG_FILE"
    fi
else
    echo "DEBUG: .env file DOES NOT EXIST"
    echo "{\"id\":\"log_$(date +%s)_env_missing\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:14\",\"message\":\".env file missing\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"B\"}" >> "$LOG_FILE"
fi

RAILWAY_KEY_PREVIEW=$(echo "$APP_KEY" | cut -c1-30)
RAILWAY_KEY_LEN=$(echo "$APP_KEY" | wc -c)
echo "DEBUG: Railway env var APP_KEY: ${RAILWAY_KEY_PREVIEW}..."
echo "{\"id\":\"log_$(date +%s)_railway_key\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:16\",\"message\":\"Railway env var APP_KEY\",\"data\":{\"railway_key_preview\":\"${RAILWAY_KEY_PREVIEW}...\",\"railway_key_length\":${RAILWAY_KEY_LEN}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"C\"}" >> "$LOG_FILE"

if [ -n "$ENV_APP_KEY" ] && [ -n "$APP_KEY" ] && [ "$ENV_APP_KEY" != "$APP_KEY" ]; then
    ENV_PREVIEW=$(echo "$ENV_APP_KEY" | cut -c1-30)
    RAILWAY_PREVIEW=$(echo "$APP_KEY" | cut -c1-30)
    echo "DEBUG: CONFLICT! .env APP_KEY differs from Railway APP_KEY"
    echo "{\"id\":\"log_$(date +%s)_conflict\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:18\",\"message\":\"APP_KEY conflict detected\",\"data\":{\"env_key_preview\":\"${ENV_PREVIEW}...\",\"railway_key_preview\":\"${RAILWAY_PREVIEW}...\"},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"D\"}" >> "$LOG_FILE"
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

# Remove .env file to force Laravel to use Railway environment variables only
# .env file may contain old/stale APP_KEY from build time
if [ -f .env ]; then
    OLD_ENV_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2- | head -1)
    OLD_KEY_PREVIEW=$(echo "$OLD_ENV_KEY" | cut -c1-30)
    RAILWAY_KEY_PREVIEW=$(echo "$APP_KEY" | cut -c1-30)
    echo "⚠️ Removing .env file (had APP_KEY: ${OLD_KEY_PREVIEW}...) to use Railway env vars only"
    rm -f .env
    echo "✅ .env removed - Laravel will use Railway env vars (${RAILWAY_KEY_PREVIEW}...)"
    # #region agent log - Verification: .env removed
    LOG_FILE="/tmp/debug.log"
    echo "{\"id\":\"log_$(date +%s)_env_removed\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:50\",\"message\":\".env file removed\",\"data\":{\"old_env_key_preview\":\"${OLD_KEY_PREVIEW}...\",\"railway_key_preview\":\"${RAILWAY_KEY_PREVIEW}...\"},\"sessionId\":\"debug-session\",\"runId\":\"post-fix\",\"hypothesisId\":\"D\"}" >> "$LOG_FILE"
    # #endregion
else
    echo "✅ No .env file - Laravel will use Railway env vars only"
fi

# Setup Railway Volume for persistent storage (if mounted)
# Railway volumes are typically mounted at /data
if [ -d "/data" ] && [ -w "/data" ]; then
    echo "📦 Railway Volume detected at /data"
    
    # Create storage directory structure in volume if it doesn't exist
    mkdir -p /data/storage/app/public/employees \
             /data/storage/app/public/users \
             /data/storage/app/public/vehicles \
             /data/storage/app/public/accommodations \
             /data/storage/app/public/employee_documents \
             /data/storage/framework/cache \
             /data/storage/framework/sessions \
             /data/storage/framework/views \
             /data/storage/logs
    
    # Copy existing storage to volume if volume is empty (only on first run)
    if [ ! -f "/data/storage/.volume-initialized" ]; then
        echo "📋 Initializing Railway Volume with existing storage..."
        if [ -d "storage/app/public" ] && [ "$(ls -A storage/app/public 2>/dev/null)" ]; then
            cp -r storage/app/public/* /data/storage/app/public/ 2>/dev/null || true
        fi
        touch /data/storage/.volume-initialized
        echo "✅ Volume initialized"
    fi
    
    # Remove existing symlink or directory if exists
    if [ -L "storage/app/public" ] || [ -d "storage/app/public" ]; then
        echo "🔗 Removing existing storage/app/public..."
        rm -rf storage/app/public
    fi
    
    # Create symlink from container storage to volume
    echo "🔗 Creating symlink: storage/app/public -> /data/storage/app/public"
    ln -sf /data/storage/app/public storage/app/public
    
    # Verify symlink was created
    if [ -L "storage/app/public" ]; then
        echo "✅ Symlink created successfully"
        SYMLINK_TARGET=$(readlink -f storage/app/public)
        echo "   Symlink points to: $SYMLINK_TARGET"
        if [ "$SYMLINK_TARGET" = "/data/storage/app/public" ]; then
            echo "   ✅ Symlink target is correct"
        else
            echo "   ⚠️ WARNING: Symlink target is incorrect!"
        fi
    else
        echo "❌ WARNING: Failed to create symlink!"
    fi
    
    # Fix permissions for volume
    chmod -R 777 /data/storage 2>/dev/null || true
    
    # Test write access to volume
    TEST_FILE="/data/storage/.write-test-$(date +%s)"
    if touch "$TEST_FILE" 2>/dev/null; then
        rm -f "$TEST_FILE"
        echo "✅ Write access to volume verified"
    else
        echo "❌ WARNING: Cannot write to volume!"
    fi
    
    echo "✅ Railway Volume configured for persistent storage"
else
    echo "⚠️ Railway Volume not detected at /data - using ephemeral storage (files will be lost on redeploy)"
    echo "   To enable persistent storage, add a Railway Volume with mount path: /data"
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

# #region agent log - Verification: Check what Laravel sees after .env removal and config:clear
LOG_FILE="/tmp/debug.log"
LARAVEL_APP_KEY=$(php artisan tinker --execute="echo config('app.key') ?: 'NULL';" 2>/dev/null | tail -1 | tr -d '\n')
RAILWAY_ENV_KEY=$(echo "$APP_KEY")
LARAVEL_KEY_PREVIEW=$(echo "$LARAVEL_APP_KEY" | cut -c1-30)
RAILWAY_KEY_PREVIEW=$(echo "$RAILWAY_ENV_KEY" | cut -c1-30)
echo "DEBUG: Laravel config('app.key') after .env removal: ${LARAVEL_KEY_PREVIEW}..."
echo "DEBUG: Railway env var APP_KEY: ${RAILWAY_KEY_PREVIEW}..."
if [ "$LARAVEL_APP_KEY" = "$RAILWAY_ENV_KEY" ]; then
    echo "✅ VERIFIED: Laravel uses Railway env var (keys match)"
    echo "{\"id\":\"log_$(date +%s)_verified\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:70\",\"message\":\"APP_KEY sync verified\",\"data\":{\"laravel_key_preview\":\"${LARAVEL_KEY_PREVIEW}...\",\"railway_key_preview\":\"${RAILWAY_KEY_PREVIEW}...\",\"match\":true},\"sessionId\":\"debug-session\",\"runId\":\"post-fix\",\"hypothesisId\":\"D\"}" >> "$LOG_FILE"
else
    echo "❌ WARNING: Laravel key differs from Railway env var"
    echo "{\"id\":\"log_$(date +%s)_mismatch\",\"timestamp\":$(date +%s)000,\"location\":\"entrypoint-railway.sh:72\",\"message\":\"APP_KEY mismatch\",\"data\":{\"laravel_key_preview\":\"${LARAVEL_KEY_PREVIEW}...\",\"railway_key_preview\":\"${RAILWAY_KEY_PREVIEW}...\",\"match\":false},\"sessionId\":\"debug-session\",\"runId\":\"post-fix\",\"hypothesisId\":\"D\"}" >> "$LOG_FILE"
fi
# #endregion

# DO NOT cache config - Railway env vars may change
# Config cache blocks environment variables from being used

# Optional: Run migrations only if explicitly enabled
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🗄️ Running migrations..."
    php artisan migrate --force --no-interaction
fi

# Storage link (only if not using Railway Volume)
# If Railway Volume is mounted, symlink is already created above
if [ ! -d "/data" ] || [ ! -w "/data" ]; then
    echo "🔗 Creating storage symlink (no Railway Volume detected)..."
    php artisan storage:link 2>/dev/null || true
else
    echo "⏭️ Skipping storage:link (Railway Volume symlink already created)"
    # Ensure public/storage symlink exists (for asset serving)
    if [ ! -L "public/storage" ]; then
        php artisan storage:link 2>/dev/null || true
    fi
fi

# Ensure server.php exists for static file serving
# php artisan serve uses server.php to handle static files correctly
if [ ! -f server.php ]; then
    echo "📋 Copying server.php to root for static file serving..."
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php server.php
fi
if [ ! -f public/server.php ]; then
    echo "📋 Copying server.php to public/ for static file serving..."
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php public/server.php
fi

# Start server
# php artisan serve automatically serves from public/ directory
# exec ensures this process becomes PID 1 and Railway can track it
echo "✅ Server starting on 0.0.0.0:${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
