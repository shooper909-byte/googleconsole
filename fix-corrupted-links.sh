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
# Every target below was resolved against the live site on 2026-08-28:
#   /stacks/                                   -> 200 (redirects to /research-stacks/)
#   /products/longevity-research-stack/        -> 200 (resolves to /products/cellular-research-panel/)
#   /products/metabolic-research-stack/        -> 200 (resolves to /research-stacks/)
#   /products/recovery-support-research-stack/ -> 404 (dead; send to the hub)
#   /products/starter-research-stack/          -> 404 (dead; send to the hub)
#   /products/recovery-research-stack/         -> 404 (dead; send to the hub)
#   /?product=<slug>-stack...                  -> 200 (resolves to /research-catalog/)
#
# Only plain-space forms occur in the content -- there are no URL-encoded
# (%20) variants, so no encoded patterns are needed.
#
# Four pages were already repaired directly via the REST API and are clean:
#   texas-research-peptides, bpc-157-kpv-recovery-immune-research,
#   bpc-157-tb-500-blend-research, mots-c-ss-31-blend-mitochondrial-research
# They simply won't match anything here, so running this is still safe.
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
if [[ "${1:-}" == "--apply" ]]; then
  DRY=""
  echo ">>> APPLYING CHANGES <<<"
else
  echo ">>> DRY RUN -- no changes will be written. Re-run with --apply to commit. <<<"
fi
echo

command -v wp >/dev/null || { echo "wp-cli not found on PATH." >&2; exit 1; }

# Resolve the real table prefix rather than assuming "wp_" -- a wrong guess
# would match nothing and report a silent success.
PREFIX="$(wp db prefix)"
POSTS="${PREFIX}posts"
echo "Table: ${POSTS}"
echo

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
  echo "--- ${pair%%|*}"
  wp search-replace "$from" "$to" "$POSTS" \
    --include-columns=post_content \
    --precise --report-changed-only ${DRY}
done

echo
echo "Remaining corrupted hrefs in post_content (expect 0 after --apply):"
wp db query "SELECT COUNT(*) AS remaining FROM ${POSTS} \
  WHERE post_content LIKE '%href=\"%Research Panel%'" --skip-column-names || true

if [[ -z "$DRY" ]]; then
  echo
  echo "Flushing caches..."
  wp cache flush
fi

cat <<'NOTE'

NOT handled here -- these are in-page anchor fragments and need the real
heading IDs from each page, so fix them by hand:
  #Research Panel-feature
  #all-active-research-Research Panel-products
  #blend-and-Research Panel-options
  #documentation-standards-across-Research Panels
  #recovery-Research Panel-context

Also worth a look: some link TEXT reads "Research Research Panels", which is
the same rename applied twice. Cosmetic only -- no links are broken by it.
NOTE
