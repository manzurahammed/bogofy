#!/usr/bin/env bash
# Build the plugin zip and release it to WordPress.org SVN, in one command.
#   Run:  bash bin/release.sh
# Page images (icon/banner/screenshots) live in <plugin>/.wporg-assets/ (not shipped).
# The SVN working copy is kept OUTSIDE the git repo at ~/plugin-svn/<slug>.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"; cd "$ROOT"
USER="manzurahammed"

# Detect slug + version from the plugin itself
MAIN="$(grep -rls --include='*.php' -m1 'Plugin Name:' . --exclude-dir=vendor --exclude-dir=node_modules | head -1)"
SLUG="$(grep -m1 'Text Domain:' "$MAIN" | sed 's/.*Text Domain:[[:space:]]*//' | tr -d '\r ')"
VERSION="$(grep -i '^Stable tag:' readme.txt | head -1 | sed 's/.*:[[:space:]]*//' | tr -d '\r ')"
[ -n "$SLUG" ] && [ -n "$VERSION" ] || { echo "ERROR: could not detect slug/version"; exit 1; }
echo "==> $SLUG  v$VERSION"

# Build a clean production zip (production autoloader, no dev deps)
echo "==> Building zip"
composer install --no-dev --optimize-autoloader --quiet
bash bin/build-release.sh >/dev/null
composer install --quiet; composer dump-autoload --quiet
ZIP="$ROOT/$SLUG.zip"; [ -f "$ZIP" ] || { echo "ERROR: build produced no $ZIP"; exit 1; }

# Deploy to SVN (working copy outside the git repo)
WC="$HOME/plugin-svn/$SLUG"; SVN_URL="https://plugins.svn.wordpress.org/$SLUG"
echo "==> Checkout/update $WC"
if [ -d "$WC/.svn" ]; then svn update "$WC"; else svn checkout --force "$SVN_URL" "$WC"; fi

echo "==> Stage trunk from zip"
TMP="$(mktemp -d)"; unzip -q "$ZIP" -d "$TMP"
SRC="$TMP/$SLUG"; [ -d "$SRC" ] || SRC="$(find "$TMP" -mindepth 1 -maxdepth 1 -type d | head -1)"
rm -rf "$WC/trunk"; mkdir -p "$WC/trunk"; cp -R "$SRC/." "$WC/trunk/"; rm -rf "$TMP"

echo "==> Copy page images from .wporg-assets/"
if [ -d "$ROOT/.wporg-assets" ] && [ -n "$(ls -A "$ROOT/.wporg-assets" 2>/dev/null)" ]; then
  mkdir -p "$WC/assets"; cp -R "$ROOT/.wporg-assets/." "$WC/assets/"
fi

find "$WC" -name '.DS_Store' -delete
cd "$WC"
svn add --force trunk assets >/dev/null 2>&1 || true
svn status | awk '/^!/ {print $2}' | xargs -r svn delete >/dev/null 2>&1 || true

echo "==> Commit + tag $VERSION"
svn ci -m "$SLUG $VERSION" --username "$USER"
svn info "^/tags/$VERSION" >/dev/null 2>&1 || { svn cp trunk "tags/$VERSION" && svn ci -m "Tag $VERSION" --username "$USER"; }
echo "DONE -> https://wordpress.org/plugins/$SLUG"
