# Trim list (W0)

Everything removed from the template base to get an API plus admin kit. Nothing shipped, so inherited migrations were edited in place.

## Removed
- `web/portal` (Next.js), its compose service/volume, nginx vhost, `docker/prod/portal*`, `packages/tokens/portal.css`, portal Playwright tests, `PORTAL_*`, `SITE_URL`, `CONTACT_NOTIFY_TO` env vars.
- API content modules: hero, about, sections, services, pricing, team, testimonials, gallery, FAQ, blog (posts, categories), contact inbox - models, migrations, seeders (including `Site/*` demo data), routes, resources, requests, policies, tests, permissions and menu rows.
- Portal support code: `ContentObserver`, `SendRevalidateWebhook`, `NewContactMessage`, `Captcha`, `HtmlSanitizer`, `LocalizePublicResponse`, `config/portal.php`, `config/contact.php`, the `contact` rate limiter.
- Second language: `*_2nd` settings columns, `HasSecondLanguage`, `Bilingual`.
- Admin: Messages page, gallery uploader, hero/about/sections/blog/content module configs and tests.
- MariaDB: compose service, `MARIADB_*` vars, `pdo_mysql`, the `mysql`/`mariadb` connections in `config/database.php`.
- `Makefile` (replaced by `scripts/check.sh`).

## Kept
Auth (Passport PKCE), users, roles, menus, settings (business fields stay until W4 reworks them), media/upload service, shared packages (tokens, api-client, api-types), Docker setup, queue worker and scheduler containers.
