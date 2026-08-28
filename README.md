# googleconsole

Search Console remediation work for
[oligopolypeptides.com](https://www.oligopolypeptides.com).

## Contents

| File | What it is |
|---|---|
| [`FINDINGS.md`](FINDINGS.md) | The audit — what is actually wrong, what is fine, and why |
| [`remediation-sheet.csv`](remediation-sheet.csv) | Prioritised fix list (P1/P2/P3 + verified "no action" rows) |
| [`redirect-map.csv`](redirect-map.csv) | Broken URL → recommended target, with inbound link counts |
| [`evidence/`](evidence/) | Raw crawl output backing every claim |

## Summary

All 227 sitemap URLs return 200. The Search Console exclusion buckets are
mostly healthy — a large share of the "Not found (404)" count is legacy
taxonomy already returning `410 Gone` on purpose.

The real problem is ~150 broken internal links that Search Console has not
surfaced, most of them from a "Stack" → "Research Panel" find/replace that
rewrote `href` values as well as display text.

Start with the P1 rows in `remediation-sheet.csv`.

## Reproducing the audit

Everything was gathered with `curl` against the live site — no credentials and
no Search Console API access. See the *Method* section of `FINDINGS.md`.
