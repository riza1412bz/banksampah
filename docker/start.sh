#!/bin/sh
set -e

# Render menetapkan port dinamis melalui variabel $PORT
PORT="${PORT:-8080}"
sed -i "s/listen 8080/listen ${PORT}/g" /etc/nginx/nginx.conf

echo "🚀 Memulai Bank Sampah pada port ${PORT}..."

# Pastikan direktori storage dan cache writable
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/private/exports \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache Laravel config, routes, dan views untuk performa maksimal di produksi
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Jalankan supervisord (menjalankan Nginx dan PHP-FPM bersamaan)
exec /usr/bin/supervisord -c /etc/supervisord.conf
