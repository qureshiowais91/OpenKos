# GrihaFlow Open-Source Inventory

Generated 2026-11-15 as part of the GrihaFlow white-label rebrand. Machine-readable artifacts live in [`compliance/`](../compliance/).

## Artifacts

| Artifact | Tool | File |
|---|---|---|
| SBOM (CycloneDX) | syft 1.57.1 | `compliance/sbom/sbom-cyclonedx.json` |
| SBOM (syft JSON) | syft 1.57.1 | `compliance/sbom/sbom-syft.json` |
| Per-package license list | syft 1.57.1 | `compliance/licenses/licenses-syft.json` |
| Vulnerability report | osv-scanner 2.7.1 | `compliance/sbom/osv-scanner-report.json` |

## Upstream / provenance

- **GrihaFlow is a fork of [OpenKOS](https://github.com/senatroxx/OpenKos) by senatroxx** — Apache License 2.0. Upstream copyright and license notices are preserved in [`LICENSE`](../LICENSE) and in the About page (`PRODUCT_COPYRIGHT`: "GrihaFlow contributors, based on OpenKOS by senatroxx (Apache-2.0)").
- **`openkos/platform` 0.2.5** (composer package from `github.com/senatroxx/openkos-platform`, locked at commit `b7862bffe119a9d1cf85488a7f588b785cd59761`) — Apache-2.0. This is the upstream plugin-platform package; its namespace (`OpenKOS\`), service IDs, and wire fields are third-party contracts and are intentionally not renamed (see `docs/branding-audit.md`).

## Direct dependency licenses (from lockfiles)

### Composer (PHP)

| Package | Version | License |
|---|---|---|
| laravel/framework | 13.25.0 | MIT |
| openkos/platform | 0.2.5 | Apache-2.0 |
| laravel/fortify | 1.37.2 | MIT |
| laravel/tinker | 3.0.2 | MIT |
| laravel/wayfinder | 0.1.20 | MIT |
| laravel/chisel | 0.1.1 | MIT |
| inertiajs/inertia-laravel | 3.1.0 | MIT |
| spatie/laravel-permission | 7.4.1 | MIT |
| spatie/laravel-query-builder | 7.3.0 | MIT |
| dompdf/dompdf | 3.1.6 | LGPL-2.1 (uses php-font-lib LGPL-2.1+, php-svg-lib LGPL-3.0+) |
| composer/semver | 3.4.4 | MIT |

### npm (JavaScript)

Nearly all direct npm dependencies are MIT. Notable exceptions: `playwright` (Apache-2.0), `typescript` (Apache-2.0), `class-variance-authority` (Apache-2.0), `lucide-react` (ISC), `eslint-import-resolver-typescript` (ISC).

## Aggregate license distribution (1,207 packages incl. transitive)

| License | Packages |
|---|---|
| MIT | 895 |
| NOASSERTION | 138 |
| ISC | 53 |
| Apache-2.0 | 47 |
| BSD-3-Clause | 38 |
| MPL-2.0 | 14 |
| BSD-2-Clause | 8 |
| BSD-3-Clause+GPL (dual, nette) | 4 |
| CC-BY-4.0 | 2 |
| 0BSD | 2 |
| MIT AND ISC | 2 |
| Python-2.0 | 1 |
| LGPL-2.1 / LGPL-2.1+ / LGPL-3.0+ | 3 (dompdf family) |

## Copyleft / copy-left-adjacent notes

- **dompdf family (LGPL-2.1+)** — used for invoice PDF generation. LGPL is compatible with the Apache-2.0 project under Apache's ASL2.0 §7 terms (unmodified library use via API). Keep as-is; no bundling concerns.
- **nette/utils, nette/schema (BSD-3 or GPL-2.0/3.0 dual)** — consumed under the BSD-3 option.
- **lightningcss (MPL-2.0)** — build-time tool, file-level copyleft, fine for Apache-2.0 projects.
- **138 NOASSERTION packages** — mostly WASM binding shims for rolldown/napi tooling (build-only, not shipped in runtime image). No license text embedded in metadata; treated as build-time only.

## Known vulnerabilities (osv-scanner 2.7.1, 2026-11-15)

43 advisories across the three lockfiles (10 in `composer.lock`, 12 in `package-lock.json`, 21 in `pnpm-lock.yaml`). Notables:

- `laravel/framework` 13.25.0 — 4 GHSA advisories
- `vite` 8.1.5 — 5 advisories (dev-only)
- `league/commonmark` 2.10.0 — 2 advisories

**Out of scope for the text-only rebrand.** Track as dependency-upgrade follow-ups (composer update is currently blocked by the VCS-pinned `openkos/platform` 0.2.5; see `docs/branding-audit.md` risk notes). The `pnpm-lock.yaml` findings may be moot — confirm whether that lockfile is still used (repo also has `package-lock.json`).

## Regeneration

```bash
syft packages -o cyclonedx-json . > compliance/sbom/sbom-cyclonedx.json
syft packages -o syft-json . > compliance/sbom/sbom-syft.json
osv-scanner scan source --format json --output-file compliance/sbom/osv-scanner-report.json -r .
```
