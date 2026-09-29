#!/usr/bin/env bash
#
# Post-deploy verification for the "Blocked due to other 4xx issue" fix.
#
# Run it before applying the change to capture a baseline, and again after the
# robots.txt rule is live. Everything here is read-only GET traffic.
#
#   bash robots-fix/verify.sh
#
# Exit status 0 means: the rule is live, the AJAX endpoints are blocked from
# crawling, nothing in the sitemap got caught, and the storefront + PayPal
# endpoint behave exactly as they did before.

set -uo pipefail

SITE="https://www.oligopolypeptides.com"
RULE="Disallow: /*?wc-ajax="
FAIL=0

pass() { printf '  [ok]   %s\n' "$1"; }
fail() { printf '  [FAIL] %s\n' "$1"; FAIL=$((FAIL + 1)); }

status() { curl -sS -o /dev/null -w '%{http_code}' "$1"; }
header() { curl -sSI "$1" | grep -i "^$2:" | tr -d '\r'; }

echo "== 1. robots.txt carries the rule =="
ROBOTS="$(curl -sS "$SITE/robots.txt")"
if grep -qF -- "$RULE" <<<"$ROBOTS"; then
  pass "robots.txt contains: $RULE"
else
  fail "robots.txt is missing: $RULE  (the fix is not live yet)"
fi
if [ "$(grep -cF -- "$RULE" <<<"$ROBOTS")" -le 1 ]; then
  pass "rule appears at most once (no duplicate from a double-apply)"
else
  fail "rule appears more than once"
fi
if grep -qF 'Allow: /wp-admin/admin-ajax.php' <<<"$ROBOTS"; then
  pass "admin-ajax.php is still explicitly allowed (needed for rendering)"
else
  fail "the Allow rule for admin-ajax.php disappeared"
fi
if grep -qF "Sitemap: $SITE/sitemap_index.xml" <<<"$ROBOTS"; then
  pass "sitemap directive intact"
else
  fail "sitemap directive missing from robots.txt"
fi

echo
echo "== 2. The rule blocks the endpoints and nothing else =="
printf '%s\n' "$ROBOTS" > /tmp/robots-live.$$.txt
if python3 "$(dirname "$0")/check-robots-coverage.py" "/tmp/robots-live.$$.txt" | tail -1 | grep -q PASS; then
  pass "matcher: AJAX endpoints blocked, all sitemap URLs still crawlable"
else
  fail "matcher reported a problem — run check-robots-coverage.py for detail"
fi
rm -f "/tmp/robots-live.$$.txt"

echo
echo "== 3. Payments are untouched (robots.txt never reaches a browser or PayPal) =="
PPC_CODE="$(status "$SITE/?wc-ajax=ppc-create-setup-token")"
PPC_BODY="$(curl -sS "$SITE/?wc-ajax=ppc-create-setup-token")"
if [ "$PPC_CODE" = "400" ] && grep -q 'Could not validate nonce' <<<"$PPC_BODY"; then
  pass "setup-token endpoint still rejects unauthenticated calls (400, nonce check intact)"
else
  fail "setup-token endpoint changed: HTTP $PPC_CODE / $PPC_BODY"
fi
FRAG_CODE="$(status "$SITE/?wc-ajax=get_refreshed_fragments")"
if [ "$FRAG_CODE" = "200" ]; then
  pass "cart-fragment AJAX still answers 200 for real visitors"
else
  fail "cart fragments returned HTTP $FRAG_CODE"
fi

echo
echo "== 4. Storefront still serves =="
for path in "/" "/research-catalog/" "/peptide-catalog/" "/cart/"; do
  code="$(status "$SITE$path")"
  [ "$code" = "200" ] && pass "$path -> 200" || fail "$path -> $code"
done
PRODUCT="$(curl -sS "$SITE/product-sitemap.xml" | grep -o '<loc>[^<]*</loc>' | head -1 | sed 's#</\?loc>##g')"
if [ -n "$PRODUCT" ]; then
  code="$(status "$PRODUCT")"
  [ "$code" = "200" ] && pass "product page $PRODUCT -> 200" || fail "product page $PRODUCT -> $code"
  body="$(curl -sS "$PRODUCT")"
  if grep -qE 'ppcp|woocommerce-paypal-payments' <<<"$body"; then
    pass "PayPal Payments assets still render on the product page"
  else
    fail "PayPal Payments assets missing from the product page"
  fi
  unset body
fi
CHECKOUT="$(status "$SITE/checkout/")"
case "$CHECKOUT" in
  200|302) pass "/checkout/ -> $CHECKOUT (302 to /cart/ is normal with an empty cart)" ;;
  *)       fail "/checkout/ -> $CHECKOUT" ;;
esac

echo
echo "== 5. The other four Search Console examples =="
printf '  %-62s %s\n' "/wp-admin/admin-ajax.php" "$(status "$SITE/wp-admin/admin-ajax.php") (400 expected without an action; allowed on purpose)"
printf '  %-62s %s\n' "/wp-content/*" "$(status "$SITE/wp-content/*") (404 expected; literal wildcard, not a page)"
printf '  %-62s %s\n' "/wp-json/oligopoly/v1/coa" "$(status "$SITE/wp-json/oligopoly/v1/coa") (200 — already fixed, stale in Search Console)"
printf '  %-62s %s\n' "/?wc-ajax=%%endpoint%%" "$(status "$SITE/?wc-ajax=%25%25endpoint%25%25") (200 — already fixed, stale in Search Console)"
header "$SITE/wp-json/oligopoly/v1/coa" "x-robots-tag" | sed 's/^/  /'

echo
if [ "$FAIL" -eq 0 ]; then
  echo "RESULT: PASS — fix is live and nothing else moved."
else
  echo "RESULT: $FAIL check(s) failed."
fi
exit $((FAIL > 0))
