#!/usr/bin/env bash
#
# AiServe.my deploy helper — run this ON the Hostinger server, inside public_html.
# Pulls the given branch (default: main) from GitHub over SSH and fixes permissions.
#
# Usage:
#   ./deploy.sh                          # deploy 'main'
#   ./deploy.sh claude/code-review-zanot3   # deploy a specific branch
#
set -euo pipefail

BRANCH="${1:-main}"

# Move to the directory this script lives in (the web root).
cd "$(dirname "$0")"

if [ ! -d .git ]; then
    echo "ERROR: this directory is not a Git repository. Run the one-time setup in DEPLOY.md first." >&2
    exit 1
fi

echo "==> Deploying branch: ${BRANCH}"
git fetch origin "${BRANCH}"

# Fast-forward to the remote branch. Untracked files (.env is outside the tree;
# new uploads under uploads/media) are left untouched.
git checkout -f -B "${BRANCH}" "origin/${BRANCH}"

# Ensure uploads stay writable for the web server.
if [ -d uploads ]; then
    chmod -R 755 uploads 2>/dev/null || true
fi

echo "==> Now live: $(git rev-parse --short HEAD) on ${BRANCH}"
echo "==> Done."
