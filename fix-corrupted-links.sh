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
# ---------------------------------------------------------------------------
# REVISED 2026-09-10. The original (2026-08-28) version of this script is
# superseded. Two things changed:
#
#   1. The bug RECURRED on the newer taxonomy. The August slugs
#      (longevity-research, metabolic-research, recovery-research) are gone
#      from the live content, but a fresh set is corrupted the same way --
#      senescence-telomere-signaling, endocrine-receptor-ligands,
#      neuropeptide-signaling, extracellular-matrix-signaling and
#      recovery-mitochondrial-redox-signaling. Fixing the links WITHOUT fixing
#      the rename process means a third wave.
#
#   2. The August script rewrote everything to /research-stacks/, which now
#      301s to /research-collections/. Applying it unchanged would bake a
#      redirect hop into every repaired link. All targets below point at the
#      final destination instead.
#
# Every target was resolved against the live site on 2026-09-10 and returns
# HTTP 200 with no redirect:
#   /research-collections/
#   /products/telomere-biology-reference-collection/
#   /products/cns-receptor-reference-collection/
#   /products/tissue-signaling-collection/
#   /products/mitochondrial-signaling-collection/
#   /products/metabolic-pathways-research-collection/
#   /product-category/endocrine-receptor-ligands/
#   /product-category/multi-compound-research-formulations/
#   /product-category/multi-compound-research-formulations/recovery-research-blends/
#
# Where a renamed product clearly corresponds to the corrupted slug, the link
# goes to that product. Where no single product corresponds, it goes to the
# matching category rather than guessing at a specific item.
#
# Only plain-space forms occur in the content -- there are no URL-encoded
# (%20) variants, so no encoded patterns are needed.
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
COLLECTIONS="${SITE}/research-collections/"
RECOVERY="${SITE}/product-category/multi-compound-research-formulations/recovery-research-blends/"

# Each pattern is anchored by the surrounding href quotes (href="<url>"), so a
# shorter slug cannot match inside a longer one -- the trailing-slash and
# slashless forms of the same slug are distinct strings and are listed
# separately. Longest-first ordering is kept as a defensive convention only.
declare -a PAIRS=(
  "${SITE}/products/performance-extracellular-matrix-signaling-Research Panel/|${SITE}/products/tissue-signaling-collection/"
  "${SITE}/products/recovery-mitochondrial-redox-signaling-Research Panel/|${SITE}/products/mitochondrial-signaling-collection/"
  "${SITE}/products/senescence-telomere-signaling-Research Panel/|${SITE}/products/telomere-biology-reference-collection/"
  "${SITE}/products/senescence-telomere-signaling-Research Panel|${SITE}/products/telomere-biology-reference-collection/"
  "${SITE}/products/extracellular-matrix-signaling-Research Panel|${SITE}/products/tissue-signaling-collection/"
  "${SITE}/products/endocrine-receptor-ligands-Research Panel|${SITE}/product-category/endocrine-receptor-ligands/"
  "${SITE}/products/neuropeptide-signaling-Research Panel/|${SITE}/products/cns-receptor-reference-collection/"
  "${SITE}/products/recovery-support-research-Research Panel/|${RECOVERY}"
  "${SITE}/products/starter-research-Research Panel/|${COLLECTIONS}"
  "${SITE}/?product=recovery-cellular-Research Panel-bpc-157-tb-500-ghk-cu-ss-31|${RECOVERY}"
  "${SITE}/?product=recovery-support-Research Panel-bpc-157-tb-500-ara-290|${RECOVERY}"
  "${SITE}/?product=starter-research-Research Panel-bpc-157-tb-500-ghk-cu|${COLLECTIONS}"
  "${SITE}/?product=advanced-multi-pathway-Research Panel|${SITE}/product-category/multi-compound-research-formulations/"
  "${SITE}/?product=metabolic-research-Research Panel|${SITE}/products/metabolic-pathways-research-collection/"
  "${SITE}/Research Panels/|${COLLECTIONS}"
  "/Research Panels/|/research-collections/"
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

FIX THE CAUSE, NOT JUST THESE LINKS
This is the second wave of the same bug. Whatever process applies the
"Stack" -> "Research Panel" style rename is still rewriting href values along
with display text. Until that is restricted to text nodes, the next rename
will corrupt the next set of slugs and this script will need a third revision.

NOT handled here -- these are in-page anchor fragments and need the real
heading IDs from each page, so fix them by hand. Find the current set with:
  wp db query "SELECT ID, post_name FROM ${POSTS} \
    WHERE post_content LIKE '%#%Research Panel%' AND post_status = 'publish'"

Also worth a look: some link TEXT reads "Research Research Panels", which is
the same rename applied twice. Cosmetic only -- no links are broken by it.
NOTE
