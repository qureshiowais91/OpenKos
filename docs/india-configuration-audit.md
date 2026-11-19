# India-Only Configuration Audit (2026-11-15)

## Objective

Make GrihaFlow India-only: country `IN`, currency `INR` (₹), timezone
`Asia/Kolkata`, locale `en-IN` (English), phone `+91`, dates `DD/MM/YYYY`,
12-hour time, Indian number grouping, metric, Indian PIN codes. One
centralized configuration; no multi-country/multi-currency workflows.

This document records what the application looked like **before** the change.
The implementation record lives in `docs/india-configuration-implementation.md`.

## Existing configuration architecture

- **Framework:** Laravel 12 (PHP 8.3) + Inertia/React (TypeScript, Vite);
  FrankenPHP runtime in Docker; PostgreSQL in production, SQLite on the
  Render free tier.
- **Settings pipeline (the existing "one source of truth" infrastructure):**
  `config/settings.php` is the setting *registry* (key → `{default, cast}`).
  `App\Services\Settings\SettingManager` resolves stored rows from the
  `settings` table first and falls back to the registry default. `Setting::get()`
  is the read API, `Setting::set()` the write API; results are shared to the
  frontend via `App\Http\Middleware\HandleInertiaRequests::share()` (`setting`
  prop + `app.timezone/locale/intl_locale/currency_scales`).
- **Display timezone (backend):** `AppServiceProvider::boot()` reads
  `Setting::get('timezone')`, validates it, and pins it into
  `config('app.display_timezone')`. `App\Support\DateTimeFormatter` and the
  invoice PDF view render through it. UTC storage is preserved for instants;
  date-only fields are formatted in UTC.
- **Display timezone (frontend):** `resources/js/app.tsx::syncDisplaySettings()`
  feeds the Inertia props into `resources/js/lib/formatters.ts`
  (`setDisplayTimezone`, `setDisplayCurrency`, `setDisplayLocale`,
  `setCurrencyScales`), which back every money/date render via `Intl.*`.
- **Money:** `App\Services\Payments\MoneyConverter` holds the ISO-4217 minor
  unit table (INR already present, scale 2) and formats via intl
  `\NumberFormatter` using `ApplicationLocale::intlLocale()`.
- **Locale:** `App\Services\Localization\ApplicationLocale` resolves the
  stored `locale` through `config('app.locale_aliases')`, maps it to an intl
  locale via `config('app.intl_locales')`, and offers choices from
  `config('app.supported_locales')`.

## Root causes (why the app was not India-oriented)

The upstream OpenKOS codebase is **Indonesian by default**, and the rebrand
(2026-11-15) was text-only, so every regional default still points at
Indonesia — with a legacy "other country" escape hatch that mutates into USD:

1. `config/settings.php` registry defaults: `country_code=ID`, `locale=id`,
   `currency=IDR`, `timezone=Asia/Jakarta`.
2. DB column defaults in `2026_05_24_091654_create_settings_table` and
   `2026_07_07_000000_create_setting_values_table`: same Indonesian values.
3. `database/seeders/SettingSeeder` explicitly seeds `ID`, `id`, `IDR`,
   `supported_currencies=[IDR, USD]`, `Asia/Jakarta`.
4. `app/Console/Commands/InstallCommand` asks the operator to choose between
   `ID` (→ IDR/Asia/Jakarta) and `XX` "Other" (→ USD/UTC) and hardcodes
   `locale=en`.
5. Demo data seeders mix currencies: `PropertyAndUnitSeeder` has an explicit
   USD rate (`450.00`), `LeaseSeeder` branches `currency === 'USD'` for
   deposits; `LoadTestDatasetSeeder` uses IDR.
6. `RegionAndCitySeeder` loads **Indonesian provinces/regencies**
   (`database/data/provinces.json`, 38 provinces; `regencies.json`, 513
   regencies) with `country_code='ID'` — these feed property/dashboard
   region and city pickers.
7. `config/app.php` exposes Indonesian as a supported locale
   (`supported_locales=['en','id']`, `intl_locales=['en'=>'en-US','id'=>'id-ID']`).
8. Frontend formatter fallbacks predate the share pipeline:
   `formatters.ts` defaults `displayTimezone='UTC'`, `displayLocale='en-US'`.
9. Phone input (`resources/js/components/shared/phone-input/`) defaults
   parsed/unprefixed numbers to **Indonesia** (`parseE164` falls back to
   `iso2='ID'`), and its combobox lists every country dial code.
10. `.docker/render-run.sh` (free-tier boot) hardcodes
    `country_code="XX"`, `currency="USD"`, `supported_currencies=["USD"]`,
    `timezone="UTC"`.
11. `App\Services\Settings\InstallationCurrencySettings` fallback string is
    `'IDR'` (duplicate of the registry default).
12. `ApplicationLocale` private fallback constants mirror the Indonesian
    config (duplicated defaults outside the registry).

## Conflicting-default inventory (pre-change)

| File | Independent default |
|---|---|
| `config/settings.php` | ID / id / IDR / Asia/Jakarta |
| `config/app.php` | `supported_locales` incl. Bahasa Indonesia; intl `en-US` |
| `2026_05_24_091654_create_settings_table.php` | ID / IDR / Asia/Jakarta |
| `2026_07_07_000000_create_setting_values_table.php` | ID / IDR / Asia/Jakarta |
| `database/seeders/SettingSeeder.php` | ID / id / IDR / [IDR, USD] / Asia/Jakarta |
| `database/seeders/RegionAndCitySeeder.php` + `database/data/{provinces,regencies}.json` | Indonesian geography, country `ID` |
| `database/seeders/PropertyAndUnitSeeder.php` | IDR rates + one USD rate |
| `database/seeders/LeaseSeeder.php` | USD currency + USD-deposit branch |
| `database/seeders/LoadTestDatasetSeeder.php` | IDR |
| `app/Console/Commands/InstallCommand.php` | ID→IDR/Asia/Jakarta vs XX→USD/UTC choice |
| `app/Services/Settings/InstallationCurrencySettings.php` | `'IDR'` fallback string |
| `app/Services/Localization/ApplicationLocale.php` | Indonesian alias/intl defaults |
| `app/Services/Payments/MoneyConverter.php` | `'IDR'` fallback string in `normalizeCurrency` |
| `resources/js/lib/formatters.ts` | `UTC` / `en-US` fallbacks |
| `resources/js/components/shared/phone-input/country-codes.ts` | Indonesia fallback for unprefixed numbers; full country list |
| `resources/js/pages/settings/general.tsx` | country free-text input (any 2 letters); currency pickers list **every** ISO code via `currency_scales` |
| `.docker/render-run.sh` | XX / USD / [USD] / UTC / en |

Modules reviewed: settings/general UI, install, demo boot, seeders,
migrations, money formatting (backend + PDF + frontend), date/time
formatting (backend + frontend), locale resolution, notifications/reminders
(they route through `ApplicationLocale` + `MoneyConverter`, so no local
defaults), phone capture, region/city pickers (properties, dashboard).

## Schema / data findings

- `leases`, `unit_rates`, `invoices`, `payments`, `expenses`,
  `recurring_expenses`, `unit_type_rates`, `payment_attempts` etc. store
  `currency` per row (data carried with the money — no global join). Demo /
  load-test data rows currently say USD or IDR.
- `regions.country_code` rows are `ID` from the Indonesian dataset.
- No user- or organization-level regional overrides exist (no locale column
  on `users`; locale/timezone/currency only live in the settings table).
- No migration of historical financial data is needed or planned: stored
  per-row currencies are preserved as-is (per the task's data-preservation
  rule), and the only deployment with data (Render free tier) reseeds a
  throwaway SQLite on every boot, so fresh installs get the new defaults via
  the updated seeders/column defaults.
- `supported_currencies` as a stored setting is retained for schema
  compatibility but is pinned to `['INR']` by defaults, seeding, the install
  path, the settings UI, and controller validation.
