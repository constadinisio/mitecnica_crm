#!/usr/bin/env bash
# Mi Tecnica CRM -- dev launcher (Linux/macOS)
# Runs pending migrations, then launches API + CRM in background.

set -e
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
echo "==> Project root: $ROOT"

# sanity checks
[[ -f "$ROOT/api/.env" ]] || cp "$ROOT/infra/env/api.env.example" "$ROOT/api/.env"
[[ -f "$ROOT/crm/.env" ]] || cp "$ROOT/infra/env/crm.env.example" "$ROOT/crm/.env"

if [[ ! -d "$ROOT/api/node_modules" ]]; then
  echo "==> Installing API deps (first run)"
  ( cd "$ROOT/api" && npm install )
fi

echo "==> Running pending migrations"
( cd "$ROOT/api" && npx knex migrate:latest )

mkdir -p "$ROOT/.logs"
API_LOG="$ROOT/.logs/api.log"
CRM_LOG="$ROOT/.logs/crm.log"
API_PID="$ROOT/.logs/api.pid"
CRM_PID="$ROOT/.logs/crm.pid"

# stop any previous instance from this launcher
for pidfile in "$API_PID" "$CRM_PID"; do
  if [[ -f "$pidfile" ]]; then
    if kill -0 "$(cat "$pidfile")" 2>/dev/null; then
      kill "$(cat "$pidfile")" 2>/dev/null || true
    fi
    rm -f "$pidfile"
  fi
done

echo "==> Launching API (port 4000) -> $API_LOG"
( cd "$ROOT/api" && npm run dev >"$API_LOG" 2>&1 & echo $! > "$API_PID" )

echo "==> Launching CRM (port 8080) -> $CRM_LOG"
( cd "$ROOT/crm" && php -S localhost:8080 -t public public/router.php >"$CRM_LOG" 2>&1 & echo $! > "$CRM_PID" )

sleep 1
cat <<EOF

Both services launched.
  API: http://localhost:4000/api/v1   (logs: tail -f $API_LOG)
  CRM: http://localhost:8080/login    (logs: tail -f $CRM_LOG)
  Login: admin@mitecnica.local / Admin123!

To stop:
  kill \$(cat $API_PID) \$(cat $CRM_PID)
EOF
