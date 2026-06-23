#!/usr/bin/env bash
#
# Static export: crawl the running WordPress site into a self-contained,
# host-independent static snapshot that can be deployed to Netlify (or any
# static host).
#
#   ./scripts/export.sh            # export http://localhost:<port> into ./dist
#   ./scripts/export.sh out_dir    # export into a custom directory
#
# WordPress itself (PHP + MySQL) cannot run on Netlify, so we publish a static
# copy of the rendered site instead. Re-running ./scripts/build.sh with a new
# site-config/site.json (or a different theme) and then re-running this script
# regenerates the snapshot for the new build.
#
set -euo pipefail

cd "$(dirname "$0")/.."

OUT_DIR="${1:-dist}"

# Resolve the port the site is served on (matches docker-compose / build.sh).
PORT="8080"
if [[ -f .env ]]; then
  ENV_PORT="$(grep -E '^WORDPRESS_PORT=' .env | cut -d= -f2 || true)"
  [[ -n "${ENV_PORT:-}" ]] && PORT="$ENV_PORT"
fi
PORT="${WORDPRESS_PORT:-$PORT}"

SRC="http://localhost:${PORT}"
ORIGIN="http://localhost:${PORT}"

if ! command -v wget >/dev/null 2>&1; then
  echo "Error: wget is required for the static export but was not found." >&2
  echo "Install it (e.g. 'apt-get install wget' / 'brew install wget')." >&2
  exit 1
fi

echo "[export] Checking that WordPress is running at ${SRC} ..."
if ! curl -fsS -o /dev/null "${SRC}/"; then
  echo "Error: could not reach ${SRC}." >&2
  echo "Start/build the site first:  ./scripts/build.sh" >&2
  exit 1
fi

echo "[export] Clearing ${OUT_DIR}/ ..."
rm -rf "${OUT_DIR}"
mkdir -p "${OUT_DIR}"

echo "[export] Crawling site into ${OUT_DIR}/ ..."
# --recursive/--page-requisites : grab every linked page plus CSS/JS/images.
# --convert-links/--adjust-extension : rewrite links to the saved (relative)
#   filenames so the snapshot works from any directory / domain.
# --reject-regex : skip dynamic-only endpoints that don't belong in a static site.
# A non-zero exit from wget is tolerated (e.g. a stray 404 on a discovery link).
wget \
  --recursive --level=inf \
  --page-requisites \
  --convert-links \
  --adjust-extension \
  --no-host-directories \
  --restrict-file-names=windows \
  --reject-regex='(/wp-json|/wp-admin|/wp-login|xmlrpc|/feed|trackback|replytocom)' \
  -e robots=off \
  --directory-prefix="${OUT_DIR}" \
  "${SRC}/" || true

if [[ ! -f "${OUT_DIR}/index.html" ]]; then
  echo "Error: export did not produce ${OUT_DIR}/index.html." >&2
  exit 1
fi

# Capture the theme's 404 template (Netlify serves this for unknown paths).
echo "[export] Capturing themed 404 page ..."
curl -fsS "${SRC}/404-not-found-$(date +%s)/" -o "${OUT_DIR}/404.html" 2>/dev/null \
  || curl -s "${SRC}/404-not-found-$(date +%s)/" -o "${OUT_DIR}/404.html" 2>/dev/null \
  || true
[[ -s "${OUT_DIR}/404.html" ]] || rm -f "${OUT_DIR}/404.html"

echo "[export] Rewriting any remaining absolute URLs to root-relative ..."
# wget converts links it downloaded; this catches the rest (canonical tags,
# REST/oEmbed discovery links, the custom logo, emoji loader, etc.) so the
# snapshot has no hard-coded localhost references.
ESC_ORIGIN="$(printf '%s' "$ORIGIN" | sed 's/[.[\*^$/]/\\&/g')"
find "${OUT_DIR}" -type f \( -name '*.html' -o -name '*.css' -o -name '*.js' -o -name '*.xml' \) -print0 \
  | xargs -0 sed -i "s#${ESC_ORIGIN}##g"

FILE_COUNT="$(find "${OUT_DIR}" -type f | wc -l | tr -d ' ')"
DIR_SIZE="$(du -sh "${OUT_DIR}" | cut -f1)"

echo ""
echo "============================================================"
echo " Static site exported to: ${OUT_DIR}/  (${FILE_COUNT} files, ${DIR_SIZE})"
echo " Preview locally:  (cd ${OUT_DIR} && python3 -m http.server 5000)"
echo " Deploy to Netlify:  ./scripts/deploy.sh        (draft preview)"
echo "                     ./scripts/deploy.sh --prod  (production)"
echo "============================================================"
