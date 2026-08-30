#!/usr/bin/env bash
#
# Build script for Zest Cookie Consent WordPress plugin.
#
# Copies the Zest JS bundle from the sibling repo (../zest) into
# this plugin's dist/ directory, and generates a versioned .pot file
# if wp-cli is available.
#
# Usage: ./scripts/build.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
ZEST_REPO="$(cd "$PLUGIN_DIR/../zest" && pwd)"
DIST_DIR="$PLUGIN_DIR/dist"

echo "Zest Cookie Consent — build"
echo "Plugin dir: $PLUGIN_DIR"
echo "Zest repo:   $ZEST_REPO"
echo ""

# Check that the Zest repo exists
if [ ! -d "$ZEST_REPO/dist" ]; then
  echo "ERROR: Zest dist/ not found at $ZEST_REPO/dist"
  echo "Run 'npm run build' in the zest repo first."
  exit 1
fi

# Read Zest version from package.json
ZEST_VERSION=$(node -e "console.log(require('$ZEST_REPO/package.json').version)" 2>/dev/null || echo "")
if [ -z "$ZEST_VERSION" ]; then
  echo "ERROR: Could not read Zest version from package.json"
  exit 1
fi

echo "Zest version: $ZEST_VERSION"

# Create dist directory
mkdir -p "$DIST_DIR"

# Copy the bundles
echo ""
echo "Copying bundles..."

# Full bundle (all 12 languages)
cp "$ZEST_REPO/dist/zest.min.js" "$DIST_DIR/zest.min.js"
echo "  zest.min.js (all 12 languages)"

# Per-language bundles
for lang in en de es fr it pt nl pl uk ru ja zh; do
  src="$ZEST_REPO/dist/zest.${lang}.min.js"
  if [ -f "$src" ]; then
    cp "$src" "$DIST_DIR/zest.${lang}.min.js"
    echo "  zest.${lang}.min.js"
  fi
done

# Headless ESM
if [ -f "$ZEST_REPO/dist/zest.headless.esm.min.js" ]; then
  cp "$ZEST_REPO/dist/zest.headless.esm.min.js" "$DIST_DIR/zest.headless.esm.min.js"
  echo "  zest.headless.esm.min.js"
fi

# Type definitions
if [ -f "$ZEST_REPO/dist/zest.d.ts" ]; then
  cp "$ZEST_REPO/dist/zest.d.ts" "$DIST_DIR/zest.d.ts"
  echo "  zest.d.ts"
fi

# Write a version manifest
echo "$ZEST_VERSION" > "$DIST_DIR/VERSION"
echo "  VERSION ($ZEST_VERSION)"

echo ""

# Regenerate the translation template when wp-cli is reachable (host or wp-cli container)
if command -v wp >/dev/null 2>&1; then
  wp i18n make-pot "$PLUGIN_DIR" "$PLUGIN_DIR/languages/zest-cmp.pot" --domain=zest-cmp 2>/dev/null \
    && echo "  languages/zest-cmp.pot" \
    || echo "  languages/zest-cmp.pot (skipped - wp-cli unavailable)"
elif docker compose -f "$PLUGIN_DIR/docker-compose.yml" ps -q wp-cli >/dev/null 2>&1 \
     && [ -n "$(docker compose -f "$PLUGIN_DIR/docker-compose.yml" ps -q wp-cli)" ]; then
  if docker compose -f "$PLUGIN_DIR/docker-compose.yml" exec -T wp-cli \
       wp i18n make-pot /var/www/html/wp-content/plugins/zest-cmp /tmp/zest-cmp.pot --domain=zest-cmp --allow-root >/dev/null 2>&1 \
    && docker compose -f "$PLUGIN_DIR/docker-compose.yml" cp wp-cli:/tmp/zest-cmp.pot "$PLUGIN_DIR/languages/zest-cmp.pot" >/dev/null 2>&1; then
    echo "  languages/zest-cmp.pot"
  else
    echo "  languages/zest-cmp.pot (skipped - wp-cli container failed)"
  fi
else
  echo "  languages/zest-cmp.pot (skipped - wp-cli unavailable)"
fi

echo "Build complete. $(ls "$DIST_DIR" | wc -l | tr -d ' ') files in dist/."