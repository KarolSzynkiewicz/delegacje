#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Railway sets PORT env var (typically 8080)
# But Railway may route to port 80 internally
# Use PORT if set, otherwise default to 80
export LISTEN_PORT=${PORT:-80}
envsubst '${LISTEN_PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/sites-available/default

# Verify PORT substitution worked
echo "Nginx will listen on port: $LISTEN_PORT (PORT env var was: ${PORT:-not set})"
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
