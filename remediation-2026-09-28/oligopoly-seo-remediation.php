<?php
/**
 * Plugin Name: OligoPoly SEO Remediation 2026-09-28
 * Description: Targeted legacy URL cleanup for confirmed Search Console 404/duplicate issues. Resolves current WooCommerce permalinks by SKU to avoid hard-coding product slugs.
 * Version: 2026.09.28.1
 */
if (!defined('ABSPATH')) { exit; }

function opseo_product_url_by_skus(array $skus) {
    if (!function_exists('wc_get_product_id_by_sku')) { return false; }
    foreach ($skus as $sku) {
        $id = wc_get_product_id_by_sku($sku);
        if (!$id || get_post_status($id) !== 'publish') { continue; }
        $url = get_permalink($id);
        if ($url) { return $url; }
    }
    return false;
}

add_action('send_headers', function () {
    if (isset($_GET['opl7_pdf'])) {
        header('X-Robots-Tag: noindex, noarchive', true);
    }
}, 1);

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) { return; }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = rawurldecode($path ?: '/');
    $key = strtolower(untrailingslashit($path));
    if ($key === '') { $key = '/'; }

    $sku_map = [
        '/products/ara-290-10mg-research-peptide' => ['OP-REC-ARA290-10MG'],
        '/products/ara-290' => ['OP-REC-ARA290-10MG'],
        '/products/mots-c-ss-31-blend' => ['OPL-BLEND-MOTSC-SS31'],
        '/products/performance-recovery-research-research panel' => ['OP-STK-PERFORMANCE-RECOVERY'],
        '/products/recovery-support-research-research panel' => ['OP-STK-RECOVERY-SUPPORT'],
        '/products/cognitive-research-research panel' => ['OP-STK-COGNITIVE'],
        '/products/metabolic-research-research panel' => ['OP-STACK-METABOLIC'],
        '/products/advanced-multi-pathway-research-research panel' => ['OP-STK-ADVANCED-MULTIPATHWAY'],
        '/products/immune-optimization-research-research panel' => ['OP-STK-IMMUNE'],
        '/products/starter-research-research panel' => ['OP-STK-STARTER'],
        '/stacks/cognitive-research-stack' => ['OP-STK-COGNITIVE'],
        '/products/recovery-cellular-research-stack' => ['OP-STK-RECOVERY-CELLULAR'],
        '/products/selank' => ['OP-COG-SELANK-5MG'],
        '/products/glp-2tz-blend-research-peptide' => ['OPL-BLEND-GLP2TZ'],
        '/products/tesamorelin-10mg-research-peptide' => ['OP-GH-TESA-10MG'],
        '/products/dihexa' => ['OP-COG-DIHEXA-10MG'],
        '/products/bpc-157-tb-500-blend' => ['OP-REC-BPCTB-20MG'],
        '/products/ss-31' => ['OP-LON-SS31-10MG'],
        '/products/pinealon-5mg-research-peptide' => ['OP-LON-PINEALON-5MG'],
        '/product/cjc-1295-no-dac-2mg-research-peptide' => ['OP-GH-CJC1295-NODAC-5MG', 'OP-GH-CJC1295-NODAC-2MG'],
        '/products/glp-3rt-blend-research-peptide' => ['OPL-BLEND-GLP3RT'],
        '/stacks/immune-optimization-stack' => ['OP-STK-IMMUNE'],
        '/products/recovery-cellular-stack' => ['OP-STK-RECOVERY-CELLULAR'],
        '/bpc-tb-blend-research-peptide' => ['OP-REC-BPCTB-20MG'],
        '/stacks/recovery-cellular-stack' => ['OP-STK-RECOVERY-CELLULAR'],
        '/products/bpc-tb-blend' => ['OP-REC-BPCTB-20MG'],
        '/products/pinealon' => ['OP-LON-PINEALON-5MG'],
        '/products/bpc-157-tb-500-research-peptide-blend' => ['OP-REC-BPCTB-20MG'],
        '/bpc-tb-blend' => ['OP-REC-BPCTB-20MG'],
        '/product/bpc-tb-blend' => ['OP-REC-BPCTB-20MG'],
        '/advanced-research-stack' => ['OP-STK-ADVANCED-MULTIPATHWAY'],
        '/tirzepatide-10mg' => ['OP-MET-TIRZ-10MG'],
        '/products/metabolic-stack' => ['OP-STACK-METABOLIC'],
        '/stacks/recovery-stack' => ['OP-STK-RECOVERY-CELLULAR'],
        '/tirzepatide-page' => ['OP-MET-TIRZ-10MG'],
        '/blog/tirzepatide-research-guide' => ['OP-MET-TIRZ-10MG'],
        '/tirzepatide-research-guide' => ['OP-MET-TIRZ-10MG'],
        '/semaglutide-research' => ['OP-MET-SEMA-10MG'],
        '/bpc-157-guide' => ['OP-REC-BPC157-10MG'],
        '/epitalon-telomere-research' => ['OP-LON-EPIT-50MG'],
        '/mots-c-peptide' => ['OP-AUX-MOTSC-10MG'],
        '/research-library/ghk-cu-research' => ['OP-LON-GHKCU-50MG'],
    ];

    if (isset($sku_map[$key])) {
        $target = opseo_product_url_by_skus($sku_map[$key]);
        if (!$target) { $target = home_url('/research-catalog/'); }
        wp_safe_redirect($target, 301, 'OligoPoly SEO Remediation');
        exit;
    }

    $hub_map = [
        '/peptide-reconstitution-guide' => '/research-library/',
        '/product-category/research-blends' => '/research-catalog/',
        '/products/cjc-1295-dac' => '/product-category/growth-hormone-research/',
    ];
    if (isset($hub_map[$key])) {
        wp_safe_redirect(home_url($hub_map[$key]), 301, 'OligoPoly SEO Remediation');
        exit;
    }

    if (isset($_GET['product']) && sanitize_title(wp_unslash($_GET['product'])) === 'cjc-1295-dac') {
        wp_safe_redirect(home_url('/product-category/growth-hormone-research/'), 301, 'OligoPoly SEO Remediation');
        exit;
    }
}, 1);
