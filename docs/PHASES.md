# Phases

Tick a phase only when it is finished and committed.

| Done | # | Phase | Scope / files |
|------|---|-------|---------------|
| [x] | 0 | Repo scaffold and docs (inherited) | .gitignore, .editorconfig, CLAUDE.md, docs/* |
| [x] | 1 | Docker infrastructure (inherited) | docker-compose.yml, docker/*, scripts/check-ports.sh, Makefile, .env.example, hello apps in web/admin, web/portal, api/public |
| [x] | 2 | Laravel API (inherited) | api/*, Dockerfile (composer, phpredis), /api/v1/health, /api/v1/meta/locales, JSON errors, Pest/Pint/Larastan/Scramble, make t/check |
| [x] | 3 | Auth and RBAC (inherited) | Passport 13, spatie/laravel-permission, menus table, /me, /me/menus, seeders, access tests |
| [x] | 4 | Settings, sections, media (inherited) | settings (singleton), sections, MediaService + WebP queue, HasSecondLanguage, PATTERNS.md |
| [x] | 5 | FAQs, services, testimonials, team, gallery (removed in W0) | migration + model + FormRequests + Resource + Policy + public/admin routes + seeder + feature test per module; image endpoints |
| [x] | 6 | Blog, contact, webhook, OpenAPI (removed in W0) | posts + categories (HTMLPurifier sanitising), public POST /contact (Redis captcha, honeypot, throttle, queued mail), admin inbox, ContentObserver -> signed revalidate webhook, `?lang=` on public reads, packages/api-types/openapi.json |
| [x] | 7 | Frontend workspace (inherited) | pnpm workspace (root package.json, pnpm-workspace.yaml, one pnpm-lock.yaml); packages/tokens (Tailwind 4 @theme, admin + portal themes), packages/api-types (openapi-typescript), packages/api-client (typed fetch); Tailwind 4 in web/admin (Vite) and web/portal (Next); one showcase route each (/showcase); docker/node/dev.sh on pnpm |
| [x] | 8 | Admin shell (inherited) | API login page + PKCE login, memory-only tokens with single-flight refresh, route guards, sidebar from /me/menus, permission-gated routes, 403/404, dashboard counts; shared DataTable/FormField/BilingualField/ConfirmDialog/Toast; config-driven CrudPage; TanStack Query + Zustand; Vitest (store, guards); Playwright login + dashboard screenshots |

| [x] | 9 | Admin modules (inherited) | users, roles (permission matrix), menus (tree + reorder), settings (tabbed bilingual singleton), sections (visibility toggle, drag reorder) as CrudPage configs; API: users, roles, permissions, menus, reorder endpoints; Vitest on configs |
| [x] | 10 | Content modules (removed in W0) | services, FAQs, testimonials, team, gallery as CrudPage configs with image upload (progress); gallery multi-upload; contact messages read-only list/detail + mark read; Vitest on configs and upload helpers |
| [x] | 11 | Blog and profile (removed in W0) | posts CrudPage config (TipTap rich text, bilingual, draft/published `status`, cover upload, SEO fields), categories, profile page (name, password: PATCH /me); posts SEO columns; Vitest on configs |
| [x] | 12 | Portal foundation (removed in W0) | Next App Router, locale routing (default unprefixed, second at /<code>) with messages/en.json + second.json, header/footer from public settings, generateMetadata, language switcher, section renderer (is_visible), Home, signed revalidate route; API: public settings + sections; Home screenshots per language |
| [x] | 13 | Portal pages (removed in W0) | About, Services, Service detail (/services/<id>), Gallery, FAQ, Privacy, Terms, localised 404, in both languages; pages reuse the section views; messages fall back to the default text; API public service detail |
| [x] | 14 | Blog, contact, SEO (removed in W0) | Blog list (category filter, pagination) and detail, contact page (captcha, honeypot, states, hours, map link), metadata/Open Graph/canonical/hreflang, sitemap.xml, robots.txt, JSON-LD, ISR tags + revalidate, Playwright smoke |
| [x] | 15 | Security, accessibility, performance (inherited) | Advisory and floor re-check, composer/pnpm audit, Trivy; review of PKCE, policies (route audit script), uploads, captcha/throttle, CORS, nginx headers, secrets; axe + Lighthouse on the portal; memory sizing; findings in docs/SECURITY.md |
| [x] | 16 | Production and release (inherited) | docker-compose.prod.yml (portal Node server, admin static via nginx, TLS from mounted certs, no dev ports/Mailpit, private DB, restart + healthchecks), backup/restore scripts, README, DEPLOY, CUSTOMIZE, UPGRADE, CHANGELOG, tag v1.0.0 |
| [x] | 17 | Polish and theming (inherited) | admin splash, collapsible nested sidebar menu, logo + favicon upload (settings, admin, portal), manual `Site*Seeder` sample data, portal redesign, mobile-friendly admin and portal |


## Web app starter kit (W phases)

Each phase has its own branch `feature/phase-<no>-<task-name>`, merged manually into `main` before the next phase starts (see CLAUDE.md).

| Done | # | Phase | Branch |
|------|---|-------|--------|
| [x] | W0 | Fork from main, PostgreSQL swap, trim to API and admin, green suite | `feature/phase-w0-fork-postgresql-swap-trim` |
| [x] | W1 | Users API: accounts, profile, password, status, sessions | `feature/phase-w1-users-api` |
| [x] | W2 | Roles and permissions API | `feature/phase-w2-roles-permissions-api` |
| [x] | W3 | Menus API | `feature/phase-w3-menus-api` |
| [x] | W4 | Settings API | `feature/phase-w4-settings-api` |
| [x] | W5 | Audit log and module scaffold | `feature/phase-w5-audit-log-module-scaffold` |
| [x] | W6 | Admin shell: sign-in, permission-aware layout, dynamic sidebar | `feature/phase-w6-admin-shell` |
| [x] | W7 | Admin screens: users, roles, menus, settings, audit log | `feature/phase-w7-admin-screens` |
| [x] | W8 | Hardening and release | `feature/phase-w8-hardening-release` |
