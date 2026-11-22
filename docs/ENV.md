# Environment variables

Root `.env` (untracked) is copied from `.env.example` by hand (`cp .env.example .env`). Compose reads it for interpolation only;
each service lists its own `environment:` (no `env_file`). Commit only `*.example` files.

| Variable | Used by | Default | Notes |
|----------|---------|---------|-------|
| COMPOSE_PROJECT_NAME | compose | gd-webapp-starter | container names are `${COMPOSE_PROJECT_NAME}-<tech>` |
| COMPOSE_PROFILES | compose | dev | `dev` enables mailpit |
| APP_ENV | php* | local | |
| APP_DOMAIN | nginx, node, php* | gd-webapp-starter.localhost | `admin.` and `api.` subdomains (production also redirects the bare domain to the admin) |
| PHP_VERSION, LARAVEL_VERSION, COMPOSER_VERSION, NODE_VERSION, NGINX_VERSION, POSTGRES_VERSION, MAILPIT_VERSION, VITE_VERSION, REACT_VERSION | compose build/images, the version check (docs/VERSIONS.md) | see docs/VERSIONS.md | must match VERSIONS.md |
| NGINX_HTTP_PORT | nginx | 31080 | published on all interfaces |
| POSTGRES_PORT | postgres | 28306 | bound to 127.0.0.1 |
| MAILPIT_SMTP_PORT / MAILPIT_UI_PORT | mailpit | 31025 / 31026 | bound to 127.0.0.1 |
| ADMIN_DEV_PORT | node, nginx | 31301 | not published; nginx proxies to them |
| POSTGRES_DB / POSTGRES_USER / POSTGRES_PASSWORD | postgres, php* | gd_webapp_starter / gd_webapp_starter / change-me | php gets them as DB_DATABASE / DB_USERNAME / DB_PASSWORD; tests use `<POSTGRES_DB>_testing` |
| UPLOADS_CONTAINER_PATH | php*, nginx, Dockerfile | /var/www/uploads | disk root and volume mount point; nginx serves `/uploads/` |
| UPLOADS_MAX_MB | php*, nginx | 5 | validation, php.ini upload limits and nginx `client_max_body_size` |
| CORS_ALLOWED_ORIGINS | php | empty (closed) | comma separated exact origins allowed to call the API from a browser; credentials are never allowed |
| TRUSTED_PROXY_CIDR | nginx | 172.16.0.0/12 | the only source whose `X-Forwarded-For` the api vhost believes (`set_real_ip_from`), so throttles see the visitor, not the proxy. Production: the load-balancer range only |
| OAUTH_ADMIN_CLIENT_ID | node (admin SPA) | fixed UUID | public PKCE client id; must equal `OAUTH_ADMIN_CLIENT_ID` in `api/.env` (the redirect URI is `<admin origin>/auth/callback`) |

| APP_DEBUG | php* | true | |
| APP_KEY | php* | set by hand (`openssl rand -base64 32`, prefixed `base64:`) | `base64:` 32 bytes |
| LOCALE_DEFAULT / LOCALE_FALLBACK / LOCALE_SUPPORTED | php* | en / en / en | SUPPORTED is comma separated; served by `GET /api/v1/meta/locales` |

`php*` = php, php-queue, php-scheduler.

Read from `api/.env` (not compose; see `api/.env.example`, `config/access.php`): `SUPERADMIN_NAME`, `SUPERADMIN_EMAIL`, `SUPERADMIN_PASSWORD` (seeder skips if empty), `OAUTH_ADMIN_CLIENT_ID` (fixed UUID), `OAUTH_ADMIN_REDIRECT_URI`. Fixed in compose for php*: `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` = database, `LOG_CHANNEL` = stderr, `DB_CONNECTION` = mariadb.

## Production (docker-compose.prod.yml, `.env.prod`)

Everything reaches the containers through compose `environment:`; there is no `.env` file inside the images. A missing required secret stops `docker compose` before anything starts.
Template: `.env.prod.example`.

| Variable | Used by | Notes |
|----------|---------|-------|
| COMPOSE_PROJECT_NAME, APP_NAME, APP_DOMAIN | all | `APP_DOMAIN` is the bare domain; `admin.` and `api.` subdomains are derived from it |
| APP_KEY | php* | required; `base64:` + 32 random bytes; keep a copy outside the server |
| HTTP_PORT / HTTPS_PORT | nginx | published ports, default 80 / 443. The only published ports of the stack |
| TLS_CERT_DIR | nginx | host directory with `fullchain.pem` and `privkey.pem`, mounted read-only; one certificate for the three names |
| POSTGRES_DB / POSTGRES_USER / POSTGRES_PASSWORD | postgres, php* | passwords required; the database is not published |
| UPLOADS_CONTAINER_PATH / UPLOADS_MAX_MB | php*, nginx | uploads volume mount path (same in all three) and size limit |
| SUPERADMIN_NAME / SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD | php* | read by the seeder; email and password required |
| OAUTH_ADMIN_CLIENT_ID | php*, nginx build | public PKCE client id, baked into the admin build; the redirect URI is derived (`https://admin.<domain>/auth/callback`) |
| MAIL_MAILER / MAIL_HOST / MAIL_PORT / MAIL_SCHEME / MAIL_USERNAME / MAIL_PASSWORD / MAIL_FROM_ADDRESS | php* | outgoing mail (`MAIL_MAILER=log` writes to the log instead of sending) |
| LOG_LEVEL | php* | default `warning`; logs go to the container's stderr (rotated, 3 x 10 MB) |
| CORS_ALLOWED_ORIGINS, TRUSTED_PROXY_CIDR | php*, nginx | see the dev table above and docs/SECURITY.md |

Set by compose, not by you: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, cache/session/queue on the database.
