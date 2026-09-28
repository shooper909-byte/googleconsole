#!/usr/bin/env bash
set -euo pipefail

if ! command -v wp >/dev/null 2>&1; then
  echo "ERROR: wp-cli is not available on PATH." >&2
  exit 1
fi

ROOT="$(cd "$(dirname "$0")" && pwd)"
MU_DIR="$(wp eval 'echo WPMU_PLUGIN_DIR;')"
mkdir -p "$MU_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="oligopoly-before-seo-remediation-${STAMP}.sql"

echo "1/5 Exporting database backup: ${BACKUP}"
wp db export "$BACKUP"

echo "2/5 Preflight SKU resolution"
wp eval-file "$ROOT/preflight.php"

echo "3/5 Installing MU plugin"
cp "$ROOT/oligopoly-seo-remediation.php" "$MU_DIR/oligopoly-seo-remediation.php"

echo "4/5 Repairing confirmed legacy href targets in WordPress content"
wp eval-file "$ROOT/fix-internal-links.php"

echo "5/5 Flushing WordPress cache"
wp cache flush || true

cat <<EOF

Applied targeted OligoPoly SEO remediation.
Database backup: ${BACKUP}
MU plugin: ${MU_DIR}/oligopoly-seo-remediation.php

NEXT:
- Purge the Pressable/CDN cache.
- Live-test the previously failing URLs in Google Search Console.
- Do NOT restart validation for intentional 404/410/system URLs.
EOF
