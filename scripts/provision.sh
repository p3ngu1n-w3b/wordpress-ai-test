#!/usr/bin/env bash
#
# Provisioning entrypoint. Runs inside the wp-cli container.
#
# Phases:
#   1. Wait for WordPress core files / DB to be ready.
#   2. Install WordPress core (idempotent).
#   3. (setup phase ends here if AI_SETUP_ONLY=1 — you pick the theme next)
#   4. Install & activate the chosen theme (builtin / wordpress.org / zip / url).
#   5. Install & activate plugins (wordpress.org slugs and/or premium zips/urls).
#   6. Import a pre-built / demo website (WXR content, widgets, customizer, options).
#   7. Hand off to provision.php for config-driven content & branding.
#
set -euo pipefail

CONFIG="/site-config/site.json"
PHP_PROVISIONER="/scripts/provision.php"
THEMES_DIR="/site-config/themes"
PLUGINS_DIR="/site-config/plugins"
IMPORT_DIR="/site-config/import"

log() { printf '\033[0;36m[provision]\033[0m %s\n' "$*"; }
err() { printf '\033[0;31m[provision:error]\033[0m %s\n' "$*" >&2; }

wp() { command wp --path=/var/www/html --allow-root "$@"; }

# Read a scalar value out of the JSON config using PHP (no jq dependency).
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

# --- SETUP-ONLY phase: stop here so the user can choose a theme ---
if [[ "${AI_SETUP_ONLY:-0}" == "1" ]]; then
  log "Setup-only mode: WordPress is installed and ready."
  log "Choose a theme (edit site.json 'theme' block, or pick one in wp-admin),"
  log "then run ./scripts/build.sh to build the rest of the site."
  log "Admin: $SITE_URL/wp-admin  (user: $ADMIN_USER)"
  exit 0
fi

# --- Install & activate the chosen theme ------------------------------------
install_theme() {
  # args: source slug zip url
  local source="$1" slug="$2" zip="$3" url="$4"
  case "$source" in
    ""|builtin)
      log "Activating built-in theme: ${slug:-ai-site}"
      wp theme activate "${slug:-ai-site}"
      ;;
    wporg)
      log "Installing theme from wordpress.org: $slug"
      wp theme install "$slug" --activate --force
      ;;
    zip)
      if [[ -f "$THEMES_DIR/$zip" ]]; then
        log "Installing theme from zip: $zip"
        wp theme install "$THEMES_DIR/$zip" --activate --force
      else
        err "Theme zip not found: $THEMES_DIR/$zip"
        return 1
      fi
      ;;
    url)
      log "Installing theme from URL: $url"
      wp theme install "$url" --activate --force
      ;;
    *)
      err "Unknown theme source: $source"
      return 1
      ;;
  esac
}

THEME_SOURCE="$(cfg theme source)"
THEME_SLUG="$(cfg theme slug)"
THEME_ZIP="$(cfg theme zip)"
THEME_URL="$(cfg theme url)"
# Default to builtin if no theme configured at all.
[[ -z "$THEME_SOURCE" && -z "$THEME_SLUG" ]] && THEME_SOURCE="builtin"
install_theme "$THEME_SOURCE" "$THEME_SLUG" "$THEME_ZIP" "$THEME_URL"

# Optional child theme (activated instead of the parent).
CHILD_SOURCE="$(cfg theme child source)"
if [[ -n "$CHILD_SOURCE" ]]; then
  install_theme "$CHILD_SOURCE" "$(cfg theme child slug)" "$(cfg theme child zip)" "$(cfg theme child url)"
fi

# --- Install & activate plugins ---------------------------------------------
# Emit "source|slug|ref" lines (ref = zip filename or url).
plugin_lines() {
  php -r '
    $c=json_decode(file_get_contents($argv[1]),true);
    foreach(($c["plugins"]??[]) as $p){
      if(is_string($p)){ echo "wporg|$p|\n"; continue; }
      $src=$p["source"]??"wporg"; $slug=$p["slug"]??""; $ref=$p["zip"]??($p["url"]??"");
      echo "$src|$slug|$ref\n";
    }' "$CONFIG"
}

while IFS='|' read -r psrc pslug pref; do
  [[ -z "$psrc" ]] && continue
  case "$psrc" in
    wporg)
      log "Installing plugin (wordpress.org): $pslug"
      wp plugin install "$pslug" --activate --force >/dev/null 2>&1 || err "Could not install plugin $pslug (continuing)."
      ;;
    zip)
      if [[ -f "$PLUGINS_DIR/$pref" ]]; then
        log "Installing plugin (zip): $pref"
        wp plugin install "$PLUGINS_DIR/$pref" --activate --force >/dev/null 2>&1 || err "Could not install plugin zip $pref (continuing)."
      else
        err "Plugin zip not found: $PLUGINS_DIR/$pref (continuing)."
      fi
      ;;
    url)
      log "Installing plugin (url): $pref"
      wp plugin install "$pref" --activate --force >/dev/null 2>&1 || err "Could not install plugin url $pref (continuing)."
      ;;
  esac
done < <(plugin_lines)

# --- Import a pre-built / demo website --------------------------------------
IMPORT_CONTENT="$(cfg import content)"
if [[ -n "$IMPORT_CONTENT" ]]; then
  if [[ -f "$IMPORT_DIR/$IMPORT_CONTENT" ]]; then
    log "Importing demo content: $IMPORT_CONTENT"
    wp plugin is-installed wordpress-importer >/dev/null 2>&1 || wp plugin install wordpress-importer --activate >/dev/null 2>&1 || true
    wp plugin activate wordpress-importer >/dev/null 2>&1 || true
    AUTHORS="$(cfg import authors)"; [[ -z "$AUTHORS" ]] && AUTHORS="create"
    wp import "$IMPORT_DIR/$IMPORT_CONTENT" --authors="$AUTHORS" || err "Content import reported errors (continuing)."
  else
    err "Import content file not found: $IMPORT_DIR/$IMPORT_CONTENT"
  fi
fi

IMPORT_WIDGETS="$(cfg import widgets)"
if [[ -n "$IMPORT_WIDGETS" && -f "$IMPORT_DIR/$IMPORT_WIDGETS" ]]; then
  if wp plugin install widget-importer-exporter --activate >/dev/null 2>&1; then
    log "Importing widgets: $IMPORT_WIDGETS"
    wp widget-importer-exporter import "$IMPORT_DIR/$IMPORT_WIDGETS" >/dev/null 2>&1 \
      || wp wie import "$IMPORT_DIR/$IMPORT_WIDGETS" >/dev/null 2>&1 \
      || err "Widget import not available via WP-CLI (import manually in wp-admin)."
  fi
fi

IMPORT_CUSTOMIZER="$(cfg import customizer)"
if [[ -n "$IMPORT_CUSTOMIZER" && -f "$IMPORT_DIR/$IMPORT_CUSTOMIZER" ]]; then
  log "Customizer export detected ($IMPORT_CUSTOMIZER). Install 'Customizer Export/Import' and import it from wp-admin if not applied automatically."
fi

# --- Content, media, menus, theme mods, generic options (PHP) ---------------
log "Applying site content & settings from config..."
wp eval-file "$PHP_PROVISIONER"

# --- Flush rewrite rules so pages resolve ---
wp rewrite flush --hard >/dev/null 2>&1 || true

log "Done. Site is available at: $SITE_URL"
log "Admin login: $SITE_URL/wp-admin  (user: $ADMIN_USER)"
