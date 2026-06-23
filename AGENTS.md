# AGENTS.md

## Cursor Cloud specific instructions

This repo is an **AI WordPress site automation toolkit**. It is entirely
**Docker-based** — there is no language package manager, no `node_modules`, and
no committed test suite. Everything runs through Docker Compose. See `README.md`
for the full product description and standard commands; this section only
captures non-obvious, durable caveats for running it in the cloud VM.

### Services (`docker-compose.yml`)
- `db` — MariaDB 11 (internal, port 3306).
- `wordpress` — official `wordpress:php8.3-apache`, published on
  `http://localhost:8080` (`${WORDPRESS_PORT}`). This is real WordPress;
  `/wp-admin` works normally. Default admin: `admin` / `admin`.
- `wpcli` — one-shot provisioner (`wordpress:cli-php8.3`) that runs
  `scripts/provision.sh` + `scripts/provision.php` to install core and apply
  `site-config/site.json`. It is **idempotent** — re-running updates in place.

### Running / building (standard commands live in `README.md`)
- Build + provision: `./scripts/build.sh` (use `--rebuild` to wipe volumes,
 `--setup-only` to install WordPress without theme/content).
- Stop: `./scripts/down.sh` (`--volumes` also deletes the DB/uploads).
- `build.sh` auto-creates `.env` from `.env.example` if missing.

### Netlify deployment (static export)
- WordPress (PHP + MySQL) can't run on Netlify, so the site is deployed as a
 **static snapshot**. The pipeline is: build locally → export → upload.
- `./scripts/export.sh` crawls the running site (`http://localhost:${WORDPRESS_PORT}`)
 with `wget` into `./dist/`, rewriting links to be host-independent and
 capturing the themed 404. Requires the stack to be up (`./scripts/build.sh`).
- `./scripts/deploy.sh [--prod] [--skip-build]` runs build + export + `netlify
 deploy` (via `npx netlify-cli`). Needs Netlify auth (`NETLIFY_AUTH_TOKEN` or
 `npx netlify login`) and a linked site (`npx netlify link`/`init`).
- `netlify.toml` sets `publish = "dist"` with **no** build command (Netlify's CI
 has no Docker/PHP). `dist/` is git-ignored.
- Changing the published site = edit `site-config/site.json`/theme, then re-run
 `./scripts/deploy.sh --prod` (re-export overwrites `dist/`).
- Preview the export with no WordPress running:
 `cd dist && python3 -m http.server 5000`.

### Lint / tests
- There is **no automated test suite or linter config**. Validate changes with:
  - Shell: `bash -n scripts/*.sh`
  - PHP (no local PHP; run in the WP image):
    `docker run --rm -v "$PWD":/src -w /src wordpress:cli-php8.3 sh -c 'for f in scripts/provision.php $(find theme -name "*.php"); do php -l "$f"; done'`

### Non-obvious gotchas
- **Docker daemon must be running.** systemd is not available in this VM, so the
  daemon is started manually (the startup/update script backgrounds `dockerd`).
  If `docker info` fails, start it yourself: `sudo nohup dockerd >/tmp/dockerd.log 2>&1 &`
  then wait a few seconds.
- The daemon uses the **`fuse-overlayfs` storage driver** (configured in
  `/etc/docker/daemon.json`) and **iptables-legacy** — this is the required
  Docker-in-Docker workaround for this environment. Do not switch to overlay2.
- If you hit `permission denied` on the Docker socket, run
  `sudo chmod 666 /var/run/docker.sock` (or prefix docker commands with `sudo`).
- `theme/ai-site/` is **bind-mounted** into the container, so theme edits show up
  live without rebuilding. WordPress core, plugins, and uploads live in the
  `wp_data` Docker volume (persisted), not in the repo — so reinstalling/editing
  plugins is not reflected in git, and a `--rebuild` wipes them.
- Premium theme/plugin/import files under `site-config/{themes,plugins,import}/`
  are git-ignored and not present by default; the bundled `ai-site` builtin theme
  flow works with no extra files.
