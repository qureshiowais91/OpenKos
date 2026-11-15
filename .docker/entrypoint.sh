#!/bin/sh

set -eu

mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Persistent volumes (Compose named volumes on first mount, Render disks, etc.)
# can arrive root-owned; the web stack runs as www-data, so fix up ownership
# before dropping privileges. Non-recursive on purpose: nested dirs are created
# by the app itself.
if [ "$(id -u)" = "0" ]; then
    chown www-data:www-data \
        /config/caddy \
        /config/psysh \
        /data/caddy \
        bootstrap/cache \
        storage \
        storage/app \
        storage/app/private \
        storage/app/public \
        storage/framework \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs

    exec runuser -u www-data -- "$@"
fi

should_optimize=false

if [ "${1:-}" = "frankenphp" ]; then
    should_optimize=true
fi

if [ "${1:-}" = "php" ] && [ "${2:-}" = "artisan" ]; then
    case "${3:-}" in
        queue:work|schedule:work)
            should_optimize=true
            ;;
    esac
fi

if [ "$should_optimize" = true ] && [ "${LARAVEL_OPTIMIZE:-true}" = true ]; then
    php artisan optimize --ansi
fi

exec "$@"
