#!/bin/sh
# Start-up: listen on $PORT (Railway sets it; default 80), prepare the database, run Apache.
set -e

PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Create the database and, the first time only, the admin account (password → ADMIN_PASSWORD or the log).
mkdir -p /var/www/html/storage
php /var/www/html/lib/init_admin.php
chown -R www-data:www-data /var/www/html/storage

exec apache2-foreground
