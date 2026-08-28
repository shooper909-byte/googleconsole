# oligopolypeptides.com — Search Console exclusion audit

**Audited 2026-08-28 against the live site.** Every claim below was verified by
fetching the URL: HTTP status, redirect chain, `<link rel=canonical>`, meta
robots, and response headers. Raw output is in [`evidence/`](evidence/).

Scope: the ~40 example URLs visible in the Search Console screenshots, plus a
full crawl of all 227 sitemap URLs and all 407 internal link targets found on
them.

---

## Headline

The exclusion buckets are largely healthy. The real damage is **not** in
Search Console's exclusion counts at all — it is ~150 broken internal links
that Search Console has not surfaced yet, most of them created by a single
bad find/replace.

| | |
|---|---|
| Sitemap URLs returning 200 | **227 / 227** |
| Internal links resolving to a 404 or malformed URL | **~150** |
| Live commercial categories carrying `noindex` | **4** |
| Redirect rules whose destination is a 404 | **11** |

---

## Status (2026-08-28)

4 of the 39 corrupted pages have been repaired directly through the WordPress
REST API and verified clean on the live site:
`texas-research-peptides`, `bpc-157-kpv-recovery-immune-research`,
`bpc-157-tb-500-blend-research`, `mots-c-ss-31-blend-mitochondrial-research`.

The remaining 35 are covered by [`fix-corrupted-links.sh`](fix-corrupted-links.sh),
which repairs all of them in one WP-CLI pass. Nothing else in this document has
been applied to the site.

---

## 1. A find/replace corrupted URLs on 39 pages (P1)

A rename of "Stack" → "Research Panel" was applied to page content but was not
restricted to display text — it rewrote the inside of `href` values too.

The result is links to paths that contain a literal space and cannot resolve:

| Broken target | Inbound links |
|---|---:|
| `/Research Panels/` | 66 |
| `/products/recovery-support-research-Research Panel/` | 7 |
| `/products/starter-research-Research Panel/` | 6 |
| `/products/metabolic-research-Research Panel` | 2 |
| `/products/longevity-research-Research Panel` | 1 |
| `/products/recovery-research-Research Panel` | 1 |

**83 links across 39 pages**, including `/faq/`, `/research-library/`,
`/peptide-catalog/`, and most comparison articles. The affected pages are
listed in [`evidence/corrupted-pages.txt`](evidence/corrupted-pages.txt).

`/Research Panels/` should be `/research-stacks/` in every case.

## 2. `/peptide-reconstitution-guide/` is a 404 with 39 inbound links (P1)

It returns 404 and is linked from ~39 product pages. That every link comes
from a product page means it is almost certainly one shared template or block —
a single edit fixes all 39.

This URL also appears in the Search Console "Not found (404)" report, so it is
the one screenshot example that is a genuine problem rather than intentional
cleanup.

## 3. Eleven redirects land on 404s (P1)

Short product slugs 301 correctly — to destinations that do not exist:

```
/products/tesamorelin  →301→  /products/tesamorelin-10mg-research-peptide/  →  404
/products/ss-31        →301→  /products/ss-31-10mg-research-peptide/        →  404
/products/ipamorelin   →301→  /products/ipamorelin-5mg-research-peptide/    →  404
```

…and the same pattern for `ara-290`, `epitalon`, `pinealon`, `melanotan-ii`,
`igf-1-lr3`, `cjc-1295-no-dac`, `bpc-157-tb-500`, and
`recovery-support-research-stack`. Roughly 66 internal links feed into these
chains.

One is a quick win: **`/products/cjc-1295-no-dac` points at the 2mg variant,
but the live catalogue has a 5mg variant** (`/products/cjc-1295-no-dac-5-mg-research-peptide/`).
That redirect just needs a corrected target.

Full mapping with suggested destinations: [`redirect-map.csv`](redirect-map.csv).

## 4. Four live commercial categories carry `noindex` (P1/P2)

| Category | In sitemap? | Canonical |
|---|---|---|
| `/product-category/cellular-research/` | **yes** | none |
| `/product-category/research-blends-cat/` | **yes** | none |
| `/product-category/research-compounds/` | no | none |
| `/product-category/research-blends-cat/mitochondrial-research-blends/` | no | none |

The first two are a direct contradiction: the sitemap asks Google to index
them while the page tells Google not to. Either remove the `noindex` and add a
self-referencing canonical, or drop them from the sitemap. Right now they do
the worst of both.

## 5. The canonical conflict on `/research-products/` — Google is right (P2)

Search Console flags `/research-products/` under "Google chose different
canonical than user". The evidence says Google's choice is correct:

| | `/research-products/` | `/research-catalog/` |
|---|---:|---:|
| `<title>` | Research Products Catalog \| OligoPoly Laboratories | *identical* |
| Products linked | 10 | 38 |
| Internal inbound links | **1** | **2,225** |

These are duplicates and `/research-catalog/` is decisively the stronger page.
**301 `/research-products/` → `/research-catalog/`** rather than trying to make
both rank.

## 6. Five account pages are in the sitemap, noindexed, and redirect into a blocked path (P2)

`/sign-in/`, `/request-account/`, `/individual-research-account/`,
`/apply-individual-research-account/`, `/apply-institutional-account/` all sit
in the sitemap, all carry `noindex`, and all 301 to `/my-account/` — which
`robots.txt` disallows. Remove them from the sitemap.

Separately, `/request-quote/` and `/research-panels/` are listed in the sitemap
but 301 elsewhere; sitemaps should list final destinations. `/research-panels/`
also absorbs **232 internal links** that could point straight at
`/research-stacks/`.

## 7. Crawl-noise fixes (P3)

- **`/?opl7_pdf=…`** serves `Content-Type: application/pdf` with
  `Content-Disposition: attachment` and **no `X-Robots-Tag`**. A meta robots tag
  cannot apply to a PDF — this needs the `X-Robots-Tag: noindex` response header.
- **`?share=facebook&nb=1`** links 302 offsite to facebook.com and are
  crawlable. Add `rel="nofollow"` or `Disallow: /*?share=`.
- **`/product-tag/`** is both `Disallow`ed in robots.txt *and* serves `noindex`.
  Google cannot read a directive on a page it is blocked from crawling. Pick
  one; the robots block alone is fine here.

---

## What needs no work

These were flagged as concerns but verified as correct. Do not spend developer
time on them.

- **Legacy taxonomy already returns `410 Gone`** — `/product-category/longevity/`,
  `/product-category/wellness/`, `/nutraceuticals/`, and the `/vitamins/*` tree.
  This is deliberate and correct. Search Console files 410 responses under
  "Not found (404)", which is why that bucket looks alarming. No redirect map is
  needed for these.
- **`/product-category/cognitive-research/`** now 301s to
  `/product-category/cognitive-systems-research/`, which is `index, follow`.
  Already fixed; the Search Console entry is stale.
- **`/product-category/recovery-research/`** is `index, follow` with a
  self-canonical. Already fixed; stale entry.
- **`/peptide-finder/cellular-health/`** is `index, follow`. Fine.
- **`/contact/?subject=404 Error&…`** returns 200 and canonicalises to
  `/contact/`. Working as intended.
- **`?add-to-cart=`, `/cart/`, `/wp-admin/`** are blocked in robots.txt as they
  should be. Do not unblock.
- **The sitemap is clean** — all 227 URLs return 200.

---

## Method

```
robots.txt + sitemap_index.xml  →  227 sitemap URLs
  ↓ fetch each, record status / canonical / robots / redirects
  ↓ extract every internal href  →  407 unique targets
  ↓ fetch each, record status + inbound link count
  ↓ map broken targets back to the pages that link to them
```

Evidence files:

| File | Contents |
|---|---|
| `evidence/sitemap-scan.txt` | Status, robots and canonical for all 227 sitemap URLs |
| `evidence/linkcheck.txt` | Status + inbound count for all 407 internal link targets |
| `evidence/probe-results.txt` | Detailed probe of the URLs shown in the screenshots |
| `evidence/internal-links.txt` | Every internal link target ranked by inbound count |
| `evidence/corrupted-pages.txt` | The 39 pages carrying corrupted `href` values |
| `evidence/robots.txt`, `evidence/all-sitemap-urls.txt` | Raw inputs |

## Caveat

Search Console reported 108 "Not found", 104 "Blocked by robots.txt", 71
"Excluded by noindex" and 74 "Crawled – currently not indexed". The screenshots
exposed roughly the first 10 rows of each, so this audit verifies a sample of
those buckets plus a complete crawl of everything reachable from the sitemap.
Exporting the full CSVs from Search Console would let the remaining rows be
classified the same way — though based on the sample, expect most of the
remainder to be 410s and cart/parameter URLs that need no action.
