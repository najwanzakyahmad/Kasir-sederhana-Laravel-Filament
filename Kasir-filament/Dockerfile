# --- Stage 1: Vendor ---
FROM php:8.2-cli AS vendor

# tools + lib buat intl & zip
RUN apt-get update && apt-get install -y \
    git curl unzip libicu-dev libzip-dev \
 && docker-php-ext-install intl zip \
 && rm -rf /var/lib/apt/lists/*

# composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
# 1) copy manifest composer
COPY composer.json composer.lock ./
# 2) install vendor TANPA scripts (hindari 'artisan' di stage ini)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts


# --- Stage 2: Runtime PHP-FPM ---
FROM php:8.2-fpm AS app
WORKDIR /var/www/html

# ekstensi Laravel + Filament (termasuk intl)
RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip intl \
 && rm -rf /var/lib/apt/lists/*

# (opsional) opcache
RUN docker-php-ext-enable opcache || true

# 3) copy seluruh source (sekarang file artisan ada)
COPY . .

# 4) bawa vendor dari stage vendor
COPY --from=vendor /app/vendor /var/www/html/vendor

# 5) autoload & discovery (aman karena artisan sudah ada). Jangan fail build kalau environment belum lengkap.
RUN composer dump-autoload -o --no-dev \
 && php artisan package:discover --no-ansi || true

# permission minimum
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
