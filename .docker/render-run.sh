#!/bin/sh

set -eu

# ponytail: Render single-container mode (web + queue + scheduler in one instance);
# split into separate services if the instance ever needs to scale beyond one
mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# ponytail: for free-tier Render where preDeployCommand is unavailable; keep
# false on paid plans so migrations stay an explicit release operation
if [ "${OPENKOS_MIGRATE_ON_BOOT:-false}" = true ]; then
    php artisan migrate --force
fi

if [ "${LARAVEL_OPTIMIZE:-true}" = true ]; then
    php artisan optimize --ansi
fi

# "|| sleep" keeps each supervisor loop alive when the child exits non-zero,
# which "set -eu" would otherwise turn into a dead background job
while :; do php artisan schedule:work --no-interaction || sleep 5; done &
while :; do php artisan queue:work --sleep=3 --tries=3 --timeout=90 --no-interaction || sleep 5; done &

exec frankenphp run --config /etc/caddy/Caddyfile
