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
| `branding`  | Logo/favicon filenames, brand colors, fonts |
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

## The theme (`theme/ai-site/`)

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
├── scripts/
│   ├── build.sh                # One-command build / rebuild
│   ├── down.sh                 # Stop the stack
│   ├── provision.sh            # WP-CLI: install core, theme, plugins
│   └── provision.php           # WP API: media, pages, menus, posts, branding
├── site-config/
│   ├── site.json               # ← your site definition
│   └── images/                 # ← your logo & photos
├── theme/ai-site/              # The generated WordPress theme
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
- **Production use:** change the admin password and DB credentials in `.env`,
  put the site behind HTTPS, and consider a managed database and object storage
  for uploads. Review/sanitize any SVGs before enabling SVG uploads on a public,
  multi-author site.
```
