# Upgrade

Two kinds of upgrade: **your site to a newer release of this boilerplate**, and **the pinned software** (PHP, Node, nginx, PostgreSQL, packages). Both end the same way: back up, deploy, check.

## Before every upgrade

1. Read [CHANGELOG.md](../CHANGELOG.md) from your current version to the new one. It lists new environment variables, seeders to run and anything that needs a manual step.
2. `sh scripts/backup.sh` and copy the backup off the server. Know your rollback target (the current tag).
3. Compare `.env.prod.example` with your `.env.prod` and add any new variable (`git diff vOLD vNEW -- .env.prod.example docs/ENV.md`).

## Upgrade the site to a new release

```sh
cd /opt/site
git fetch --tags
git checkout <new-tag>  # the new tag
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build --wait
```

What happens: the images are rebuilt, the php container runs `migrate --force` before it reports healthy, nginx rebuilds the admin.

Then sync permissions and run what the changelog asks for, typically new seeders (they only add missing rows):

```sh
docker compose -f docker-compose.prod.yml --env-file .env.prod exec php php artisan permissions:sync
docker compose -f docker-compose.prod.yml --env-file .env.prod exec php php artisan db:seed --class=<NewModuleSeeder> --force
```

Finish with the checks from DEPLOY.md step 5, sign in to the admin and open the Audit log: your own sign-in changes should appear as soon as you edit something.

## Upgrade pinned software

The single source of truth is [VERSIONS.md](VERSIONS.md): a **pin** (what we build against) and a **floor** (never go below). The routine, in a branch of your fork:

1. Look up the new version and its advisories (links are in the table). Never go below a floor.
2. Change the pin in `docs/VERSIONS.md` (with source and date), then in `.env.example`, `.env.prod.example` (and your `.env` / `.env.prod`) for images, or in `composer.json` / `package.json` / `pnpm-workspace.yaml` for packages. Pins in package files are exact, no ranges.
3. `docker compose exec php composer update <package>` or `pnpm up <package>@<version>`; commit the lock file.
4. The README checks (Pest, Pint, Larastan, composer audit, security gates) and the Playwright suites. Fix what breaks before merging.
5. Deploy as above. Image pins (PHP, Node, nginx, PostgreSQL) rebuild or pull new images on `up -d --build`.

Notes per component:

- **PostgreSQL**: minor updates are safe (pull the new image). A major jump (18 to 19) needs a dump and restore into a fresh volume: take a backup and test the restore on a spare server first.
- **PHP**: the pin is the patch release; rebuild the image. Read the release notes for removed ini settings (`docker/php/security.ini`).
- **Node / Vite**: rebuilds the admin in the nginx image. Keep `pnpm audit` clean and run the Vitest suite.
- **nginx**: re-run `nginx -t` (the compose healthcheck fails if the config does not load).
- Run `docker run aquasec/trivy image <image>` on the new images when you want the scan of record (see SECURITY.md for how results are recorded).

## Rollback

If the new version misbehaves:

```sh
git checkout <previous-tag>  # the previous tag
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build --wait
```

Code rolls back this way; the **database does not**. If the release ran migrations that changed data, restore the backup you took before upgrading:

```sh
sh scripts/restore.sh backups/<timestamp-before-upgrade>
```

Migrations in this boilerplate are written to be additive, but a restore is the only guaranteed way back. This is why the backup comes first.

## Updating the boilerplate itself in a fork

Add the original repository as `upstream`, then `git fetch upstream --tags` and merge a tag (`git merge <tag>`). Conflicts are most likely in `docs/`, `packages/tokens/*.css`, `config/permissions.php`, `routes/api.php` and `DatabaseSeeder.php` (the `// <make:module ...>` markers are where your modules go).
Keep your customisations in the files [CUSTOMIZE.md](CUSTOMIZE.md) names, and avoid editing shared code unless you must; that keeps merges small.
