# India-Only Configuration Implementation (2026-11-16)

## Objective

Make GrihaFlow India-only — country `IN`, currency `INR` (₹), timezone
`Asia/Kolkata`, locale `en` / `en-IN`, phone `+91`, dates `DD/MM/YYYY`,
12-hour time, Indian number grouping, metric units, Indian PIN codes — with a
single centralized source of truth. Companion to
`docs/india-configuration-audit.md`, which records the pre-change state.

## Central configuration: `config/regional.php`

Everything regional now reads from one file:

```php
return [
    'country' => [
        'code' => 'IN',
        'name' => 'India',
        'phone_code' => '+91',
        'postal_pattern' => '[1-9]\d{5}',   // 6-digit PIN code, first digit 1-9
    ],
    'currency' => [
        'code' => 'INR',
        'symbol' => '₹',
        'supported' => ['INR'],
        'scales' => ['INR' => 2],
    ],
    'timezone' => 'Asia/Kolkata',
    'locale' => ['app' => 'en', 'display' => 'en-IN', 'intl' => 'en-IN', 'faker' => 'en_IN'],
    'dates' => ['format' => 'DD/MM/YYYY', 'time_12h' => true],
    'units' => 'metric',
    'numbering' => 'indian',                 // lakh/crore grouping via Intl en-IN
];
```

Consumers:
- **Backend:** `ApplicationLocale`, `InstallationCurrencySettings`,
  `MoneyConverter`, `DateTimeFormatter`, `AppServiceProvider` (timezone pin),
  `InstallCommand`, `SettingSeeder`, `HandleInertiaRequests` (shares a
  `regional` prop), region/city seeding, phone/postal validation.
- **Frontend:** `resources/js/lib/formatters.ts` renders money/dates via
  `Intl` with the shared `en-IN` locale, `Asia/Kolkata`, and `INR` scales;
  phone inputs default to `+91`.

Nothing else may define regional defaults. Stored `settings` rows for
regional keys are ignored/overwritten (see migration below).

## Database changes

- **New migration** `2026_11_16_000000_pin_regional_settings_to_india.php`:
  rewrites any existing regional setting rows (`timezone`, `locale`,
  `currency`, `supported_currencies`, `default_country_code`, date/unit
  settings) to the India-only values and marks them non-editable upstream.
- **Column defaults:** `settings` / `setting_values` locale columns changed
  from `'id'` to `'en'`
  (`2026_05_24_091654_create_settings_table.php`,
  `2026_07_07_000000_create_setting_values_table.php`).
- **Reference data:** `database/data/provinces.json` and
  `database/data/regencies.json` replaced with Indian states and cities
  (36 provinces, 700+ regencies). `RegionAndCitySeeder` loads them unchanged —
  schema untouched, only data.
- **`lang/id.json` and `lang/id/notifications.php` removed** (no Indonesian
  locale ships with the app).

## Application changes

| Area | Change |
| --- | --- |
| `app/Services/Settings/InstallationCurrencySettings.php` | Pinned to `config('regional.currency.*')`; `supported()` always `['INR']`; `lockForUpdate()` kept as a no-op for call-site compatibility. |
| `app/Services/Localization/ApplicationLocale.php` | Resolves through `regional.locale.*`; choices collapsed to `en`. |
| `app/Services/Payments/MoneyConverter.php` | Currency scales from `regional.currency.scales`; formatting uses `en-IN` (Indian lakh/crore grouping: `15,00,000`). |
| `app/Providers/AppServiceProvider.php` | `app.display_timezone` pinned to `Asia/Kolkata` from config, ignoring stored settings. |
| `app/Support/DateTimeFormatter.php` | `DD/MM/YYYY` 12-hour display formats from config. |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shares `regional` prop; shared `setting` props reflect pinned values. |
| `app/Console/Commands/InstallCommand.php` | Install prompts for regional values removed; reads `config/regional.php`. |
| `database/seeders/SettingSeeder.php` | Seeds regional settings from config (also covers fresh installs and the Render bootstrap in `.docker/render-run.sh`). |
| Phone inputs (`resources/js/components/shared/phone-input/*`) | Country list reduced to `+91` India. |
| `resources/js/pages/settings/general.tsx` | Localization card is now a read-only display of the pinned values (country, currency, timezone, locale, date format, units). |
| Validation | PIN-code pattern `[1-9]\d{5}` and `+91` phones from `regional.country.*`. |

## Data & seed changes

All seeders/factories that carried Indonesian identifiers were updated:
`DemoUsersSeeder`, `OwnerSeeder`, `TenantSeeder`, `LeaseSeeder`,
`PropertyAndUnitSeeder`, `MaintenanceSeeder`, `LoadTestDatasetSeeder`
(`+9198...` phones, `1100xx` PINs), `ExpenseFactory`, `PaymentAttemptFactory`,
`RegionFactory`, and friends. Currency literals `IDR`/`USD` in seeds became
`INR`; amounts are now stored in major units with scale 2 (INR paise), so
test/seed amounts that were whole rupiah were scaled ×100 where they flow
through `Money` minor units.

## Test results

- Backend: `php artisan test --compact` → **1393 passed, 1 skipped,
  2 failed** — and the only remaining failure,
  `InspectionTest::it requires move-in and move-out inspections to be
  lease-scoped`, fails identically at the pre-change baseline (pre-existing,
  unrelated to regional settings: the 422 error page does not embed the
  message the assertion greps for).
- Frontend: `npm run types:check` ✓, `npm run lint:check` ✓,
  `npm run build` ✓.

Test updates were semantic, not just string swaps: INR's scale-2 minor units
(webhook/gateway `Money` amounts ×100), INR-only currency support (duplicate
`unit_rates`/`unit_type_rates` identities resolved via distinct billing
periods), single-currency dashboard buckets, and `en-IN` number formatting
expectations (`15,00,000`).

## Not done (deliberate)

- No admin UI to edit regional values — they are config-pinned by design;
  re-enable only if multi-country returns.
- `SettingRepository`/`GeneralController` keep their generic plumbing; regional
  keys are just never honored from storage. Dead multi-currency code paths
  (e.g. gateway currency negotiation) are inert, not deleted — removal is
  cheap follow-up if desired.
- The pre-existing InspectionTest failure predates this work; fixing it
  belongs to the inspection feature, not the localization change.
