#!/bin/sh
set -e

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --force --no-interaction
fi

if ! php artisan migrate:status >/dev/null 2>&1; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
