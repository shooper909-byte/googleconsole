<?php
/**
 * Read-only production inspection, part 2: existing redirect rules, collection
 * contents, the PDF generator, and every malformed "Research Panel" href.
 * Usage: wp eval-file inspect-production-2.php <report-path>
 */
$out = isset($args[0]) ? $args[0] : 'php://stdout';
global $wpdb;
$r = [];

// Existing remediation plugin (active) — full source.
$existing = WP_CONTENT_DIR . '/plugins/oligopoly-seo-remediation-v2-active/oligopoly-seo-remediation.php';
$r['existing_plugin'] = is_readable($existing) ? file_get_contents($existing) : null;

// Rank Math redirections.
$r['rank_math_modules'] = get_option('rank_math_modules');
$r['rank_math_redirections'] = $wpdb->get_results("SELECT id, sources, url_to, header_code, status FROM {$wpdb->prefix}rank_math_redirections", ARRAY_A);

// Redirection plugin items (plugin folder is renamed "-disabled" but check its rows).
$r['redirection_items_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}redirection_items");

// Collection / stack contents.
$ids = [70, 3474, 3477, 3480, 3483, 468, 469, 472, 473, 477, 478, 479, 471, 470];
foreach ($ids as $id) {
    $p = get_post($id);
    if (!$p) { continue; }
    $meta = get_post_meta($id);
    $keep = [];
    foreach ($meta as $k => $v) {
        if (preg_match('/(child|bundle|mnm|contents|component|include|_children|upsell|crosssell)/i', $k)) { $keep[$k] = $v; }
    }
    $r['collections'][$id] = [
        'status' => $p->post_status, 'slug' => $p->post_name, 'title' => $p->post_title,
        'type' => function_exists('wc_get_product') && wc_get_product($id) ? wc_get_product($id)->get_type() : null,
        'excerpt' => wp_strip_all_tags($p->post_excerpt),
        'content_start' => mb_substr(trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($p->post_content))), 0, 700),
        'meta' => $keep,
    ];
}

// PDF generator: Code Snippets table and WPCode posts.
$snip_table = $wpdb->prefix . 'snippets';
if ($wpdb->get_var("SHOW TABLES LIKE '{$snip_table}'") === $snip_table) {
    $rows = $wpdb->get_results("SELECT id, name, active, scope, priority, code FROM {$snip_table} WHERE code LIKE '%opl7%' OR code LIKE '%X-Robots%' OR code LIKE '%redirect%'", ARRAY_A);
    foreach ($rows as $row) {
        $row['code'] = $row['code'];
        $r['snippets'][] = $row;
    }
    $r['snippet_list'] = $wpdb->get_results("SELECT id, name, active, scope FROM {$snip_table}", ARRAY_A);
}
$r['wpcode'] = $wpdb->get_results("SELECT ID, post_title, post_status FROM {$wpdb->posts} WHERE post_type = 'wpcode' AND post_content LIKE '%opl7%'", ARRAY_A);
foreach (['WP_CONTENT_DIR' => WP_CONTENT_DIR . '/plugins', 'mu' => WPMU_PLUGIN_DIR] as $label => $d) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if (substr($p, -4) !== '.php') { continue; }
        $c = @file_get_contents($p);
        if ($c !== false && strpos($c, 'opl7') !== false) { $r['opl7_files'][] = str_replace(WP_CONTENT_DIR, '', $p); }
    }
}

// Malformed hrefs: every distinct value, with counts, split by where it lives.
$hrefs = [];
$posts = $wpdb->get_results("SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts} WHERE post_content LIKE '%Research Panel%' OR post_content LIKE '%Research\\%20Panel%'");
foreach ($posts as $p) {
    if (!preg_match_all('#href=(\\\\?["\'])([^"\'\\\\]*Research(?: |%20)Panel[^"\'\\\\]*)\1#i', $p->post_content, $m)) { continue; }
    $bucket = $p->post_type === 'revision' ? 'revision' : $p->post_status . ':' . $p->post_type;
    foreach ($m[2] as $h) {
        $hrefs[$h]['count'] = ($hrefs[$h]['count'] ?? 0) + 1;
        $hrefs[$h]['where'][$bucket] = ($hrefs[$h]['where'][$bucket] ?? 0) + 1;
        if ($p->post_type !== 'revision') { $hrefs[$h]['posts'][] = (int) $p->ID; }
    }
    // Does the page define the matching anchor id with the same text?
    if (preg_match_all('#id=["\']([^"\']*Research Panel[^"\']*)["\']#i', $p->post_content, $ids_m)) {
        foreach ($ids_m[1] as $idv) { $r['ids_with_research_panel'][$idv][] = (int) $p->ID; }
    }
}
foreach ($hrefs as $h => &$v) { if (isset($v['posts'])) { $v['posts'] = array_values(array_unique($v['posts'])); } }
$r['post_content_hrefs'] = $hrefs;

// Same search in postmeta (Elementor data, Rank Math, etc.).
$metas = $wpdb->get_results("SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE '%Research Panel%' OR meta_value LIKE '%Research\\%20Panel%'");
foreach ($metas as $m) {
    $vals = [];
    if (preg_match_all('#(?:href=\\\\?["\']|"url":"|https?:\\\\?/\\\\?/)([^"\'\\s<]*Research(?: |%20)Panel[^"\'\\s<]*)#i', $m->meta_value, $mm)) { $vals = array_values(array_unique($mm[0])); }
    $r['postmeta_hits'][] = ['meta_id' => (int) $m->meta_id, 'post_id' => (int) $m->post_id, 'key' => $m->meta_key, 'link_like' => array_slice($vals, 0, 10)];
}

// Options holding link-like "Research Panel" values (menus, widgets, theme mods, Elementor kits).
$opts = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE '%Research Panel%' OR option_value LIKE '%Research\\%20Panel%'");
foreach ($opts as $o) {
    $vals = [];
    if (preg_match_all('#(?:href=\\\\?["\']|https?:\\\\?/\\\\?/[^"\'\\s<]*)[^"\'\\s<]*Research(?: |%20)Panel[^"\'\\s<]*#i', $o->option_value, $mm)) { $vals = array_values(array_unique($mm[0])); }
    $r['option_hits'][] = ['name' => $o->option_name, 'link_like' => array_slice($vals, 0, 10)];
}

// A real published product id for an opl7_pdf test.
$r['published_product_ids'] = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID");

file_put_contents($out, wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "report written\n";
