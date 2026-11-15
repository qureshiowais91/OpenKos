# Branding Audit — OpenKOS → GrihaFlow

Text-only rebrand audit. Performed 2026-11-15 against commit `6cf3789`.
Search: `rg -i "openkos|open-kos|open kos"` over the full repo (excluding `vendor/`, `node_modules/`, `storage/`, `public/build/`, `.git/`, lockfiles handled separately). ~180 files matched.

**Scope rule:** only text changes. No layout, CSS, images, icons, fonts, or functional changes. Visual output stays identical.

## Provenance

- This repository is a fork of **https://github.com/senatroxx/OpenKos** (remote `upstream`, Apache-2.0).
- The app depends on the composer package **`openkos/platform`** (`OpenKOS\` PHP namespace) — the plugin platform core.
- Attribution must be preserved (Apache-2.0 §4): `LICENSE`, about-page license/copyright display, upstream repository link, and `docs/open-source-inventory.md`.

## Replacement map

### Replaced — customer-facing text

| Original | Replacement | Where |
|---|---|---|
| `OpenKOS` (prose/strings/titles) | `GrihaFlow` | UI strings, seeders, commands, notifications, tests, docs |
| `OpenKos` (title-case variant) | `GrihaFlow` | `lang/id/notifications.php`, about page, notification-branding test |
| site_name default `OpenKOS` | `GrihaFlow` | `config/settings.php`, `SettingSeeder`, `InstallCommand`, `TestCase` |
| `APP_NAME=OpenKOS`, `VITE_APP_NAME` fallback `'OpenKOS'` | `GrihaFlow` | `.env.example`, `resources/js/app.tsx` |
| "OpenKOS Marketplace" | "GrihaFlow Marketplace" | `settings/plugins.tsx` (upstream URL unchanged, see kept list) |
| "About OpenKOS", "OpenKOS software", install info texts | GrihaFlow equivalents | `settings/about.tsx` |
| `budi@openkos.com`, `demo.staff@openkos.com`, `demo.tenant@openkos.com` | `…@grihaflow.com` | seeders (`OwnerSeeder`, `DemoUsersSeeder`, `LeaseSeeder`, `MaintenanceSeeder`, `DemoRotateCommand`), tests |
| `owner@openkos.test` (docs/env values) | `owner@grihaflow.test` | docs, Render env values |
| `assets/brand/openkos-logo.svg` path | `assets/brand/grihaflow-logo.svg` | file renamed (same image bytes), `BrandingAssetController`, `AboutController` |
| "not compatible with the current OpenKOS version" | GrihaFlow version | `RuntimePluginGraphValidator`, `PluginManagementService` |
| RentReminder / mail / WhatsApp texts containing OpenKOS | GrihaFlow | notifications + drivers + lang files |
| Invoice PDF branding text | GrihaFlow | invoice PDF generation + `TenantInvoicePdfTest` |

### Replaced — technical metadata

| Original | Replacement | Notes |
|---|---|---|
| `GRIHAFLOW_` env prefix (e.g. `GRIHAFLOW_MIGRATE_ON_BOOT`, `GRIHAFLOW_OWNER_EMAIL`, `GRIHAFLOW_PLUGIN_PATH`, `GRIHAFLOW_MARKETPLACE_URL`, `GRIHAFLOW_HTTP_BIND`, `GRIHAFLOW_E2E_*`, `GRIHAFLOW_BUILD_VERSION`) | `GRIHAFLOW_` | first-party env vars; **requires updating Render service env list in the same deploy** |
| composer name `openkos/openkos` | `grihaflow/grihaflow` | app package (not consumed by others); description → "Property & Community Management."; `composer.lock` regenerated |
| Docker scripts `/usr/local/bin/openkos-render`, `openkos-entrypoint` | `grihaflow-render`, `grihaflow-entrypoint` | both Dockerfiles + `render.yaml` dockerCommand in sync |
| compose image default `ghcr.io/senatroxx/openkos:latest` | `grihaflow/app:latest` | production compose default (our image name per spec) |
| volume `openkos_uploads`, tmp files `openkos-<scenario>-playwright.*`, DB names `openkos` | `grihaflow_*` / `grihaflow-…` / `grihaflow` | `.env.example`, compose, playwright, install default |
| `render.yaml` service `openkos`, `openkos-db` | `grihaflow`, `grihaflow-db` | blueprint currently unused (free tier) |
| Repository/folder refs | `grihaflow-platform` / `grihaflow` | docs, README, runbook |

### Kept intentionally — third-party contracts & provenance (renaming breaks compatibility)

| Identifier | Why kept |
|---|---|
| `OpenKOS\` PHP namespace (`OpenKOS\Platform\*`, `OpenKOS\Core\Contracts\*`, `OpenKOS\Plugins\*` incl. `src/Plugins/*`) | namespace of the `openkos/platform` composer dependency; plugins must extend its contracts |
| `OpenKOS::` facade calls (`OpenKOS::settings()`, `::navigation()`, …) | vendor facade of `openkos/platform` |
| `OpenKOSManager`, `OpenKOSFacade` class names (incl. test files `OpenKOSManagerTest`, `OpenKOSFacadeTest`) | vendor classes |
| composer dependency `openkos/platform` (`require`, `shared_packages`, `InstalledVersions::getPrettyVersion('openkos/platform')`, `require.openkos/platform` checks) | package name on packagist |
| `extra.openkos.plugin` composer manifest schema + `compatibility['openkos']` | platform plugin manifest contract (marketplace artifacts + `ComposerPluginDiscovery`, `RuntimePluginArtifactValidator` read it) |
| Driver registry keys `openkos/smtp`, `openkos/log`, `openkos/whatsapp-log`, `openkos/fonnte`, mailer name `openkos-smtp` | notification registry keys shared with the `openkos/platform` package and marketplace plugins |
| Cache keys `openkos:settings:*`, `openkos.build-info.*`, temp prefix `.openkos-plugin-` | internal, non-customer-facing; renaming adds churn, zero value |
| Payment-gateway protocol fields `openkos_reference`, `openkos_reference_mismatch` | wire contract with payment gateways/webhooks |
| Marketplace URL `https://marketplace.openkos.id` | external service endpoint (upstream's plugin marketplace), renaming breaks connectivity |
| Upstream links `https://github.com/senatroxx/OpenKos` in attribution contexts (LICENSE file, about page repository URL, open-source inventory) | Apache-2.0 attribution / provenance |
| Test class names `OpenKOSManagerTest`, `OpenKOSFacadeTest`, `Ope184DemoSeederTest`, `ope-184` seeder namespace | vendor class names / non-branding fixture prefix |

## Risk notes

1. **Env prefix rename is deployment-coupled.** New code reads `GRIHAFLOW_*`; the Render service must switch env keys in the same deploy window (env PUT during the build, before the new container boots, or migrations/owner-seed silently skip).
2. **Demo email change** affects only the seeded demo world (fresh DB every boot on Render). Public credentials doc updated accordingly.
3. **`composer.lock`** must be regenerated for the app package rename (`composer update --lock`) — dependency versions untouched.
4. **Existing installs' `site_name`** DB row keeps its stored value; new defaults apply on fresh installs/seeded DBs. Render's free-tier DB reseeds on every boot, so it picks up GrihaFlow automatically.
5. **No functional behavior changes**: registry keys, namespaces, protocol fields, and marketplace URL untouched; tests assert unchanged behavior with the new display strings.

## Post-implementation updates (2026-11-15)

- `composer.lock`: root name → `grihaflow/grihaflow`; content-hash hand-computed with composer 2.11's exact algorithm and **validated against the original lock** (reproduced `46947d45ea007b765375f3d9a8ce0515` exactly); `composer validate` passes. Note: composer 2.11's `JsonFile::encode($relevantContent, 0)` uses flags `0` (compact JSON) — pretty-printed hashes are wrong.
- Third-brand **AavaasDesk** had live remnants (title fallbacks, portal metadata, settings pages, 2 tests, `lang/id.json:1051`) — all replaced with GrihaFlow. Historical docs `docs/branding/IMPLEMENTATION.md` + `FUTURE-SPRINT.md` preserved untouched.
- Pass-B bug found and fixed: facade imports `use OpenKOS\Platform\Facades\OpenKOS;` had their alias segment renamed while `OpenKOS::` usages weren't (5 files restored to the vendor class name).
- Invoice PDFs are data-driven (`site_name` setting) — no hardcoded brand.
- Final verification: suite **1394 passed / 1 failed / 2 skipped — identical to the pre-rebrand baseline** (1 failure is pre-existing upstream); docker image builds; all three demo logins OK; titles render "GrihaFlow". See `docs/rebranding-change-log.md` and `docs/deployment-runbook.md`.
