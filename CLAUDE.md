# gd-webapp-starter

## Repo map
- `api/` — Laravel API (PHP 8.5, PostgreSQL 18; cache, sessions and queue use the database)
- `web/admin` — Vite + React admin app
- `packages/` — shared packages
- `docker/` — Dockerfiles and compose config
- `docs/` — PHASES.md, VERSIONS.md, ENV.md, DECISIONS.md, PATTERNS.md

## Commands (docker compose and sh scripts only; there is no Makefile, never use make)
- `docker compose up -d --build --wait` / `docker compose down --remove-orphans` — start / stop the stack
- `sh scripts/check-ports.sh` — check host ports from .env are free or ours
- `docker compose exec -T php vendor/bin/pest --compact --filter=...` — targeted Pest tests
- Final check (once, at the end of a phase; needs the stack up): `sh scripts/check.sh`, which runs `docker compose config -q`, `sh -n` on scripts, `docker compose exec -T nginx nginx -t`, then in php: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`, `vendor/bin/pest --compact`, `composer audit`, `php artisan security:routes`; plus `sh scripts/check-secrets.sh`, `node scripts/check-floors.mjs`

## Rules
1) One phase at a time; read only CLAUDE.md, the phase row and files it names.
2) No repo-wide exploring, no unrelated refactors, no dependency not in VERSIONS.md.
3) Reuse the reference patterns in docs/PATTERNS.md; do not invent new ones.
4) While working run only targeted tests (pest --filter=...); run the final check once at the end.
5) Keep command output short (quiet flags, | tail -n 20). Never paste logs back.
6) No screenshots unless the phase says so.
7) Finish: tick the phase, git commit "phase N: title", reply in 8 lines or fewer
   (done, deviations, decisions I must make), then STOP and wait for approval.
8) Commits and PRs: never add a Claude signature, Co-Authored-By trailer, "Generated with" line
   or any other Claude attribution to commit messages, PR descriptions, code or docs. Anywhere.

## Phase workflow (every phase)
1) Start from an up-to-date main (`git checkout main && git pull origin main`); create the branch `feature/phase-<no>-<task-name-in-kebab-case>` (listed in docs/PHASES.md).
2) Work only on that branch. Commit with plain messages ("phase N: title" plus an optional short body); no Claude signature, trailer or attribution anywhere.
3) When `sh scripts/check.sh` passes, tick docs/PHASES.md, commit and `git push -u origin <branch>`.
4) Give the pull request link to main (https://github.com/sadrulhossain/gd-webapp-starter/compare/main...<branch>?expand=1, or `gh pr create --base main` with a plain title and body) and ask: "Please merge this pull request manually and tell me when the merge is done."
5) Stop. Never merge the pull request, never push to main, never start the next phase without the confirmation.
6) After the confirmation: switch to main, pull, start the next phase from step 1.
"Commit and STOP" in a phase prompt means steps 3 to 5.

## Design rules (invariants)
- Permissions: declared only in config/permissions.php and synced with `permissions:sync`; every admin route declares one; a test fails on any route without it.
- Superadmin: bypasses checks through a single Gate::before; cannot be deleted, suspended or edited; the last active superadmin is protected.
- No escalation: a user can grant only permissions they hold.
- Menus are data, not code; visibility is a convenience, the API enforces access.
- Settings: declared in config/settings.php with type, default, validation and a public flag; secrets are encrypted and write-only.
- Audit: append-only, sensitive attributes redacted, every module uses the Auditable trait.
- Modules: created with `php artisan make:module` so they inherit permissions, menu entry, audit and tests.
- Theme: the admin theme stays as inherited from main. The admin is English only.
