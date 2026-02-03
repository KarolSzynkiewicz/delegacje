#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Railway routes external traffic to port 80, internal healthcheck to PORT (8080)
# Listen on both ports to handle both
echo "Nginx will listen on ports: 80 and 8080 (Railway PORT env var: ${PORT:-not set})"
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
