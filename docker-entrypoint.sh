#!/bin/bash
set -e

# Create installed marker (in case it was lost)
touch /var/www/html/storage/installed

# Create storage link if not exists
if [ ! -L /var/www/html/public/storage ]; then
    ln -sf /var/www/html/storage/app/public /var/www/html/public/storage
fi

# Run package discovery (registers all service providers including cache)
php artisan package:discover --ansi 2>/dev/null || true

# Clear stale caches
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Start Apache
exec apache2-foreground
