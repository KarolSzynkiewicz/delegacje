#!/bin/sh
set -ex

echo "🚀 Starting Laravel on Railway with Nginx + PHP-FPM (Port: ${PORT:-8000})"
echo "🔧 Entrypoint version: 2026-02-05-nginx"

# Validate APP_KEY FIRST
if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY environment variable is not set!"
    echo ""
    echo "Generate locally with: php artisan key:generate --show"
    echo "Then add to Railway Variables: APP_KEY=base64:xxxxx"
    echo ""
    exit 1
fi

# Remove .env file to force Laravel to use Railway environment variables only
if [ -f .env ]; then
    echo "⚠️ Removing .env file to use Railway env vars only"
    rm -f .env
fi

# Fix permissions for storage
echo "🔧 Fixing permissions..."
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Clear ALL caches FIRST
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Setup Railway Volume for persistent storage (if mounted)
if [ -d "/data" ] && [ -w "/data" ]; then
    echo "📦 Railway Volume detected at /data"
    
    # Create storage directory structure in volume
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
    mkdir -p /data/storage/app/public
    rm -rf storage/app/public
    ln -sf /data/storage/app/public storage/app/public
    
    # Verify symlink
    if [ -L "storage/app/public" ]; then
        echo "✅ Symlink created successfully"
        SYMLINK_TARGET=$(readlink -f storage/app/public 2>/dev/null || readlink storage/app/public)
        echo "   Symlink points to: $SYMLINK_TARGET"
    fi
    
    # Fix permissions for volume
    chmod -R 777 /data/storage 2>/dev/null || true
    echo "✅ Railway Volume configured for persistent storage"
else
    echo "⚠️ Railway Volume not detected - using ephemeral storage"
fi

# Create public/storage symlink (standard Laravel way)
echo "🔗 Creating public/storage symlink..."
php artisan storage:link 2>/dev/null || true

# Verify public/storage symlink
if [ -L "public/storage" ]; then
    echo "✅ public/storage symlink created"
    STORAGE_LINK_TARGET=$(readlink -f public/storage 2>/dev/null || readlink public/storage)
    echo "   public/storage -> $STORAGE_LINK_TARGET"
else
    echo "⚠️ WARNING: public/storage symlink not created"
fi

# Optional: Run migrations only if explicitly enabled
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "🗄️ Running migrations..."
    php artisan migrate --force --no-interaction
fi

# Generate Nginx config with PORT variable
echo "🔧 Generating Nginx configuration..."
PORT_VALUE=${PORT:-8000}
sed "s/__PORT__/$PORT_VALUE/g" /etc/nginx/templates/default.conf.template > /etc/nginx/sites-available/default
echo "   Nginx will listen on port: $PORT_VALUE"

# Verify Nginx config
echo "🔍 Verifying Nginx configuration..."
nginx -t

# Create PHP-FPM socket directory
mkdir -p /var/run/php
chown www-data:www-data /var/run/php

echo "✅ Setup complete. Starting services via supervisord..."
echo "   Nginx will listen on port: ${PORT:-8000}"
echo "   PHP-FPM will use unix socket: /var/run/php/php8.3-fpm.sock"

# Start services via supervisord (defined in CMD)
exec "$@"
