#!/bin/sh
set -eu
if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY belum diisi. Buat sekali dengan artisan key:generate --show, simpan ke .env, lalu jalankan ulang." >&2
  exit 1
fi
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
php artisan storage:link --no-interaction
if [ "${PURO_RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --seed --force --no-interaction
  php artisan puro:knowledge-sync
fi
exec "$@"
