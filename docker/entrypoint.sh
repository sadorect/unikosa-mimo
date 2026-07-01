#!/bin/sh
set -e

# Run Laravel bootstrap tasks on first startup of the app/queue/scheduler containers.
# In production (APP_ENV=production) this also caches config, routes, and events.

cd /var/www/html

# Ensure storage directory structure exists (the named volume only persists /storage).
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views

php artisan storage:link --no-interaction 2>/dev/null || true

if [ "${APP_ENV}" = "production" ]; then
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache
fi

exec "$@"
