#!/usr/bin/env bash
# Run from the Pressable production WordPress root. Stops on the first failure.
set -euo pipefail
command -v wp >/dev/null || { echo "ERROR: wp-cli not on PATH" >&2; exit 1; }

ROOT="$(cd "$(dirname "$0")" && pwd)"
MU_DIR="$(wp eval 'echo WPMU_PLUGIN_DIR;')"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups}"
BACKUP="${BACKUP_DIR}/oligopoly-before-gsc-remediation-${STAMP}.sql"

[ "$(wp option get home)" = "https://www.oligopolypeptides.com" ] || { echo "ERROR: not the oligopolypeptides.com install" >&2; exit 1; }

echo "1/6 Database backup: ${BACKUP}"
mkdir -p "$BACKUP_DIR"
wp db export "$BACKUP"
[ -s "$BACKUP" ] || { echo "ERROR: backup is empty" >&2; exit 1; }

echo "2/6 Installing MU plugin (previous copy kept as .bak-${STAMP})"
[ -f "$MU_DIR/oligopoly-seo-remediation.php" ] && cp "$MU_DIR/oligopoly-seo-remediation.php" "$MU_DIR/oligopoly-seo-remediation.php.bak-${STAMP}"
cp "$ROOT/oligopoly-seo-remediation.php" "$MU_DIR/oligopoly-seo-remediation.php"

echo "3/6 Preflight (aborts and removes the MU plugin on any failure)"
if ! wp eval-file "$ROOT/preflight.php"; then
  rm -f "$MU_DIR/oligopoly-seo-remediation.php"; echo "Preflight failed; MU plugin removed." >&2; exit 1
fi

echo "4/6 Link repair dry run"
wp eval-file "$ROOT/fix-internal-links.php"

echo "5/6 Link repair"
wp eval-file "$ROOT/fix-internal-links.php" --apply

echo "6/6 Cache flush"
wp cache flush
echo "Done. Backup: ${BACKUP}. Purge the Pressable edge cache, then run verify-live.sh."
