<?php
$groups = [
    ['OP-REC-ARA290-10MG'], ['OPL-BLEND-MOTSC-SS31'], ['OP-STK-PERFORMANCE-RECOVERY'],
    ['OP-STK-RECOVERY-SUPPORT'], ['OP-STK-COGNITIVE'], ['OP-STACK-METABOLIC'],
    ['OP-STK-ADVANCED-MULTIPATHWAY'], ['OP-STK-IMMUNE'], ['OP-STK-STARTER'],
    ['OP-STK-RECOVERY-CELLULAR'], ['OP-COG-SELANK-5MG'], ['OPL-BLEND-GLP2TZ'],
    ['OP-GH-TESA-10MG'], ['OP-COG-DIHEXA-10MG'], ['OP-REC-BPCTB-20MG'],
    ['OP-LON-SS31-10MG'], ['OP-LON-PINEALON-5MG'],
    ['OP-GH-CJC1295-NODAC-5MG','OP-GH-CJC1295-NODAC-2MG'], ['OPL-BLEND-GLP3RT'],
    ['OP-MET-TIRZ-10MG'], ['OP-MET-SEMA-10MG'], ['OP-REC-BPC157-10MG'],
    ['OP-LON-EPIT-50MG'], ['OP-AUX-MOTSC-10MG'], ['OP-LON-GHKCU-50MG'],
];

if (!function_exists('wc_get_product_id_by_sku')) {
    fwrite(STDERR, "FAIL: WooCommerce SKU lookup is unavailable.\n");
    exit(1);
}
$missing = 0;
foreach ($groups as $candidates) {
    $found = false;
    foreach ($candidates as $sku) {
        $id = wc_get_product_id_by_sku($sku);
        if ($id && get_post_status($id) === 'publish') {
            echo "OK   {$sku} -> " . get_permalink($id) . "\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "WARN no published product found for: " . implode(' OR ', $candidates) . "\n";
        $missing++;
    }
}
echo "Preflight complete. Missing groups: {$missing}. Missing groups use safe hub fallbacks.\n";
