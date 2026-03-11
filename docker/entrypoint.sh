#!/bin/sh
set -e

cd /var/www/html

echo ">>> Aguardando PostgreSQL..."
until php -r "new PDO('pgsql:host='.\$_ENV['DB_HOST'].';port='.\$_ENV['DB_PORT'].';dbname='.\$_ENV['DB_DATABASE'], \$_ENV['DB_USERNAME'], \$_ENV['DB_PASSWORD']);" 2>/dev/null; do
  sleep 2
done
echo ">>> PostgreSQL OK"

echo ">>> Rodando migrations..."
php artisan migrate --force

echo ">>> Cacheando..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

echo ">>> Iniciando..."
exec supervisord -c /etc/supervisord.conf
