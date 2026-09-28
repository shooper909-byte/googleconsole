# OligoPoly Search Console remediation — 2026-09-28

This package is deliberately targeted. It does **not** try to make every Search Console exclusion disappear.

## Implemented fixes

- Legacy product and research-collection URLs redirect to the current published WooCommerce product **resolved by SKU at runtime**, avoiding guessed slugs.
- Confirmed corrupted `Stack` → `Research Panel` hrefs are repaired without globally renaming visible text.
- Live `?opl7_pdf=...` responses receive `X-Robots-Tag: noindex, noarchive`.
- Intentional retired taxonomies, WordPress system paths, and compliance-risk retired pages are left alone.

## Production execution

This must run on the **Pressable production WordPress environment** because GitHub is not the live WordPress database.

```bash
cd remediation-2026-09-28
bash apply-live-remediation.sh
```

The script exports a database backup before any write.

After execution, purge Pressable/CDN cache and live-test the current September examples in Search Console. Do not restart validation for intentional 404/410/system URLs.
