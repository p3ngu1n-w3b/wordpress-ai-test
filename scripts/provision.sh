#!/usr/bin/env bash
#
# Provisioning entrypoint. Runs inside the wp-cli container.
#
# 1. Waits for WordPress core files / DB to be ready.
# 2. Installs WordPress (idempotent).
# 3. Activates the AI Site theme.
# 4. Installs configured plugins.
# 5. Hands off to provision.php for the content-heavy work (media, pages,
#    menus, theme mods, posts) via `wp eval-file`.
#
set -euo pipefail

CONFIG="/site-config/site.json"
PHP_PROVISIONER="/scripts/provision.php"

log() { printf '\033[0;36m[provision]\033[0m %s\n' "$*"; }
err() { printf '\033[0;31m[provision:error]\033[0m %s\n' "$*" >&2; }

wp() { command wp --path=/var/www/html --allow-root "$@"; }

# Read a value out of the JSON config using PHP (no jq dependency).
cfg() {
  php -r '$c=json_decode(file_get_contents($argv[1]),true); $keys=array_slice($argv,2); $v=$c; foreach($keys as $k){ if(is_array($v)&&array_key_exists($k,$v)){$v=$v[$k];}else{$v="";break;} } if(is_array($v)){echo "";}else{echo $v;}' "$CONFIG" "$@"
}

if [[ ! -f "$CONFIG" ]]; then
  err "Config file $CONFIG not found."
  exit 1
fi

# --- Wait for wp-config.php (created by the wordpress container) ---
log "Waiting for WordPress to be configured..."
for i in $(seq 1 60); do
  if [[ -f /var/www/html/wp-config.php ]] && wp db check >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

if ! wp db check >/dev/null 2>&1; then
  err "Database not reachable / WordPress not configured in time."
  exit 1
fi

# --- Install WordPress core (idempotent) ---
SITE_TITLE="$(cfg site title)"
SITE_URL="$(cfg site url)"
[[ -z "$SITE_URL" ]] && SITE_URL="http://localhost:${WORDPRESS_PORT:-8080}"
ADMIN_USER="$(cfg site admin user)";  [[ -z "$ADMIN_USER" ]] && ADMIN_USER="${WORDPRESS_ADMIN_USER:-admin}"
ADMIN_EMAIL="$(cfg site admin email)"; [[ -z "$ADMIN_EMAIL" ]] && ADMIN_EMAIL="${WORDPRESS_ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASS="${WORDPRESS_ADMIN_PASSWORD:-admin}"

if wp core is-installed >/dev/null 2>&1; then
  log "WordPress already installed; updating core URL/title."
  wp option update blogname "$SITE_TITLE" >/dev/null
  wp option update siteurl "$SITE_URL" >/dev/null
  wp option update home "$SITE_URL" >/dev/null
else
  log "Installing WordPress core..."
  wp core install \
    --url="$SITE_URL" \
    --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" \
    --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" \
    --skip-email
fi

# --- Locale / timezone ---
LANG_CODE="$(cfg site language)"
if [[ -n "$LANG_CODE" && "$LANG_CODE" != "en_US" ]]; then
  log "Installing language: $LANG_CODE"
  wp language core install "$LANG_CODE" >/dev/null 2>&1 || true
  wp site switch-language "$LANG_CODE" >/dev/null 2>&1 || true
fi
TZ_STRING="$(cfg site timezone)"
[[ -n "$TZ_STRING" ]] && wp option update timezone_string "$TZ_STRING" >/dev/null 2>&1 || true

# --- Pretty permalinks ---
log "Enabling pretty permalinks."
wp rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true

# --- Activate theme ---
log "Activating ai-site theme."
wp theme activate ai-site

# --- Install configured plugins ---
PLUGIN_COUNT="$(php -r '$c=json_decode(file_get_contents($argv[1]),true); echo isset($c["plugins"])&&is_array($c["plugins"])?count($c["plugins"]):0;' "$CONFIG")"
if [[ "$PLUGIN_COUNT" -gt 0 ]]; then
  while IFS= read -r plugin; do
    [[ -z "$plugin" ]] && continue
    log "Installing plugin: $plugin"
    wp plugin install "$plugin" --activate >/dev/null 2>&1 || err "Could not install plugin $plugin (continuing)."
  done < <(php -r '$c=json_decode(file_get_contents($argv[1]),true); foreach(($c["plugins"]??[]) as $p){echo $p,"\n";}' "$CONFIG")
fi

# --- Content, media, menus, theme mods (PHP) ---
log "Applying site content from config..."
wp eval-file "$PHP_PROVISIONER"

# --- Flush rewrite rules so pages resolve ---
wp rewrite flush --hard >/dev/null 2>&1 || true

log "Done. Site is available at: $SITE_URL"
log "Admin login: $SITE_URL/wp-admin  (user: $ADMIN_USER)"
