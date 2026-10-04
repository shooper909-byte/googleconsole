# GHK-Cu 50 mg: batch SGX-OL-2026-0811-K99 layout (2026-10-04)

This change gives the GHK-Cu 50 mg product (post 441, SKU `OP-LON-GHKCU-50MG`) the same batch layout as Tirzepatide and Retatrutide.

**Status:** deployed and verified live on 2026-10-04.

## What changed

| Item | Before | After |
|---|---|---|
| Featured image | 3434 | 4006 `ghk-cu-50mg-front-back-batch-SGX-OL-2026-0811-K99.png` (Tirzepatide render, relabelled by `make_ghk.py`) |
| Gallery | empty | 3434 (previous main image), 4007 highlighted SteriGenix COA, 4008 Janoshik source-lot test (redacted) |
| Description | no batch section | "GHK-Cu 50 mg — Batch SGX-OL-2026-0811-K99 COA" box at the top, plus a "Source-lot test" paragraph |
| Short description | — | Batch SGX-OL-2026-0811-K99 COA PDF link added |
| Rank Math share images | old 2026/08 vial webp | 4006 |

## Documents

- **Main COA:** the existing site PDF `2026/08/OligoPoly_Laboratories_GHK-Cu_COA.pdf`. It is SteriGenix report RPT-2026-515608 for batch SGX-OL-2026-0811-K99 (analyzed 08/26/2026; HPLC 99.74%; net 52.06 mg). The copy the owner supplied is the same report without the OligoPoly cover page.
- **Source-lot test:** Janoshik task #102112, batch WBS20260130, analyzed 27 Jan 2026, purity 99.453%.
  - The owner says batch SGX-OL-2026-0811-K99 was filled by dividing this 100 mg-vial lot into 50 mg vials.
  - The page says the test was run on a 100 mg vial before division, and that its 114.58 mg figure does not apply to the 50 mg vials.
  - At the owner's request, the Client and Manufacturer fields are covered with visible "REDACTED" bars. No other field or result was altered.
  - The Janoshik verify key is still shown.

## Rollback

All previous values are in the option `opl_ghk50_janoshik_backup_20261004`:

- `post_content`
- `post_excerpt`
- `_thumbnail_id`
- `_product_image_gallery`
- the four Rank Math image metas
