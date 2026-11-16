# VolunteerHub API — Docker image for Render (Render has no built-in PHP runtime).
FROM php:8.4-cli

# System packages + PHP extensions: PostgreSQL, images (gd), zip, intl
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libicu-dev \
        libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql gd zip intl bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP packages first (cached between deploys when composer files don't change)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# App code
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker/start.sh

# Render sends traffic to $PORT (10000 by default)
ENV PORT=10000 \
    PHP_CLI_SERVER_WORKERS=4
EXPOSE 10000

CMD ["docker/start.sh"]
