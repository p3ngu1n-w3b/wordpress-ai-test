#!/usr/bin/env bash
#
# Stop the stack. Pass --volumes to also delete the database and uploads.
#
set -euo pipefail
cd "$(dirname "$0")/.."

if docker compose version >/dev/null 2>&1; then
  DC="docker compose"
else
  DC="docker-compose"
fi

if [[ "${1:-}" == "--volumes" ]]; then
  $DC down -v
  echo "[down] Stack stopped and volumes removed."
else
  $DC down
  echo "[down] Stack stopped (data preserved). Use --volumes to wipe data."
fi
