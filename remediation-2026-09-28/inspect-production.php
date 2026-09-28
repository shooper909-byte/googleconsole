<?php
/**
 * Read-only production inspection for the 2026-09-28 remediation.
 * Usage: wp eval-file inspect-production.php <report-path>
 * Writes a JSON report; changes nothing in WordPress.
 */
$out = isset($args[0]) ? $args[0] : 'php://stdout';
$r = [];

$r['site'] = [
    'home' => home_url(), 'siteurl' => site_url(), 'abspath' => ABSPATH,
    'wp' => get_bloginfo('version'), 'wc' => defined('WC_VERSION') ? WC_VERSION : null,
    'mu_dir' => WPMU_PLUGIN_DIR, 'permalink_structure' => get_option('permalink_structure'),
    'wc_permalinks' => get_option('woocommerce_permalinks'),
];
$r['active_plugins'] = get_option('active_plugins');
$r['mu_plugins'] = array_map('basename', glob(WPMU_PLUGIN_DIR . '/*.php') ?: []);
$r['theme'] = get_stylesheet();

$skus = ['OP-REC-ARA290-10MG','OPL-BLEND-MOTSC-SS31','OP-STK-PERFORMANCE-RECOVERY','OP-STK-RECOVERY-SUPPORT',
    'OP-STK-COGNITIVE','OP-STACK-METABOLIC','OP-STK-ADVANCED-MULTIPATHWAY','OP-STK-IMMUNE','OP-STK-STARTER',
    'OP-STK-RECOVERY-CELLULAR','OP-COG-SELANK-5MG','OPL-BLEND-GLP2TZ','OP-GH-TESA-10MG','OP-COG-DIHEXA-10MG',
    'OP-REC-BPCTB-20MG','OP-LON-SS31-10MG','OP-LON-PINEALON-5MG','OP-GH-CJC1295-NODAC-5MG','OP-GH-CJC1295-NODAC-2MG',
    'OPL-BLEND-GLP3RT','OP-MET-TIRZ-10MG','OP-MET-SEMA-10MG','OP-REC-BPC157-10MG','OP-LON-EPIT-50MG',
    'OP-AUX-MOTSC-10MG','OP-LON-GHKCU-50MG'];
foreach ($skus as $sku) {
    $id = function_exists('wc_get_product_id_by_sku') ? wc_get_product_id_by_sku($sku) : 0;
    $r['skus'][$sku] = $id ? ['id' => $id, 'status' => get_post_status($id), 'type' => get_post_type($id),
        'title' => get_the_title($id), 'permalink' => get_permalink($id)] : null;
}

global $wpdb;
// Every product with its SKU, so missing SKUs can be matched to current products.
$rows = $wpdb->get_results("SELECT p.ID, p.post_name, p.post_status, p.post_title, pm.meta_value AS sku
    FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_sku'
    WHERE p.post_type = 'product' ORDER BY p.post_status, p.post_title", ARRAY_A);
$r['products'] = $rows;

// Where do malformed "Research Panel" hrefs live?
$pat = "%Research Panel%";
$pat2 = "%Research\\%20Panel%";
$r['corrupt']['posts'] = $wpdb->get_results($wpdb->prepare(
    "SELECT ID, post_type, post_status, post_name FROM {$wpdb->posts} WHERE (post_content LIKE %s OR post_content LIKE %s) AND post_content REGEXP 'href=[^>]*Research(%%20| )Panel'",
    $pat, '%Research%20Panel%'), ARRAY_A);
$r['corrupt']['postmeta'] = $wpdb->get_results($wpdb->prepare(
    "SELECT post_id, meta_key, LENGTH(meta_value) len FROM {$wpdb->postmeta} WHERE meta_value REGEXP %s",
    '-research-Research( |%20|\\\\u0020)Panel'), ARRAY_A);
$r['corrupt']['options'] = $wpdb->get_col($wpdb->prepare(
    "SELECT option_name FROM {$wpdb->options} WHERE option_value REGEXP %s", '-research-Research( |%20)Panel'));
$r['corrupt']['termmeta'] = $wpdb->get_results($wpdb->prepare(
    "SELECT term_id, meta_key FROM {$wpdb->termmeta} WHERE meta_value REGEXP %s", '-research-Research( |%20)Panel'), ARRAY_A);
$r['corrupt']['menu_items'] = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_menu_item_url' AND meta_value LIKE '%Research%Panel%'", ARRAY_A);

// Concrete href samples (post_content) for each malformed slug.
$samples = [];
foreach ($r['corrupt']['posts'] as $p) {
    $c = get_post_field('post_content', $p['ID']);
    if (preg_match_all('#href=(["\'])([^"\']*Research(?: |%20)Panel[^"\']*)\1#i', $c, $m)) {
        $samples[$p['ID']] = array_values(array_unique($m[2]));
    }
}
$r['corrupt']['href_samples'] = $samples;

// Existing redirect mechanisms.
$r['redirect_tables'] = $wpdb->get_col("SHOW TABLES LIKE '%redirect%'");
foreach ($r['redirect_tables'] as $t) {
    $cols = $wpdb->get_col("SHOW COLUMNS FROM `$t`");
    $r['redirect_table_cols'][$t] = $cols;
    if (in_array('url', $cols, true)) {
        $r['redirect_rows'][$t] = $wpdb->get_results("SELECT * FROM `$t` WHERE url LIKE '%products/%' OR url LIKE '%stack%' OR url LIKE '%guide%' OR url LIKE '%tirzepatide%' OR url LIKE '%bpc%' LIMIT 300", ARRAY_A);
    }
}
$r['old_slugs'] = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_old_slug'", ARRAY_A);

// Code that references legacy sources, redirects or the PDF endpoint.
$needles = ['opl7_pdf', 'tesamorelin-10mg-research-peptide', 'ara-290-10mg-research-peptide', 'products/ss-31',
    'peptide-reconstitution-guide', 'wp_redirect', 'wp_safe_redirect', 'X-Robots-Tag'];
$dirs = [WPMU_PLUGIN_DIR, WP_CONTENT_DIR . '/plugins', get_stylesheet_directory(), get_template_directory()];
$hits = [];
foreach (array_unique($dirs) as $d) {
    if (!is_dir($d)) { continue; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if (substr($p, -4) !== '.php' || preg_match('#/plugins/(woocommerce|jetpack|akismet|wordpress-seo|seo-by-rank-math|google-listings|woocommerce-[a-z-]+)/#', $p)) { continue; }
        $c = @file_get_contents($p);
        if ($c === false) { continue; }
        foreach ($needles as $n) {
            if (stripos($c, $n) !== false) { $hits[str_replace(WP_CONTENT_DIR, '', $p)][] = $n; }
        }
    }
}
$r['code_hits'] = $hits;

// PDF handler: show the lines around every opl7_pdf reference.
foreach ($hits as $rel => $ns) {
    if (!in_array('opl7_pdf', $ns, true)) { continue; }
    $lines = file(WP_CONTENT_DIR . $rel);
    foreach ($lines as $i => $line) {
        if (stripos($line, 'opl7_pdf') !== false || preg_match('/add_action\(|header\(|exit|die\(/', $line)) {
            $r['pdf_code'][$rel][] = ($i + 1) . ': ' . rtrim(substr($line, 0, 220));
        }
    }
}

// A few real product IDs for PDF testing.
$r['sample_product_ids'] = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID LIMIT 5");

file_put_contents($out, wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "report written\n";
