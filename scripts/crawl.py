#!/usr/bin/env python3
"""Dependency-free crawl of www.oligopolypeptides.com.

Records, for every sitemap URL and every internal link target:
status, full redirect chain, declared rel=canonical, meta robots,
X-Robots-Tag, <title>, first <h1>, and internal outlinks.
"""
import concurrent.futures as cf
import gzip
import html
import json
import re
import ssl
import sys
import threading
import urllib.error
import urllib.parse
import urllib.request

HOST = "www.oligopolypeptides.com"
ORIGIN = "https://" + HOST
UA = "Mozilla/5.0 (compatible; OligoPolySEOAudit/1.0)"
TIMEOUT = 30
WORKERS = 8

CTX = ssl.create_default_context(cafile="/root/.ccr/ca-bundle.crt")

RE_CANON = re.compile(
    r"""<link\b[^>]*\brel\s*=\s*["']?canonical["']?[^>]*>""", re.I)
RE_HREF = re.compile(r"""\bhref\s*=\s*["']([^"']*)["']""", re.I)
RE_ROBOTS = re.compile(
    r"""<meta\b[^>]*\bname\s*=\s*["']?robots["']?[^>]*>""", re.I)
RE_CONTENT = re.compile(r"""\bcontent\s*=\s*["']([^"']*)["']""", re.I)
RE_TITLE = re.compile(r"<title[^>]*>(.*?)</title>", re.I | re.S)
RE_H1 = re.compile(r"<h1[^>]*>(.*?)</h1>", re.I | re.S)
RE_A = re.compile(r"""<a\b[^>]*\bhref\s*=\s*["']([^"']*)["']""", re.I)
RE_TAG = re.compile(r"<[^>]+>")

_print_lock = threading.Lock()


def text(raw):
    return re.sub(r"\s+", " ", html.unescape(RE_TAG.sub("", raw or ""))).strip()


def fetch(url, max_hops=10):
    """Fetch without auto-redirect so the whole chain is visible."""
    chain, current = [], url
    for _ in range(max_hops):
        req = urllib.request.Request(current, headers={
            "User-Agent": UA,
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Encoding": "gzip",
        })
        opener = urllib.request.build_opener(NoRedirect, urllib.request.HTTPSHandler(context=CTX))
        try:
            resp = opener.open(req, timeout=TIMEOUT)
            code, hdrs = resp.getcode(), resp.headers
            body = resp.read()
        except urllib.error.HTTPError as e:
            code, hdrs = e.code, e.headers
            try:
                body = e.read()
            except Exception:
                body = b""
        except Exception as e:
            chain.append({"url": current, "status": 0, "error": type(e).__name__ + ": " + str(e)})
            return chain, None, {}
        if hdrs.get("Content-Encoding", "").lower() == "gzip":
            try:
                body = gzip.decompress(body)
            except Exception:
                pass
        hop = {"url": current, "status": code,
               "x_robots_tag": hdrs.get("X-Robots-Tag") or "",
               "content_type": (hdrs.get("Content-Type") or "").split(";")[0].strip()}
        if 300 <= code < 400 and hdrs.get("Location"):
            nxt = urllib.parse.urljoin(current, hdrs["Location"])
            hop["location"] = nxt
            chain.append(hop)
            if nxt == current:
                return chain, None, {}
            current = nxt
            continue
        chain.append(hop)
        return chain, body, dict(hdrs)
    return chain, None, {}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None


def parse(url, body):
    out = {"canonical": "", "meta_robots": "", "title": "", "h1": "",
           "h1_count": 0, "internal_links": [], "external_links": 0}
    if not body:
        return out
    try:
        doc = body.decode("utf-8", "replace")
    except Exception:
        return out
    m = RE_CANON.search(doc)
    if m:
        h = RE_HREF.search(m.group(0))
        if h:
            out["canonical"] = urllib.parse.urljoin(url, html.unescape(h.group(1).strip()))
    m = RE_ROBOTS.search(doc)
    if m:
        c = RE_CONTENT.search(m.group(0))
        if c:
            out["meta_robots"] = c.group(1).strip()
    m = RE_TITLE.search(doc)
    if m:
        out["title"] = text(m.group(1))
    h1s = RE_H1.findall(doc)
    out["h1_count"] = len(h1s)
    if h1s:
        out["h1"] = text(h1s[0])
    seen = set()
    for href in RE_A.findall(doc):
        href = html.unescape(href.strip())
        if not href or href.startswith(("#", "mailto:", "tel:", "javascript:")):
            continue
        absu = urllib.parse.urljoin(url, href)
        p = urllib.parse.urlparse(absu)
        if p.scheme not in ("http", "https"):
            continue
        if p.netloc.endswith("oligopolypeptides.com"):
            clean = urllib.parse.urlunparse((p.scheme, p.netloc, p.path, p.params, p.query, ""))
            if clean not in seen:
                seen.add(clean)
                out["internal_links"].append(clean)
        else:
            out["external_links"] += 1
    return out


def crawl_one(url):
    chain, body, _ = fetch(url)
    final = chain[-1]
    rec = {
        "url": url,
        "status": chain[0].get("status", 0),
        "hops": len(chain) - 1,
        "redirect_chain": [h["url"] for h in chain[1:]],
        "final_url": final.get("url", url),
        "final_status": final.get("status", 0),
        "content_type": final.get("content_type", ""),
        "x_robots_tag": final.get("x_robots_tag", ""),
        "error": final.get("error", ""),
    }
    rec.update(parse(rec["final_url"], body))
    rec["bytes"] = len(body or b"")
    with _print_lock:
        sys.stderr.write(".")
        sys.stderr.flush()
    return rec


def main():
    seeds = [l.strip() for l in open(sys.argv[1]) if l.strip()]
    with cf.ThreadPoolExecutor(WORKERS) as ex:
        pages = list(ex.map(crawl_one, seeds))
    sys.stderr.write("\n[sitemap pass done]\n")

    # every internal link target discovered on those pages
    inbound = {}
    for p in pages:
        for t in p["internal_links"]:
            inbound.setdefault(t, set()).add(p["url"])
    known = {p["url"] for p in pages}
    todo = sorted(t for t in inbound if t not in known)
    sys.stderr.write("[link targets to probe: %d]\n" % len(todo))
    with cf.ThreadPoolExecutor(WORKERS) as ex:
        targets = list(ex.map(crawl_one, todo))
    sys.stderr.write("\n[link pass done]\n")

    for rec in pages + targets:
        rec["inbound_count"] = len(inbound.get(rec["url"], ()))
        rec["inbound_sample"] = sorted(inbound.get(rec["url"], ()))[:5]
        rec["in_sitemap"] = rec["url"] in known

    json.dump({"pages": pages, "link_targets": targets},
              open(sys.argv[2], "w"), indent=1)
    sys.stderr.write("wrote %s\n" % sys.argv[2])


if __name__ == "__main__":
    main()
