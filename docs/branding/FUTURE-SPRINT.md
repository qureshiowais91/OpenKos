# AavaasDesk — Future Sprint Proposal: Multi-Brand / Multi-Tenant Branding

**Date:** 2026-11-14
**Status:** Not approved — separate approval required before implementation.
**Context:** The current rebrand (see `IMPLEMENTATION.md`) is a **single installation identity** driven by the global `site_name` setting. Everything below is deferred because it requires architectural change, not string/config edits.

## Proposed features (deferred)

1. **Multiple customer-specific brands** — each deployment/customer sees its own brand, not one shared `site_name`.
2. **Multi-tenant branding** — brand per tenant *company* within one installation (owner serves multiple landlords with distinct identities).
3. **Customer-specific domains** — `portal.aavaasdesk.com` today; `portal.clientname.com` per customer (needs wildcard TLS, host header resolution, trusted proxies per domain).
4. **Centralized reseller dashboard** — a top-level user that manages multiple branded customer installations/customers, their usage and billing.
5. **Customer-specific theme customization** — per-brand color palette, logo, typography overrides in CSS/Tailwind variables and dark/light mode tokens.

## Dependencies

- Settings model must become scoped: per-installation → per-customer → per-domain (keys like `site_name`, `mail_config`, `branding_*` currently global; `Setting::set/get` has no scope dimension).
- `BrandingAssetController` and asset storage are single-brand (one `branding_logo_path`). Needs per-brand paths/CDN keys.
- `PublicPortalMetadataResolver` + `PublicListingController` resolve one public site; needs domain → brand resolution middleware.
- Mail driver config (`mail_config`) is global; per-brand sender identity requires per-customer SMTP credentials and selection at send time.
- Frontend: `app-logo.tsx`, Tailwind theme tokens and `HandleInertiaRequests` shared props assume a single brand.

## Complexity

- **High**: settings scoping touches every `Setting::get` call site (audit required); migration of existing rows.
- **Medium**: per-brand assets, domain routing middleware, reseller dashboard (new UI + auth scope).
- **Low–Medium**: theme tokens (CSS variables per brand).

## Risks

- Cache keys must include brand/domain scope or branding leaks across customers.
- Webhook/payment gateway URLs become brand-specific → gateway callbacks need domain mapping.
- Signed payment links (`pay/invoices/{token}`) must resolve brand by lease/tenant, not by global setting.
- Email deliverability: per-customer SMTP credentials require reputation management per domain.
- License note: product remains OpenKOS (Apache 2.0) under the hood; rebranding must not alter required notices on the About/license surfaces.

## Acceptance criteria (when approved)

1. Two customers on one installation each see only their own brand on login, dashboard, portal, public pages and emails.
2. Requests to customer-specific domains resolve the correct brand with valid TLS and correct `APP_NAME`-independent page titles.
3. Email sender name/address matches the brand of the recipient's customer; Mailpit/manual previews confirm.
4. Settings cache invalidation is scoped — changing brand A never affects brand B.
5. Reseller dashboard lists customers, their domains, assets and health; unauthorized brand access is denied (policy tests).
6. Theme customization: palette/logo changes propagate to light/dark and mobile without rebuild.
7. All existing tests keep passing; new tests cover brand resolution, scoping, domain mapping and mail sender selection.
