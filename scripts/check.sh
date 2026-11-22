#!/bin/sh
# Final check, run once at the end of a phase (needs the stack up): sh scripts/check.sh
# Quiet: logs to .cache/check.log and prints the last 20 lines of a failing step only.
set -u
cd "$(dirname "$0")/.."
mkdir -p .cache
LOG=.cache/check.log
C="docker compose"

step() {
  name=$1; shift
  if "$@" >"$LOG" 2>&1; then echo "ok   $name"; else echo "FAIL $name"; tail -n 20 "$LOG"; exit 1; fi
}
php_exec() { $C exec -T php "$@"; }

step "compose config (dev)" $C config -q
step "compose config (prod)" env APP_KEY=check $C -f docker-compose.prod.yml --env-file .env.prod.example config -q
for f in scripts/*.sh docker/*/*.sh; do step "sh -n $f" sh -n "$f"; done
[ -n "$($C ps --status running -q php)" ] || { echo "check: stack is down, run: docker compose up -d --build --wait"; exit 1; }
step "nginx -t" $C exec -T nginx nginx -t
step "pint" php_exec vendor/bin/pint --test
step "phpstan" php_exec vendor/bin/phpstan analyse --memory-limit=1G --no-progress
step "pest" php_exec vendor/bin/pest --compact
step "composer audit" php_exec composer audit
step "security:routes" php_exec php artisan security:routes
step "secrets" sh scripts/check-secrets.sh
step "floors" node scripts/check-floors.mjs
# pins in .env match docs/VERSIONS.md
bad=0
for kv in $(grep -E '^[A-Z_]+_VERSION=' .env); do
  k=${kv%%=*}; v=${kv#*=}
  grep -qF "| $v " docs/VERSIONS.md || { echo "MISMATCH $k=$v"; bad=1; }
done
[ "$bad" = 0 ] && echo "ok   versions" || exit 1
echo "check: ok"
