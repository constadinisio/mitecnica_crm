#!/usr/bin/env bash
# Compile the Tailwind CSS bundle. Requires node/npm installed.
set -euo pipefail

HERE="$(cd "$(dirname "$0")/../crm" && pwd)"
cd "$HERE"

if [ ! -d node_modules ]; then
  echo "Installing Tailwind..."
  npm install
fi

echo "Building output.css..."
npx tailwindcss \
  -c ./tailwind.config.js \
  -i ./public/assets/css/input.css \
  -o ./public/assets/css/output.css \
  --minify

echo "Done: crm/public/assets/css/output.css"
