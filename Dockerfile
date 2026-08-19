FROM php:8.4-fpm-alpine

# System packages
RUN apk add --no-cache \
    autoconf \
    linux-headers \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip unzip curl git \
    mariadb-client \
    sqlite-dev \
    icu-dev \
    nodejs npm \
    && rm -rf /var/cache/apk/*

# PHP extensions
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd intl xml opcache sockets

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first (cached layer)
COPY composer.json composer.lock ./
COPY app-modules/ ./app-modules/
RUN composer install --no-interaction --no-progress --no-scripts

# App code
COPY . .

# Clear stale cached manifests copied from host and re-discover packages
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php bootstrap/cache/routes-v7.php \
    && php artisan package:discover --ansi || true

# Frontend build
RUN npm ci --ignore-scripts && npm run build && rm -rf node_modules

# Entrypoint for runtime permissions fix
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
