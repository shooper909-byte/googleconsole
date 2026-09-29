<?php
/**
 * Plugin Name: OligoPoly SEO Phases 1-3 (2026-09-29)
 * Description: Priority-page indexing fixes, compound identity and documentation blocks for the main research products, and topic-cluster navigation. All identity data is sourced (PubChem / FDA GSRS) and every reference is PubMed-indexed. Research use only; no dosing or medical-use content.
 * Version: 2026.09.29.1
 */
if (!defined('ABSPATH')) { exit; }

const OPSEO3_ORIGIN = 'https://www.oligopolypeptides.com';

/* ------------------------------------------------------------------------ *
 * Phase 1a — internal links that pass through a 301: send them straight to
 * the final URL. Exact path matches only (fragment kept, queries untouched).
 * ------------------------------------------------------------------------ */
function opseo3_link_map(): array {
    return [
        '/coa'                                  => '/research-peptides-with-coa/',
        '/coa/'                                 => '/research-peptides-with-coa/',
        '/quality-standards'                    => '/quality-standards/',
        '/quality-testing/'                     => '/quality-testing-hub/',
        '/research-panels/'                     => '/research-collections/',
        '/peptide-reconstitution-guide/'        => '/peptide-storage-handling-guide/',
        '/cellular-longevity-hub/'              => '/senescence-biology-hub/',
        '/metabolic-research-hub/'              => '/endocrine-receptor-hub/',
        '/recovery-research-hub/'               => '/ecm-signaling-hub/',
        '/product-category/research-products/'  => '/research-catalog/',
        '/products/semaglutide-10mg-research-peptide/' => '/semaglutide-vs-tirzepatide/',
        '/bpc-157-vs-ghk-cu/'                   => '/ghk-cu-vs-bpc-157/',
        '/hplc-vs-lc-ms/'                       => '/quality-testing-hub/hplc-vs-lc-ms/',
    ];
}

function opseo3_rewrite_links(string $html): string {
    if ($html === '' || stripos($html, '<html') === false) { return $html; }
    $map = opseo3_link_map();
    $out = preg_replace_callback('#\bhref=(["\'])([^"\']+)\1#i', function ($m) use ($map) {
        $url = html_entity_decode($m[2], ENT_QUOTES);
        $parts = parse_url($url);
        if ($parts === false || isset($parts['query'])) { return $m[0]; }
        if (isset($parts['host']) && !preg_match('/^(www\.)?oligopolypeptides\.com$/i', $parts['host'])) { return $m[0]; }
        $path = $parts['path'] ?? '';
        if (!isset($map[$path])) { return $m[0]; }
        $new = OPSEO3_ORIGIN . $map[$path] . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
        return 'href=' . $m[1] . esc_url($new) . $m[1];
    }, $html);
    return $out === null ? $html : $out;
}

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) { return; }
    ob_start('opseo3_rewrite_links');
}, -10000);

/* ------------------------------------------------------------------------ *
 * Phase 1b — sitemap. The legacy "v2" SEO plugin drops every URL listed in
 * its old redirect map from the Rank Math sitemap. Code Snippet #567 lets
 * these two live products through the redirect on purpose, so they must be
 * in the sitemap too.
 * ------------------------------------------------------------------------ */
function opseo3_sitemap_passthrough(string $loc): bool {
    $path = trailingslashit(strtolower((string) parse_url($loc, PHP_URL_PATH)));
    return in_array($path, ['/products/tirzepatide-10mg-research-peptide/', '/products/cagrilintide-5mg-research-peptide/'], true);
}

add_action('init', function () {
    if (!class_exists('Oligopoly_SEO_Remediation')) { return; }
    $entry_cb = ['Oligopoly_SEO_Remediation', 'rank_math_sitemap_entry'];
    $url_cb   = ['Oligopoly_SEO_Remediation', 'rank_math_sitemap_url'];
    if (has_filter('rank_math/sitemap/entry', $entry_cb) !== false) {
        remove_filter('rank_math/sitemap/entry', $entry_cb, 20);
        add_filter('rank_math/sitemap/entry', function ($entry, $type = null, $object = null) use ($entry_cb) {
            if (is_array($entry) && opseo3_sitemap_passthrough((string) ($entry['loc'] ?? ''))) { return $entry; }
            return call_user_func($entry_cb, $entry, $type, $object);
        }, 20, 3);
    }
    if (has_filter('rank_math/sitemap/url', $url_cb) !== false) {
        remove_filter('rank_math/sitemap/url', $url_cb, 20);
        add_filter('rank_math/sitemap/url', function ($output, $url = null) use ($url_cb) {
            if (is_array($url) && opseo3_sitemap_passthrough((string) ($url['loc'] ?? ''))) { return $output; }
            return call_user_func($url_cb, $output, $url);
        }, 20, 2);
    }
}, 20);

/* ------------------------------------------------------------------------ *
 * Phase 1c — one H1 per page. On these two pages the theme's page title
 * duplicated the page's own, more descriptive H1.
 * ------------------------------------------------------------------------ */
add_filter('hello_elementor_page_title', function ($show) {
    if (is_page() && in_array((int) get_queried_object_id(), [2505, 727], true)) { return false; }
    return $show;
});

/* Phase 1c — one meta description per page. Hello Elementor prints the post
 * excerpt as a second <meta name="description"> after Rank Math's own. */
add_filter('hello_elementor_description_meta_tag', '__return_false');

/* ------------------------------------------------------------------------ *
 * Shared data: references (PubMed-indexed) and topic clusters.
 * ------------------------------------------------------------------------ */
function opseo3_refs(): array {
    return [
        'coskun2022'  => ['Coskun T, et al. LY3437943, a novel triple glucagon, GIP, and GLP-1 receptor agonist for glycemic control and weight loss: from discovery to clinical proof of concept. Cell Metab. 2022;34(9):1234-1247.', 'https://doi.org/10.1016/j.cmet.2022.07.013'],
        'urva2022'    => ['Urva S, et al. LY3437943, a novel triple GIP, GLP-1, and glucagon receptor agonist in people with type 2 diabetes: a phase 1b trial. Lancet. 2022;400(10366):1869-1881.', 'https://doi.org/10.1016/S0140-6736(22)02033-5'],
        'coskun2018'  => ['Coskun T, et al. LY3298176, a novel dual GIP and GLP-1 receptor agonist: from discovery to clinical proof of concept. Mol Metab. 2018;18:3-14.', 'https://doi.org/10.1016/j.molmet.2018.09.009'],
        'sun2022'     => ['Sun B, et al. Structural determinants of dual incretin receptor agonism by tirzepatide. Proc Natl Acad Sci U S A. 2022;119(13):e2116506119.', 'https://doi.org/10.1073/pnas.2116506119'],
        'kruse2021'   => ['Kruse T, et al. Development of cagrilintide, a long-acting amylin analogue. J Med Chem. 2021;64(15):11183-11194.', 'https://doi.org/10.1021/acs.jmedchem.1c00565'],
        'seiwerth2018'=> ['Seiwerth S, et al. BPC 157 and standard angiogenic growth factors. Curr Pharm Des. 2018;24(18):1972-1989.', 'https://doi.org/10.2174/1381612824666180712110447'],
        'vukojevic2022'=> ['Vukojevic J, et al. Pentadecapeptide BPC 157 and the central nervous system. Neural Regen Res. 2022;17(3):482-487.', 'https://doi.org/10.4103/1673-5374.320969'],
        'goldstein2015'=> ['Goldstein AL, Kleinman HK. Advances in the basic and clinical applications of thymosin β4. Expert Opin Biol Ther. 2015;15 Suppl 1:S139-45.', 'https://doi.org/10.1517/14712598.2015.1011617'],
        'pickart2008' => ['Pickart L. The human tri-peptide GHK and tissue remodeling. J Biomater Sci Polym Ed. 2008;19(8):969-988.', 'https://doi.org/10.1163/156856208784909435'],
        'maquart1988' => ['Maquart FX, et al. Stimulation of collagen synthesis in fibroblast cultures by the tripeptide-copper complex glycyl-L-histidyl-L-lysine-Cu2+. FEBS Lett. 1988;238(2):343-346.', 'https://doi.org/10.1016/0014-5793(88)80509-x'],
        'pickart2012' => ['Pickart L, Vasquez-Soltero JM, Margolina A. The human tripeptide GHK-Cu in prevention of oxidative stress and degenerative conditions of aging. Oxid Med Cell Longev. 2012;2012:324832.', 'https://doi.org/10.1155/2012/324832'],
        'lee2015'     => ['Lee C, et al. The mitochondrial-derived peptide MOTS-c promotes metabolic homeostasis and reduces obesity and insulin resistance. Cell Metab. 2015;21(3):443-454.', 'https://doi.org/10.1016/j.cmet.2015.02.009'],
        'kim2018'     => ['Kim KH, et al. The mitochondrial-encoded peptide MOTS-c translocates to the nucleus to regulate nuclear gene expression in response to metabolic stress. Cell Metab. 2018;28(3):516-524.', 'https://doi.org/10.1016/j.cmet.2018.06.008'],
        'kumagai2021' => ['Kumagai H, et al. MOTS-c reduces myostatin and muscle atrophy signaling. Am J Physiol Endocrinol Metab. 2021;320(4):E680-E690.', 'https://doi.org/10.1152/ajpendo.00275.2020'],
        'covarrubias2021' => ['Covarrubias AJ, et al. NAD+ metabolism and its roles in cellular processes during ageing. Nat Rev Mol Cell Biol. 2021;22(2):119-141.', 'https://doi.org/10.1038/s41580-020-00313-x'],
        'czabak2006'  => ['Czabak-Garbacz R, et al. Influence of long-term treatment with tuftsin analogue TP-7 on the anxiety-phobic states and body weight. Pharmacol Rep. 2006;58(4):562-567.', 'https://pubmed.ncbi.nlm.nih.gov/16963804/'],
    ];
}

/** Cluster: label => [url, anchor text]. The product page is always first. */
function opseo3_clusters(): array {
    $coa = OPSEO3_ORIGIN . '/research-peptides-with-coa/';
    $purity = [OPSEO3_ORIGIN . '/peptide-purity-guide/', 'Peptide purity guide: HPLC, LC-MS and COA documentation'];
    return [
        'retatrutide' => ['title' => 'Retatrutide research cluster', 'links' => [
            ['Product', OPSEO3_ORIGIN . '/products/retatrutide-10mg-research-peptide/', 'Retatrutide 10 mg research peptide (OL-3RT)'],
            ['Research guide', OPSEO3_ORIGIN . '/what-is-retatrutide/', 'What is retatrutide? Research guide'],
            ['Mechanism', OPSEO3_ORIGIN . '/triple-agonist-peptides/', 'Triple-agonist peptides: GLP-1/GIP/glucagon receptor research'],
            ['Comparison', OPSEO3_ORIGIN . '/retatrutide-vs-tirzepatide/', 'Retatrutide vs tirzepatide'],
            ['Comparison', OPSEO3_ORIGIN . '/retatrutide-vs-cagrilintide/', 'Retatrutide vs cagrilintide'],
            ['Testing', OPSEO3_ORIGIN . '/retatrutide-source/', 'Retatrutide source, purity and COA documentation'],
            ['COA', $coa . '?batch=P260628-L013', 'Published COA: retatrutide 5 mg, batch P260628-L013'],
        ]],
        'tirzepatide' => ['title' => 'Tirzepatide research cluster', 'links' => [
            ['Product', OPSEO3_ORIGIN . '/products/tirzepatide-10mg-research-peptide/', 'Tirzepatide 10 mg research peptide (OL-2TZ)'],
            ['Research guide', OPSEO3_ORIGIN . '/what-is-tirzepatide-research-guide/', 'What is tirzepatide? Research guide'],
            ['Mechanism', OPSEO3_ORIGIN . '/single-vs-dual-agonist-peptides/', 'Single vs dual agonist peptides: GLP-1 and GIP receptor research'],
            ['Comparison', OPSEO3_ORIGIN . '/semaglutide-vs-tirzepatide/', 'Semaglutide vs tirzepatide'],
            ['Comparison', OPSEO3_ORIGIN . '/retatrutide-vs-tirzepatide/', 'Retatrutide vs tirzepatide'],
            ['Testing', $purity[0], $purity[1]],
            ['COA', $coa . '?batch=20260609', 'Published COA: tirzepatide 10 mg, batch 20260609'],
        ]],
        'cagrilintide' => ['title' => 'Cagrilintide research cluster', 'links' => [
            ['Product', OPSEO3_ORIGIN . '/products/cagrilintide-5mg-research-peptide/', 'Cagrilintide 5 mg research peptide (OL-CAG)'],
            ['Research guide', OPSEO3_ORIGIN . '/what-is-cagrilintide-triple-receptor-peptide/', 'What is cagrilintide?'],
            ['Mechanism', OPSEO3_ORIGIN . '/cagrilintide-research-guide-amylin-analog/', 'Cagrilintide research guide: long-acting amylin analogue'],
            ['Comparison', OPSEO3_ORIGIN . '/cagrilintide-vs-semaglutide/', 'Cagrilintide vs semaglutide'],
            ['Comparison', OPSEO3_ORIGIN . '/retatrutide-vs-cagrilintide/', 'Retatrutide vs cagrilintide'],
            ['Testing', $purity[0], $purity[1]],
            ['COA', $coa, 'Certificate library (a cagrilintide lot record is published when released)'],
        ]],
        'bpc-157' => ['title' => 'BPC-157 research cluster', 'links' => [
            ['Product', OPSEO3_ORIGIN . '/products/bpc-157-10mg-research-peptide/', 'BPC-157 10 mg research peptide'],
            ['Research guide', OPSEO3_ORIGIN . '/bpc-157-research-mechanisms-applications/', 'BPC-157 research: mechanisms and published studies'],
            ['Comparison', OPSEO3_ORIGIN . '/bpc-157-vs-tb-500-protocols/', 'BPC-157 vs TB-500'],
            ['Comparison', OPSEO3_ORIGIN . '/ghk-cu-vs-bpc-157/', 'GHK-Cu vs BPC-157'],
            ['Blend', OPSEO3_ORIGIN . '/products/bpc-157-tb-500-blend/', 'BPC-157 + TB-500 research blend'],
            ['Testing', OPSEO3_ORIGIN . '/hplc-testing-explained/', 'HPLC testing explained'],
            ['COA', $coa, 'Certificate library (a BPC-157 lot record is published when released)'],
        ]],
        'ghk-cu' => ['title' => 'GHK-Cu research cluster', 'links' => [
            ['Product', OPSEO3_ORIGIN . '/products/ghk-cu-50mg-research-peptide/', 'GHK-Cu 50 mg research peptide'],
            ['Research guide', OPSEO3_ORIGIN . '/what-is-ghk-cu-research-guide/', 'What is GHK-Cu? Research guide'],
            ['Comparison', OPSEO3_ORIGIN . '/ghk-cu-vs-bpc-157/', 'GHK-Cu vs BPC-157'],
            ['Hub', OPSEO3_ORIGIN . '/ecm-signaling-hub/', 'ECM signaling hub'],
            ['Testing', OPSEO3_ORIGIN . '/quality-testing-hub/hplc-vs-lc-ms/', 'HPLC vs LC-MS in peptide testing'],
            ['COA', $coa . '?batch=SGX-OL-2026-0811-K99', 'Published COA: GHK-Cu 50 mg, batch SGX-OL-2026-0811-K99'],
        ]],
        'mots-c' => ['title' => 'MOTS-c research cluster', 'links' => [
            ['Product', OPSEO3_ORIGIN . '/products/mots-c-10mg-research-peptide/', 'MOTS-c 10 mg research peptide'],
            ['Research guide', OPSEO3_ORIGIN . '/what-is-mots-c-research-guide/', 'What is MOTS-c? Research guide'],
            ['Mechanism', OPSEO3_ORIGIN . '/mitochondrial-research-peptides-guide/', 'Mitochondrial research peptides guide'],
            ['Comparison', OPSEO3_ORIGIN . '/ss-31-vs-mots-c/', 'SS-31 vs MOTS-c'],
            ['Comparison', OPSEO3_ORIGIN . '/epitalon-vs-mots-c/', 'Epitalon vs MOTS-c'],
            ['Testing', $purity[0], $purity[1]],
            ['COA', $coa, 'Certificate library (a MOTS-c lot record is published when released)'],
        ]],
    ];
}

/** Articles that belong to a cluster (by slug) and get the cluster navigation. */
function opseo3_article_clusters(): array {
    return [
        'what-is-retatrutide' => 'retatrutide', 'triple-agonist-peptides' => 'retatrutide', 'retatrutide-vs-cagrilintide' => 'retatrutide', 'retatrutide-source' => 'retatrutide',
        'retatrutide-vs-tirzepatide' => 'tirzepatide',
        'what-is-tirzepatide-research-guide' => 'tirzepatide', 'single-vs-dual-agonist-peptides' => 'tirzepatide', 'semaglutide-vs-tirzepatide' => 'tirzepatide',
        'what-is-cagrilintide-triple-receptor-peptide' => 'cagrilintide', 'cagrilintide-research-guide-amylin-analog' => 'cagrilintide', 'cagrilintide-vs-semaglutide' => 'cagrilintide',
        'bpc-157-research-mechanisms-applications' => 'bpc-157', 'bpc-157-vs-tb-500-protocols' => 'bpc-157', 'bpc-157-tb-500-blend-research' => 'bpc-157',
        'what-is-ghk-cu-research-guide' => 'ghk-cu', 'ghk-cu-vs-bpc-157' => 'ghk-cu',
        'what-is-mots-c-research-guide' => 'mots-c', 'mitochondrial-research-peptides-guide' => 'mots-c', 'ss-31-vs-mots-c' => 'mots-c', 'epitalon-vs-mots-c' => 'mots-c',
    ];
}

function opseo3_render_cluster(string $key, string $current_url = ''): string {
    $clusters = opseo3_clusters();
    if (!isset($clusters[$key])) { return ''; }
    $c = $clusters[$key];
    $items = '';
    foreach ($c['links'] as [$kind, $url, $text]) {
        $is_current = $current_url !== '' && untrailingslashit($url) === untrailingslashit($current_url);
        $items .= '<li><span class="opseo3-kind">' . esc_html($kind) . '</span> '
            . ($is_current ? '<strong aria-current="page">' . esc_html($text) . '</strong>' : '<a href="' . esc_url($url) . '">' . esc_html($text) . '</a>')
            . '</li>';
    }
    return '<nav class="opseo3-cluster" aria-label="' . esc_attr($c['title']) . '"><h2>' . esc_html($c['title']) . '</h2><ul>' . $items . '</ul>'
        . '<p class="opseo3-ruo">All OligoPoly materials are for laboratory research use only. Not for human or veterinary use.</p></nav>';
}

add_filter('the_content', function ($content) {
    if (!is_singular(['post', 'page']) || !in_the_loop() || !is_main_query()) { return $content; }
    $slug = (string) get_post_field('post_name', get_the_ID());
    $map = opseo3_article_clusters();
    if (!isset($map[$slug]) || strpos($content, 'opseo3-cluster') !== false) { return $content; }
    return $content . opseo3_render_cluster($map[$slug], get_permalink());
}, 40);

/* ------------------------------------------------------------------------ *
 * Phase 2 — compound identity and documentation on the main product pages.
 * Identity values are copied from the cited public record; nothing here is a
 * lot-specific result. Lot data lives only in the linked COA.
 * ------------------------------------------------------------------------ */
function opseo3_products(): array {
    $pc = function (int $cid) { return ['PubChem CID ' . $cid, 'https://pubchem.ncbi.nlm.nih.gov/compound/' . $cid]; };
    return [
        12 => ['compound' => 'Retatrutide (LY3437943)', 'code' => 'OL-3RT', 'strength' => '10 mg',
            'class' => 'Synthetic peptide; agonist at the GIP, GLP-1 and glucagon receptors (triple agonist)',
            'identity' => [['CAS number', '2381089-83-2'], ['UNII', 'NOP2Y096GV']],
            'source' => ['FDA GSRS substance record NOP2Y096GV', 'https://gsrs.ncats.nih.gov/ginas/app/beta/substances/NOP2Y096GV'],
            'identity_note' => 'Molecular formula and weight are not listed here; confirm them against the lot COA.',
            'coa' => ['?batch=P260628-L013', 'The currently published retatrutide COA covers the 5 mg presentation (batch P260628-L013). A 10 mg lot record has not been published yet.'],
            'refs' => ['coskun2022', 'urva2022'], 'cluster' => 'retatrutide',
            'related' => [['Retatrutide 5 mg', '/products/retatrutide-5mg-research-peptide/'], ['Retatrutide 20 mg', '/products/retatrutide-20mg-research-peptide/'], ['Tirzepatide 10 mg', '/products/tirzepatide-10mg-research-peptide/'], ['Cagrilintide 5 mg', '/products/cagrilintide-5mg-research-peptide/']]],
        39 => ['compound' => 'Tirzepatide (LY3298176)', 'code' => 'OL-2TZ', 'strength' => '10 mg',
            'class' => 'Synthetic fatty-acid-modified peptide; dual GIP and GLP-1 receptor agonist',
            'identity' => [['CAS number', '2023788-19-2'], ['Molecular formula', 'C225H348N48O68'], ['Molecular weight', '4813 g/mol (computed)']],
            'source' => $pc(166567236),
            'coa' => ['?batch=20260609', 'Published COA: tirzepatide 10 mg, batch 20260609.'],
            'refs' => ['coskun2018', 'sun2022'], 'cluster' => 'tirzepatide',
            'related' => [['Tirzepatide 20 mg', '/products/tirzepatide-20-mg-research-peptide/'], ['Retatrutide 10 mg', '/products/retatrutide-10mg-research-peptide/'], ['Semaglutide 5 mg', '/products/semaglutide-5mg-research-peptide/']]],
        436 => ['compound' => 'Cagrilintide', 'code' => 'OL-CAG', 'strength' => '5 mg',
            'class' => 'Long-acting, lipidated amylin analogue',
            'identity' => [['CAS number', '1415456-99-3'], ['Molecular formula', 'C194H312N54O59S2'], ['Molecular weight', '4409 g/mol (computed)']],
            'source' => $pc(171397054),
            'coa' => ['', 'A cagrilintide lot record has not been published yet. Records appear in the certificate library when released.'],
            'refs' => ['kruse2021'], 'cluster' => 'cagrilintide',
            'related' => [['Cagrilintide 5 mg – 6 vial kit', '/products/cagrilintide-5-mg-6-vial-research-kit/'], ['Retatrutide 5 mg', '/products/retatrutide-5mg-research-peptide/'], ['Retatrutide 10 mg', '/products/retatrutide-10mg-research-peptide/']]],
        49 => ['compound' => 'BPC-157 (stable gastric pentadecapeptide)', 'code' => '', 'strength' => '10 mg',
            'class' => 'Synthetic 15-amino-acid peptide',
            'identity' => [['Sequence', 'GEPPPGKPADDAGLV'], ['CAS number', '137525-51-0'], ['Molecular formula', 'C62H98N16O22'], ['Molecular weight', '1419.5 g/mol (computed)']],
            'source' => $pc(9941957),
            'coa' => ['', 'A BPC-157 lot record has not been published yet. Records appear in the certificate library when released.'],
            'refs' => ['seiwerth2018', 'vukojevic2022'], 'cluster' => 'bpc-157',
            'related' => [['BPC-157 5 mg', '/products/bpc-157-5-mg/'], ['BPC-157 + TB-500 blend', '/products/bpc-157-tb-500-blend/'], ['TB-500 10 mg', '/products/tb-500-10mg-research-peptide/'], ['GHK-Cu 50 mg', '/products/ghk-cu-50mg-research-peptide/']]],
        55 => ['compound' => 'BPC-157 + TB-500 (two-component research blend)', 'code' => '', 'strength' => '10 mg + 10 mg',
            'class' => 'Multi-component catalog item: two synthetic peptides',
            'identity' => [['BPC-157', 'GEPPPGKPADDAGLV · CAS 137525-51-0 · C62H98N16O22 · 1419.5 g/mol (PubChem CID 9941957)'], ['TB-500', 'Ac-LKKTETQ, an N-acetylated heptapeptide containing the LKKTETQ motif of thymosin β4 · CAS 885340-08-9 · C38H68N10O14 · 889.0 g/mol (PubChem CID 62707662)']],
            'source' => ['PubChem CIDs 9941957 and 62707662', 'https://pubchem.ncbi.nlm.nih.gov/compound/62707662'],
            'identity_note' => 'The blend has no single CAS number; the identifiers above describe each component.',
            'coa' => ['', 'A blend lot record has not been published yet. Records appear in the certificate library when released.'],
            'refs' => ['seiwerth2018', 'goldstein2015'], 'cluster' => 'bpc-157',
            'related' => [['BPC-157 10 mg', '/products/bpc-157-10mg-research-peptide/'], ['TB-500 10 mg', '/products/tb-500-10mg-research-peptide/'], ['BPC-157 + KPV blend', '/products/bpc-157-kpv-blend/']]],
        441 => ['compound' => 'GHK-Cu (copper(II) complex of glycyl-L-histidyl-L-lysine)', 'code' => '', 'strength' => '50 mg',
            'class' => 'Copper-binding tripeptide complex',
            'identity' => [['CAS number (GHK-Cu)', '89030-95-5'], ['Registered formula', 'C14H23CuN6O4+ (PubChem CID 71587328)'], ['Molecular weight', '402.92 g/mol (computed)'], ['Peptide ligand (GHK)', 'CAS 49557-75-7 · C14H24N6O4 · 340.38 g/mol (PubChem CID 73587)']],
            'source' => $pc(71587328),
            'coa' => ['?batch=SGX-OL-2026-0811-K99', 'Published COA: GHK-Cu 50 mg, batch SGX-OL-2026-0811-K99.'],
            'refs' => ['pickart2008', 'maquart1988', 'pickart2012'], 'cluster' => 'ghk-cu',
            'related' => [['GHK-Cu 50 mg – 6 vial kit', '/products/ghk-cu-50-mg-6-vial-research-kit/'], ['KPV 10 mg + GHK-Cu 50 mg blend', '/products/kpv-10-mg-ghk-cu-50-mg-research-blend/'], ['BPC-157 10 mg', '/products/bpc-157-10mg-research-peptide/']]],
        443 => ['compound' => 'MOTS-c (mitochondrial open reading frame of the 12S rRNA-c)', 'code' => '', 'strength' => '10 mg',
            'class' => 'Mitochondrial-derived 16-amino-acid peptide',
            'identity' => [['Sequence', 'MRWQEMGYIFYPRKLR'], ['CAS number', '1627580-64-6'], ['Molecular formula', 'C101H152N28O22S2'], ['Molecular weight', '2174.6 g/mol (computed)']],
            'source' => $pc(146675088),
            'coa' => ['', 'A MOTS-c lot record has not been published yet. Records appear in the certificate library when released.'],
            'refs' => ['lee2015', 'kim2018', 'kumagai2021'], 'cluster' => 'mots-c',
            'related' => [['NAD+ 500 mg', '/products/nad-500mg-research-compound/'], ['Mitochondrial Signaling Collection', '/products/mitochondrial-signaling-collection/']]],
        63 => ['compound' => 'β-Nicotinamide adenine dinucleotide (NAD+)', 'code' => '', 'strength' => '500 mg',
            'class' => 'Small-molecule coenzyme (not a peptide)',
            'identity' => [['CAS number', '53-84-9'], ['Molecular formula', 'C21H27N7O14P2'], ['Molecular weight', '663.4 g/mol (computed)']],
            'source' => $pc(5892),
            'coa' => ['?batch=AYK20260418-NAD500', 'Published COA: NAD+ 500 mg, batch AYK20260418-NAD500.'],
            'refs' => ['covarrubias2021'], 'cluster' => '',
            'related' => [['NAD+ 500 mg – 6 vial kit', '/products/nad-500-mg-6-vial-research-kit/'], ['MOTS-c 10 mg', '/products/mots-c-10mg-research-peptide/'], ['Mitochondrial Signaling Collection', '/products/mitochondrial-signaling-collection/']]],
        447 => ['compound' => 'Selank (TP-7)', 'code' => '', 'strength' => '5 mg',
            'class' => 'Synthetic heptapeptide; tuftsin analogue (Thr-Lys-Pro-Arg-Pro-Gly-Pro)',
            'identity' => [['Sequence', 'TKPRPGP'], ['CAS number', '129954-34-3'], ['Molecular formula', 'C33H57N11O9'], ['Molecular weight', '751.9 g/mol (computed)']],
            'source' => $pc(11765600),
            'coa' => ['?batch=OP-20260720-B001', 'Published COA: Selank 5 mg, batch OP-20260720-B001.'],
            'refs' => ['czabak2006'], 'cluster' => '',
            'related' => [['Selank 5 mg – 6 vial kit', '/products/selank-5-mg-6-vial-research-kit/'], ['Semax 10 mg', '/products/semax-10mg-research-peptide/'], ['CNS Receptor Reference Collection', '/products/cns-receptor-reference-collection/']]],
    ];
}

add_action('woocommerce_after_single_product_summary', function () {
    if (!function_exists('is_product') || !is_product()) { return; }
    $id = (int) get_the_ID();
    $all = opseo3_products();
    if (!isset($all[$id])) { return; }
    $p = $all[$id];
    $refs = opseo3_refs();
    $coa_base = OPSEO3_ORIGIN . '/research-peptides-with-coa/';

    $rows = '<tr><th scope="row">Compound</th><td>' . esc_html($p['compound']) . '</td></tr>';
    if ($p['code'] !== '') { $rows .= '<tr><th scope="row">Catalog code</th><td>' . esc_html($p['code']) . '</td></tr>'; }
    $rows .= '<tr><th scope="row">Research classification</th><td>' . esc_html($p['class']) . '</td></tr>';
    $rows .= '<tr><th scope="row">Catalog strength</th><td>' . esc_html($p['strength']) . ' per catalog unit</td></tr>';
    foreach ($p['identity'] as [$k, $v]) { $rows .= '<tr><th scope="row">' . esc_html($k) . '</th><td>' . esc_html($v) . '</td></tr>'; }
    $rows .= '<tr><th scope="row">Identity source</th><td><a href="' . esc_url($p['source'][1]) . '" rel="nofollow noopener" target="_blank">' . esc_html($p['source'][0]) . '</a></td></tr>';

    $coa_link = $coa_base . $p['coa'][0];
    $coa_html = '<p>' . esc_html($p['coa'][1]) . ' <a href="' . esc_url($coa_link) . '">' . ($p['coa'][0] !== '' ? 'Open the certificate record' : 'Open the certificate library') . '</a>.</p>'
        . '<p>Testing methodology: identity is confirmed by LC-MS and purity by HPLC where those results appear on the published certificate. See <a href="' . esc_url(OPSEO3_ORIGIN . '/hplc-testing-explained/') . '">HPLC testing explained</a> and <a href="' . esc_url(OPSEO3_ORIGIN . '/how-to-read-a-coa/') . '">how to read a COA</a>. Batch results are reported only on the certificate itself.</p>';

    $ref_html = '';
    foreach ($p['refs'] as $r) {
        if (isset($refs[$r])) { $ref_html .= '<li>' . esc_html($refs[$r][0]) . ' <a href="' . esc_url($refs[$r][1]) . '" rel="nofollow noopener" target="_blank">Source</a></li>'; }
    }
    $related = '';
    foreach ($p['related'] as [$t, $u]) { $related .= '<li><a href="' . esc_url(OPSEO3_ORIGIN . $u) . '">' . esc_html($t) . '</a></li>'; }

    echo '<section class="opseo3-identity" aria-labelledby="opseo3-identity-title"><h2 id="opseo3-identity-title">Compound identity &amp; documentation</h2>'
        . '<table class="opseo3-table"><tbody>' . $rows . '</tbody></table>'
        . (!empty($p['identity_note']) ? '<p class="opseo3-note">' . esc_html($p['identity_note']) . '</p>' : '')
        . '<h3>Batch documentation</h3>' . $coa_html
        . '<h3>Scientific references</h3><ol class="opseo3-refs">' . $ref_html . '</ol>'
        . '<p class="opseo3-note">References describe published laboratory and clinical research on the compound. They are not claims about this research material, which is not a drug product.</p>'
        . '<h3>Related research materials</h3><ul>' . $related . '</ul>'
        . '<p class="opseo3-ruo"><strong>Research use only.</strong> Not for human or veterinary use, diagnosis or treatment.</p>'
        . '</section>';
    if ($p['cluster'] !== '') { echo opseo3_render_cluster($p['cluster'], get_permalink($id)); }
}, 15);

add_action('wp_head', function () {
    if (!(is_singular() || (function_exists('is_product') && is_product()))) { return; }
    echo '<style id="opseo3-css">.opseo3-identity,.opseo3-cluster{width:min(1180px,calc(100% - 32px));margin:32px auto;padding:24px;border:1px solid rgba(148,163,184,.25);border-radius:14px}'
        . '.opseo3-identity h2,.opseo3-cluster h2{margin:0 0 14px;font-size:1.45rem}.opseo3-identity h3{margin:22px 0 8px;font-size:1.1rem}'
        . '.opseo3-table{width:100%;border-collapse:collapse}.opseo3-table th,.opseo3-table td{padding:8px 10px;border-bottom:1px solid rgba(148,163,184,.18);text-align:left;vertical-align:top;overflow-wrap:anywhere}'
        . '.opseo3-table th{width:32%;font-weight:600}.opseo3-note,.opseo3-ruo{font-size:.9rem;opacity:.85}.opseo3-refs li{margin:6px 0}'
        . '.opseo3-cluster ul{list-style:none;padding:0;margin:0;display:grid;gap:8px}.opseo3-kind{display:inline-block;min-width:118px;font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;opacity:.7}</style>';
}, 50);
