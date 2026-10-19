#!/bin/sh
set -e

# Pre-create Livewire v4 compilation directories if they do not exist
mkdir -p /var/www/html/storage/framework/views/livewire/classes /var/www/html/storage/framework/views/livewire/views

# Fix permissions on bind-mounted directories at runtime
# (the Dockerfile chown gets overridden by the Windows host bind mount)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear stale cached package manifests mounted from host
rm -f /var/www/html/bootstrap/cache/packages.php /var/www/html/bootstrap/cache/services.php /var/www/html/bootstrap/cache/routes-v7.php /var/www/html/bootstrap/cache/config.php

# Re-discover packages
php artisan package:discover --ansi || true

# Enable Yammi audit-log UI dashboard (idempotent — safe to run every start)
php artisan audit-log:ui enable || true

if [ $# -gt 0 ]; then
    exec docker-php-entrypoint "$@"
else
    exec docker-php-entrypoint php-fpm
fi
