# OligoPoly SEO Phases 1–3 — 2026-09-29

Technical SEO audit, product-page optimisation and topic clusters for `https://www.oligopolypeptides.com` (Pressable site 1635271).

**Status: deployed to production and verified live on 2026-09-29.** Evidence:
- `evidence-live-audit-2026-09-29.txt`: live crawl after the deploy.
- `evidence-apply-2026-09-29.json`: every value written, with its previous value.

What was left alone:
- No redesign.
- No price, product or stock change.
- No product was published or unpublished.
- Product H1s (the OL-xxx code names) were left as they are.

Every compound fact on the site comes from a public source:
- CAS: CAS registry / GSRS.
- Formula and computed MW: PubChem.
- Sequences: PubChem.
- Literature: DOIs verified in PubMed.

No purity figures, test results or COAs were invented. No dosing, treatment or efficacy claims were added. Every new block carries the research-use-only statement.

## Files

| File | Purpose |
|---|---|
| `oligopoly-seo-phases.php` | MU plugin: link map, sitemap fix, H1 and meta-description fixes, product identity sections, cluster navigation |
| `apply-seo-phases.php` | One-time data changes. Dry run by default; `--apply` to write; `--report=` for a JSON report. Previous values go to the option `opseo3_backup_20260929` |
| `update-snippet-454.php` | Points Code Snippet #454's metadata map at the new Rank Math values for six products. Old snippet code is kept in the same backup option |
| `content/*.html` | Bodies of the three new research guides |
| `inspect-production-3.php` | Read-only inspection used for the audit |

## Phase 1 — technical audit of the priority pages

Before the change:

| Issue | Pages | Fix |
|---|---|---|
| Duplicate H1 from the signup popup (snippet #49 used `<h1 id="opl-entry-title">`) | every page | Popup heading changed to `<h2>`; its CSS selector now targets the id |
| Extra theme page-title H1 | `/batch-verification/`, `/quality-testing-hub/` | `hello_elementor_page_title` returns false for pages 2505 and 727 |
| Second `<meta name="description">` (Hello Elementor prints the excerpt after Rank Math's) | product pages | `hello_elementor_description_meta_tag` returns false |
| Missing from the XML sitemap: the legacy v2 SEO plugin drops every URL in its old redirect map, but snippet #567 deliberately keeps these two live | Tirzepatide 10 mg, Cagrilintide 5 mg | The v2 sitemap filters are wrapped so these two URLs pass through |
| Stale "PROVISIONAL — HOLD" meta descriptions on products released 2026-08-26 | Retatrutide, Tirzepatide, Cagrilintide | New titles and descriptions (Phase 2) |
| Internal links that go through a 301 (13 targets) or to a 404 (1 target) | site-wide | An output-buffer link map rewrites the exact hrefs to their final 200 URL |
| Duplicate article canonicals | posts 1260, 764, 763, 1268 | Canonical set to the primary article |

After the change, all 20 audited URLs plus the 3 new guides pass these checks:
- a single `200`
- `index, follow`
- a self-referencing canonical
- present in the sitemap
- exactly one H1 and one meta description
- appropriate schema: `Product`/`Offer`/`BreadcrumbList` on products, `BlogPosting`/`BreadcrumbList` on articles

The one exception is Tesamorelin (see Blockers). A crawl of the 154 internal URLs linked from these pages found no 3xx and no 4xx `href`s.

## Phase 2 — ten product pages

For products 12, 39, 436, 49, 55, 441, 443, 63, 447 (and the Semaglutide 5 mg audit):
- **Rank Math title and description:** rewritten with the compound name, strength, CAS and published COA batch where one exists.
- **"Compound identity & documentation" section,** below the summary:
  - sequence, CAS, formula and MW, each with its source
  - classification, strength and form
  - COA link, or an honest note when no batch is published
  - testing methodology
  - literature references, related compounds and the cluster links
  - the RUO statement
- **WooCommerce attributes:** "CAS number" and "Molecular weight" added, so "Additional information" and the existing Trust Authority block show them.

Retatrutide has no published formula or MW, so none is shown.

| Product | Title (live) | COA linked |
|---|---|---|
| Retatrutide 10 mg | Retatrutide 10 mg Research Peptide (OL-3RT) \| OligoPoly Laboratories | 5 mg lot P260628-L013 is published. The 10 mg lot is not; the page says so |
| Tirzepatide 10 mg | Tirzepatide 10 mg Research Peptide (OL-2TZ) \| OligoPoly Laboratories | 20260609 |
| Cagrilintide 5 mg | Cagrilintide 5 mg Research Peptide (OL-CAG) \| OligoPoly Laboratories | none published |
| BPC-157 10 mg | BPC-157 10 mg Research Peptide \| CAS 137525-51-0 \| OligoPoly | none published |
| BPC-157 + TB-500 | BPC-157 + TB-500 Research Blend (10 mg + 10 mg) \| OligoPoly | none published |
| GHK-Cu 50 mg | GHK-Cu 50 mg Research Peptide \| Published COA \| OligoPoly | SGX-OL-2026-0811-K99 |
| MOTS-c 10 mg | MOTS-c 10 mg Research Peptide \| CAS 1627580-64-6 \| OligoPoly | none published |
| NAD+ 500 mg | NAD+ 500 mg Research Material \| Published COA \| OligoPoly | AYK20260418-NAD500 |
| Selank 5 mg | Selank 5 mg Research Peptide \| Published COA \| OligoPoly | OP-20260720-B001 |
| Tesamorelin | not changed: the product is a draft | — |

## Phase 3 — topic clusters

Each cluster runs Product → What Is → Mechanism → Comparison → Testing/Purity → COA. A cluster navigation block with links to every other member is added to the product and to every article in the cluster.

| Cluster | Product | What Is | Mechanism | Comparison | Testing / Purity | COA |
|---|---|---|---|---|---|---|
| Retatrutide | `/products/retatrutide-10mg-research-peptide/` | `/what-is-retatrutide/` | `/triple-agonist-peptides/` | `/retatrutide-vs-tirzepatide/`, `/retatrutide-vs-cagrilintide/` | `/retatrutide-source/` | `?batch=P260628-L013` (5 mg lot) |
| Tirzepatide | `/products/tirzepatide-10mg-research-peptide/` | **`/what-is-tirzepatide-research-guide/` (new)** | `/single-vs-dual-agonist-peptides/` | `/semaglutide-vs-tirzepatide/`, `/retatrutide-vs-tirzepatide/` | `/peptide-purity-guide/` | `?batch=20260609` |
| Cagrilintide | `/products/cagrilintide-5mg-research-peptide/` | `/what-is-cagrilintide-triple-receptor-peptide/` | `/cagrilintide-research-guide-amylin-analog/` | `/cagrilintide-vs-semaglutide/`, `/retatrutide-vs-cagrilintide/` | `/peptide-purity-guide/` | COA library |
| BPC-157 | `/products/bpc-157-10mg-research-peptide/` (+ blend) | `/bpc-157-research-mechanisms-applications/` | same guide | `/bpc-157-vs-tb-500-protocols/`, `/ghk-cu-vs-bpc-157/` | `/hplc-testing-explained/` | COA library |
| GHK-Cu | `/products/ghk-cu-50mg-research-peptide/` | **`/what-is-ghk-cu-research-guide/` (new)** | `/ecm-signaling-hub/` | `/ghk-cu-vs-bpc-157/` | `/quality-testing-hub/hplc-vs-lc-ms/` | `?batch=SGX-OL-2026-0811-K99` |
| MOTS-c | `/products/mots-c-10mg-research-peptide/` | **`/what-is-mots-c-research-guide/` (new)** | `/mitochondrial-research-peptides-guide/` | `/ss-31-vs-mots-c/`, `/epitalon-vs-mots-c/` | `/peptide-purity-guide/` | COA library |

COA links go to `/research-peptides-with-coa/`, with `?batch=` where a batch is published.

Per-compound "testing" articles were not created on purpose: without batch data they would be thin duplicates of the shared testing guides, so each cluster links to those guides instead.

## Deployment record

1. **Database backup** before any write: `/srv/htdocs/.opseo-backups-r7t2x9/oligopoly-before-seo-phases-20260929.sql`.
2. **Packages downloaded on the server** from this branch by commit SHA, each with a sha256 check.
3. **MU plugin installed:** `wp-content/mu-plugins/oligopoly-seo-phases.php`. Final sha256 `f81ee074…6cc1`, commit 67600ac.
4. **`apply-seo-phases.php`:** dry run, then `--apply`. 42 changes, 0 errors.
5. **`update-snippet-454.php`:** dry run, then `--apply`. It changed exactly six map entries: BPC-157, BPC-157 + TB-500, GHK-Cu, MOTS-c, NAD+ and Selank.
6. **Caches:** object cache flushed, edge cache purged, Rank Math sitemap cache invalidated.
7. **Cleanup:** temporary report files in `uploads/` deleted.

## Blockers and items for the owner

- **Search Console.** There is no GSC access from this environment, so the owner still needs to:
  - resubmit `https://www.oligopolypeptides.com/sitemap_index.xml`
  - run a URL Inspection live test and Request Indexing for the three new guides, Tirzepatide 10 mg and Cagrilintide 5 mg
- **Tesamorelin 10 mg (post 442)** is a draft marked "Outside Launch 1.0 catalog". Its URL 301s to `/ipamorelin-vs-tesamorelin-research-comparison/`. It was not republished, because that would be a product change.
- **Semaglutide 10 mg** 301s to `/semaglutide-vs-tirzepatide/` on purpose. The indexable Semaglutide product is `/products/semaglutide-5mg-research-peptide/`.
- **GLP products carry the meta `_opl_public_sale_removed_20260702`,** a compliance memo. They remain indexable as before; the owner should confirm that is intended.
- **COA records are not published** for Retatrutide 10 mg, Cagrilintide, BPC-157, MOTS-c or the BPC-157 + TB-500 blend. These pages say so instead of linking a COA.
- **`/peptide-catalog/`** may compete with `/research-catalog/` for catalog queries. Not changed.
- **`/gh-axis-research-peptides-cjc-1295-ipamorelin-tesamorelin/`** does not exist:
  - Links to it are rewritten to `/cjc-1295-vs-tesamorelin/`.
  - One reference remains in the Research Library's JSON search index (a data attribute, not a link).

## Rollback

- All previous values are in the option `opseo3_backup_20260929`: meta keys, snippet #49 code, snippet #454 code, and attribute state.
- Delete the MU plugin to remove the output-layer changes.
- The three new posts can be moved to draft.
