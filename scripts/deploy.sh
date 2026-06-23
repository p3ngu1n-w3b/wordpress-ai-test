#!/usr/bin/env bash
#
# Deploy the static snapshot of the WordPress site to Netlify.
#
#   ./scripts/deploy.sh           # build + export + deploy a draft preview
#   ./scripts/deploy.sh --prod    # build + export + deploy to production
#   ./scripts/deploy.sh --skip-build   # reuse the current dist/ as-is
#
# WordPress (PHP + MySQL) cannot run on Netlify, so we deploy a static export
# of the rendered site. To publish a *different* WordPress build, change
# site-config/site.json (or the theme) and just re-run this script — it rebuilds
# the site, re-exports it, and pushes the new snapshot to the same Netlify site.
#
set -euo pipefail

cd "$(dirname "$0")/.."

OUT_DIR="dist"
PROD=0
SKIP_BUILD=0
for arg in "$@"; do
  case "$arg" in
    --prod) PROD=1 ;;
    --skip-build) SKIP_BUILD=1 ;;
    *) echo "Unknown option: $arg" >&2; exit 1 ;;
  esac
done

# Resolve a Netlify CLI invocation (local install, global, or npx).
if command -v netlify >/dev/null 2>&1; then
  NETLIFY="netlify"
elif command -v npx >/dev/null 2>&1; then
  NETLIFY="npx --yes netlify-cli"
else
  echo "Error: Netlify CLI not found and npx is unavailable." >&2
  echo "Install Node.js (which provides npx) or 'npm i -g netlify-cli'." >&2
  exit 1
fi

if [[ "$SKIP_BUILD" -eq 0 ]]; then
  echo "[deploy] Building/refreshing the WordPress site ..."
  ./scripts/build.sh
  echo "[deploy] Exporting static snapshot ..."
  ./scripts/export.sh "$OUT_DIR"
fi

if [[ ! -f "${OUT_DIR}/index.html" ]]; then
  echo "Error: ${OUT_DIR}/index.html not found. Run ./scripts/export.sh first." >&2
  exit 1
fi

echo "[deploy] Checking Netlify authentication ..."
if ! $NETLIFY status >/dev/null 2>&1; then
  echo "You are not logged in to Netlify (or no site is linked)." >&2
  echo "  - Interactive login:  $NETLIFY login" >&2
  echo "  - Or set a token:     export NETLIFY_AUTH_TOKEN=..." >&2
  echo "  - Then link a site:   $NETLIFY link   (or '$NETLIFY init' to create one)" >&2
  exit 1
fi

DEPLOY_ARGS=(deploy --dir "$OUT_DIR")
if [[ "$PROD" -eq 1 ]]; then
  DEPLOY_ARGS+=(--prod)
  echo "[deploy] Deploying ${OUT_DIR}/ to PRODUCTION ..."
else
  echo "[deploy] Deploying ${OUT_DIR}/ as a draft preview ..."
fi

$NETLIFY "${DEPLOY_ARGS[@]}"
