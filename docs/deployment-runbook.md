# GrihaFlow Deployment Runbook

Operational notes for the Render free-tier deployment of GrihaFlow (based on OpenKOS, see `docs/open-source-inventory.md`). Post-rebrand state: the app reads `GRIHAFLOW_*` env vars and renders "GrihaFlow" branding.

## Render service facts (as of 2026-11-15)

- **Service**: `srv-dbskqi0ae00c738bius0` (name `openkos-free` — renaming optional; URL `https://openkos-free.onrender.com` is kept for now)
- **CLI**: `~/.local/bin/render` v2.36.0; API key at `~/.render/cli.yaml`
  (`KEY=$(awk '/^api:/{f=1} f&&/key:/{print $2; exit}' ~/.render/cli.yaml)` — never echo it)
- **Auto-deploy**: every push to `main` builds and deploys; env-var changes alone do NOT redeploy
- **Current env vars**: `DEMO_MODE=true`, `GRIHAFLOW_MIGRATE_ON_BOOT=true`, `GRIHAFLOW_OWNER_EMAIL=owner@grihaflow.test`, `GRIHAFLOW_OWNER_PASSWORD=<rotating>`, `DB_DATABASE=/tmp/app.sqlite`, `APP_KEY=<set>`

## Deployment sequence for the rebrand (executed 2026-11-15)

The rebranded code reads `GRIHAFLOW_*` while the old container used `OPENKOS_*`. Ordering matters — a deploy boots with the **env list captured when it was created**:

1. `GET https://api.render.com/v1/services/{id}/env-vars` with Bearer key — save the full current list (PUT replaces it entirely).
2. Transform: `OPENKOS_MIGRATE_ON_BOOT` → `GRIHAFLOW_MIGRATE_ON_BOOT`, `OPENKOS_OWNER_EMAIL` → `GRIHAFLOW_OWNER_EMAIL` (value `owner@grihaflow.test`), `OPENKOS_OWNER_PASSWORD` → `GRIHAFLOW_OWNER_PASSWORD`. Keep `DEMO_MODE`, `DB_DATABASE`, `APP_KEY` unchanged.
3. **PUT the full transformed list first** (`PUT /env-vars`, 200 = applied).
4. Then trigger the deploy: `POST /v1/services/{id}/deploys` (PUT alone does not redeploy; only commits do).
5. Poll `GET /v1/services/{id}/deploys/{deployId}` until `live`.
6. Verify: page title renders **GrihaFlow**; demo logins below all succeed.

### Incident log (dep-dbssk9h5efls73f4d950)

The first rebrand deploy (push-triggered at 15:05 UTC, before the env PUT landed) booted with the stale `OPENKOS_*` env: `GRIHAFLOW_MIGRATE_ON_BOOT` was unset, so migrate was skipped and `db:seed` crashed with `SQLiteDatabaseDoesNotExistException` for `/tmp/app.sqlite` (`update_failed`, exit 1 ~2.5 min after boot start). Service kept serving the previous stable build; no manual rollback needed. Fix = the ordering above; the follow-up deploy (`dep-dbssnoei0phs73e1nar0`) went live on the first poll.

## Current live state (2026-11-15, after rebrand deploy)

- Live deploy: `dep-dbssnoei0phs73e1nar0` (commit `60796dd`), status `live`
- URL: `https://openkos-free.onrender.com` — `<title>GrihaFlow</title>` on login + portal
- Verified: `budi@grihaflow.com` → dashboard 200, `demo.staff@grihaflow.com` → 302, `demo.tenant@grihaflow.com` → portal 200 (password `PASSWORD_20261115`)
- Env (14 vars): `DEMO_MODE=true`, `GRIHAFLOW_MIGRATE_ON_BOOT=true`, `GRIHAFLOW_OWNER_EMAIL=owner@grihaflow.test`, `GRIHAFLOW_OWNER_PASSWORD=Skein-Purify-Repay-9`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/tmp/app.sqlite`, `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=stderr`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `TRUSTED_PROXIES=*`, `APP_KEY`
- Private owner account (demo mode off): `owner@grihaflow.test` / `Skein-Purify-Repay-9`

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

Login API notes: GET /login first for the cookie jar, extract `XSRF-TOKEN`, URL-decode it into `X-XSRF-TOKEN`, add `Origin` + `X-Requested-With: XMLHttpRequest`, POST JSON to `/login` (plain POSTs get 419; each successful login rotates the token, so re-fetch the jar between accounts).

## Known follow-ups

- 43 OSV advisories in lockfiles — see `docs/open-source-inventory.md`.
- `composer update --lock` blocked by VCS-pinned `openkos/platform@0.2.5` (content-hash is hand-maintained and validated).
