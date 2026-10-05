# Selank 5 mg: batch OP-20260720-B001 layout (2026-10-05)

This gives the Selank 5 mg product (post 447, `/products/selank-5mg-research-peptide/`) the same layout as Semaglutide 5 mg.

## COA

- SteriGenix report RPT-2026-986810 for batch OP-20260720-B001, client OligoPoly, analyzed 08/26/2026.
- Results: LC-MS identity confirmed, HPLC 99.26%, net content 5.11 mg.
- The owner supplied the 2-page report. The existing site PDF `2026/08/OligoPoly_Laboratories_Selank_COA.pdf` is the same report with the OligoPoly cover page in front, so that file is the one linked.
- The cover page rounds net content to "5.1 mg". The page uses the lab report's 5.11 mg.

## Changes

- **Featured image:** the vial photo, made by `make_selank.py`.
- **Gallery:** in this order:
  1. the previous main image
  2. the COA cover page
  3. the highlighted COA
- **Description:** a COA box at the top.
- **Short description:** a COA link was added.
- **Rank Math share images:** now point to the vial photo.

## Rollback

Restore the values from the option `opl_selank5_coa_backup_20261005`.
