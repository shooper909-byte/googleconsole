#!/usr/bin/env python3
"""Turn crawl.json into the export + GSC inspection worksheet."""
import csv, json, collections, urllib.parse

d = json.load(open('crawl.json'))
allr = d['pages'] + d['link_targets']

def norm(u):
    p = urllib.parse.urlparse(u)
    path = p.path or '/'
    if not path.endswith('/') and '.' not in path.rsplit('/', 1)[-1]:
        path += '/'
    return urllib.parse.urlunparse((p.scheme, p.netloc.lower(), path, '', p.query, ''))

def onsite(u):
    return urllib.parse.urlparse(u).netloc.endswith('oligopolypeptides.com')

# ---- dedupe by normalised REQUEST url, keep richest record ----
best = {}
for r in allr:
    k = norm(r['url'])
    cur = best.get(k)
    if cur is None or (len(r.get('title') or '') > len(cur.get('title') or '')):
        if cur: r['inbound_count'] = max(r['inbound_count'], cur['inbound_count'])
        best[k] = r
recs = list(best.values())

def bucket(r):
    """Classify into the Search Console 'why not indexed' vocabulary."""
    if r['error']:                                   return 'Malformed URL (not crawlable)'
    if not onsite(r['final_url']):                   return 'Redirects off-site'
    if r['final_status'] == 404:                     return 'Not found (404)'
    if r['final_status'] == 410:                     return 'Not found (404) [410 Gone]'
    if r['final_status'] in (401, 403):              return 'Blocked due to access forbidden'
    if r['final_status'] == 429:                     return 'Crawl rate-limited (retry)'
    if r['final_status'] != 200:                     return 'Other status %s' % r['final_status']
    if r['hops'] > 0:                                return 'Page with redirect'
    mr = r['meta_robots'].lower()
    if 'noindex' in mr or 'noindex' in r['x_robots_tag'].lower():
        return "Excluded by 'noindex' tag"
    c, f = r['canonical'], r['final_url']
    if c and norm(c) != norm(f):                     return 'Alternate page with proper canonical tag'
    if not c:                                        return 'Duplicate without user-selected canonical'
    return 'Indexable'

for r in recs:
    r['bucket'] = bucket(r)
    r['self_canonical'] = bool(r['canonical']) and norm(r['canonical']) == norm(r['final_url'])

# ---------- full inventory export ----------
cols = ['url','bucket','status','final_status','hops','final_url','redirect_chain',
        'canonical','self_canonical','meta_robots','x_robots_tag','content_type',
        'title','h1','h1_count','in_sitemap','inbound_count']
with open('url-inventory.csv','w',newline='') as fh:
    w = csv.writer(fh, lineterminator='\n'); w.writerow(cols)
    for r in sorted(recs, key=lambda x: x['url']):
        w.writerow([r['url'], r['bucket'], r['status'], r['final_status'], r['hops'],
                    r['final_url'], ' -> '.join(r['redirect_chain']), r['canonical'],
                    r['self_canonical'], r['meta_robots'], r['x_robots_tag'],
                    r['content_type'], r['title'], r['h1'], r['h1_count'],
                    r['in_sitemap'], r['inbound_count']])

print("=== BUCKET DISTRIBUTION (deduped, %d URLs) ===" % len(recs))
for b, c in collections.Counter(r['bucket'] for r in recs).most_common():
    print(f"  {c:5d}  {b}")

# ---------- duplicate titles on UNIQUE final URLs ----------
uniq = {}
for r in recs:
    if r['final_status'] == 200 and r['content_type'].startswith('text/html') and onsite(r['final_url']):
        k = norm(r['final_url'])
        if k not in uniq or r['inbound_count'] > uniq[k]['inbound_count']:
            uniq[k] = r
indexable = [r for r in uniq.values() if 'noindex' not in r['meta_robots'].lower()]
print("\nunique on-site 200 pages: %d   indexable: %d" % (len(uniq), len(indexable)))

t = collections.defaultdict(list)
for r in indexable:
    if r['title']: t[r['title']].append(r)
dups = {k: v for k, v in t.items() if len(v) > 1}
print("\n=== TRUE DUPLICATE TITLES (distinct pages sharing a title) ===")
print("groups:", len(dups), " pages:", sum(len(v) for v in dups.values()))
for title, rs in sorted(dups.items(), key=lambda kv: -len(kv[1])):
    print(f"\n [{len(rs)}x] {title[:92]}")
    for r in sorted(rs, key=lambda x: -x['inbound_count']):
        print(f"    in={r['inbound_count']:4d} {'SITEMAP' if r['in_sitemap'] else '       '} "
              f"canon={'SELF' if r['self_canonical'] else (r['canonical'] or 'NONE')[:55]}")
        print(f"        {r['final_url'][:98]}")
json.dump({k: [r['final_url'] for r in v] for k, v in dups.items()}, open('dup-titles.json','w'), indent=1)

# =====================================================================
# TRUE canonical conflicts: pages that each CLAIM canonical status
# (self-canonical or no canonical) yet share a <title> with another.
# These are what drives GSC "Duplicate, Google chose different canonical".
# =====================================================================
claimers = [r for r in indexable if r['self_canonical'] or not r['canonical']]
ct = collections.defaultdict(list)
for r in claimers:
    if r['title']:
        ct[r['title']].append(r)
conflicts = {k: v for k, v in ct.items() if len(v) > 1}
print("\n\n########## CANONICAL CONFLICTS (competing self-canonical pages) ##########")
print("groups: %d   pages: %d" % (len(conflicts), sum(len(v) for v in conflicts.values())))
rows = []
for title, rs in sorted(conflicts.items(), key=lambda kv: -max(x['inbound_count'] for x in kv[1])):
    rs = sorted(rs, key=lambda x: -x['inbound_count'])
    winner = rs[0]
    print(f"\n [{len(rs)}x] {title[:92]}")
    for r in rs:
        tag = 'KEEP (strongest)' if r is winner else 'CONSOLIDATE ->'
        print(f"    {tag:17} in={r['inbound_count']:4d} sitemap={str(r['in_sitemap']):5} "
              f"canon={'SELF' if r['self_canonical'] else 'NONE'}")
        print(f"        {r['final_url']}")
    for r in rs[1:]:
        rows.append({'title': title, 'duplicate_url': r['final_url'],
                     'duplicate_inbound': r['inbound_count'],
                     'duplicate_in_sitemap': r['in_sitemap'],
                     'recommended_canonical': winner['final_url'],
                     'winner_inbound': winner['inbound_count']})
with open('canonical-conflicts.csv','w',newline='') as fh:
    w = csv.DictWriter(fh, lineterminator='\n', fieldnames=['title','duplicate_url','duplicate_inbound',
        'duplicate_in_sitemap','recommended_canonical','winner_inbound'])
    w.writeheader(); w.writerows(rows)
print("\n[wrote canonical-conflicts.csv: %d rows]" % len(rows))

# ---------- 'Discovered - currently not indexed' candidates ----------
# Profile: indexable, in sitemap, 200, self-canonical, but starved of
# internal links -> Google knows the URL but has not prioritised a crawl.
cands = sorted((r for r in indexable
                if r['in_sitemap'] and r['self_canonical'] and r['hops'] == 0),
               key=lambda x: (x['inbound_count'], x['url']))
print("\n\n########## LOW-EQUITY INDEXABLE SITEMAP URLS ##########")
print("(most likely to sit in 'Discovered - currently not indexed')")
for r in cands[:60]:
    print(f"  inbound={r['inbound_count']:4d}  {r['final_url']}")
with open('discovered-not-indexed-candidates.csv','w',newline='') as fh:
    w = csv.writer(fh, lineterminator='\n'); w.writerow(['url','internal_inbound_links','title'])
    for r in cands:
        w.writerow([r['final_url'], r['inbound_count'], r['title']])
print("[wrote discovered-not-indexed-candidates.csv: %d rows]" % len(cands))
