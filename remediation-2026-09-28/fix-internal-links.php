<?php
if (!function_exists('wc_get_product_id_by_sku')) {
    fwrite(STDERR, "WooCommerce SKU lookup is unavailable. Abort.\n");
    exit(1);
}

function opseo_fix_target_from_skus(array $skus, string $fallback = '/research-catalog/') : string {
    foreach ($skus as $sku) {
        $id = wc_get_product_id_by_sku($sku);
        if ($id && get_post_status($id) === 'publish') {
            $url = get_permalink($id);
            if ($url) { return $url; }
        }
    }
    return home_url($fallback);
}

$map = [
    '/products/ara-290-10mg-research-peptide/' => [['OP-REC-ARA290-10MG'], '/research-catalog/'],
    '/products/ara-290' => [['OP-REC-ARA290-10MG'], '/research-catalog/'],
    '/products/mots-c-ss-31-blend/' => [['OPL-BLEND-MOTSC-SS31'], '/research-catalog/'],
    '/products/performance-recovery-research-Research Panel/' => [['OP-STK-PERFORMANCE-RECOVERY'], '/research-stacks/'],
    '/products/recovery-support-research-Research Panel/' => [['OP-STK-RECOVERY-SUPPORT'], '/research-stacks/'],
    '/products/cognitive-research-Research Panel/' => [['OP-STK-COGNITIVE'], '/research-stacks/'],
    '/products/metabolic-research-Research Panel/' => [['OP-STACK-METABOLIC'], '/research-stacks/'],
    '/products/advanced-multi-pathway-research-Research Panel/' => [['OP-STK-ADVANCED-MULTIPATHWAY'], '/research-stacks/'],
    '/products/immune-optimization-research-Research Panel/' => [['OP-STK-IMMUNE'], '/research-stacks/'],
    '/products/starter-research-Research Panel/' => [['OP-STK-STARTER'], '/research-stacks/'],
    '/stacks/cognitive-research-stack' => [['OP-STK-COGNITIVE'], '/research-stacks/'],
    '/products/recovery-cellular-research-stack/' => [['OP-STK-RECOVERY-CELLULAR'], '/research-stacks/'],
    '/products/selank' => [['OP-COG-SELANK-5MG'], '/research-catalog/'],
    '/products/glp-2tz-blend-research-peptide/' => [['OPL-BLEND-GLP2TZ'], '/research-catalog/'],
    '/products/tesamorelin-10mg-research-peptide/' => [['OP-GH-TESA-10MG'], '/research-catalog/'],
    '/peptide-reconstitution-guide/' => [[], '/research-library/'],
    '/products/dihexa' => [['OP-COG-DIHEXA-10MG'], '/research-catalog/'],
    '/products/bpc-157-tb-500-blend/' => [['OP-REC-BPCTB-20MG'], '/research-catalog/'],
    '/products/ss-31' => [['OP-LON-SS31-10MG'], '/research-catalog/'],
    '/products/pinealon-5mg-research-peptide/' => [['OP-LON-PINEALON-5MG'], '/research-catalog/'],
    '/product/cjc-1295-no-dac-2mg-research-peptide/' => [['OP-GH-CJC1295-NODAC-5MG','OP-GH-CJC1295-NODAC-2MG'], '/research-catalog/'],
    '/products/glp-3rt-blend-research-peptide/' => [['OPL-BLEND-GLP3RT'], '/research-catalog/'],
    '/stacks/immune-optimization-stack' => [['OP-STK-IMMUNE'], '/research-stacks/'],
    '/products/recovery-cellular-stack' => [['OP-STK-RECOVERY-CELLULAR'], '/research-stacks/'],
    '/bpc-tb-blend-research-peptide' => [['OP-REC-BPCTB-20MG'], '/research-catalog/'],
    '/stacks/recovery-cellular-stack' => [['OP-STK-RECOVERY-CELLULAR'], '/research-stacks/'],
    '/products/bpc-tb-blend' => [['OP-REC-BPCTB-20MG'], '/research-catalog/'],
    '/products/pinealon' => [['OP-LON-PINEALON-5MG'], '/research-catalog/'],
    '/products/bpc-157-tb-500-research-peptide-blend/' => [['OP-REC-BPCTB-20MG'], '/research-catalog/'],
    '/bpc-tb-blend/' => [['OP-REC-BPCTB-20MG'], '/research-catalog/'],
    '/product/bpc-tb-blend/' => [['OP-REC-BPCTB-20MG'], '/research-catalog/'],
    '/advanced-research-stack' => [['OP-STK-ADVANCED-MULTIPATHWAY'], '/research-stacks/'],
    '/tirzepatide-10mg' => [['OP-MET-TIRZ-10MG'], '/research-catalog/'],
    '/products/metabolic-stack' => [['OP-STACK-METABOLIC'], '/research-stacks/'],
    '/stacks/recovery-stack' => [['OP-STK-RECOVERY-CELLULAR'], '/research-stacks/'],
];

$resolved = [];
foreach ($map as $old => [$skus, $fallback]) {
    $resolved[$old] = $skus ? opseo_fix_target_from_skus($skus, $fallback) : home_url($fallback);
}

global $wpdb;
$post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_status IN ('publish','draft','private') AND post_content <> ''");
$changed_posts = 0;
$changed_links = 0;

foreach ($post_ids as $post_id) {
    $content = (string) get_post_field('post_content', $post_id);
    $new = $content;
    $post_changes = 0;

    foreach ($resolved as $old => $target) {
        $encoded_old = str_replace(' ', '%20', $old);
        $sources = [
            $old, $encoded_old, home_url($old), str_replace(' ', '%20', home_url($old)),
            'https://www.oligopolypeptides.com' . $old,
            'https://oligopolypeptides.com' . $old,
            'http://www.oligopolypeptides.com' . $old,
            'http://oligopolypeptides.com' . $old,
        ];
        foreach (array_unique($sources) as $source) {
            foreach (['"', "'"] as $quote) {
                $needle = 'href=' . $quote . $source . $quote;
                $replacement = 'href=' . $quote . esc_url($target) . $quote;
                $count = 0;
                $new = str_replace($needle, $replacement, $new, $count);
                $post_changes += $count;
            }
        }
    }

    if ($new !== $content) {
        wp_update_post(['ID' => (int) $post_id, 'post_content' => $new]);
        $changed_posts++;
        $changed_links += $post_changes;
        echo "Updated post {$post_id}: {$post_changes} href replacement(s)\n";
    }
}
echo "DONE: {$changed_links} href replacement(s) across {$changed_posts} post(s).\n";
