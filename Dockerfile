# syntax=docker/dockerfile:1

# ---------- Frontend build (composer install) ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-interaction


# ---------- Runtime: PHP-FPM ----------
FROM php:8.3-fpm-alpine AS runtime

# Dependencies runtime: pdo_mysql dipakai koneksi database, gd & zip untuk dompdf,
# intl untuk format tanggal Bahasa Indonesia.
RUN apk add --no-cache \
        icu-dev \
        libpng \
        libjpeg-turbo \
        libwebp \
        freetype \
        libzip \
        oniguruma \
        nginx \
        supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        intl \
        gd \
        zip \
        opcache \
    && apk del icu-dev

COPY docker/php.ini /usr/local/etc/php/conf.d/jhs.ini
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

# Nginx + PHP-FPM dijalankan oleh supervisor.
COPY docker/entrypoint.sh /usr/local/bin/jhs-entrypoint
RUN chmod +x /usr/local/bin/jhs-entrypoint

RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["jhs-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
