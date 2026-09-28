#!/usr/bin/env bash
# Live HTTP verification: prints each redirect hop and the final status.
BASE="${BASE:-https://www.oligopolypeptides.com}"
chain() {
  local u="$1" out="" r s n
  for _ in 1 2 3 4 5 6 7 8 9 10; do
    r=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" --max-time 20 "$u"); s=${r%% *}; n=${r#* }
    out+="$s $u"
    if [[ "$s" =~ ^30 ]] && [ -n "$n" ]; then out+=" -> "; u="$n"; else break; fi
  done
  echo "$out"
}
while read -r p; do [ -n "$p" ] && chain "$BASE$p"; done <<'URLS'
/products/performance-recovery-research-Research%20Panel/
/products/recovery-support-research-Research%20Panel/
/products/cognitive-research-Research%20Panel/
/products/metabolic-research-Research%20Panel/
/products/advanced-multi-pathway-research-Research%20Panel/
/products/immune-optimization-research-Research%20Panel/
/products/starter-research-Research%20Panel/
/Research%20Panels/
/products/ara-290
/products/mots-c-ss-31-blend/
/advanced-research-stack
/products/metabolic-stack
/epitalon-telomere-research
/product-category/research-blends
/peptide-reconstitution-guide/
/products/tesamorelin-10mg-research-peptide/
/products/ss-31
/products/bpc-157-tb-500-blend/
URLS
for h in http://oligopolypeptides.com/ http://www.oligopolypeptides.com/ https://oligopolypeptides.com/ https://www.oligopolypeptides.com/; do chain "$h"; done
curl -sS -D - -o /dev/null "$BASE/?opl7_pdf=research-compound&item=441" | grep -iE '^HTTP|x-robots-tag'
