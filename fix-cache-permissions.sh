#!/bin/bash
# Script to fix cache permissions in Laravel Sail
# Usage: ./fix-cache-permissions.sh

echo "Fixing cache permissions in Laravel Sail..."

./vendor/bin/sail exec laravel.test bash -c "
    # Create cache directories if they don't exist
    mkdir -p /var/www/html/storage/framework/cache/data
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views
    mkdir -p /var/www/html/bootstrap/cache
    
    # Fix ownership
    chown -R sail:sail /var/www/html/storage/framework/cache
    chown -R sail:sail /var/www/html/storage/framework/sessions
    chown -R sail:sail /var/www/html/storage/framework/views
    chown -R sail:sail /var/www/html/bootstrap/cache
    
    # Fix directory permissions (775 = rwxrwxr-x)
    find /var/www/html/storage/framework/cache -type d -exec chmod 775 {} \;
    find /var/www/html/storage/framework/sessions -type d -exec chmod 775 {} \;
    find /var/www/html/storage/framework/views -type d -exec chmod 775 {} \;
    find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
    
    # Fix file permissions (664 = rw-rw-r--)
    find /var/www/html/storage/framework/cache -type f -exec chmod 664 {} \;
    find /var/www/html/storage/framework/sessions -type f -exec chmod 664 {} \;
    find /var/www/html/storage/framework/views -type f -exec chmod 664 {} \;
    find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;
    
    echo 'Cache permissions fixed!'
"

echo "Done!"
