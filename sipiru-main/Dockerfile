# syntax=docker/dockerfile:1
# Image aplikasi Laravel (PHP-FPM) — Sistem Peminjaman Ruang USK
FROM node:22-bookworm-slim AS node

FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libicu-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip gd intl bcmath opcache pcntl exif \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js + npm (untuk build asset Vite di dalam container)
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Samakan UID/GID www-data dengan user host agar file di bind mount tidak dimiliki root
RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data \
    && mkdir -p /var/www/.npm /var/www/.composer /var/www/html \
    && chown -R www-data:www-data /var/www

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/php/wait-for-vendor.sh /usr/local/bin/wait-for-vendor
RUN chmod +x /usr/local/bin/wait-for-vendor

ENV COMPOSER_HOME=/var/www/.composer

WORKDIR /var/www/html
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
