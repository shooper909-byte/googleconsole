<?php
/**
 * Repair corrupted "Research Panel" link targets in live WordPress content.
 *
 * Usage (after the MU plugin is installed):
 *   wp eval-file fix-internal-links.php            # dry run: report only
 *   wp eval-file fix-internal-links.php --apply    # write changes
 *
 * Scope, deliberately narrow:
 *  - Only href values (post_content) and Elementor link values (_elementor_data)
 *    whose target resolves through the MU plugin's legacy map are changed.
 *  - Visible text, headings, titles, meta descriptions and anchor ids are NOT touched.
 *  - Fragment-only links such as "#Research Panel-feature" are left alone: their
 *    matching id attributes carry the same text, so they still work in-page.
 *  - Revisions are not modified.
 *  - Writes go through $wpdb, so content is not re-filtered by kses (WP-CLI runs
 *    without an unfiltered_html user) and no new revisions are created.
 */
if (!function_exists('opseo_20260928_target_for')) {
    fwrite(STDERR, "ABORT: the oligopoly-seo-remediation MU plugin is not loaded.\n");
    exit(1);
}

$apply = in_array('--apply', $args ?? [], true);
global $wpdb;

/** New URL for a malformed link, or null to leave it unchanged. */
$resolve_link = function (string $url): ?string {
    if ($url === '' || $url[0] === '#') { return null; }
    $decoded = rawurldecode(str_replace('\\/', '/', $url));
    if (stripos($decoded, 'research panel') === false) { return null; }
    $parts = parse_url($decoded);
    if ($parts === false) { return null; }
    if (isset($parts['host']) && !preg_match('/(^|\.)oligopolypeptides\.com$/i', $parts['host'])) { return null; }
    $query = [];
    if (isset($parts['query'])) { parse_str($parts['query'], $query); }
    return opseo_20260928_target_for($parts['path'] ?? '/', $query);
};

$report = ['mode' => $apply ? 'apply' : 'dry-run', 'posts_changed' => 0, 'href_replacements' => 0,
    'elementor_rows_changed' => 0, 'elementor_replacements' => 0, 'unresolved' => [], 'changes' => []];

// 1) post_content hrefs (every non-revision post type).
$rows = $wpdb->get_results(
    "SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts}
     WHERE post_type <> 'revision' AND (post_content LIKE '%Research Panel%' OR post_content LIKE '%Research\\%20Panel%')"
);
foreach ($rows as $row) {
    $count = 0;
    $new = preg_replace_callback(
        '#(href\s*=\s*)(["\'])([^"\']*?Research(?: |%20)Panel[^"\']*?)\2#i',
        function ($m) use ($resolve_link, &$count, &$report, $row) {
            $target = $resolve_link($m[3]);
            if ($target === null) {
                if ($m[3] !== '' && $m[3][0] !== '#') { $report['unresolved'][$m[3]][] = (int) $row->ID; }
                return $m[0];
            }
            $count++;
            $report['changes'][] = ['post' => (int) $row->ID, 'from' => $m[3], 'to' => $target];
            return $m[1] . $m[2] . esc_url($target) . $m[2];
        },
        $row->post_content
    );
    if ($new === null) {
        $report['unresolved']['REGEX FAILURE (' . preg_last_error_msg() . ') in post_content'][] = (int) $row->ID;
        continue;
    }
    if ($count > 0 && $new !== $row->post_content) {
        $report['posts_changed']++;
        $report['href_replacements'] += $count;
        if ($apply) {
            $wpdb->update($wpdb->posts, ['post_content' => $new], ['ID' => (int) $row->ID]);
            clean_post_cache((int) $row->ID);
        }
    }
}

// 2) Elementor data. Decoded as JSON and walked value by value, so no assumption is
//    made about how quotes and slashes are escaped in storage. Only "url" values and
//    href attributes inside HTML strings are changed; the data is re-encoded the way
//    Elementor saves it (wp_json_encode).
$fix_html_hrefs = function (string $html, int $post_id) use ($resolve_link, &$report, &$count_ref) {
    return preg_replace_callback(
        '#(href\s*=\s*)(["\'])([^"\']*?Research(?: |%20)Panel[^"\']*?)\2#i',
        function ($m) use ($resolve_link, &$report, $post_id, &$count_ref) {
            $target = $resolve_link($m[3]);
            if ($target === null) {
                if ($m[3] !== '' && $m[3][0] !== '#') { $report['unresolved'][$m[3]][] = $post_id; }
                return $m[0];
            }
            $count_ref++;
            $report['changes'][] = ['post' => $post_id, 'meta' => '_elementor_data', 'from' => $m[3], 'to' => $target];
            return $m[1] . $m[2] . esc_url($target) . $m[2];
        },
        $html
    );
};
$walk = function ($node, int $post_id) use (&$walk, $resolve_link, $fix_html_hrefs, &$report, &$count_ref) {
    if (is_array($node) || is_object($node)) {
        $is_obj = is_object($node);
        $arr = (array) $node;
        foreach ($arr as $k => $v) {
            if ($k === 'url' && is_string($v)) {
                $target = $resolve_link($v);
                if ($target !== null) {
                    $count_ref++;
                    $report['changes'][] = ['post' => $post_id, 'meta' => '_elementor_data', 'from' => $v, 'to' => $target];
                    $arr[$k] = $target;
                    continue;
                }
            }
            $arr[$k] = $walk($v, $post_id);
        }
        return $is_obj ? (object) $arr : $arr;
    }
    if (is_string($node) && stripos($node, 'href') !== false && (stripos($node, 'Research Panel') !== false || stripos($node, 'Research%20Panel') !== false)) {
        $fixed = $fix_html_hrefs($node, $post_id);
        return $fixed === null ? $node : $fixed;
    }
    return $node;
};
$metas = $wpdb->get_results(
    "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta}
     WHERE meta_key = '_elementor_data' AND (meta_value LIKE '%Research Panel%' OR meta_value LIKE '%Research\\%20Panel%')"
);
foreach ($metas as $meta) {
    $data = json_decode($meta->meta_value);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $report['unresolved']['_elementor_data is not valid JSON (' . json_last_error_msg() . '), left unchanged'][] = (int) $meta->post_id;
        continue;
    }
    $count_ref = 0;
    $fixed = $walk($data, (int) $meta->post_id);
    if ($count_ref === 0) { continue; }
    $new = wp_json_encode($fixed);
    if (!$new || json_decode($new) === null) {
        $report['unresolved']['_elementor_data re-encode failed, left unchanged'][] = (int) $meta->post_id;
        continue;
    }
    $report['elementor_rows_changed']++;
    $report['elementor_replacements'] += $count_ref;
    if ($apply) {
        $wpdb->update($wpdb->postmeta, ['meta_value' => $new], ['meta_id' => (int) $meta->meta_id]);
        clean_post_cache((int) $meta->post_id);
        delete_post_meta((int) $meta->post_id, '_elementor_element_cache');
    }
}

// 3) Remaining malformed (non-fragment) hrefs after the run.
$remaining = 0;
$check = $wpdb->get_col(
    "SELECT post_content FROM {$wpdb->posts}
     WHERE post_type <> 'revision' AND post_status IN ('publish','private','draft','pending','future')
     AND (post_content LIKE '%Research Panel%' OR post_content LIKE '%Research\\%20Panel%')"
);
foreach ($check as $content) {
    if (preg_match_all('#href\s*=\s*(["\'])(?!\#)[^"\']*Research(?: |%20)Panel[^"\']*\1#i', $content, $m)) {
        $remaining += count($m[0]);
    }
}
$report['remaining_malformed_non_fragment_hrefs'] = $apply ? $remaining : '(dry run: run with --apply, then re-run to verify)';

$out = null;
foreach ($args ?? [] as $a) { if (strpos($a, '--report=') === 0) { $out = substr($a, 9); } }
$json = wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($out) { file_put_contents($out, $json); }
echo "mode={$report['mode']} posts_changed={$report['posts_changed']} href_replacements={$report['href_replacements']} "
    . "elementor_rows={$report['elementor_rows_changed']} elementor_replacements={$report['elementor_replacements']} "
    . "unresolved=" . count($report['unresolved']) . " remaining=" . (is_int($report['remaining_malformed_non_fragment_hrefs']) ? $report['remaining_malformed_non_fragment_hrefs'] : 'n/a') . "\n";
