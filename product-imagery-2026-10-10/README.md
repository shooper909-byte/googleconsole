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
