#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Railway auto-detected port 9000 - Nginx listens on 9000
echo "Nginx will listen on port: 9000 (Railway auto-detected this port)"
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
