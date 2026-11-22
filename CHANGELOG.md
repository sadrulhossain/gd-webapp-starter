# Changelog

Format: [Keep a Changelog](https://keepachangelog.com), versions follow [SemVer](https://semver.org).
Each entry lists new environment variables and seeders to run, because upgrades need them (see docs/UPGRADE.md).

## [2.0.0] - 2026-11-22

The starter: the business-site content, the portal and MariaDB are gone; the base is a Laravel API and a React admin on PostgreSQL.

### Added
- **Users**: status (active, suspended), session list and sign-out everywhere, own-profile and password routes, superadmin protection (cannot be deleted, suspended or edited; the last active one is protected).
- **Roles and permissions**: permissions declared only in `config/permissions.php` and applied with `php artisan permissions:sync`; no escalation (you grant only what you hold); a test fails on any admin route without a permission check.
- **Menus**: menus as data, hide and show, custom entries (`menus.create`, `menus.delete`), reorder.
- **Settings**: declared in `config/settings.php` (type, default, validation, public flag); secrets are encrypted and write-only; key/value storage.
- **Audit log**: append-only (model and database trigger), `Auditable` trait, redaction of sensitive values, `GET /api/v1/audit-logs` with filters (`audit.view`).
- **make:module**: `php artisan make:module Name` scaffolds a module with permissions, policy, menu entry, audit and tests.
- **Admin**: permission-aware shell and landing page, per-user menu cache, screens for users (suspend, reactivate, sign out everywhere), roles, menus, settings and the audit log.
- **Hardening**: rate limits on the signed-in API (300/min per account) and anonymous reads (60/min per address); review in docs/SECURITY.md.

### Changed
- Database: PostgreSQL 18 (cache, sessions and queue use it). Backups use `pg_dump`.
- `GET /api/v1/public/settings` returns only settings flagged public.
- The `settings` table is replaced by `setting_values` (key/value); the migration copies the existing row.
- Docs (DEPLOY, ENV, UPGRADE, PATTERNS) rewritten for the API and admin stack. `IMAGE_ID` was removed from `.env.prod.example` (it belonged to the portal).

### Notes
- Upgrading from the business-site release is not supported; start from this version.
- New environment variables: none. After deploying run `php artisan permissions:sync` and `php artisan db:seed --force` (adds the Audit log menu entry and new permissions).
- Known limits: the dev CSP still allows inline scripts (SECURITY.md finding 5); the audit log has no purge.

[2.0.0]: https://github.com/sadrulhossain/gd-webapp-starter/releases/tag/v2.0.0

## [1.0.0] - 2026-11-13

First release.

### Added
- **API** (Laravel 13, PHP 8.5, MariaDB): sections, services, team, testimonials, gallery, FAQs, blog (categories, posts, sanitised rich text), contact inbox with captcha, honeypot and throttle,
  settings, users, roles and permissions, per-user menus, dashboard, image uploads with WebP variants, content-change webhook, OAuth 2 with PKCE (Passport), OpenAPI export.
- **Admin** (Vite, React 19): sign-in through the authorization-code flow with PKCE, CRUD screens driven by `modules.ts`, TipTap blog editor, profile, role matrix, bilingual fields.
- **Portal** (Next.js 16): Home and section pages, services, gallery, FAQ, blog (categories, pagination), contact page, legal pages, localised 404, two languages, ISR with tag revalidation,
  canonical/hreflang/Open Graph, `sitemap.xml`, `robots.txt`, JSON-LD.
- **Security**: S256-only PKCE, closed CORS, security headers and CSP per vhost, per-visitor throttling, upload validation by content, security gates (route/policy audit, secrets scan, version floors).
  Findings and scans: docs/SECURITY.md.
- **Quality**: Pest, Larastan, Pint; Vitest; Playwright smoke and axe suites (WCAG 2 A/AA, both languages, zero violations); Lighthouse SEO 100.
- **Production** (`docker-compose.prod.yml`, `.env.prod.example`): portal as a Node server, admin built static and served by nginx, TLS from mounted certificates, only ports 80/443 published,
  private MariaDB, restart policies, healthchecks, rotated logs, uploads path from env. `scripts/backup.sh` and `scripts/restore.sh` (database, uploads, OAuth keys).
- **Docs**: README, DEPLOY, CUSTOMIZE, UPGRADE, ENV, PATTERNS, DECISIONS, SECURITY, SIZING, VERSIONS, PHASES.

### Notes
- Environment variables: see docs/ENV.md and `.env.prod.example`. Seeders: run `php artisan db:seed --force` once on first deploy.
- The database is MariaDB 11.8 and there is no Redis (docs/DECISIONS.md, 010); backups use `mariadb-dump`.
- Known limits: the portal CSP still allows inline scripts (nonces are the next step); Lighthouse performance 90+ was not reached under the default mobile throttle on the slow development host (docs/SECURITY.md).

[1.0.0]: https://github.com/sadrulhossain/gd-business-site-boilerplate/releases/tag/v1.0.0
