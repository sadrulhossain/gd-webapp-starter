# gd-webapp-starter

A starter for web applications: a Laravel API and a React admin app with users, roles, permissions, menus and settings.
New products add their own modules on top. Admin is English only. Cache, queue and sessions use PostgreSQL (no Redis).

| Part | Where | Stack |
|------|-------|-------|
| API | `api/` | Laravel 13, PHP 8.5, PostgreSQL 18, Passport (OAuth 2 + PKCE), spatie/permission |
| Admin | `web/admin` | Vite, React 19, TanStack Query, TipTap |
| Shared | `packages/` | design tokens, typed API client, OpenAPI types |

Versions and their security floors: [docs/VERSIONS.md](docs/VERSIONS.md).

## Quick start (development)

You need Docker with Compose and a browser. Nothing else is installed on the host.

```sh
git clone https://github.com/sadrulhossain/gd-webapp-starter.git && cd gd-webapp-starter
cp .env.example .env     # then set APP_KEY=base64:<output of: openssl rand -base64 32> and change every change-me
docker compose up -d --build --wait
sh scripts/ensure-test-db.sh   # creates the testing database used by Pest
```

Then open (the `.localhost` names resolve to your machine in modern browsers; no hosts file edit needed):

| URL | What |
|-----|------|
| http://admin.gd-webapp-starter.localhost:31080 | Admin (sign in with the superadmin below) |
| http://api.gd-webapp-starter.localhost:31080/docs/api | API reference (Scramble, dev only) |
| http://localhost:31026 | Mailpit, catches outgoing mail |

First run only: set `SUPERADMIN_EMAIL`, `SUPERADMIN_PASSWORD` and `OAUTH_ADMIN_REDIRECT_URI` in `api/.env` (copy `api/.env.example`), then
`docker compose exec php php artisan migrate --seed`. Change every `change-me` in `.env`.

Logo and favicon are uploaded in the admin under Settings; the built-in ones show until then.

Daily commands:

| Command | What |
|---------|------|
| `docker compose up -d --build --wait` / `docker compose down --remove-orphans` | start (build, wait healthy) / stop |
| `docker compose exec -T php vendor/bin/pest --compact --filter=Name` | targeted Pest tests (omit `--filter` for the full suite) |
| `docker compose exec -T php vendor/bin/pint --test` | code style |
| `docker compose exec -T php vendor/bin/phpstan analyse --memory-limit=1G --no-progress` | Larastan |
| `docker compose exec -T php composer audit` | dependency advisories |
| `docker compose exec -T nginx nginx -t` | nginx config test |
| `docker compose config -q` | compose file validity |
| `sh scripts/check.sh` | the whole final check, once per phase |
| `docker compose exec -T php php artisan security:routes`, `sh scripts/check-secrets.sh`, `node scripts/check-floors.mjs` | security gates: route/policy audit, secrets scan, version floors |
| `sh scripts/check-ports.sh` | host ports from `.env` are free or ours |
| `docker compose exec -T php php artisan scramble:export --path=storage/openapi.json` | re-export the API spec; move `api/storage/openapi.json` to `packages/api-types/openapi.json`, then `pnpm generate:types` |

The final check for a phase is one script: `sh scripts/check.sh` (compose configs, shell syntax, nginx, Pint, Larastan, Pest, composer audit, route audit, secrets, version floors). Everything runs through `docker compose` and `sh`; there is no Makefile.

Browser tests (Playwright, official image): `docker compose --profile e2e run --rm --no-deps -T e2e npx playwright test`.

## Production

One VPS (1 vCPU / 1 GB is the minimum, 2 GB recommended: [docs/SIZING.md](docs/SIZING.md)), your domain, a TLS certificate.
`docker-compose.prod.yml` serves the admin as built static files from nginx, publishes only ports 80 and 443,
keeps PostgreSQL private and restarts everything on failure. Steps: [docs/DEPLOY.md](docs/DEPLOY.md). Backups: `sh scripts/backup.sh`.

## Documentation

| File | Read it for |
|------|-------------|
| [docs/OVERVIEW.md](docs/OVERVIEW.md) | what the kit is and what ships in it |
| [docs/DEPLOY.md](docs/DEPLOY.md) | VPS setup, TLS, first deploy, backups, restore |
| [docs/CUSTOMIZE.md](docs/CUSTOMIZE.md) | branding, new modules, new settings |
| [docs/UPGRADE.md](docs/UPGRADE.md) | updating a running site, rollback |
| [docs/PATTERNS.md](docs/PATTERNS.md) | the reference code shapes to copy |
| [docs/ENV.md](docs/ENV.md) | every environment variable |
| [docs/SECURITY.md](docs/SECURITY.md) | review findings, scans, accessibility and performance results |
| [docs/DECISIONS.md](docs/DECISIONS.md), [docs/PHASES.md](docs/PHASES.md) | why things are the way they are, what was built when |
| [CHANGELOG.md](CHANGELOG.md) | releases |

## Repository map

```
api/        Laravel API            docker/     Dockerfiles, nginx templates, prod entrypoints
web/admin   Admin SPA              docs/       the documents above
            scripts/    check, ports, secrets, version floors, backup, restore
packages/   tokens, api-client, api-types
```
