---
name: ci-check
description: Run the same checks as CI locally before pushing — PHP syntax lint, PHPCS, PHPUnit, ESLint, and Jest. Use when asked to "run CI", "check before pushing", "run the checks", or verify the branch is green.
---

# Run CI checks locally

Reproduce `.github/workflows/ci.yml` on the local machine so failures are caught before they hit GitHub. Target environment is **PHP 8.0 + WordPress latest** (CI pins these; locally use whatever PHP/Node is installed and note any version drift).

## Prerequisites (install once)

```bash
composer install          # dev deps: phpunit, phpcs, wpcs
npm ci                    # JS deps: eslint, jest
```

PHPUnit needs the WordPress test suite. If `/tmp/wordpress-tests-lib` is missing, install it (matches the CI step):

```bash
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest true
```

## The checks (run all; report each pass/fail)

Run them in this order and don't stop at the first failure — collect every result so the user sees the full picture.

1. **PHP syntax check (lint)** — mirrors CI exactly:
   ```bash
   find . \( -path ./vendor -o -path ./node_modules \) -prune -o \
     -name '*.php' -print0 | xargs -0 -n1 -P4 php -l
   ```
2. **PHPCS** (coding standards, `.phpcs.xml`):
   ```bash
   composer run-script phpcs
   ```
   Auto-fixable violations: `composer run-script phpcbf`.
3. **PHPUnit**:
   ```bash
   composer run-script test
   ```
4. **ESLint** (`frontend/src`):
   ```bash
   npm run lint
   ```
   Auto-fixable: `npm run lint:fix`.
5. **Jest** (React UI tests under `frontend/__tests__/`):
   ```bash
   npm test -- --passWithNoTests
   ```

## Reporting

Summarize as a checklist (✅/❌ per check). For any failure, show the relevant output and the fix command (`phpcbf` / `lint:fix`) when one applies. Only report "ready to push" when all five pass.
