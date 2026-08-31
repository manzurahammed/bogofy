#!/usr/bin/env bash
#
# release <version>   e.g.  release 4.3.3
#
# Validates a release locally, then triggers the GitHub Actions release workflow
# (.github/workflows/release.yml). It NEVER deploys to WordPress.org from your
# machine — the pipeline in GitHub Actions does that with repo secrets.
#
# Run it from the repo (see README for the `release` alias):
#   bash bin/release.sh 4.3.3
#   composer release 4.3.3
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"; cd "$ROOT"

RELEASE_BRANCH="release"
WORKFLOW="release.yml"

VERSION="${1:-}"

die() { echo "✗ $*" >&2; exit 1; }

# 1. Version argument present and valid semver ---------------------------------
[ -n "$VERSION" ] || die "Usage: release <version>   (e.g. release 4.3.3)"
echo "$VERSION" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$' \
  || die "Version '$VERSION' is not valid semantic versioning (expected X.Y.Z, e.g. 4.3.3)."

# 2. On the allowed release branch --------------------------------------------
CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
[ "$CURRENT_BRANCH" = "$RELEASE_BRANCH" ] \
  || die "Releases must run from '$RELEASE_BRANCH' (you are on '$CURRENT_BRANCH')."

# 3. Working tree clean --------------------------------------------------------
[ -z "$(git status --porcelain)" ] \
  || die "Working tree is not clean. Commit or stash your changes first."

# 4. Local branch in sync with origin, and this exact commit is pushed --------
git fetch --quiet origin "$RELEASE_BRANCH" 2>/dev/null || true
if [ -n "$(git rev-list "HEAD..origin/$RELEASE_BRANCH" 2>/dev/null || true)" ]; then
  die "Local '$RELEASE_BRANCH' is behind origin/$RELEASE_BRANCH. Run: git pull"
fi
# The commit we release must exist on origin/<release> so the workflow can check
# out exactly it. This also catches unpushed local commits (HEAD ahead of origin).
COMMIT="$(git rev-parse HEAD)"
if ! git merge-base --is-ancestor "$COMMIT" "origin/$RELEASE_BRANCH" 2>/dev/null; then
  die "Current commit ${COMMIT:0:8} is not on origin/$RELEASE_BRANCH. Run: git push"
fi

# 5. Plugin header + readme.txt must already be bumped to <version> ------------
echo "Verifying version files..."
bash bin/check-versions.sh "$VERSION" \
  || die "Version mismatch. Bump bogofy.php and readme.txt to $VERSION, commit, then retry."

# 6. Version must be new: tag must not already exist (local or remote) ---------
#    This is both the "different from the current release" check and the first
#    idempotency guard.
if git rev-parse -q --verify "refs/tags/$VERSION" >/dev/null 2>&1; then
  die "Git tag '$VERSION' already exists locally — this version was already released."
fi
if git ls-remote --exit-code --tags origin "refs/tags/$VERSION" >/dev/null 2>&1; then
  die "Git tag '$VERSION' already exists on origin — version $VERSION has already been released."
fi

# Summary + explicit confirmation ---------------------------------------------
cat <<EOF

Preparing release $VERSION

Branch:         $CURRENT_BRANCH
Commit:         ${COMMIT:0:8}
Working tree:   clean
Plugin version: $VERSION

The release workflow will (against commit ${COMMIT:0:8}):
- Run tests and quality checks
- Build the production plugin
- Create Git tag $VERSION
- Create GitHub Release $VERSION
- Deploy to WordPress.org
- Verify the deployment

EOF

read -r -p "Continue? [y/N] " reply
case "$reply" in
  y|Y|yes|YES) ;;
  *) die "Aborted — nothing was triggered." ;;
esac

# Dispatch the workflow, pinning the exact commit to release ------------------
# The workflow file is read from the branch tip, but every job checks out
# inputs.commit, so the tag/build/deploy use exactly this commit even if new
# code lands on the branch in the meantime.
echo "Triggering GitHub Actions '$WORKFLOW' for $VERSION (commit ${COMMIT:0:8})..."

if command -v gh >/dev/null 2>&1; then
  gh workflow run "$WORKFLOW" --ref "$RELEASE_BRANCH" -f version="$VERSION" -f commit="$COMMIT"
  echo "✓ Release workflow dispatched."
  echo "  Watch it:  gh run watch"
else
  # Fallback: GitHub REST API (needs a token with 'workflow'/'repo' scope).
  TOKEN="${GH_TOKEN:-${GITHUB_TOKEN:-}}"
  [ -n "$TOKEN" ] || die "GitHub CLI (gh) not found and no GH_TOKEN/GITHUB_TOKEN set. Install gh, or export a token."
  REPO_SLUG="$(git config --get remote.origin.url \
    | sed -E 's#(git@|https://|ssh://git@)github.com[:/]##; s#\.git$##')"
  resp_code="$(curl -sS -o /tmp/bogofy-release-dispatch.json -w '%{http_code}' \
    -X POST \
    -H "Accept: application/vnd.github+json" \
    -H "Authorization: Bearer $TOKEN" \
    -H "X-GitHub-Api-Version: 2022-11-28" \
    "https://api.github.com/repos/$REPO_SLUG/actions/workflows/$WORKFLOW/dispatches" \
    -d "{\"ref\":\"$RELEASE_BRANCH\",\"inputs\":{\"version\":\"$VERSION\",\"commit\":\"$COMMIT\"}}")"
  if [ "$resp_code" = "204" ]; then
    echo "✓ Release workflow dispatched via GitHub API."
    echo "  Track it: https://github.com/$REPO_SLUG/actions/workflows/$WORKFLOW"
  else
    cat /tmp/bogofy-release-dispatch.json >&2 2>/dev/null || true
    die "Failed to dispatch workflow (HTTP $resp_code)."
  fi
fi
