# Development

## Three-environment testing

The plugin is tested across three Docker environments — minimum, current, and latest PHP:

| Profile    | Command                                                              | Port | WP   | PHP  | Purpose                         |
|------------|----------------------------------------------------------------------|------|------|------|---------------------------------|
| Default    | `docker compose up -d`                                               | 8888 | 6.8  | 8.4  | Latest WP + latest PHP          |
| Dev        | `docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d`  | 8889 | 6.8  | 8.4  | Daily development               |
| Compat     | `docker compose -f docker-compose.yml -f docker-compose.compat.yml up -d` | 8890 | 5.4  | 7.4  | Minimum WP + minimum PHP        |

Each profile uses its own database volume, so they don't interfere with each other. You can run all three simultaneously.

## Quick start

```bash
# Pick an environment, e.g. default:
docker compose up -d

# Wait for wp-cli to finish installing (~15s)
docker compose logs wp-cli

# Open the site
open http://localhost:8888

# Admin panel
open http://localhost:8888/wp-admin
# Username: admin / Password: admin
```

The plugin is mounted as a volume — edit PHP files and reload the page, no rebuild needed.

## Rebuilding the Zest JS bundle

```bash
cd ../zest && npm run build && cd -
./scripts/build.sh
```

## Translations (i18n)

Admin UI strings live in `languages/zest-cmp.pot` (translation template). It is regenerated automatically by `./scripts/build.sh` via `wp i18n make-pot` — no npm tooling involved. To contribute a translation, copy the `.pot` to `zest-cmp-<locale>.po`, fill in `msgstr`, and compile to `.mo` (e.g. with `wp i18n make-mo languages/`).

The banner/modal texts shown to visitors are baked into the Zest JS bundle (12 languages, auto-selected in the browser) — they come from the [Zest repo](https://github.com/freshjuice-dev/zest), not from WordPress translation files.

## Stopping

```bash
docker compose down
# To wipe the database too:
docker compose down -v
```

## PHP compatibility

Target: PHP 7.4+. The code uses `declare(strict_types=1)`, typed properties, return type declarations, and the null coalescing assignment operator (`??=`). Union types (`bool|string`) are intentionally avoided — they require PHP 8.0+.

Static compatibility check:

```bash
# Install PHPCompatibilityWP once
composer require --dev phpcompatibility/phpcompatibility-wp:"^2.0"

# Check all PHP files against 7.4
vendor/bin/phpcs --standard=PHPCompatibilityWP --runtime-set testVersion 7.4- \
  zest-cmp.php includes/
```
