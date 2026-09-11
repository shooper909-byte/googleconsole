# googleconsole

Search Console remediation work for
[oligopolypeptides.com](https://www.oligopolypeptides.com).

## Contents

| File | What it is |
|---|---|
| [`RE-AUDIT-2026-09-10.md`](RE-AUDIT-2026-09-10.md) | **Current.** Re-crawl + the owner's Search Console exports |
| [`exports/`](exports/) | Full 1,128-URL inventory, the 73-URL GSC inspection worksheet, supplied GSC data |
| [`scripts/`](scripts/) | The crawler and analysis that produce those exports |
| [`FINDINGS.md`](FINDINGS.md) | The 2026-08-28 audit — superseded in places, see the re-audit |
| [`remediation-sheet.csv`](remediation-sheet.csv) | Prioritised fix list, refreshed 2026-09-10 (P0/P1/P2 + verified "no action" rows) |
| [`redirect-map.csv`](redirect-map.csv) | 29 broken URLs → verified live targets, with inbound link counts |
| [`fix-corrupted-links.sh`](fix-corrupted-links.sh) | WP-CLI repair for the malformed `-Research Panel` hrefs (revised 2026-09-10) |
| [`evidence/`](evidence/) | Raw crawl output backing every claim |

## Summary (as of 2026-09-10)

All 234 sitemap URLs resolve to 200. Canonical tagging is sound — there are
**no competing self-canonical pages** on the site.

The problems are elsewhere:

- **Indexing is declining.** Not-indexed went 349 → 1,006 since June while
  indexed drifted 273 → 285.
- **All measured search traffic is on the legacy `shop.` host** — 22 of 24
  impressions and the site's only click. The redirects to `www` are correct and
  one hop; they just have not been reattributed yet.
- **The "Stack" → "Research Panel" rename bug recurred** on the newer taxonomy.
  15 malformed URLs (literal spaces in `href`) are still live.
- **30 sitemap URLs have zero internal links**, which is the likely bulk of
  Search Console's 56 `Discovered – currently not indexed`.

Start with [`RE-AUDIT-2026-09-10.md`](RE-AUDIT-2026-09-10.md), then work
[`exports/gsc-inspection-worksheet.csv`](exports/gsc-inspection-worksheet.csv).

## Reproducing the audit

```
python3 scripts/crawl.py <seed-urls.txt> crawl.json   # fetch
python3 scripts/analyze.py                            # classify + inventory
python3 scripts/worksheet.py                          # priority worksheet
```

Standard library only — no credentials and no Search Console API access. The
`GSC_*` columns of the worksheet are filled in by hand from URL Inspection.
