#!/bin/sh
# Don't exit on error - we want to continue even if migrations fail
set +e

echo "=== Application Setup ==="

# Storage link
php artisan storage:link || true

# Clear cache
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Database connection check
max_attempts=20
attempt=0
db_ready=false

while [ $attempt -lt $max_attempts ]; do
    if php -r "
        try {
            \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$pdo->query('SELECT 1');
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; then
        db_ready=true
        break
    fi
    attempt=$((attempt + 1))
    echo "Waiting for database... ($attempt/$max_attempts)"
    sleep 3
done

# Migrations
if [ "$db_ready" = true ]; then
    php artisan migrate --force || echo "Migration failed, continuing..."
else
    echo "Database not ready, skipping migrations"
fi

# Cache
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "=== Setup Complete ==="

# Substitute PORT variable in nginx config (Railway uses dynamic ports)
# Use PORT if set, otherwise default to 80
NGINX_PORT=${PORT:-80}
echo "PORT environment variable: $PORT"
echo "Using Nginx port: $NGINX_PORT"

# Use perl for reliable substitution (handles special chars better than sed)
perl -i -pe "s/listen 0\.0\.0\.0:8080;/listen 0.0.0.0:$NGINX_PORT;/" /etc/nginx/sites-available/default
echo "Nginx configured to listen on 0.0.0.0:$NGINX_PORT"

# Verify the substitution worked
echo "Verifying Nginx config..."
grep "listen" /etc/nginx/sites-available/default || echo "WARNING: Could not find listen directive"

# Test Nginx configuration before starting
echo "Testing Nginx configuration..."
nginx -t || echo "WARNING: Nginx configuration test failed, but continuing..."

# Start PHP-FPM in background (daemon mode) - only if we're not in static test mode
if [ -f /var/www/html/public/index.php ]; then
    echo "Starting PHP-FPM..."
    php-fpm -D
    sleep 2
else
    echo "Static test mode - skipping PHP-FPM"
fi

# Execute CMD (nginx as main process)
echo "Starting Nginx..."
echo "About to exec: $@"
echo "Current PID: $$"

# Double-check Nginx config one more time
echo "Final Nginx config check:"
cat /etc/nginx/sites-available/default | grep listen

# Verify Nginx can start (test run)
echo "Testing if Nginx can start..."
timeout 2 nginx -g "daemon off;" 2>&1 || echo "Nginx test completed (timeout expected)"

# Execute Nginx as main process (PID 1)
# This is what Railway needs - Nginx must be the main process
echo "Executing Nginx as main process..."
exec "$@"
