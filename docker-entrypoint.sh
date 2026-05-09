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

# Automate Database Migration & Seeding if empty
# This safely checks if the 'users' table exists. If not, it migrates and seeds.
# This prevents wiping out your data when Render restarts the container.
php -r "
require __DIR__.'/vendor/autoload.php';
\$app = require_once __DIR__.'/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
try {
    if (!Illuminate\Support\Facades\Schema::hasTable('users')) {
        echo \"[INFO] Database is empty. Running migrations and seeders...\\n\";
        Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);
        echo \"[INFO] Database setup completed successfully!\\n\";
    } else {
        echo \"[INFO] Database already set up. Skipping migrations.\\n\";
    }
} catch (Exception \$e) {
    echo \"[WARNING] Could not connect or check database: \" . \$e->getMessage() . \"\\n\";
}
"

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Start Apache
exec apache2-foreground
