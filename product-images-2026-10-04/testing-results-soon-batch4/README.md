# All remaining products without a COA: "testing / results soon" vial images (2026-10-05)

The owner asked for every product without a published COA to show as under testing.

## COA status, checked against `/research-peptides-with-coa/`

| Group | Products | Treatment |
|---|---|---|
| COA published | Tirzepatide 10 mg, Retatrutide 5 mg, Semaglutide 5 mg, Selank 5 mg, NAD+ 500 mg, GHK-Cu 50 mg | Keep their batch / COA layout |
| COA published | KLOW 80 mg (batch 20260805KL80) | Not marked as testing |
| Already done earlier | AOD-9604, Retatrutide 10 mg and 20 mg, Cagrilintide 5 mg, MOTS-c | — |
| Done here | The 15 products listed in `specs.txt` | See below |
| Not changed | 6-vial kits, collections, bundles | Multi-vial packs with their own artwork; a two-vial photo would misrepresent them |

## The 15 products done here

Products (post IDs in `specs.txt`):

- Semaglutide 10 mg
- Tirzepatide 20 mg
- BPC-157 10 mg and 5 mg
- TB-500 10 mg
- Semax 10 mg
- KPV 10 mg
- DSIP 5 mg
- PT-141 10 mg
- CJC-1295 No DAC 5 mg
- the blends: BPC-157 + TB-500, Semaglutide + Cagrilintide, Retatrutide + Cagrilintide, BPC-157 + KPV, KPV + GHK-Cu

For each one:

- **Featured image:** a new vial photo, made from the Tirzepatide render. `make_example.py` shows the script; only the label text changes between products. The batch label reads TESTING / RESULTS SOON.
- **Gallery:** the previous main image moves to the front of the gallery, and existing gallery images are kept.
- **Rank Math share images:** now point to the new photo.
- **Not changed:** product text, price and stock.

## Rollback

Every previous value is in the option `opl_testing_images_backup_20261005`, keyed by post ID.
