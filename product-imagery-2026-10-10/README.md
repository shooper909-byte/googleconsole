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
