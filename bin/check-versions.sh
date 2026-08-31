#!/usr/bin/env bash
#
# Verify the plugin's version numbers are consistent.
#
# Reads the version from the two authoritative sources:
#   - Plugin header  "Version:"    in bogofy.php
#   - readme.txt     "Stable tag:"
#
# Usage:
#   bash bin/check-versions.sh              # header and readme must match each other
#   bash bin/check-versions.sh <version>    # header and readme must both equal <version>
#
# Exits non-zero (with a clear message) on any mismatch. Used by both the local
# `release` command and the GitHub Actions release workflow so the rule lives in
# exactly one place.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAIN_FILE="$ROOT/bogofy.php"
README="$ROOT/readme.txt"

EXPECTED="${1:-}"

[ -f "$MAIN_FILE" ] || { echo "ERROR: plugin main file not found: $MAIN_FILE" >&2; exit 1; }
[ -f "$README" ]    || { echo "ERROR: readme.txt not found: $README" >&2; exit 1; }

HEADER_VERSION="$(grep -iE '^[[:space:]]*\*?[[:space:]]*Version:' "$MAIN_FILE" \
  | head -1 | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
README_VERSION="$(grep -iE '^[[:space:]]*Stable tag:' "$README" \
  | head -1 | sed -E 's/.*Stable tag:[[:space:]]*//' | tr -d '[:space:]')"

echo "Plugin header version:  ${HEADER_VERSION:-<none>}"
echo "readme.txt stable tag:  ${README_VERSION:-<none>}"
[ -n "$EXPECTED" ] && echo "Requested version:      ${EXPECTED}"

fail=0
[ -n "$HEADER_VERSION" ] || { echo "ERROR: could not read Version from bogofy.php" >&2; fail=1; }
[ -n "$README_VERSION" ] || { echo "ERROR: could not read Stable tag from readme.txt" >&2; fail=1; }

if [ "$HEADER_VERSION" != "$README_VERSION" ]; then
  echo "ERROR: plugin header ($HEADER_VERSION) and readme.txt stable tag ($README_VERSION) do not match." >&2
  fail=1
fi

if [ -n "$EXPECTED" ]; then
  if [ "$HEADER_VERSION" != "$EXPECTED" ]; then
    echo "ERROR: plugin header ($HEADER_VERSION) does not match requested version ($EXPECTED)." >&2
    fail=1
  fi
  if [ "$README_VERSION" != "$EXPECTED" ]; then
    echo "ERROR: readme.txt stable tag ($README_VERSION) does not match requested version ($EXPECTED)." >&2
    fail=1
  fi
fi

if [ "$fail" -eq 0 ]; then
  echo "✓ Versions are consistent."
fi
exit "$fail"
