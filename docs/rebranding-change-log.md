# GrihaFlow Rebranding Change Log

Complete record of the text-only white-label rebrand (OpenKOS → GrihaFlow) executed 2026-11-15. Scope rules and the replacement map are in `docs/branding-audit.md`.

## Method

Mechanical passes with `rg -l` file selection + `perl -pi -e` in-place edits, followed by hand edits. Passes were exclusion-aware (vendor, node_modules, public/build, storage, .git, docs/branding historical AavaasDesk records were never touched).

| Pass | Pattern | Replacement | Notes |
|---|---|---|---|
| A | `OPENKOS_` | `GRIHAFLOW_` | env vars (needed `rg --hidden` for `.env.example`) |
| B | `\bOpenKOS\b(?![\\:])` | `GrihaFlow` | 54 files; **two bugs found and fixed later** (see Bugs) |
| C | `@openkos.com` → `@grihaflow.com`, `openkos.test` → `grihaflow.test`, `openkos.app` → `grihaflow.app` | seeders/tests/e2e; perl `@` needed escaping (`\@`) |
| D | asset `openkos-logo.svg` → `grihaflow-logo.svg` | `git mv` + controller refs |

## Changed (technical metadata)

- `composer.json` — name `grihaflow/grihaflow`, description "Property & Community Management."
- `composer.lock` — root name + `content-hash` **hand-computed** with composer 2.11's exact algorithm (`md5(json_encode(array_intersect_key + config.platform/policy, ksort), 0))`), validated: original hash reproduced exactly, `composer validate` passes. `composer update --lock` is impossible until `openkos/platform` 0.2.5 is resolvable.
- `config/app.php` — name default `GrihaFlow`, `GRIHAFLOW_BUILD_*` env keys.
- `.env.example` — `APP_NAME=GrihaFlow`, `DB_DATABASE=grihaflow`, `GRIHAFLOW_*`, `GRIHAFLOW_MARKETPLACE_URL` (value kept: `https://marketplace.openkos.id`).
- Docker/render: `Dockerfile`, `.docker/php/Dockerfile.prod` (entries `grihaflow-render`/`grihaflow-entrypoint`), `.docker/render-run.sh` (site_name → GrihaFlow, native `db:seed` in demo mode), `render.yaml` (service `grihaflow`, db `grihaflow-db`), `compose.production.yaml` (volume `grihaflow_uploads`, image `grihaflow/app:latest`), `InstallCommand` DB default `grihaflow`.
- CI: `.github/workflows/{tests,lint,production-smoke,publish-nightly-image,publish-stable-image}.yml` — image → `grihaflow/app` (GHCR); nightly requires a `grihaflow` org on GitHub, documented in runbook.
- `.github/{FUNDING.yml,ISSUE_TEMPLATE/*,pull_request_template.md}` — GrihaFlow wording.
- `playwright.config.ts` + e2e tmp/db names; e2e seeds rebranded.
- `README.md` — title/intro GrihaFlow + new **Attribution** section (upstream OpenKOS by senatroxx, Apache-2.0, link to inventory).

## Changed (customer-facing)

- `app.blade.php` title fallback → GrihaFlow; `config('app.name')` default → GrihaFlow.
- `PublicPortalMetadataResolver::DEFAULT_SITE_NAME` → GrihaFlow (third-brand **AavaasDesk** remnants also replaced: accept-invitation.tsx, seo.tsx, settings/general.tsx, settings/mail.tsx, `lang/id.json:1051`, 2 tests).
- `resources/js/app.tsx` name fallback → GrihaFlow.
- Settings About page (`AboutController`): `PRODUCT_NAME='GrihaFlow'`, `PRODUCT_DESCRIPTION='Property & Community Management.'`, `PRODUCT_TAGLINE='Property operations, without the chaos.'`, `PRODUCT_COPYRIGHT='GrihaFlow contributors, based on OpenKOS by senatroxx (Apache-2.0)'`, logoUrl → `grihaflow-logo.svg` (repo URL kept pointing at upstream).
- Demo/owner emails: `budi@grihaflow.com`, `demo.staff@grihaflow.com`, `demo.tenant@grihaflow.com`, owner `owner@grihaflow.test` (InstallCommand default).
- Laravel notifications wording (`lang/id/notifications.php`), `lang/id.json` UI strings.
- Invoice PDFs are data-driven from `site_name` — no hardcoded brand found.

## Kept intentionally (third-party contracts)

Full table in `docs/branding-audit.md`. Summary: `OpenKOS\` PHP namespaces + facade alias `OpenKOS::` + `OpenKOSManager`, `openkos/platform` dependency + `extra.openkos.plugin` manifest schema, driver IDs `openkos/smtp|log|whatsapp-log|fonnte` / mailer `openkos-smtp`, cache keys `openkos:settings:*` / `openkos.build-info.*`, temp prefix `.openkos-plugin-`, gateway wire fields `openkos_reference(_mismatch)`, `https://marketplace.openkos.id`, upstream links in attribution contexts, test class names `OpenKOSManagerTest`/`OpenKOSFacadeTest`, `ope-184` fixture prefix. Historical AavaasDesk records (`docs/branding/IMPLEMENTATION.md`, `FUTURE-SPRINT.md`) preserved untouched.

## Bugs caught during verification

1. **Facade import mangled by pass B** — `use OpenKOS\Platform\Facades\OpenKOS;` (alias at import end) was renamed to `...Facades\GrihaFlow;` while `OpenKOS::` usages were untouched (lookahead `(?![\\:])` excludes `::` but not the import alias position). 5 files fixed back to the vendor facade class name: `HandleInertiaRequests.php`, `SettingValueRequest.php`, `DynamicSettingsAccessTest.php`, `PluginBootTest.php`, `OpenKOSFacadeTest.php`. Lesson: alias-at-import-end needs the same exclusion as `::`.
2. **Suite environment quirks (pre-existing, not rebrand)** — Laravel 12 uses mutable dotenv, so a local `.env` with `APP_ENV=production` overrides phpunit.xml and prohibits migrations under RefreshDatabase; fixed locally with a hermetic `.env.testing`. Also stale www-data-owned `storage/framework/views` blocked recompilation as uid 1000 (tempnam fallback warning); fixed with a clean storage mount for test runs.

## Verification (2026-11-15)

- Final sweep: no `OpenKOS`/`AavaasDesk` outside the keep-list; no `OPENKOS_` env refs anywhere.
- **Test suite: 1394 passed, 1 failed, 2 skipped — byte-identical result to the pre-rebrand baseline** (the 1 failure is a pre-existing upstream assertion in `InspectionTest`).
- `composer validate` — valid.
- Docker image builds (`grihaflow-test`); demo boot: migrations + seeds OK, page titles render **GrihaFlow**, all three demo logins verified (owner → dashboard 200, staff → dashboard 200, tenant → portal 200).
- SBOM + license + vulnerability artifacts generated (`docs/open-source-inventory.md`).

## Out of scope (follow-ups)

- Dependency upgrades for the 43 OSV advisories (blocked by upstream package resolution).
- GitHub repo rename to `grihaflow-platform` + `grihaflow` org for GHCR images.
- Render service rename (`openkos-free` → `grihaflow`) and `APP_URL` change (currently `openkos-free.onrender.com` — kept).
