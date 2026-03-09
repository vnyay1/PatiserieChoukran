#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ ! -L public/storage ]; then
  php artisan storage:link >/dev/null 2>&1 || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

exec "$@"
