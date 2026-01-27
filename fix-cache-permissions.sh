#!/bin/bash
# Script to fix cache permissions in Laravel Sail
# Usage: ./fix-cache-permissions.sh

echo "Fixing cache permissions in Laravel Sail..."

./vendor/bin/sail exec laravel.test bash -c "
    mkdir -p /var/www/html/storage/framework/cache/data
    chown -R sail:sail /var/www/html/storage/framework/cache
    chmod -R 775 /var/www/html/storage/framework/cache
    echo 'Cache permissions fixed!'
"

echo "Done!"
