# AOD-9604 10 mg: vial image, batch "testing / results soon" (2026-10-05)

This gives the AOD-9604 10 mg product (post 60, `/products/aod-9604-10mg-research-peptide/`) the same vial photo as Retatrutide and GHK-Cu.

**Status:** deployed and verified live on 2026-10-05.

- **Image:** made by `make_aod.py` from the Tirzepatide render.
  - Front label: **AOD-9604 10 MG**.
  - Batch label: **TESTING / RESULTS SOON**, because no COA has been published for AOD-9604 yet.
- **Changes on the site:**
  - Featured image: 3081 → 4012.
  - The old image (3081) moved into the gallery.
  - The Rank Math Facebook/Twitter images now point to 4012.
- **Not changed:** product text, price and stock.
- **Rollback:** restore the previous values from the option `opl_aod_image_backup_20261005`.
