# Deploy to a VPS

Target: one Linux VPS (Debian/Ubuntu), Docker Engine with the Compose plugin, a domain you control. Size: [SIZING.md](SIZING.md).
The production stack is `docker-compose.prod.yml` with its own env file. The dev file (`docker-compose.yml`) is never used in production.

## What runs

| Service | Image | Notes |
|---------|-------|-------|
| `nginx` | built from `docker/prod/nginx.Dockerfile` | the only published ports (80, 443). TLS from mounted certs. Serves the admin (built static files) and proxies the API. Also builds the admin |
| `php`, `php-queue`, `php-scheduler` | built from `docker/prod/php.Dockerfile` | code and vendor baked in, opcache, migrations on start |
| `postgres` | `postgres:<pin>` | **no published port**, data in the `postgres-data` volume |

No Mailpit, no dev servers, no Redis (the app uses PostgreSQL for cache, sessions and queue). Every service has a restart policy, a healthcheck and rotated logs.

## 1. DNS

Create three records pointing at the server: `example.com` (it only redirects to the admin), `admin.example.com` and `api.example.com` (`APP_DOMAIN=example.com`).

## 2. Server

```sh
# Docker Engine + Compose plugin: https://docs.docker.com/engine/install/
sudo ufw allow OpenSSH && sudo ufw allow 80,443/tcp && sudo ufw enable
git clone <your fork> /opt/app && cd /opt/app && git checkout <release-tag>
```

Add 1 GB swap if the server has 1 GB of RAM. Do not publish any other port: the database must stay private.

## 3. TLS certificate

One certificate must cover all three names (a wildcard `*.example.com` plus `example.com`, or a SAN certificate). With Let's Encrypt and certbot:

```sh
sudo certbot certonly --standalone -d example.com -d admin.example.com -d api.example.com
sudo mkdir -p /etc/gd-webapp-starter/certs
sudo cp -L /etc/letsencrypt/live/example.com/{fullchain,privkey}.pem /etc/gd-webapp-starter/certs/
```

Symlinks into `/etc/letsencrypt` do not resolve inside the container, so copy the real files (`cp -L`). Renewal (monthly cron, or a certbot deploy hook):
renew, copy the files again, then `docker compose -f docker-compose.prod.yml --env-file .env.prod exec nginx nginx -s reload`.
(Certbot's standalone mode needs port 80 free: run it before the first start, and use `--webroot` or a DNS challenge for renewals.)
If a load balancer or CDN terminates TLS in front, mount its origin certificate here and set `TRUSTED_PROXY_CIDR` to the balancer's address range only.

## 4. Configure

```sh
cp .env.prod.example .env.prod
chmod 600 .env.prod
```

Edit `.env.prod`: `APP_DOMAIN`, `APP_NAME`, `TLS_CERT_DIR`, the SMTP settings, and replace every `change-me`. Generate the secrets with
`openssl rand -base64 32` (`APP_KEY` is `base64:` plus that value). `SUPERADMIN_EMAIL` / `SUPERADMIN_PASSWORD` become the first admin.
Compose refuses to start when a required secret is missing. **Keep a copy of `.env.prod` outside the server** (a password manager): losing `APP_KEY` loses encrypted data (secret settings).

## 5. First start

```sh
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build --wait
docker compose -f docker-compose.prod.yml --env-file .env.prod exec php php artisan db:seed --force
```

`--wait` returns when every healthcheck passes. The seeders create the roles and permissions (from `config/permissions.php`), the menus, the superadmin from `.env.prod` and the admin OAuth client.
They are idempotent, so running them again is safe; they never overwrite edited content.

Check: `https://admin.example.com` (sign in), `https://api.example.com/api/v1/health` (`"status":"ok"`).
Then in the admin: Settings (name, contact details, logo), Roles (create the roles your team needs), and change the superadmin password.

To run the production commands shorter: `alias dcp='docker compose -f docker-compose.prod.yml --env-file .env.prod'`.

## 6. Backups

```sh
sh scripts/backup.sh      # backups/<timestamp>/{database.sql.gz,uploads.tar.gz,oauth-keys.tar.gz}, keeps 14 days (KEEP_DAYS)
```

Schedule it and copy the result off the server:

```cron
15 2 * * *  cd /opt/app && sh scripts/backup.sh >> backups/backup.log 2>&1
```

The script checks that the dump is complete and the archives are valid, and fails loudly otherwise. It dumps PostgreSQL with `pg_dump` (the stack has no Redis).
`.env.prod` is deliberately not in the backup. **Test a restore** on a spare server before you need it:

```sh
sh scripts/restore.sh backups/20261113-021500     # asks for "yes"; replaces database, uploads and OAuth keys
```

The audit log lives in the database, so it is in the backup. It is append-only: the database refuses to change or delete its rows, so a restore is the only way to roll it back.

## 7. Operate

| Task | Command |
|------|---------|
| Status | `dcp ps` (all `healthy`) |
| Logs | `dcp logs -f --tail 100 nginx php` |
| Restart one service | `dcp restart php` |
| Artisan | `dcp exec php php artisan <command>` |
| Sync permissions after an upgrade | `dcp exec php php artisan permissions:sync` (add `--prune` to remove undeclared ones) |
| Failed queue jobs | `dcp exec php php artisan queue:failed` |
| Stop | `dcp down` (volumes stay; never add `-v` unless you mean to delete the data) |

Disk: uploads and the database grow (the audit log most of all); `docker system df` and `df -h` monthly. Updating: [UPGRADE.md](UPGRADE.md).

## Hardening notes

- The production CSP drops `unsafe-eval` and `unsafe-inline` scripts for the admin.
- HSTS is sent on every HTTPS response. Do not enable it for a domain you cannot keep on HTTPS.
- The API limits requests: 300 a minute per signed-in account, 60 a minute per address for anonymous reads, 10 a minute per address and email for sign-in. nginx passes the real client address only from `TRUSTED_PROXY_CIDR`.
- Only 22, 80 and 443 should be reachable from the internet. PostgreSQL is not published; verify with `ss -ltnp`.
- Rotate the database password and the superadmin password if `.env.prod` was ever exposed (change the file, then `up -d` and, for the superadmin, re-run the seeder).
