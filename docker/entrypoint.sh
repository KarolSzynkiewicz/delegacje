#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Railway sets PORT env var (typically 8080)
# But Railway routes external traffic to port 80 internally
# We need to listen on port 80 for Railway to route traffic correctly
export LISTEN_PORT=80
envsubst '${LISTEN_PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/sites-available/default

# Verify PORT substitution worked
echo "Nginx will listen on port: $LISTEN_PORT (Railway PORT env var: ${PORT:-not set})"
echo "Note: Railway routes external traffic to port 80, regardless of PORT env var"
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
