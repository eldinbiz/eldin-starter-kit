#!/bin/sh
set -e

echo "🔧 Running Laravel container initialization..."

# Wait for MariaDB to be ready
echo "⏳ Waiting for database connection..."
MAX_TRIES=30
TRIES=0
until php -r "
try {
    \$dbh = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'connected';
} catch (PDOException \$e) {
    exit(1);
}
" 2>/dev/null; do
    TRIES=$((TRIES + 1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "❌ Could not connect to database after ${MAX_TRIES} attempts. Exiting."
        exit 1
    fi
    echo "   Attempt ${TRIES}/${MAX_TRIES} - database not ready yet..."
    sleep 2
done
echo "✅ Database connection established."

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction
fi

# Create storage symlink if it doesn't exist
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link --force
fi

# Run database migrations if AUTO_MIGRATE is set to true
if [ "${AUTO_MIGRATE}" = "true" ]; then
    echo "🗄️  Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Clear and cache configuration for production
if [ "${APP_ENV}" = "production" ]; then
    echo "⚡ Optimizing application for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

echo "🚀 Container ready. Executing: $@"
exec "$@"
