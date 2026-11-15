# GrihaFlow Deployment Runbook

Operational notes for the Render free-tier deployment of GrihaFlow (based on OpenKOS, see `docs/open-source-inventory.md`). Post-rebrand state: the app reads `GRIHAFLOW_*` env vars and renders "GrihaFlow" branding.

## Render service facts (as of 2026-11-15)

- **Service**: `srv-dbskqi0ae00c738bius0` (name `openkos-free` — renaming optional; URL `https://openkos-free.onrender.com` is kept for now)
- **CLI**: `~/.local/bin/render` v2.36.0; API key at `~/.render/cli.yaml`
  (`KEY=$(awk '/^api:/{f=1} f&&/key:/{print $2; exit}' ~/.render/cli.yaml)` — never echo it)
- **Auto-deploy**: every push to `main` builds and deploys; env-var changes alone do NOT redeploy
- **Current env vars**: `DEMO_MODE=true`, `GRIHAFLOW_MIGRATE_ON_BOOT=true`, `GRIHAFLOW_OWNER_EMAIL=owner@grihaflow.test`, `GRIHAFLOW_OWNER_PASSWORD=<rotating>`, `DB_DATABASE=/tmp/app.sqlite`, `APP_KEY=<set>`

## Deployment sequence for the rebrand (executed / repeatable)

The rebranded code reads `GRIHAFLOW_*` while the old container used `OPENKOS_*`. The env switch must land **during the same build window** as the code deploy:

1. `GET https://api.render.com/v1/services/{id}/env-vars` with Bearer key — save the full current list (PUT replaces it entirely).
2. Transform: `OPENKOS_MIGRATE_ON_BOOT` → `GRIHAFLOW_MIGRATE_ON_BOOT`, `OPENKOS_OWNER_EMAIL` → `GRIHAFLOW_OWNER_EMAIL` (value `owner@grihaflow.test`), `OPENKOS_OWNER_PASSWORD` → `GRIHAFLOW_OWNER_PASSWORD`. Keep `DEMO_MODE`, `DB_DATABASE`, `APP_KEY` unchanged.
3. Push the rebrand commit(s) — build starts.
4. **During the build window**, `PUT /env-vars` with the full transformed list.
5. Poll `/v1/services/{id}/deploys/{id}` until `live`.
6. Verify: page title renders **GrihaFlow**; demo logins below all 200.

## Demo accounts (email rotated to @grihaflow.com)

| Role | Email | Password |
|---|---|---|
| Owner | `budi@grihaflow.com` | `PASSWORD_<UTC YYYYMMDD>` (rotated daily by `grihaflow-render:demo-rotate`) |
| Staff | `demo.staff@grihaflow.com` | same pattern |
| Tenant (portal) | `demo.tenant@grihaflow.com` | same pattern |

Demo mode runs the native `db:seed --force` chain + settings override (locale `en`, timezone `UTC`) on every boot; seeds are idempotent (skip when users exist). Staff role has only `dashboard.view, tenants.view, tenants.update, leases.view` — 403 on `/properties` is by design.

## Post-deploy user actions (cannot be automated here)

- **GitHub repo rename** to `grihaflow-platform` (gh CLI/HTTPS unavailable from this machine) → then `git remote set-url origin git@github.com:qureshiowais91/grihaflow-platform.git`.
- **Create `grihaflow` org on GitHub** for the GHCR images (`grihaflow/app`) referenced by `.github/workflows/publish-nightly-image.yml` / `publish-stable-image.yml` — until then those workflows fail on push, which is cosmetic.
- Optional: rename the Render service to `grihaflow` and update `APP_URL`.

## Local verification recipe

```bash
docker build -t grihaflow-test:latest .
KEY=$(grep "^APP_KEY=" .env.testing | cut -d= -f2)
docker run -d --name grihaflow-run-test -p 8093:8080 \
  -e DEMO_MODE=true -e GRIHAFLOW_MIGRATE_ON_BOOT=true \
  -e GRIHAFLOW_OWNER_EMAIL=owner@grihaflow.test \
  -e GRIHAFLOW_OWNER_PASSWORD='Test-Pass-20261115!' \
  -e DB_DATABASE=/tmp/app.sqlite -e APP_KEY="$KEY" grihaflow-test:latest
# titles + logins: GET /login → <title>GrihaFlow</title>; POST /login needs
# XSRF-TOKEN cookie + X-XSRF-TOKEN + Origin headers (plain curl POSTs get 419)
```

Test suite (hermetic):

```bash
# .env.testing: APP_ENV=testing, sqlite :memory:, real APP_KEY
docker run --rm -v "$PWD":/app -v /tmp/opencode/test-storage:/app/storage \
  -w /app --user 1000 -e APP_ENV=testing openkos-test:latest \
  php -d memory_limit=2G ./vendor/bin/pest --compact
```

Gotchas learned: Laravel 12's mutable dotenv makes a production `.env` override phpunit.xml (use `.env.testing`); stale root-owned `storage/framework/views` breaks view recompilation for uid-1000 test runs (mount fresh storage); `app:install` is interactive-only (demo flow never calls it); host disk must have headroom (was 100% full once — prune with `docker system prune`).

## Known follow-ups

- 43 OSV advisories in lockfiles — see `docs/open-source-inventory.md`.
- `composer update --lock` blocked by VCS-pinned `openkos/platform@0.2.5` (content-hash is hand-maintained and validated).
