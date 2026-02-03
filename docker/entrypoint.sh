#!/bin/sh
set -e

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
sed -i "s/\${PORT:-80}/$NGINX_PORT/g" /etc/nginx/sites-available/default
echo "Nginx configured to listen on port $NGINX_PORT"

# Wait a moment for processes to be ready
sleep 2

# Execute CMD
exec "$@"
