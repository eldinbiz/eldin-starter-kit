# Stage 0: Composer binary
FROM composer:2 AS composer

# Stage 1: Base PHP 8.4 FPM with extensions
FROM php:8.4-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    linux-headers \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    supervisor \
    fcgi \
    mariadb-client \
    icu-dev \
    && rm -rf /var/cache/apk/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    xml \
    opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Copy composer from composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files (composer.json first for layer caching)
COPY composer.json composer.lock ./

# ---- DEV Stage ----
FROM base AS dev

# Install all dependencies (including dev)
RUN composer install --no-interaction --no-progress

# Copy application code
COPY . .

# Install xdebug
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

# Copy custom PHP ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ---- PROD Stage ----
FROM base AS prod

# Install production dependencies only
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

# Copy application code
COPY . .

# Install Node.js and npm for frontend build
RUN apk add --no-cache nodejs npm

# Install npm dependencies and build assets
RUN npm ci --ignore-scripts \
    && npm run build

# Remove Node.js and npm to keep image lean
RUN apk del nodejs npm \
    && rm -rf node_modules \
    && rm -rf /var/cache/apk/*

# Copy production PHP ini
COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/99-custom.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
