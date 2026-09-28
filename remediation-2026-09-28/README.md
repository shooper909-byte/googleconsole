# OligoPoly Search Console remediation — 2026-09-28

Targeted technical SEO repair for `https://www.oligopolypeptides.com` (Pressable site 1635271). It does **not** try to clear every Search Console exclusion, and it leaves intentional 404/410/system URLs alone.

**Status: deployed to production and verified on 2026-09-28.** See [Deployment record](#deployment-record-2026-09-28).

## What it fixes

1. **Corrupted internal links.** An earlier global `Stack → Research Panel` replacement rewrote `href` values, for example `/stacks/` → `/Research Panels/` and `/products/starter-research-stack/` → `/products/starter-research-Research Panel/`. `fix-internal-links.php` rewrites only those link targets to their current destination. Visible text, titles, meta descriptions and anchor `id`s are untouched.
2. **Broken legacy redirects.** `oligopoly-seo-remediation.php` (an MU plugin) sends each corrupted URL, and each legacy URL whose existing redirect ended in a 404 or took two hops, **directly** to its current equivalent. Current product destinations are resolved by WooCommerce SKU at request time.
3. **PDF duplicates.** No change was needed. Code Snippet #153, which generates `?opl7_pdf=` responses, already sends `X-Robots-Tag: noindex` (verified live). The PR's original `send_headers` hook was redundant and was removed.

## Why the original PR #4 package was changed before deployment

Production inspection (`inspect-production.php`, `inspect-production-2.php`, both read-only) showed:

- **16 of the 26 mapped SKUs are drafted**, not published. The original code would have silently redirected them to `/research-catalog/`.
- **`/products/bpc-157-tb-500-blend/` is a live product.** The original map would have redirected it to itself.
- **Three older redirect layers already exist:**
  - Code Snippets #593–#595 execute when Code Snippets loads.
  - Plugin `oligopoly-seo-remediation-v2-active` runs on `init` at priority -1000.
  - Rank Math has 23 stored redirects.

  A `template_redirect` hook, as in the original, never runs for URLs those layers already match. Several of their rules point at drafted products (301 → 404) or chain two hops. The MU plugin therefore runs at MU-plugin load time and handles **only** the URLs that were broken. Every legacy URL that already produced a single 301 → 200 was left to its existing rule.
- **The corruption was wider than the seven Search Console examples:** 34 distinct malformed hrefs, 140 occurrences in 47 live posts, pages and products.
- **The original fixer used `wp_update_post()` under WP-CLI.** That re-runs kses without an `unfiltered_html` user and can strip markup, and it creates revisions. The fixer now writes through `$wpdb`.
- **The first Elementor pattern could fail silently.** It could hit PCRE limits on 56–65 KB values. Elementor data is now handled by decoding the JSON.

## Destinations for retired (drafted) stacks

| Retired stack / URL family | Destination |
|---|---|
| cognitive, neuropeptide-signaling | CNS Receptor Reference Collection (`OP-STACK-NEURO`) |
| metabolic, endocrine-receptor-ligands | Metabolic Pathways Research Collection (`OP-STACK-METABOLIC`) |
| performance-recovery, advanced-multi-pathway | Comparative Pathway Collection (`OP-STK-ADVANCED-MULTIPATHWAY`) |
| longevity, senescence-telomere-signaling, anti-aging-repair | Telomere Biology Reference Collection (`OP-STK-LONGEVITY`) |
| recovery-mitochondrial-redox-signaling | Mitochondrial Signaling Collection (`OP-STACK-CELLULAR`) |
| (performance-)extracellular-matrix-signaling | Tissue Signaling Collection (`OP-STACK-REGEN`) |
| recovery-support, ARA-290 | `/recovery-research-blends/` (the site's existing hub for this stack) |
| recovery-cellular, recovery, immune, starter, GH-axis | `/research-collections/` (retired, no equivalent collection) |
| MOTS-c + SS-31 blend | MOTS-c 10 mg (`OP-AUX-MOTSC-10MG`) |

If a SKU is ever unpublished, the plugin **skips** that redirect and logs it rather than falling back to the catalog.

## Files

| File | Purpose |
|---|---|
| `oligopoly-seo-remediation.php` | MU plugin: the redirect map and SKU resolution |
| `preflight.php` | Fails on any unpublished SKU, missing hub page/post, or self-redirect |
| `fix-internal-links.php` | Link repair; dry run by default, `--apply` to write |
| `apply-live-remediation.sh` | Runs backup → install → preflight → dry run → apply → cache flush, stopping on failure |
| `verify-live.sh` | Live HTTP checks (redirect hops, canonical host, PDF header) |
| `inspect-production*.php` | Read-only production inspection used to build the map |
| `evidence-*.txt` | Production results from the deployment below |

## Deployment record (2026-09-28)

- **Install:** Pressable site 1635271, `home`/`siteurl` `https://www.oligopolypeptides.com`, WordPress 7.1.2, WooCommerce 11.1.2, PHP 8.5.11, ABSPATH `/srv/htdocs/__wp__/`.
- **Backups, taken before any write:**
  - `wp db export` → `/srv/htdocs/.opseo-backups-r7t2x9/oligopoly-before-gsc-remediation-20260928-2325.sql` (87,204,977 bytes, 123 tables, "Dump completed"; the path returns 403 over HTTP).
  - Pressable on-demand database and filesystem backups (requests 948661 and 948662).
- **MU plugin:** `/srv/htdocs/wp-content/mu-plugins/oligopoly-seo-remediation.php` (sha256 `65b724e8…a65ba17ad`). There was no earlier OligoPoly MU plugin to replace.
- **Preflight:** 53 mappings, 0 failures.
- **Link repair:** 47 posts changed, 140 hrefs, 0 malformed non-fragment hrefs remaining (`evidence-link-repair-2026-09-28.txt`).
- **Caches:** WordPress object cache flushed; Pressable edge cache purged.
- **Redirects:** 47 of 47 remediated sources return exactly `301 → 200` (`evidence-redirects-2026-09-28.txt`).
- **Edited pages:** all 47 render 200 and link to no malformed URL.
- **PDF:** `/?opl7_pdf=research-compound&item=441` → `200 application/pdf`, `x-robots-tag: noindex`.
- **Canonical host:** https www → 200; https apex → 301 → www; http www → 301 → https www; http apex → 301 → https apex → 301 → www (two hops at Pressable's edge, no 503, no loop).

## Left as is, deliberately

- Intentional 404/410/system and retired-taxonomy URLs.
- Legacy URLs whose existing redirect is already a direct `301 → 200`, for example Tesamorelin → comparison article, `/products/ss-31` → mitochondrial article, and `/peptide-reconstitution-guide/` → storage guide.
- In-page `#Research Panel-…` fragment links: their target `id`s carry the same text, so they work, and fragments are not crawled as URLs.
- `_elementor_data` on posts 191, 194 and 717, which was already invalid JSON before this work. 191 and 194 contain no links; 717 is an unused library template, and its links do not appear on any live page.
- Revisions.
