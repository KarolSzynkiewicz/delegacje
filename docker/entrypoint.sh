#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Start PHP-FPM in background
php-fpm -D

# Execute Nginx as main process (PID 1)
exec nginx -g 'daemon off;'
