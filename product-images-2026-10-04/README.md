# Retatrutide 5 mg: batch P260628-L013 images and COA section (2026-10-04)

This change gives the Retatrutide 5 mg product page (`/products/retatrutide-5mg-research-peptide/`, post 3395, SKU `OP-MET-RETA-5MG`) the same batch-traceability layout as Tirzepatide 10 mg (post 39).

**Status:** deployed to production and verified live on 2026-10-04.

## What changed

| Item | Before | After |
|---|---|---|
| Featured image (also the catalog card) | 3433 `2026/08/OP-MET-RETA-5MG.png` | 4005 `2026/10/retatrutide-5mg-front-back-batch-P260628-L013.png`: the same photo render as Tirzepatide's catalog image, with the labels changed to RETATRUTIDE 5 MG / batch P260628-L013 |
| Gallery | empty | 4001 `…-front-batch-side-P260628-L013.webp`, 4002 `…-web-hero.webp`, 4003 `retatrutide-coa-P260628-L013-highlighted.png` |
| Description | no batch section; "COA status: … no COA is shown for this product" | A new "Retatrutide 5 mg — Batch P260628-L013 COA" section at the top, styled like Tirzepatide's. It has a COA PDF button and the vial/COA hero image. The COA status line now links the published PDF and the batch lookup. |
| Short description | began "Under testing — lot documentation pending." | That opening sentence is removed and a "Batch P260628-L013 COA" PDF link is added (same pattern as Tirzepatide) |

- **COA PDF:** the existing `2026/08/OligoPoly_Laboratories_Retatrutide_COA.pdf`, which `/research-peptides-with-coa/?batch=P260628-L013` already lists as verified.
- **COA facts shown:** SteriGenix report RPT-2026-680901, analyzed 08/26/2026, HPLC 99.41%. All of them come from that COA.

The source images are in `images/`.

The featured image was made from `2026/08/tirzepatide-10mg-front-back-batch20260609.png` by `images/make_reta.py`. The script erases the product-name and batch text, then sets the new text in Oswald SemiBold, using the same white and magenta as the original. The caps are still black, as in the Tirzepatide render; the COA lists the cap colour as white.

## How it was applied

1. The previous content, excerpt, thumbnail and gallery were saved to the option `opl_reta5_images_backup_20261004`.
2. The images were uploaded through the REST media endpoint. A temporary application password was used and revoked immediately afterwards; a check confirmed it returns 401.
3. Content, excerpt and the featured image were set via REST, and the gallery via `_product_image_gallery`.
4. The object cache was flushed and the edge cache purged.

## Left alone, for the owner

- **SEO description:** the Rank Math meta description (`rank_math_description`) and the Product schema description still begin "Under testing — lot documentation pending."
- **Snippet #616:** Code Snippet #616 ("OPL Launch Readiness Fixes 20261002") contains that wording, so it was not overridden.
- **If you want it changed:** update both the meta description and snippet #616.

## Rollback

Restore the values from `opl_reta5_images_backup_20261004`: `post_content`, `post_excerpt`, `_thumbnail_id` (3433) and `_product_image_gallery` (empty).
