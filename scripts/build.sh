#!/usr/bin/env bash
#
# One-command build: start the stack and provision the site from
# site-config/site.json.
#
#   ./scripts/build.sh            # build / update the site
#   ./scripts/build.sh --rebuild  # wipe data first, then build from scratch
#
set -euo pipefail

cd "$(dirname "$0")/.."

# Resolve docker compose binary (v2 plugin or legacy).
if docker compose version >/dev/null 2>&1; then
  DC="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  DC="docker-compose"
else
  echo "Error: Docker Compose is not installed." >&2
  exit 1
fi

REBUILD=0
for arg in "$@"; do
  case "$arg" in
    --rebuild) REBUILD=1 ;;
    *) ;;
  esac
done

if [[ ! -f .env ]]; then
  echo "[build] Creating .env from .env.example"
  cp .env.example .env
fi

if [[ "$REBUILD" -eq 1 ]]; then
  echo "[build] Removing existing containers and volumes..."
  $DC down -v
fi

echo "[build] Starting database and WordPress..."
$DC up -d db wordpress

echo "[build] Running provisioner..."
# Run the one-shot provisioner in the foreground and stream its logs.
$DC run --rm wpcli

PORT="$(grep -E '^WORDPRESS_PORT=' .env | cut -d= -f2 || true)"
PORT="${PORT:-8080}"
echo ""
echo "============================================================"
echo " Site is ready:    http://localhost:${PORT}"
echo " Admin dashboard:  http://localhost:${PORT}/wp-admin"
echo "============================================================"
