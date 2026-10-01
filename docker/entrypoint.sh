#!/bin/sh
set -e

cd /var/www/html

# Pastikan .env selalu ada agar konfigurasi konsisten antar restart.
if [ ! -f .env ]; then
    echo "[jhs] .env tidak ditemukan, menyalin dari .env.example"
    cp .env.example .env
fi

# APP_KEY wajib terisi sebelum cache konfigurasi dibuat.
if ! grep -qE '^APP_KEY=.+' .env; then
    echo "[jhs] APP_KEY kosong, membuat key baru"
    php artisan key:generate --force
fi

# Buang cache bawaan image build supaya .env di repo dipakai.
php artisan config:clear >/dev/null 2>&1 || true
php artisan cache:clear  >/dev/null 2>&1 || true
php artisan view:clear    >/dev/null 2>&1 || true

if [ "${JHS_RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[jhs] menjalankan migrasi database"
    php artisan migrate --force
fi

if [ "${JHS_RUN_SEEDERS:-false}" = "true" ]; then
    echo "[jhs] menjalankan seeder demo"
    php artisan db:seed --force
fi

if [ "${JHS_RUN_OPTIMIZE:-true}" = "true" ]; then
    echo "[jhs] menyimpan cache konfigurasi, route, dan view"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache

echo "[jhs] aplikasi siap pada http://0.0.0.0:8000"

exec "$@"
