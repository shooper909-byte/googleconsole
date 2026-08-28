#!/usr/bin/env bash
#
# Repairs the corrupted internal links on oligopolypeptides.com.
#
# Cause: a "Stack" -> "Research Panel" rename was applied to page content without
# being restricted to display text, so it rewrote the inside of href values too.
# The result is link targets containing a literal space, which cannot resolve.
#
# This ONLY rewrites href values. Display text is left exactly as it is --
# the rename itself was intentional and is not being undone.
#
# Every target below was verified against the live site on 2026-08-28:
#   /stacks/                                  -> 200 (redirects to /research-stacks/)
#   /products/longevity-research-stack/       -> 200 (resolves to /products/cellular-research-panel/)
#   /products/metabolic-research-stack/       -> 200 (resolves to /research-stacks/)
#   /products/recovery-support-research-stack/-> 404 (dead; send to the hub)
#   /products/starter-research-stack/         -> 404 (dead; send to the hub)
#   /products/recovery-research-stack/        -> 404 (dead; send to the hub)
#   /?product=<slug>-stack...                 -> 200 (resolves to /research-catalog/)
#
# USAGE
#   1. Take a database backup first.
#   2. Dry run (makes no changes, prints what would change):
#        bash fix-corrupted-links.sh
#   3. Apply:
#        bash fix-corrupted-links.sh --apply
#
set -euo pipefail

DRY="--dry-run"
[[ "${1:-}" == "--apply" ]] && DRY=""

SITE="https://www.oligopolypeptides.com"

# Longest/most specific patterns run first so shorter ones cannot clobber them.
declare -a PAIRS=(
  "${SITE}/products/recovery-support-research-Research Panel/|${SITE}/research-stacks/"
  "${SITE}/products/starter-research-Research Panel/|${SITE}/research-stacks/"
  "${SITE}/products/longevity-research-Research Panel|${SITE}/products/cellular-research-panel/"
  "${SITE}/products/metabolic-research-Research Panel|${SITE}/research-stacks/"
  "${SITE}/products/recovery-research-Research Panel|${SITE}/research-stacks/"
  "${SITE}/?product=recovery-cellular-Research Panel-bpc-157-tb-500-ghk-cu-ss-31|${SITE}/research-catalog/"
  "${SITE}/?product=starter-research-Research Panel-bpc-157-tb-500-ghk-cu|${SITE}/research-catalog/"
  "${SITE}/?product=recovery-support-Research Panel-bpc-157-tb-500-ara-290|${SITE}/research-catalog/"
  "${SITE}/?product=advanced-multi-pathway-Research Panel|${SITE}/research-catalog/"
  "${SITE}/?product=metabolic-research-Research Panel|${SITE}/research-catalog/"
  "${SITE}/Research Panels/|${SITE}/research-stacks/"
  "/Research Panels/|/research-stacks/"
)

for pair in "${PAIRS[@]}"; do
  from="href=\"${pair%%|*}\""
  to="href=\"${pair##*|}\""
  echo "--- ${from}"
  wp search-replace "$from" "$to" wp_posts \
    --include-columns=post_content \
    --precise --report-changed-only $DRY
done

echo
echo "Done. Now flush caches:"
echo "  wp cache flush"
echo
echo "NOT handled here -- these need per-page heading IDs, so fix them by hand:"
echo '  #Research Panel-feature'
echo '  #all-active-research-Research Panel-products'
echo '  #blend-and-Research Panel-options'
echo '  #documentation-standards-across-Research Panels'
echo '  #recovery-Research Panel-context'
