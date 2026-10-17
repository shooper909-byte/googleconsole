# Homepage "Research collections" cards — 2026-10-17

Restyles the "Research collections" section on the `https://www.oligopolypeptides.com` homepage (Pressable site 1635271, front page = page 381).

**Status: deployed to production on 2026-10-17.**

## Why it looked unfinished

The section's markup uses `.op9-stack-card` / `.op9-stack-body` / `.op9-stack-link`, but the homepage stylesheet only defines the matching design for `.op9-set-card`. With no rules for the `stack` class names, the cards rendered as bare images, underlined headings and loose links. This is probably another casualty of the set/stack renames already documented in `../remediation-2026-09-28/`.

## What changed

CSS only. No post content, Elementor data, images, links, text or other sections were touched.

- Cards now match the site's existing product cards: panel background, 1px border, 16px radius, consistent 8:5 image crop, aligned titles, and the "Explore the set →" call to action in the accent colour.
- A "Collection 01–04" badge comes from a CSS counter, not new markup.
- The whole card is clickable. It's the same `/research-collections/` link through a stretched `::after`, so no new link was added.
- Hover lift, accent border and image zoom; a keyboard focus ring; motion is off under `prefers-reduced-motion`.
- The stray empty `<p>` elements that `wpautop` inserted into the grid and cards are hidden.
- Existing breakpoints are kept: 4 columns, then 2 at ≤980px, then 1 at ≤720px.

## Files

| File | Purpose |
|---|---|
| `collections.css` | The rules, all scoped to `#op9-home` |
| `oligopoly-home-collections.php` | MU plugin that prints `collections.css` in `wp_head` on the front page only |
| `before-desktop.png`, `live-desktop.png`, `live-mobile.png` | Before and after renders |

## Deployment record

- Wrote `WPMU_PLUGIN_DIR/oligopoly-home-collections.php` through `wp eval`, after checking that no file with that name existed (sha256 `fc7ea5bd44bd9b5c97a314b8ba207eadaa4b991364992e3ba27d65cec93969ad`).
- Purged the Pressable edge cache and flushed the object cache.
- Checked the result:
  - The homepage serves `<style id="opl-home-collections-css">`.
  - `/research-collections/` doesn't.
  - The rest of the homepage HTML is unchanged from the pre-deploy copy.

## Revert

Delete `wp-content/mu-plugins/oligopoly-home-collections.php`, then purge the caches.
