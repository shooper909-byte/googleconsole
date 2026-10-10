# Bundle hero and research-collection images — 2026-10-10

Replaces the old black-label "QR placeholder" renders with composites of the
current product photos, so every vial shown matches the shop: the front label
plus the back vial with its published batch number, or "TESTING / RESULTS SOON"
where no COA batch is published yet.

| File (replaces the `.png` of the same name in `wp-content/uploads/2026/08/`) | Products (front + back vial) |
|---|---|
| `BUNDLE-BUILDER-HERO-20261010.webp` (2000×800) | Retatrutide 5 mg (P260628-L013), Selank 5 mg (OP-20260720-B001), Cagrilintide 5 mg (testing) |
| `OP-RESEARCH-COLLECTION-METABOLIC-20261010.webp` (1600×1000) | Retatrutide 5 mg, Tirzepatide 10 mg (20260609), Semaglutide 5 mg (SGX-OL-2026-0811-S88), Cagrilintide 5 mg (testing) |
| `OP-RESEARCH-COLLECTION-CELLULAR-20261010.webp` | NAD+ 500 mg (AYK20260418-NAD500), GHK-Cu 50 mg (SGX-OL-2026-0811-K99), Selank 5 mg, Cagrilintide 5 mg (testing) |
| `OP-RESEARCH-COLLECTION-NEURO-20261010.webp` | Selank 5 mg, NAD+ 500 mg, GHK-Cu 50 mg |
| `OP-RESEARCH-COLLECTION-REGEN-20261010.webp` | GHK-Cu 50 mg, NAD+ 500 mg, Selank 5 mg |

Each collection keeps the same products the old image showed. No batch number
was invented; every one is copied from the product photo already on the site.

Rebuild: `python3 build_images.py <dir of *-front-back-*.png> out/`

## Deployment record (2026-10-10, Pressable site 1635271)

- **Backup first:** Pressable on-demand DB backup (request 982145).
- **New attachments:** 4047 (hero), 4048 Metabolic, 4049 Cellular, 4050 Neuro, 4051 Regen, all in `uploads/2026/10/`. They use new file names because the old PNGs are served with a 1-year cache. The old attachments 3496 and 3724–3727 and their files are unchanged.
- **`apply-imagery.php apply`:** rewrote the old PNG URLs in pages 381 (home) and 3485 (Build Your Bundle), WPCode 3540 (published), 3637 and 3669 (drafts), one `_wpcode_last_error` meta and the `wpcode_snippets` option. Previous values are in the option `opimg_backup_20261010`.
- **`patch-snippet-587.php apply`:** Code Snippet #587 (Research Collections Migration) now maps products 3474/3477/3480/3483 to 4048–4051. Its output rewrite also sends the old collection PNG URLs to the new WebP files. The old code is in `opimg_backup_20261010_snippet587`.
- **Featured images:** the raw `_thumbnail_id` of products 3474/3477/3480/3483 is set to 4048–4051. The shop and collections grid reads this raw value, bypassing #587's filter. Previous values (3490, 3811, 3813, 3769) are in `opimg_backup_20261010_thumbs`.
- **Caches:** object cache flushed, edge cache purged.
- **Verified live:** `/`, `/build-your-research-bundle/`, `/research-collections/`, `/shop/`, and the Metabolic and CNS collection product pages all return 200. None of them references `2026/08/BUNDLE-BUILDER-HERO*`, `OP-RESEARCH-COLLECTION-*.png` or `OP-STACK-*` any more.

## Round 2 — every page (2026-10-10)

Request: go through every page and do the same for products whose images are not yet in the batch-label style.

Method: crawled all 227 sitemap URLs, made contact sheets of every image that wasn't a logo, lab photo or COA, and sorted them into old-style product shots and the rest.

### New images (`out-products/`, built by `build_products.py` and `make_klow.py`)

| Product(s) | Image |
|---|---|
| 8 six-vial kits (Cagrilintide, GHK-Cu, NAD+, Retatrutide 20/5, Selank, Semaglutide 5, Tirzepatide 10) | The product's batch-label photo with a "6-VIAL RESEARCH KIT" heading |
| Build Your Research Bundle: 3, 6 and 9 vials | Grid of 3/6/9 batch-label product photos with a heading |
| Comparative Pathway Collection | Semaglutide 5 mg + Cagrilintide 5 mg (the products the old image showed) |
| Telomere Biology Reference Collection | GHK-Cu 50 mg + BPC-157 5 mg (the products its description lists) |
| KLOW blend | The KPV + GHK-Cu blend photo with the front label relabelled "KLOW BLEND" (Oswald, colours sampled from the label). The back vial keeps "TESTING / RESULTS SOON": no KLOW COA is published |
| Mega menu (every page) | Sema + Cagri blend batch-label photo, 800×534 like the old slot |

### Scripts (each one is a dry run unless given `apply`; each saves previous values)

| Script | What it changed | Backup option |
|---|---|---|
| `apply-products.php` | Raw `_thumbnail_id` of the 15 products; Snippet #587 entries `3487/70 => 3728` | `opimg_backup_20261010_products` |
| `apply-cleanup.php` | 24 product galleries (old vial shots removed; batch-label and COA images kept), 16 Rank Math social-image values, Snippet #100 mega-menu image | `opimg_backup_20261010_cleanup` |
| `apply-content-map.php` | Old images of products that are sold → that product's batch-label photo, in page/post content, Elementor data, options and snippets (32 rows, then 3 more) | `opimg_backup_20261010_content`, `opimg_backup_20261010_content_<HHMMSS>` |
| `survey-remaining.php`, `scan-pages.php` | Read-only checks | — |

DB backup before round 2: Pressable on-demand request 982154.

### Left as is, deliberately

Images of products that are not sold and have no batch-label photo stay unchanged, because there is nothing truthful to replace them with. These include Epitalon, Ipamorelin, Tesamorelin, SS-31, IGF-1 LR3, Follistatin, PEG-MGF, Dihexa, Pinealon, ARA-290, Thymosin α1/β4, Melanotan II, the GLP-2TZ / GLP-3RT / MOTS-c + SS-31 / Epitalon + Pinealon blends, bacteriostatic water and the supplements. They appear only on `/compare/`, `/research-blends/` and a few comparison articles.
