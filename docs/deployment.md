# Production Deployment

GrihaFlow production uses the immutable FrankenPHP image published at
`grihaflow/app`.

The production Compose reference contains only the application roles:

- `web`: FrankenPHP HTTP server on container port `8080`
- `queue`: database-backed Laravel queue worker
- `scheduler`: Laravel scheduler

PostgreSQL is external. Database-backed cache and sessions remain the default,
and production does not require Redis.

## Configuration

Create an untracked `.env.production` file with the normal Laravel settings,
including:

- `APP_KEY`
- `APP_URL`
- `APP_ENV=production`
- `APP_DEBUG=false`
- PostgreSQL connection settings
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- SMTP settings
- `FILESYSTEM_DISK=local` with the Compose-managed upload volume for durable
  same-host uploads
- `TRUSTED_PROXIES` containing the reverse-proxy addresses or networks

## Branding storage verification

Logo and favicon files use `FILESYSTEM_DISK` and are streamed through the
application's branding routes, including when the disk is private. Automated
coverage uses Laravel's local fake disk; there is no remote-storage test
harness. Before enabling a remote or private production disk, manually verify
upload, replacement, removal, and the `Content-Type` returned by both branding
routes. Also verify that an Inertia navigation after a favicon change updates
the browser tab; this remains an integration check rather than a browser test.

The application is HTTP-only inside the Compose network. Terminate TLS in
Traefik, Caddy, Cloudflare Tunnel, or another external load balancer and
forward `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Port`, and
`X-Forwarded-Proto`. Configure `TRUSTED_PROXIES` only for proxies that are
actually trusted. `TRUSTED_PROXIES=*` is appropriate only when the application
is unreachable except through a trusted private proxy network.

## Release sequence

Migrations are an explicit one-shot release operation. They are never run by
web, queue, or scheduler startup:

```bash
docker compose -f compose.production.yaml run --rm web php artisan migrate --force
docker compose -f compose.production.yaml up -d
```

Each long-lived container initializes Laravel's runtime caches using the
configuration injected into that container before starting its process. This
keeps configuration-specific cache files in the actual runtime container and
avoids writing them into a disposable migration container.

The current route set supports route caching. If future routes use closures,
remove route caching from the runtime optimization step before deployment.

To deploy a specific immutable image:

```bash
GRIHAFLOW_IMAGE=grihaflow/app:1.2.3 \
docker compose -f compose.production.yaml up -d
```

The Compose file mounts the named `grihaflow_uploads` volume at
`storage/app/private` for tenant documents and payment proofs. The volume is
durable across container replacement on the same Docker host, but it is not a
cross-host or multi-replica filesystem. Back it up with the host's volume
backup process before replacing the host. If the application is later scaled
across hosts, move these uploads to shared object storage or shared storage
before doing so.

Runtime plugin packages use the same private persistent storage under
`storage/app/private/plugins`. Keep that path available to the web, queue, and scheduler
containers. After installing, enabling, disabling, or updating a runtime plugin, restart
FrankenPHP workers, queue workers, and the scheduler so each process boots the new plugin
set. Runtime plugin installation never changes the root Composer files.

## Image tags

Stable version tags publish `1.2.3`, `1.2`, `1`, and `latest`.

Nightly builds publish `nightly` and an immutable tag containing the UTC build
date and commit SHA. Nightly builds never move `latest`.

## Render (single service)

The repository includes a Render Blueprint (`render.yaml`) that deploys the
whole application as one service: FrankenPHP serves HTTP on the container's
default port while `schedule:work` and `queue:work` run as supervised
background processes in the same instance (`.docker/render-run.sh`, wired in
as the Docker command). A managed Render Postgres backs the database, and a
persistent disk is mounted at `storage/app/private` for tenant documents and
payment proofs.

Blueprint specifics:

- `preDeployCommand` runs `php artisan migrate --force` before every deploy,
  keeping migrations an explicit release operation even in single-service mode.
- `TRUSTED_PROXIES=*` is set because all ingress reaches the service through
  Render's own proxy and the application is unreachable except through it.
- The attached disk prevents zero-downtime deploys (Render stops the old
  instance before starting the new one) and prevents scaling to multiple
  instances. Splitting into separate web/worker services is required before
  scaling; that matches the Compose topology instead.
- Attach a custom domain and set `APP_URL` accordingly after the first sync.

First-time setup runs after the initial deploy succeeds. Open Render Shell
(paid plan) or SSH into the service and run:

```bash
php artisan app:install
```

This migrates, seeds roles and permissions, sets site settings, and creates
the first owner account.

To deploy the published immutable image instead of building on Render, change
`runtime: docker` / `dockerfilePath` / `dockerCommand` for:

```yaml
runtime: image
image:
    url: grihaflow/app:1.2.3
```

and move the migrate command to Render Shell or a one-off job per release.

### Free tier

The free web plan cannot use persistent disks or `preDeployCommand`, and free
Postgres expires after 30 days, so a free deployment is for evaluation only:

- Remove the `disk` block and the `databases` entry; uploads and branding
  files are lost on every redeploy.
- Add the env var `GRIHAFLOW_MIGRATE_ON_BOOT=true` so the container migrates on
  startup instead of `preDeployCommand`. Keep it `false` on paid plans.
- Either create a paid Postgres and paste its connection string as `DB_URL`,
  or point `DB_CONNECTION=sqlite` at a path under the (ephemeral) filesystem
  for throwaway testing.
- The service sleeps after 15 minutes without traffic; the first request
  wakes it with a delay.
- Because there is no Shell and one-off jobs are paid-only, the first owner
  account is seeded on boot: set `GRIHAFLOW_OWNER_EMAIL` and
  `GRIHAFLOW_OWNER_PASSWORD`; the boot script seeds roles and settings
  (`site_name`, `locale=en`, `currency=USD`, `timezone=UTC`) only while no
  users exist. Rotate the password after the first login — without these
  vars the app falls back to its Indonesian default locale
  (`config/settings.php`).
- Env-var changes made via the Render API do **not** trigger a deploy (only
  commits do); trigger one manually with
  `POST /v1/services/{id}/deploys`. `PATCH /v1/services/{id}` ignores env
  vars; the working endpoint is `PUT /v1/services/{id}/env-vars`, which
  replaces the entire list — GET the current list first and PUT the merged
  set.
- Demo mode (optional): set `DEMO_MODE=true` and boot seeds the app's native
  demo world (`db:seed` — roles, tenants, `ope-184-demo-*` properties, 17
  leases, invoices, maintenance) and shares three accounts: `budi@grihaflow.com`
  (owner), `demo.staff@grihaflow.com` (staff role: tenants/leases), and
  `demo.tenant@grihaflow.com` (tenant portal, linked to a seeded tenant with an
  active lease). Their password is `PASSWORD_<UTC date, YYYYMMDD>` — it
  rotates every 24h at midnight UTC and old copies stop working
  (`app:demo-rotate`; no-op unless `DEMO_MODE=true`). Every boot reseeds the
  fresh `/tmp` SQLite, so demo changes never persist. With `DEMO_MODE=true`
  the env-driven owner account is not created — `budi@grihaflow.com` is the
  owner login.
- The FrankenPHP binary must ship without file capabilities for Render's
  restricted capability set (`setcap -r /usr/local/bin/frankenphp` in the
  Dockerfile), and the runner binds to `$PORT` via `SERVER_NAME`.
- Email (verification, password reset) is not wired on the free tier: with no
  `MAIL_*` env vars the mailer falls back to `log`, so emails are written to
  the Render logs instead of sent — verification links can be recovered by
  grepping the logs (they expire after ~60 minutes and must be opened while
  logged in). For real email set `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`,
  `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, and
  `APP_URL=https://openkos-free.onrender.com`.
