# Semaglutide 5 mg: batch SGX-OL-2026-0811-S88 layout (2026-10-05)

This gives the Semaglutide 5 mg product (post 3397, `/products/semaglutide-5mg-research-peptide/`) the same batch layout as Tirzepatide, Retatrutide 5 mg, GHK-Cu and NAD+.

## COA

- SteriGenix report RPT-2026-652278 for batch SGX-OL-2026-0811-S88, client OligoPoly, analyzed 08/26/2026.
- Results: LC-MS identity confirmed, HPLC 99.27%, net content 5.07 mg.
- The owner supplied the 2-page report. The existing site PDF `2026/08/OligoPoly_Laboratories_Semaglutide_COA.pdf` is the same report with the OligoPoly cover page in front, so that file is the one linked.

## Changes

- **Featured image:** the vial photo, made by `make_sema.py`.
- **Gallery:** in this order:
  1. the previous main image
  2. the OligoPoly COA cover page, rendered from the site PDF (as Tirzepatide's gallery has a COA cover)
  3. the highlighted COA
- **Description:** a "Semaglutide 5 mg — Batch SGX-OL-2026-0811-S88 COA" box at the top.
- **Short description:** a COA link was added.
- **Rank Math share images:** now point to the vial photo.

## Rollback

Restore the values from the option `opl_sema5_coa_backup_20261005`.
