#!/usr/bin/env bash
# Mi Tecnica CRM — local setup (Linux/macOS)
set -euo pipefail

HERE="$(cd "$(dirname "$0")/.." && pwd)"
echo "==> Project root: $HERE"

echo "==> Ensuring .env files exist"
[ -f "$HERE/api/.env" ] || cp "$HERE/infra/env/api.env.example" "$HERE/api/.env"
[ -f "$HERE/crm/.env" ] || cp "$HERE/infra/env/crm.env.example" "$HERE/crm/.env"

echo "==> Installing API deps"
( cd "$HERE/api" && npm install )

echo "==> Running migrations"
( cd "$HERE/api" && npx knex migrate:latest )

echo "==> Running seeds"
( cd "$HERE/api" && npx knex seed:run )

echo ""
echo "Setup OK. Next steps:"
echo "  cd api && npm run dev"
echo "  (in another shell) cd crm && php -S localhost:8080 -t public public/router.php"
echo ""
echo "CRM login: admin@mitecnica.local / Admin123!"
