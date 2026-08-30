---
name: dev-run
description: Activate and exercise the Bogofy plugin in the local WordPress dev site to test changes by hand. Use when asked to "run the plugin", "activate it", "test in WordPress", or "see the change in the app".
---

# Run the plugin in the local WP dev site

This plugin lives inside a working WordPress install at `/Users/manzurahammed/www/wordpress-dev` (the plugin dir is `wp-content/plugins/buy-one-get-one`). `wp-cli` is available at `/usr/local/bin/wp`. It depends on **WooCommerce** (`Requires Plugins: woocommerce`).

Run all `wp` commands from the WordPress root:

```bash
cd /Users/manzurahammed/www/wordpress-dev
```

## First build the admin/frontend assets

The React UI is built with Vite into `assets/` — build it (or run the watcher) before testing UI changes:

```bash
cd wp-content/plugins/buy-one-get-one
npm ci            # first time only
npm run build     # one-off build
# or: npm run dev # Vite watch mode while iterating on frontend/src
```

## Activate the plugin (and its dependency)

```bash
cd /Users/manzurahammed/www/wordpress-dev
wp plugin is-installed woocommerce && wp plugin activate woocommerce
wp plugin activate buy-one-get-one
wp plugin list --status=active        # confirm both are active
```

## Sanity checks

- Confirm no PHP fatals on load: `wp eval 'echo "loaded\n";'`
- Watch the debug log while clicking through the admin UI (enable `WP_DEBUG`/`WP_DEBUG_LOG` in `wp-config.php` if needed):
  ```bash
  tail -f /Users/manzurahammed/www/wordpress-dev/wp-content/debug.log
  ```
- The BOGO admin screen is registered by the plugin (see `inc/` for the admin page hooks); open it in the browser to verify the React app mounts and the BOGO rule flows work end to end.

## Reset between tests

```bash
wp plugin deactivate buy-one-get-one && wp plugin activate buy-one-get-one
```

Report what you observed (UI mounted, rule created, free product added to cart, or the exact error/log line) rather than just "it runs".
