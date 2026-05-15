#!/usr/bin/env bash
# =============================================================================
# deploy.sh — Push workspace changes to production
# Usage:
#   ./deploy.sh              — deploy theme only (git pull + purge cache)
#   ./deploy.sh cli "..."    — run a Moodle CLI command on the server
#   ./deploy.sh shell        — open an interactive SSH shell
# =============================================================================

set -euo pipefail

HOST="${SSH_HOST}"
PORT="${SSH_PORT}"
USER="${SSH_USER}"
PASS="${SSH_PASS}"
MOODLE="${PROD_MOODLE_ROOT}"
DATA="${PROD_MOODLE_DATA}"
THEME="${MOODLE}/theme/atipico"
PHP="php"

_ssh() {
    sshpass -p "$PASS" ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p "$PORT" "$USER@$HOST" "$@"
}

case "${1:-deploy}" in

  deploy)
    echo "==> Pulling latest theme from GitHub..."
    _ssh "cd '$THEME' && git pull --ff-only"

    echo "==> Purging Moodle caches..."
    _ssh "$PHP '$MOODLE/admin/cli/purge_caches.php'"

    echo ""
    echo "✓ Done. Live site updated."
    ;;

  cli)
    if [ -z "${2:-}" ]; then
        echo "Usage: ./deploy.sh cli \"<moodle CLI command>\""
        echo "Example: ./deploy.sh cli \"php /path/to/moodle/admin/cli/purge_caches.php\""
        exit 1
    fi
    echo "==> Running Moodle CLI: ${2}"
    _ssh "$PHP '$MOODLE/admin/cli/${2}'"
    ;;

  run)
    # Run any raw command on the server
    shift
    echo "==> Running: $*"
    _ssh "$*"
    ;;

  shell)
    echo "==> Opening SSH shell on ${HOST}..."
    sshpass -p "$PASS" ssh -o StrictHostKeyChecking=no -p "$PORT" "$USER@$HOST"
    ;;

  purge)
    echo "==> Purging Moodle caches..."
    _ssh "$PHP '$MOODLE/admin/cli/purge_caches.php'"
    echo "✓ Caches purged."
    ;;

  *)
    echo "Unknown command: ${1}"
    echo "Usage: ./deploy.sh [deploy|cli|run|purge|shell]"
    exit 1
    ;;

esac
