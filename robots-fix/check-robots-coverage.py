#!/usr/bin/env python3
"""Google-style robots.txt matcher, used to prove the new wc-ajax rule blocks
the AJAX endpoints and nothing else.

Implements the matching rules Google documents: `*` matches any sequence,
`$` anchors the end, the longest matching rule wins, and Allow wins a tie.
Compared against path + query string, which is what Google matches on.
"""
import re
import sys
import urllib.request
import urllib.parse

UA_GROUP = "*"


def parse(robots_text):
    rules, in_group = [], False
    for raw in robots_text.splitlines():
        line = raw.split("#", 1)[0].strip()
        if not line or ":" not in line:
            continue
        field, value = (p.strip() for p in line.split(":", 1))
        field = field.lower()
        if field == "user-agent":
            in_group = value == UA_GROUP
        elif field in ("allow", "disallow") and in_group and value:
            rules.append((field, value))
    return rules


def to_regex(pattern):
    out = ""
    for ch in pattern:
        if ch == "*":
            out += ".*"
        elif ch == "$":
            out += "$"
        else:
            out += re.escape(ch)
    return re.compile("^" + out)


def verdict(rules, url):
    p = urllib.parse.urlsplit(url)
    target = p.path + (("?" + p.query) if p.query else "")
    best = None  # (length, field)
    for field, value in rules:
        if to_regex(value).match(target):
            length = len(value)
            if best is None or length > best[0] or (length == best[0] and field == "allow"):
                best = (length, field)
    return "ALLOWED" if best is None or best[1] == "allow" else "BLOCKED"


def sitemap_urls(index_url):
    urls = []
    with urllib.request.urlopen(index_url) as r:
        index = r.read().decode()
    for sm in re.findall(r"<loc>(.*?)</loc>", index):
        with urllib.request.urlopen(sm) as r:
            urls += re.findall(r"<loc>(.*?)</loc>", r.read().decode())
    return urls


MUST_BLOCK = [
    "https://www.oligopolypeptides.com/?wc-ajax=ppc-create-setup-token",
    "https://www.oligopolypeptides.com/?wc-ajax=%%endpoint%%",
    "https://www.oligopolypeptides.com/?wc-ajax=get_refreshed_fragments",
    "https://www.oligopolypeptides.com/?wc-ajax=add_to_cart",
    "https://www.oligopolypeptides.com/?wc-ajax=checkout",
    "https://www.oligopolypeptides.com/cart/",
    "https://www.oligopolypeptides.com/checkout/",
]

MUST_ALLOW = [
    "https://www.oligopolypeptides.com/",
    "https://www.oligopolypeptides.com/research-catalog/",
    "https://www.oligopolypeptides.com/peptide-catalog/",
    "https://www.oligopolypeptides.com/faq/",
    "https://www.oligopolypeptides.com/research-stacks/",
    "https://www.oligopolypeptides.com/products/bpc-157-5mg-research-peptide/",
    "https://www.oligopolypeptides.com/product-category/recovery-research/",
    "https://www.oligopolypeptides.com/wp-admin/admin-ajax.php",
    "https://www.oligopolypeptides.com/wp-json/oligopoly/v1/coa",
    # product URLs that merely contain the word "cart" or a query must stay crawlable
    "https://www.oligopolypeptides.com/contact/?subject=404%20Error",
]


def main():
    robots_file = sys.argv[1] if len(sys.argv) > 1 else "robots-fix/robots.txt.proposed"
    rules = parse(open(robots_file).read())
    print(f"Rules parsed from {robots_file} for User-agent: * -> {len(rules)}")
    for f, v in rules:
        print(f"  {f}: {v}")

    failures = 0

    print("\nMust be BLOCKED (crawl noise / internal endpoints):")
    for u in MUST_BLOCK:
        v = verdict(rules, u)
        ok = v == "BLOCKED"
        failures += not ok
        print(f"  [{'ok' if ok else 'FAIL'}] {v:8} {u}")

    print("\nMust stay ALLOWED (content + render resources):")
    for u in MUST_ALLOW:
        v = verdict(rules, u)
        ok = v == "ALLOWED"
        failures += not ok
        print(f"  [{'ok' if ok else 'FAIL'}] {v:8} {u}")

    print("\nCollateral check — every URL in the sitemap must stay crawlable:")
    urls = sitemap_urls("https://www.oligopolypeptides.com/sitemap_index.xml")
    blocked = [u for u in urls if verdict(rules, u) == "BLOCKED"]
    print(f"  {len(urls)} sitemap URLs checked, {len(blocked)} blocked")
    for u in blocked:
        print(f"  [FAIL] BLOCKED {u}")
    failures += len(blocked)

    print("\nRESULT:", "PASS — rule hits the endpoints and nothing else" if failures == 0
          else f"FAIL — {failures} problem(s)")
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
