#!/bin/sh
set -e

# Fix permissions on bind-mounted directories at runtime
# (the Dockerfile chown gets overridden by the Windows host bind mount)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

exec docker-php-entrypoint php-fpm
