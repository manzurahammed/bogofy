# Releasing Bogofy

The release pipeline validates locally, then runs everything in GitHub Actions.
Nothing is deployed to WordPress.org from a developer machine.

Releases are cut from the dedicated **`release`** branch. The `main` branch never
triggers a release: merging to `main` only runs normal CI.

```
release <version>  ──►  GitHub Actions (release.yml)
                          validate → test → build → tag → GitHub Release → WP.org deploy → verify
```

## One-time setup

### 1. GitHub Secrets

Add your WordPress.org credentials as **repository secrets**
(Settings → Secrets and variables → Actions → New repository secret):

| Secret           | Value                                   |
| ---------------- | --------------------------------------- |
| `WPORG_USERNAME` | Your WordPress.org username             |
| `WPORG_PASSWORD` | Your WordPress.org account password     |

These are read only by the `deploy` job in `release.yml` and are never printed.

> Recommended: create a GitHub **Environment** named `wordpress-org`
> (Settings → Environments) and add "Required reviewers". The deploy job is
> pinned to that environment, so a human must approve before WordPress.org is
> touched. (Optional — the pipeline works without it.)

### 2. The `release` command

Pick whichever you prefer:

```bash
# a) run the script directly
bash bin/release.sh 4.3.3

# b) via composer
composer release 4.3.3

# c) add a shell alias so you can type `release 4.3.3`
#    (add to ~/.zshrc / ~/.bashrc)
alias release='bash "$(git rev-parse --show-toplevel)"/bin/release.sh'
```

The command uses the GitHub CLI (`gh`) if installed; otherwise it falls back to
the GitHub REST API using `GH_TOKEN` or `GITHUB_TOKEN` (needs `workflow` scope).

## Releasing a new version

```bash
git checkout release
git pull                       # or: git merge main   (bring in what you're releasing)

# 1. Bump the version in BOTH files and commit:
#      - bogofy.php   ->  Version: 4.3.3
#      - readme.txt   ->  Stable tag: 4.3.3
#    (add a matching "= 4.3.3 =" section under == Changelog ==)
git commit -am "Release 4.3.3"
git push

# 2. Trigger the release
release 4.3.3
```

You'll see a summary and a `Continue? [y/N]` prompt. On `y`, the workflow is
dispatched with the **exact commit SHA** you're on. Every job checks out that
SHA and the tag is created from it, so code merged into `release` after you start
the release is never accidentally included.

## What the local command checks (before it triggers anything)

- Version argument is valid semver (`X.Y.Z`).
- You are on the `release` branch.
- Working tree is clean and not behind `origin/release`.
- `bogofy.php` **and** `readme.txt` already equal the requested version.
- The Git tag does not already exist (locally or on origin).
- The exact commit being released is already pushed to `origin/release`.

If any check fails, nothing is triggered.

## What GitHub Actions does (`.github/workflows/release.yml`)

Sequential jobs — a failure in any job stops the ones after it:

1. **validate** — semver, version consistency (`bin/check-versions.sh`), and that
   the Git tag / GitHub Release / WordPress.org tag do **not** already exist.
2. **quality** — PHP syntax check, PHPCS, PHPUnit, ESLint, Jest.
3. **build** — `composer install --no-dev`, `npm run build`, then
   `bin/build-release.sh` to produce `bogofy.zip` (honoring `.distignore`).
   Asserts dev files (`.git`, `.github`, `node_modules`, `tests`, `bin`,
   `phpunit.xml`, …) are excluded and that `vendor/autoload.php` + built assets
   are included.
4. **release** — creates the annotated Git tag `4.3.3`, pushes it, and creates a
   GitHub Release (`gh release create --verify-tag`, notes pulled from the
   `readme.txt` changelog). Fails if the tag/release already exists.
5. **deploy** — `10up/action-wordpress-plugin-deploy` deploys the pre-built
   package to WordPress.org SVN (`/trunk` + `/tags/4.3.3`, updates the stable
   tag, syncs `.wporg-assets/` to `/assets`).
6. **verify** — polls `https://plugins.svn.wordpress.org/bogofy/tags/4.3.3/`
   until it returns HTTP 200.

## Idempotency & failure handling

- **Tag / Release / WP.org tag already exists** → `validate` fails immediately
  with a clear message; nothing is overwritten.
- **Tests fail** → no tag, no release, no deploy.
- **Build fails** → no release, no deploy.
- **Deploy fails after the GitHub Release was created** → the tag and Release
  remain (safe). Re-run only the failed jobs from the workflow run page to retry
  the WordPress.org deploy — `validate` will no longer complain because the SVN
  tag isn't there yet. Inspect commits at
  `https://plugins.trac.wordpress.org/log/bogofy/`.
- **Double trigger for the same version** → the `concurrency` group serializes
  runs, and the second one fails at `validate`.

## Testing the pipeline safely (no real release)

Trigger a **dry run** from the Actions UI (Run workflow → set `dry_run = true`)
or via the CLI:

```bash
gh workflow run release.yml --ref release \
  -f version=4.3.3 -f commit="$(git rev-parse origin/release)" -f dry_run=true
```

A dry run performs validate → tests → build only. It does **not** create a tag,
GitHub Release, or deploy to WordPress.org.

## Normal CI vs Release

`.github/workflows/ci.yml` runs on pushes and pull requests (tests + quality).
`.github/workflows/release.yml` runs **only** on manual `workflow_dispatch` via
the `release` command — merging code never publishes to WordPress.org.
