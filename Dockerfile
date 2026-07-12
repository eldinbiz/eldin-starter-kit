FROM php:8.4-fpm-alpine

# System packages
RUN apk add --no-cache \
    linux-headers \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip unzip curl git \
    mariadb-client \
    icu-dev \
    nodejs npm \
    && rm -rf /var/cache/apk/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql mbstring exif pcntl bcmath gd intl xml opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first (cached layer)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress

# App code
COPY . .

# Frontend build
RUN npm ci --ignore-scripts && npm run build && rm -rf node_modules

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

CMD ["php-fpm"]
