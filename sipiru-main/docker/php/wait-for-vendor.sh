#!/bin/sh
# Menunggu dependency Composer terpasang sebelum menjalankan worker (queue/scheduler).
while [ ! -f /var/www/html/vendor/autoload.php ] || [ ! -f /var/www/html/.env ]; do
    echo "[wait-for-vendor] Menunggu 'composer install' dan file .env ..."
    sleep 5
done
exec "$@"
