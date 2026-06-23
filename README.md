# AI WordPress Site Automation

Build a complete, **real WordPress site** from a single config file (plus your
images) — automatically. You describe the site in `site-config/site.json`, drop
your logo/photos into `site-config/images/`, run one command, and you get a
fully provisioned WordPress installation: branded theme, pages, menus, posts,
media, and a polished front page.

Because the output is a normal WordPress install running the real WordPress core,
**nothing about WordPress changes** — the admin dashboard, block/Gutenberg
editor, plugins, users, comments, REST API, and themes all work exactly as they
do on any WordPress site. The automation only *populates* WordPress; it never
replaces it.

You can build on the **bundled `ai-site` theme** or on a **pre-built premium
theme such as [BeTheme](https://themes.muffingroup.com/betheme/)** — including
importing one of its pre-built demo websites. See
[Using a pre-built / premium theme](#using-a-pre-built--premium-theme-eg-betheme).

---

## How it works

```
site-config/site.json   ─┐
site-config/images/*    ─┤
                         ├──▶  scripts/provision.sh + provision.php (WP-CLI)
theme/ai-site/ (theme)  ─┘            │
                                      ▼
                         A live WordPress site (Docker)
                     http://localhost:8080  +  /wp-admin
```

1. **`docker-compose.yml`** runs three services: MariaDB, WordPress
   (php-apache), and a one-shot WP-CLI provisioner.
2. **`theme/ai-site/`** is a standard, modern WordPress theme. Its branding,
   hero, sections, and contact details are read from theme mods (Customizer
   settings), so the AI-generated content stays fully editable in `wp-admin`.
3. **`scripts/provision.sh` + `scripts/provision.php`** read your config and use
   the WordPress API / WP-CLI to install WordPress, import media, set branding,
   create pages & menus, configure the static front page, and publish posts.
   The provisioner is **idempotent** — re-running updates the site in place
   instead of creating duplicates.

---

## Quick start

Requirements: **Docker** + **Docker Compose v2**.

```bash
# 1. (Optional) copy and tweak environment defaults
cp .env.example .env

# 2. Edit your site definition
#    - site-config/site.json   (text, colors, pages, menus, posts…)
#    - site-config/images/     (logo.svg, hero.svg, …)

# 3. Build the site
./scripts/build.sh
```

Then open:

- Site: <http://localhost:8080>
- Admin: <http://localhost:8080/wp-admin> (default `admin` / `admin`)

To rebuild from a clean slate (wipes the database and uploads):

```bash
./scripts/build.sh --rebuild
```

To stop:

```bash
./scripts/down.sh            # keep data
./scripts/down.sh --volumes  # delete data
```

---

## Defining a site (`site-config/site.json`)

Everything the AI needs to build the site lives in one JSON file. A full example
ships in this repo (a fictional coffee roaster). The schema is documented in
[`docs/site-config.schema.json`](docs/site-config.schema.json).

Key sections:

| Key         | What it controls |
|-------------|------------------|
| `site`      | Title, tagline, language, timezone, URL, admin account |
| `theme`     | Which theme to install/activate: built-in, wordpress.org, or premium zip/URL (+ child theme & generic options) |
| `import`    | Import a pre-built/demo website: WXR content, widgets, customizer, options, menu assignment |
| `branding`  | Logo/favicon filenames, brand colors, fonts (colors/fonts apply to the built-in theme) |
| `contact`   | Email, phone, address, social links (shown in footer) |
| `hero`      | Front-page hero heading, subheading, image, CTA buttons |
| `sections`  | Front-page content blocks: `features`, `about`, `gallery`, `cta` |
| `pages`     | Pages to create; mark one with `"front_page": true` |
| `menus`     | `primary` and `footer` menus (link items to pages by `slug`) |
| `posts`     | Blog posts to publish, with optional categories |
| `plugins`   | wordpress.org plugin slugs to install & activate |

Image fields (`branding.logo`, `hero.image`, `sections[].image`,
`sections[].images[]`) reference **filenames inside `site-config/images/`**. They
are imported into the WordPress media library automatically (SVG uploads are
enabled by the theme).

### Minimal example

```json
{
  "site": { "title": "My Bakery", "tagline": "Fresh every morning" },
  "branding": { "colors": { "primary": "#b5651d", "accent": "#f4c430" } },
  "hero": {
    "heading": "Warm bread, baked daily",
    "subheading": "Order online for same-day pickup.",
    "cta": { "label": "Order now", "url": "/shop" }
  },
  "pages": [
    { "slug": "home", "title": "Home", "front_page": true },
    { "slug": "shop", "title": "Shop", "content": "<p>Our menu…</p>" }
  ],
  "menus": {
    "primary": [
      { "title": "Home", "page": "home" },
      { "title": "Shop", "page": "shop" }
    ]
  }
}
```

---

## Publishing the site to Netlify

WordPress itself (PHP + MySQL) can't run on Netlify, **and neither can it build
in Netlify's CI** (the build image has no Docker/PHP/MySQL). So the site is
published as a **static snapshot** of the rendered pages: you build the site
locally, export it to plain HTML/CSS/JS/images under `dist/`, and Netlify simply
serves that folder. The result looks and behaves like the live site for
visitors, and you re-export whenever you change the build.

The generated `dist/` folder is **committed to the repo** — that's what makes
Git-based Netlify deploys work (Netlify has nothing to build, so it just
publishes the committed snapshot). There are two ways to ship it:

### Option A — Git-based deploy (push to deploy)

Connect the repo to Netlify once (Netlify picks up `netlify.toml`, which sets
`publish = "dist"` and an empty build command). After that, updating the live
site is just: regenerate `dist/`, commit, push.

```bash
# 1. Build the WordPress site locally (Docker)
./scripts/build.sh

# 2. Crawl the running site into a self-contained ./dist folder
./scripts/export.sh

# 3. Commit the snapshot and push — Netlify auto-deploys it
git add dist && git commit -m "Update site snapshot" && git push
```

### Option B — CLI deploy (no commit needed)

`scripts/deploy.sh` runs build → export → upload via the Netlify CLI, so you can
deploy without committing `dist/`:

```bash
./scripts/deploy.sh          # draft preview URL
./scripts/deploy.sh --prod   # production URL
```

### Previewing the export locally

The export is host-independent (all links are relative / root-relative), so you
can preview exactly what Netlify will serve:

```bash
./scripts/export.sh
cd dist && python3 -m http.server 5000   # then open http://localhost:5000
```

### Changing to a different / new WordPress build

Because deployment is "build locally → export → upload", switching the published
site to a different build is just the normal workflow followed by a redeploy:

1. Edit `site-config/site.json` (text, colors, pages…) and/or swap the theme
   (the `theme` block — built-in, wordpress.org, or a premium zip/URL), and
   update `site-config/images/` as needed.
2. Regenerate and ship the snapshot — either commit it
   (`./scripts/build.sh && ./scripts/export.sh`, then `git add dist && git commit
   && git push`) or upload it directly (`./scripts/deploy.sh --prod`).

The new snapshot replaces the old one on the **same** Netlify site, so the live
URL always reflects your latest build. To start from a clean WordPress install
instead of updating in place, add `--rebuild` to the build step
(`./scripts/build.sh --rebuild`) before exporting.

### First-time Netlify setup

**Git-based (Option A):** create a Netlify site from this repo (New site → Import
from Git). No build settings are needed — [`netlify.toml`](netlify.toml) already
sets `publish = "dist"` with an empty build command, and `dist/` is committed, so
the first deploy publishes the committed snapshot.

**CLI-based (Option B):** `scripts/deploy.sh` uses the
[Netlify CLI](https://docs.netlify.com/cli/get-started/) via `npx`. Authenticate
and link (or create) a site once:

```bash
npx netlify login          # or: export NETLIFY_AUTH_TOKEN=...
npx netlify link           # connect to an existing site
# or
npx netlify init           # create a new Netlify site
```

> Important: do **not** set a Netlify build command (leave it empty). WordPress
> cannot be built in Netlify's CI, so Netlify must only publish the committed
> `dist/` folder.

---

## Using a pre-built / premium theme (e.g. BeTheme)

The toolkit is theme-agnostic. The recommended flow matches "AI sets up
WordPress → you pick the theme → automation builds the rest":

### 1. Let the AI set up WordPress

```bash
./scripts/build.sh --setup-only
```

This installs and configures a clean WordPress (core, admin user, pretty
permalinks) and then stops — no theme or content yet.

### 2. Choose your theme

Point the `theme` block in `site-config/site.json` at the theme you want. The
`source` can be:

| `source`  | Use it for | Required fields |
|-----------|------------|-----------------|
| `builtin` | The bundled `ai-site` theme | `slug: "ai-site"` |
| `wporg`   | A free wordpress.org theme | `slug` (e.g. `astra`) |
| `zip`     | A premium theme zip you own | `slug`, `zip` (file in `site-config/themes/`) |
| `url`     | A theme zip hosted at a URL | `slug`, `url` |

For **BeTheme** (a premium theme), copy the ready-made example and drop in your
licensed files:

```bash
cp site-config/examples/betheme.json site-config/site.json

# Premium binaries you own (git-ignored, never committed):
#   site-config/themes/betheme.zip          (+ betheme-child.zip)
#   site-config/plugins/js_composer.zip      (bundled page builder, etc.)
#   site-config/plugins/revslider.zip
#   site-config/import/demo-content.xml      (a pre-built website export)
```

Plugins accept either a wordpress.org slug (string) or an object for premium
zips/URLs:

```json
"plugins": [
  "contact-form-7",
  { "source": "zip", "slug": "js_composer", "zip": "js_composer.zip" },
  { "source": "url", "slug": "revslider", "url": "https://example.com/revslider.zip" }
]
```

### 3. Run the automated build

```bash
./scripts/build.sh
```

This installs the theme (and child theme), installs/activates the plugins, then
imports the pre-built website defined under `import` (content via the WordPress
importer, plus optional widgets/customizer/options and menu assignment) and
applies any generic `theme.options`.

### Getting a pre-built website's import files

Premium demos (including BeTheme's pre-built websites) are imported here in a
portable, license-respecting way using standard WordPress exports:

1. Install the theme + the demo you like once (locally or on the vendor sandbox).
2. **Tools → Export → All content** to produce the WXR (`.xml`) file.
3. Optionally export widgets (*Widget Importer/Exporter*) and theme settings
   (*Customizer Export/Import*).
4. Put the files in `site-config/import/` and reference them in `site.json`.

> Premium themes/plugins are licensed; this repo never bundles them. Place your
> own licensed files in `site-config/themes`, `site-config/plugins`,
> `site-config/import` — those paths are git-ignored.

---

## The built-in theme (`theme/ai-site/`)

A hand-written, responsive, accessibility-minded classic theme that supports:

- Custom logo, custom colors & fonts (via Customizer / theme mods)
- Primary + footer nav menu locations, widget areas, sidebars
- A config-driven front page (hero + feature/about/gallery/CTA sections)
- Standard templates: `index`, `single`, `page`, `archive`, `search`, `404`
- Block editor support (`theme.json`, wide/align, editor styles)
- SVG media uploads

Everything the provisioner sets is exposed under **Appearance → Customize → AI
Site Settings**, so you (or the site owner) can keep editing in WordPress after
the automated build — exactly like a normal WordPress site.

---

## Reusing this for a new client/site

1. Replace `site-config/site.json` with the new site's details.
2. Replace the files in `site-config/images/` with the new assets.
3. Run `./scripts/build.sh --rebuild`.

That's the entire workflow: **give the config + images, get a working WordPress
site**.

---

## Project layout

```
.
├── docker-compose.yml          # MariaDB + WordPress + WP-CLI provisioner
├── .env.example                # Ports, DB creds, admin account
├── netlify.toml                # Netlify config (publishes the static export)
├── scripts/
│   ├── build.sh                # One-command build / rebuild
│   ├── down.sh                 # Stop the stack
│   ├── export.sh               # Crawl the live site into ./dist (static)
│   ├── deploy.sh               # Build + export + deploy to Netlify
│   ├── provision.sh            # WP-CLI: install core, theme, plugins
│   └── provision.php           # WP API: media, pages, menus, posts, branding
├── site-config/
│   ├── site.json               # ← your site definition
│   ├── images/                 # ← your logo & photos
│   ├── themes/                 # ← premium theme zips (git-ignored)
│   ├── plugins/                # ← premium plugin zips (git-ignored)
│   ├── import/                 # ← pre-built/demo export files (git-ignored)
│   └── examples/
│       └── betheme.json        # Ready-to-edit config for the BeTheme flow
├── theme/ai-site/              # The bundled built-in WordPress theme
└── docs/
    └── site-config.schema.json # JSON schema for site.json
```

---

## Notes & FAQ

- **Is this still "real" WordPress?** Yes. It runs the official WordPress Docker
  image with MariaDB. Plugins, the block editor, multisite, REST API, etc. are
  all unmodified. The toolkit only automates setup using WP-CLI and the public
  WordPress API.
- **Can I edit content after building?** Yes — log into `/wp-admin` and edit
  pages, posts, menus, and branding (Customizer) like any WordPress site.
- **Can I use a premium theme like BeTheme?** Yes — set `theme.source` to `zip`
  (or `url`) and supply your licensed theme/plugin zips and a demo export. See
  [Using a pre-built / premium theme](#using-a-pre-built--premium-theme-eg-betheme).
  The toolkit installs the theme + plugins and imports the pre-built website; you
  own and provide the licensed files (they are never committed).
- **Production use:** change the admin password and DB credentials in `.env`,
  put the site behind HTTPS, and consider a managed database and object storage
  for uploads. Review/sanitize any SVGs before enabling SVG uploads on a public,
  multi-author site.
```
