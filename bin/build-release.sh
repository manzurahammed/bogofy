#!/usr/bin/env bash
#
# Build a WordPress.org-ready release zip that honors .distignore.
# No wp-cli / Docker required — uses rsync + zip only.
#
# Usage:
#   composer install --no-dev   # make sure vendor/ is production-only
#   npm run build               # make sure assets/build is current
#   bash bin/build-release.sh
#
set -euo pipefail

SLUG="bogofy"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BUILD="$(mktemp -d)"
DEST="$BUILD/$SLUG"

mkdir -p "$DEST"

# rsync treats lines starting with '#' or ';' in an exclude-from file as comments,
# so .distignore can be used directly.
rsync -a \
  --exclude-from="$ROOT/.distignore" \
  --exclude='.git' \
  "$ROOT/" "$DEST/"

mkdir -p "$ROOT/build"
ZIP="$ROOT/build/${SLUG}.zip"
rm -f "$ZIP"
( cd "$BUILD" && zip -rqX "$ZIP" "$SLUG" )

rm -rf "$BUILD"

echo "Built: $ZIP"
echo "Contents (first 40 entries):"
# `sed` reads all input (unlike `head`, which closes the pipe early and makes
# `unzip` fail with SIGPIPE under `set -o pipefail`). The `|| true` is a guard.
unzip -l "$ZIP" | sed -n '2,41p' || true
