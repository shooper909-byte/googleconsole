#!/usr/bin/env python3
"""Build the Search Console URL-Inspection worksheet."""
import csv, json, urllib.parse

inv = list(csv.DictReader(open('url-inventory.csv')))
by = {r['url']: r for r in inv}

def norm(u):
    p = urllib.parse.urlparse(u); path = p.path or '/'
    if not path.endswith('/') and '.' not in path.rsplit('/',1)[-1]: path += '/'
    return urllib.parse.urlunparse((p.scheme, p.netloc.lower(), path, '', p.query, ''))

rows, seen = [], set()
def add(pri, url, why, action):
    k = norm(url)
    if k in seen: return
    seen.add(k)
    r = by.get(url) or {}
    rows.append({
        'priority': pri, 'url': url, 'why_inspect': why,
        'our_final_status': r.get('final_status',''),
        'our_redirects_to': r.get('final_url','') if r.get('hops','0') not in ('0','') else '',
        'our_declared_canonical': r.get('canonical',''),
        'our_meta_robots': r.get('meta_robots',''),
        'our_internal_inbound_links': r.get('inbound_count',''),
        'our_in_sitemap': r.get('in_sitemap',''),
        'recommended_action': action,
        # --- fill these in from GSC URL Inspection ---
        'GSC_verdict':'', 'GSC_google_selected_canonical':'',
        'GSC_user_declared_canonical':'', 'GSC_coverage_state':'',
        'GSC_last_crawl':'', 'GSC_crawled_as':'', 'GSC_indexing_allowed':'',
        'GSC_referring_page':'', 'GSC_matches_our_expectation_YN':'',
    })

# P0 - the entire measured search footprint lives on the legacy host
add('P0','https://shop.oligopolypeptides.com/products/selank-5mg-research-peptide/',
    'Holds 22 of the property\'s 24 impressions and its only click. Confirm Google now selects the www URL as canonical.',
    'Confirm 301 is one hop to the live www page; keep it permanently.')
add('P0','https://www.oligopolypeptides.com/products/selank-5mg-research-peptide/',
    'Redirect destination of the only page earning clicks. Was a 404 in the August audit, now 200.',
    'Verify indexed and self-canonical; this must inherit the shop URL\'s equity.')
add('P0','https://shop.oligopolypeptides.com/',
    'Legacy host homepage, 2 impressions.','Confirm one-hop 301 to www homepage.')

# P1 - orphans (no internal links at all) => classic "Discovered - not indexed"
orphans = [r for r in inv if r['in_sitemap']=='True' and r['inbound_count']=='0'
           and r['bucket']=='Indexable']
for r in sorted(orphans, key=lambda x:x['url']):
    add('P1', r['final_url'],
        'In sitemap, indexable, but ZERO internal links point to it (orphan).',
        'Link it from a relevant hub/category page, then request indexing.')

# P1 - sitemap URLs that redirect
for r in inv:
    if r['in_sitemap']=='True' and r['hops'] not in ('0','') and r['final_status']=='200':
        hops = int(r['hops'])
        add('P1', r['url'],
            f'Listed in the sitemap but {hops}-hop redirect to {r["final_url"]}.',
            'Replace with the final destination in the sitemap, or drop it.')

# P1 - noindex but submitted in sitemap (direct contradiction)
for r in inv:
    if r['in_sitemap']=='True' and 'noindex' in (r['meta_robots'] or '').lower():
        add('P1', r['url'],
            'Submitted in the sitemap while serving noindex - a direct contradiction.',
            'Remove from sitemap (these are account pages, correctly noindexed).')

# P2 - dead ends still receiving internal links
for r in sorted(inv, key=lambda x:-int(x['inbound_count'] or 0)):
    if r['bucket'].startswith('Not found') and int(r['inbound_count'] or 0) > 0:
        add('P2', r['url'],
            f'404 with {r["inbound_count"]} internal links pointing at it.',
            'Restore the page or repoint the links.')
    if r['bucket'].startswith('Malformed') and int(r['inbound_count'] or 0) > 0:
        add('P2', r['url'],
            f'Malformed URL (literal space) with {r["inbound_count"]} internal links.',
            'Fix the href values; a rename overwrote link targets as well as display text.')

cols = ['priority','url','why_inspect','our_final_status','our_redirects_to',
        'our_declared_canonical','our_meta_robots','our_internal_inbound_links',
        'our_in_sitemap','recommended_action','GSC_verdict','GSC_google_selected_canonical',
        'GSC_user_declared_canonical','GSC_coverage_state','GSC_last_crawl','GSC_crawled_as',
        'GSC_indexing_allowed','GSC_referring_page','GSC_matches_our_expectation_YN']
rows.sort(key=lambda r:(r['priority'], r['url']))
with open('gsc-inspection-worksheet.csv','w',newline='') as fh:
    w=csv.DictWriter(fh, fieldnames=cols, lineterminator='\n'); w.writeheader(); w.writerows(rows)

import collections
print("worksheet rows:", len(rows))
for p,c in sorted(collections.Counter(r['priority'] for r in rows).items()):
    print(f"  {p}: {c}")
