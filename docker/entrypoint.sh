#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Railway sets PORT env var (typically 8080) but may route external traffic to port 80
# Listen on both ports to handle both internal (healthcheck) and external traffic
export LISTEN_PORT=${PORT:-80}
envsubst '${LISTEN_PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/sites-available/default

# Add port 80 to nginx config if not already there (for external Railway routing)
if ! grep -q "listen 0.0.0.0:80" /etc/nginx/sites-available/default; then
    sed -i 's/listen 0.0.0.0:${LISTEN_PORT};/listen 0.0.0.0:80;\n    listen 0.0.0.0:${LISTEN_PORT};/' /etc/nginx/sites-available/default
    envsubst '${LISTEN_PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/sites-available/default
fi

# Verify PORT substitution worked
echo "Nginx will listen on ports: 80 and $LISTEN_PORT (Railway PORT env var: ${PORT:-not set})"
grep "listen" /etc/nginx/sites-available/default

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
