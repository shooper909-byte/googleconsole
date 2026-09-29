<?php
/**
 * Read-only inspection for the SEO Phases 1-3 work: where product-template text and
 * the signup-popup H1 come from, Rank Math settings for priority products, COA
 * records, and every published post/page (for topic-cluster planning).
 * Usage: wp eval-file inspect-production-3.php <report-path>
 */
$out = isset($args[0]) ? $args[0] : 'php://stdout';
global $wpdb;
$r = [];

// 1) Which files and DB rows contain key template strings.
$needles = ['Published when available in product or COA documentation', 'Unlock 20% Off Your First', 'semaglutide-vs-tirzepatide', 'PROVISIONAL — HOLD', 'PROVISIONAL &mdash; HOLD', 'rank_math/sitemap/entry', 'rank_math/sitemap/exclude'];
$dirs = [WPMU_PLUGIN_DIR, WP_CONTENT_DIR . '/plugins', get_stylesheet_directory()];
foreach ($dirs as $d) {
    if (!is_dir($d)) { continue; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if (substr($p, -4) !== '.php' || preg_match('#/(woocommerce|jetpack|elementor|seo-by-rank-math|wordfence|wpforms-lite|google-listings-and-ads|facebook-for-woocommerce)/#', $p) || strpos($p, '-disabled/') !== false) { continue; }
        $c = @file_get_contents($p);
        if ($c === false) { continue; }
        foreach ($needles as $n) {
            if (strpos($c, $n) !== false) { $r['files'][str_replace(WP_CONTENT_DIR, '', $p)][] = $n; }
        }
    }
}
foreach ($needles as $n) {
    $like = '%' . $wpdb->esc_like($n) . '%';
    $r['db'][$n] = [
        'snippets' => $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}snippets WHERE active = 1 AND code LIKE %s", $like)),
        'posts' => $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s LIMIT 20", $like)),
        'postmeta' => $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_key FROM {$wpdb->postmeta} WHERE meta_value LIKE %s LIMIT 20", $like), ARRAY_A),
        'options' => $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s LIMIT 20", $like)),
    ];
}

// 2) Rank Math meta for the priority products.
$ids = [12, 39, 47, 436, 49, 55, 441, 443, 442, 63, 455, 447, 446];
foreach ($ids as $id) {
    $meta = [];
    foreach (get_post_meta($id) as $k => $v) {
        if (strpos($k, 'rank_math') === 0 || in_array($k, ['_sku', '_wp_old_slug'], true) || stripos($k, 'cas') !== false || stripos($k, 'molecular') !== false || stripos($k, 'opl') !== false || stripos($k, 'coa') !== false) {
            $meta[$k] = mb_substr(is_array($v) ? implode(' | ', $v) : $v, 0, 400);
        }
    }
    $p = get_post($id);
    $r['products'][$id] = ['slug' => $p->post_name, 'status' => $p->post_status, 'title' => $p->post_title,
        'excerpt' => mb_substr(wp_strip_all_tags($p->post_excerpt), 0, 600),
        'content_len' => strlen($p->post_content), 'content_start' => mb_substr(wp_strip_all_tags($p->post_content), 0, 900), 'meta' => $meta];
}
$r['rank_math_sitemap'] = get_option('rank-math-options-sitemap');
$r['rank_math_titles_product'] = array_intersect_key((array) get_option('rank-math-options-titles'), array_flip(['pt_product_robots', 'pt_product_custom_robots', 'pt_product_title', 'pt_product_description', 'noindex_empty_taxonomies']));

// 3) COA records (any CPT / table with coa in the name).
$r['coa_post_types'] = array_values(array_filter(get_post_types(), function ($t) { return stripos($t, 'coa') !== false || stripos($t, 'batch') !== false; }));
foreach ($r['coa_post_types'] as $t) {
    $r['coa_posts'][$t] = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_status, post_name FROM {$wpdb->posts} WHERE post_type = %s ORDER BY ID DESC LIMIT 100", $t), ARRAY_A);
}
$r['coa_tables'] = $wpdb->get_col("SHOW TABLES LIKE '%coa%'");
foreach ($r['coa_tables'] as $t) {
    $r['coa_table_rows'][$t] = $wpdb->get_results("SELECT * FROM `$t` LIMIT 60", ARRAY_A);
}
$r['coa_options'] = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%coa%' LIMIT 50");

// 4) Every published post and page (for clusters and internal links).
$r['content'] = $wpdb->get_results("SELECT ID, post_type, post_name, post_title, post_date, CHAR_LENGTH(post_content) AS len FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post','page') ORDER BY post_type, post_name", ARRAY_A);
foreach ($r['content'] as &$row) {
    $row['robots'] = get_post_meta($row['ID'], 'rank_math_robots', true);
    $row['canonical'] = get_post_meta($row['ID'], 'rank_math_canonical_url', true);
}
unset($row);

// 5) The signup gate snippet source (popup H1).
foreach ([49, 570, 568, 569, 567, 566, 565] as $sid) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, name, active, code FROM {$wpdb->prefix}snippets WHERE id = %d", $sid), ARRAY_A);
    if ($row) { $r['snippet_code'][$sid] = $row; }
}

file_put_contents($out, wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "report written\n";
