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

# ponytail: demo world is the native seeder chain (roles, regions, owner,
# tenants, ope-184-demo-* properties, leases, invoices, maintenance) — English
# UI over the IDR-priced demo data, no custom fixtures to maintain
if [ "${DEMO_MODE:-false}" = true ]; then
    php artisan db:seed --force --quiet
    php artisan tinker --execute '
        App\Models\Setting::set("locale", "en");
        App\Models\Setting::set("timezone", "UTC");
        echo "demo world seeded\n";
    '
fi

# ponytail: free Render tier has no shell/one-off jobs, so the first owner is
# seeded from env on boot; rotate OPENKOS_OWNER_PASSWORD after first login
if [ -n "${OPENKOS_OWNER_EMAIL:-}" ] && [ -n "${OPENKOS_OWNER_PASSWORD:-}" ]; then
    php artisan db:seed --class='Database\Seeders\RoleAndPermissionSeeder' --force
    php artisan tinker --execute '
        if (!App\Models\User::exists()) {
            $u = App\Models\User::create([
                "name" => "Owner",
                "email" => getenv("OPENKOS_OWNER_EMAIL"),
                "password" => Illuminate\Support\Facades\Hash::make(getenv("OPENKOS_OWNER_PASSWORD")),
                "is_active" => true,
            ]);
            $u->markEmailAsVerified();
            $u->assignRole("owner");
            App\Models\Setting::set("site_name", "OpenKOS");
            App\Models\Setting::set("country_code", "XX");
            App\Models\Setting::set("locale", "en");
            App\Models\Setting::set("currency", "USD");
            App\Models\Setting::set("supported_currencies", ["USD"]);
            App\Models\Setting::set("timezone", "UTC");
            echo "owner account created\n";
        }
    '
fi

# ponytail: demo accounts (idempotent; no-op unless DEMO_MODE=true)
php artisan app:demo-rotate

# Render assigns PORT (default 10000); honor it, fall back to the image default
export SERVER_NAME=":${PORT:-8080}"

exec frankenphp run --config /etc/caddy/Caddyfile
