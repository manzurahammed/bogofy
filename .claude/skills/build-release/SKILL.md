---
name: build-release
description: Build the WordPress.org-ready plugin zip (honoring .distignore) and optionally deploy to the WordPress.org SVN repo. Use when asked to "build the zip", "package the plugin", "cut a release", or "deploy to WordPress.org".
---

# Build & release the plugin

The plugin ships two scripts — prefer them over hand-rolled commands.

- `bin/build-release.sh` — produces a clean `bogofy.zip` (rsync + zip, honoring `.distignore`). No wp-cli/Docker.
- `bin/release.sh` — builds **and** deploys to WordPress.org SVN (checkout/update `~/plugin-svn/bogofy`, stage trunk, commit, tag). Requires SVN commit access.

## Build the zip only (safe, no publishing)

Make dependencies production-clean first, build assets, then package:

```bash
composer install --no-dev --optimize-autoloader   # production autoloader
npm ci && npm run build                            # current admin/frontend assets
bash bin/build-release.sh                          # -> ./bogofy.zip
```

Afterwards, restore dev dependencies so local tooling (phpunit/phpcs) works again:

```bash
composer install && composer dump-autoload
```

The script prints the zip path and a listing of its contents — sanity-check that `vendor/` is present (required at runtime) and that dev/tooling files excluded by `.distignore` (tests, node_modules, config) are **absent**.

## Full release to WordPress.org (publishing — confirm first)

This publishes publicly and is hard to reverse. **Confirm with the user before running**, and check that `readme.txt` `Stable tag:` and the `Version:` header in `bogofy.php` match the intended release version.

```bash
bash bin/release.sh
```

It auto-detects slug (`bogofy`) and version (from `readme.txt` `Stable tag:`), builds a production zip, syncs to the SVN working copy at `~/plugin-svn/bogofy`, copies page assets from `.wporg-assets/`, commits trunk, and creates the `tags/<version>` tag.

## GitHub release (alternative)

Pushing a `v*` tag triggers `.github/workflows/build-release.yml`, which builds the zip and attaches it to a GitHub Release. Use this when you want a GitHub artifact rather than a WordPress.org publish.

## Before any release, bump the version consistently

Version lives in three places — keep them in sync:
- `bogofy.php` header `Version:`
- `readme.txt` `Stable tag:`
- `package.json` / `composer.json` if relevant
