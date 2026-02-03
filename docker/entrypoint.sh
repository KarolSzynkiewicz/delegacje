#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Railway sets PORT env var and routes traffic to that port
# We must listen on the PORT that Railway provides
export LISTEN_PORT=${PORT:-80}
envsubst '${LISTEN_PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/sites-available/default

# Verify PORT substitution worked
echo "Nginx will listen on port: $LISTEN_PORT (Railway PORT env var: ${PORT:-not set})"
grep "listen" /etc/nginx/sites-available/default | head -1

# Test Nginx config
nginx -t || {
    echo "ERROR: Nginx config test failed!"
    exit 1
}

# Start PHP-FPM in background
php-fpm -D

# Execute Nginx as main process (PID 1)
echo "Starting Nginx..."
# Use exec to replace shell with Nginx (becomes PID 1)
exec nginx -g 'daemon off;' 2>&1
