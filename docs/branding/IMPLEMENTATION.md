# AavaasDesk Branding — Implementation Record

**Date:** 2026-11-14
**Scope:** Rebrand customer-facing identity of this OpenKOS installation to **AavaasDesk** ("Manage Properties. Simplify Living."), per the decision system: configuration first, small reversible modifications second, major changes deferred.

## Level 1 — Existing functionality (no code changes)

### Settings (database, via `Setting::set`)

| Key | Value |
|---|---|
| `site_name` | AavaasDesk |
| `public_site_name` | AavaasDesk |
| `public_homepage_title` | AavaasDesk — Manage Properties. Simplify Living. |
| `public_homepage_description` | AavaasDesk is a property management desk for landlords and tenants in India — leases, rent reminders, payments and maintenance in one place. |
| `branding_logo_path` | `branding/logo.png` |
| `branding_favicon_path` | `branding/favicon.png` |
| `public_og_image_path` | `branding/og.png` |
| `country_code` | IN |
| `currency` | INR |
| `supported_currencies` | `["INR"]` |
| `locale` | en |
| `timezone` | Asia/Kolkata |
| `mail_config` | smtp → host `mailpit`, port 1025, from `no-reply@aavaasdesk.test`, from-name AavaasDesk (local preview only) |

All of these are normal owner-editable settings reachable at Settings → General / Public Portal in the app.

### Branding assets

Generated AavaasDesk assets (Pillow: navy `#1B2A4A` + teal `#14B8A6` rounded mark, DejaVu Sans Bold wordmark) written to:

- `storage/app/private/branding/logo.png` (512×128)
- `storage/app/private/branding/favicon.png` (64×64)
- `storage/app/private/branding/og.png` (1200×630)

Note: the local disk root is `storage/app/private` (Laravel 11 layout) — files were initially placed at `storage/app/branding` and were not found by `BrandingAssetController` until moved. Assets are files on disk, so they **survive container restarts**.

### `.env`

- `APP_NAME=AavaasDesk` (drives browser title, `MAIL_FROM_NAME` and `VITE_APP_NAME`).
- No destructive commands run; no database reset.

## Level 2 — Small reversible modifications

Goal: remove hardcoded customer-facing OpenKos names; make email branding follow the configured installation name.

| File | Change |
|---|---|
| `app/Notifications/UserInvitation.php` | Subject/greeting/intro now use `__('... :app ...', ['app' => Setting::get('site_name', config('app.name'))])` |
| `app/Notifications/TenantInvitation.php` | Same `:app` parameter for subject/greeting |
| `lang/en/notifications.php` | `tenant_invitation.subject` / `.greeting` now `:app` placeholders |
| `lang/id.json` | Matching Indonesian strings re-keyed to `:app` forms; "Set your password … OpenKOS account" → AavaasDesk; `"OpenKOS reference:"` → `"Reference:"` |
| `resources/js/pages/auth/accept-invitation.tsx` | Layout description: "activate your AavaasDesk account" |
| `resources/js/pages/leases/invoice-detail.tsx` | Label `OpenKOS reference:` → `Reference:` |
| `resources/js/pages/public-portal/seo.tsx` | Helper text: "existing AavaasDesk default" |
| `resources/js/pages/settings/general.tsx`, `mail.tsx` | Input placeholder `OpenKOS` → `AavaasDesk` |
| `resources/views/app.blade.php` | `<title>` fallback `OpenKOS` → `AavaasDesk` |
| `app/Services/PublicPortal/PublicPortalMetadataResolver.php` | `DEFAULT_SITE_NAME` `OpenKOS` → `AavaasDesk` |
| `tests/Feature/Settings/PublicPortalSettingsTest.php` | Fallback expectation updated to `AavaasDesk` |
| `tests/Feature/NotificationBrandingTest.php` | **New**: asserts invitation emails contain the configured site name and no "OpenKos" |

All changes are string-level and revertible with `git checkout`.

## Repository audit (OpenKos occurrences)

| Classification | Where | Action |
|---|---|---|
| Customer-facing branding | notifications, lang strings, page titles/labels, public-portal resolver | **Replaced** (see table above) |
| Internal code namespace | `OpenKOS\Core\...` imports, `OpenKOS::` facade usage, platform/plugin services | Kept — renaming would be a Level 3 refactor |
| Developer documentation | `docs/` (~37 occurrences) | Kept |
| Licensing & attribution | `AboutController` PRODUCT_NAME/REPOSITORY_URL/COPYRIGHT, About page strings ("About OpenKOS", "OpenKOS project repository"), Apache 2.0 notices | Kept — attribution must stay accurate |
| Build infrastructure | `config/settings.php` defaults, `InstallCommand`, seeders, migrations (`site_name` default 'OpenKOS') | Kept — internal fresh-install defaults, overridden by configured settings |

## Verification

### Functional checks

- `GET /up` → 200; `GET /` → 200 with `<title>AavaasDesk</title>`.
- `GET /branding/logo`, `/branding/favicon`, `/branding/og-image` → 200 `image/png` (previously fell back to `openkos-logo.svg` until assets were moved to `storage/app/private/branding`).
- Public portal metadata resolver returns siteName/title/description/og-image all branded AavaasDesk.
- Mailpit (`http://localhost:8025`) previews: **staff invitation** ("You are invited to AavaasDesk" / "Welcome to AavaasDesk") and **tenant invitation**, both from-name **AavaasDesk**. Rent reminder / payment templates contain no OpenKos strings (verified by grep) and inherit the AavaasDesk from-name.

### Tests

- Targeted: `NotificationBrandingTest` + `PublicPortalSettingsTest` → **12 passed**.
- Full suite: **1385 passed / 8 failed**. 7 failures are **pre-existing** on this codebase/environment (verified by stashing all branding changes and re-running the failing classes): `MailManagerTest` (MailFailed event), `InspectionTest`, `ComposerPluginDiscoveryTest` ×2, `WhatsAppSettingsTest`, plus 1 more; only 1 failure was branding-related and was fixed (resolver fallback expectation).
- Production build (`npm run build` in container) → **built in 11.5s, no errors**. No `typecheck` script exists in `package.json`.

## Known limitations

1. Pre-existing test failures unrelated to branding remain (see above).
2. Invoice PDF header uses the configured `site_name` (already AavaasDesk) but the PDF engine may cache fonts/layout — regenerate an invoice to confirm.
3. `config/settings.php`, migrations and `SettingSeeder` still default to 'OpenKOS' for fresh installs — intentional (internal defaults), a future deployment is configured via Settings/`InstallCommand`.
4. Logo/favicons are PNG per the accepted mime types (`image/jpeg`, `image/png`, `image/webp`); SVG is not accepted by the upload path.
5. Dev CLI/installer strings ("Setup the initial owner account for OpenKOS", demo seeder notes) remain — not customer-facing.

## AavaasDesk identity summary

| Element | Value |
|---|---|
| Product name | AavaasDesk |
| Tagline | Manage Properties. Simplify Living. |
| Country / Currency / Timezone | India / INR / Asia/Kolkata |
| Language | English |
| Colors | Navy `#1B2A4A`, Teal `#14B8A6` |
