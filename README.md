# Zest CMP for WordPress

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-5.2%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)

WordPress plugin for [Zest](https://cookiezest.com/) — a lightweight, zero-dependency GDPR/CCPA cookie consent toolkit with Shadow DOM isolation, script blocking, and cookie interception.

> **Looking for docs as a site owner?** See the [knowledge base](https://cookiezest.com/docs/) or the WordPress.org readme ([readme.txt](readme.txt)).

## What it does

Injects the [Zest JS bundle](https://github.com/freshjuice-dev/zest) into your site and adds a **Settings → Cookie Consent** screen where you configure everything without touching code:

- **Consent banner, settings modal, floating widget** — fully Shadow DOM encapsulated
- **Script blocking** — four modes (safe / manual / strict / doomsday), known trackers auto-detected
- **Cookie & storage interception** — `document.cookie`, `localStorage`, `sessionStorage` writes queue until consent
- **Do Not Track / GPC** — three behaviors: auto-reject, preselect, or ignore
- **Geo / jurisdiction gating** — GDPR banner in the EU, nothing elsewhere (opt-in, via zest-geo gateway)
- **Custom theme** — accent color, corner radius, and a full 5-slot custom palette
- **12 languages** with browser auto-detection
- **Live preview** of the banner inside the settings screen

## Requirements

- WordPress 5.2+
- PHP 7.4+

## Installation

1. Download the latest release and upload `zest-cmp` to `/wp-content/plugins/`, or install through the WordPress plugins screen
2. Activate the plugin
3. Go to **Settings → Cookie Consent**

## Development

```bash
# Local WordPress environment (http://localhost:8888, wp-admin login: zest/zest)
docker compose up -d

# Rebuild the bundled JS from the sibling zest repo
cd ../zest && npm run build && cd -
./scripts/build.sh
```

See [DEVELOPMENT.md](DEVELOPMENT.md) for the full dev guide (three-environment testing, translations, PHP compatibility checks).

## Structure

```
zest-cmp.php                     # Main plugin file
includes/
  class-zest-cmp-settings.php    # Admin: Overview/Settings tabs, live preview
  class-zest-cmp-enqueue.php     # Frontend: bundle enqueue + ZestConfig output
uninstall.php                    # Drops plugin options on uninstall
languages/zest-cmp.pot           # Translation template
scripts/build.sh                 # Copies the Zest bundle from ../zest into dist/
```

`dist/` is gitignored — it is built on deploy (see `.github/workflows/deploy.yml`).

## License

GPL-3.0-or-later. The bundled Zest JS library is MIT-licensed.
