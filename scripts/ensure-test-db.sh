#!/bin/sh
# Pest refreshes "<POSTGRES_DB>_testing"; never the dev database. Idempotent.
set -u
cd "$(dirname "$0")/.."
docker compose exec -T postgres sh -c 'psql -U "$POSTGRES_USER" -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = '"'"'${POSTGRES_DB}_testing'"'"'" | grep -q 1 || psql -U "$POSTGRES_USER" -d postgres -c "CREATE DATABASE \"${POSTGRES_DB}_testing\""' >/dev/null 2>&1
exit 0
