<?php
/**
 * Code Snippet #454 ("OligoPoly Priority Product Commercial Metadata 20260724") overrides
 * the title and meta description of several products at rank_math priority 3100 and again
 * in an output buffer, so the Rank Math values set by apply-seo-phases.php never reach the
 * page for these six products. This points the snippet's map entries for those products at
 * the same Rank Math values, so there is one set of metadata (title, description, og and
 * twitter tags). Every other entry in the map is left unchanged.
 *
 * Usage: wp eval-file update-snippet-454.php [--apply]
 * The previous snippet code is stored in the option opseo3_backup_20260929 under
 * 'snippet:454:code' before the write.
 */
$apply = in_array('--apply', $args ?? [], true);
global $wpdb;
$table = $wpdb->prefix . 'snippets';
$code  = (string) $wpdb->get_var("SELECT code FROM {$table} WHERE id = 454");
if (strpos($code, 'OPL_PRIORITY_PRODUCT_METADATA_20260724') === false) {
    echo "ABORT: snippet 454 is not the expected metadata snippet\n";
    return;
}
if (!preg_match("~base64_decode\\('([A-Za-z0-9+/=]+)'\\)~", $code, $m)) {
    echo "ABORT: map not found\n";
    return;
}
$map = json_decode(base64_decode($m[1]), true);
if (!is_array($map)) {
    echo "ABORT: map does not decode\n";
    return;
}
$targets = [49 => 'products/bpc-157-10mg-research-peptide', 55 => 'products/bpc-157-tb-500-blend',
    441 => 'products/ghk-cu-50mg-research-peptide', 443 => 'products/mots-c-10mg-research-peptide',
    63 => 'products/nad-500mg-research-compound', 447 => 'products/selank-5mg-research-peptide'];
$changes = [];
foreach ($targets as $id => $path) {
    $t = (string) get_post_meta($id, 'rank_math_title', true);
    $d = (string) get_post_meta($id, 'rank_math_description', true);
    if ($t === '' || $d === '' || !isset($map[$path])) {
        echo "ABORT: missing value for {$id} {$path}\n";
        return;
    }
    if ($map[$path]['title'] !== $t || $map[$path]['description'] !== $d) {
        $changes[] = $path;
        $map[$path]['title'] = $t;
        $map[$path]['description'] = $d;
    }
}
$new = str_replace($m[1], base64_encode(wp_json_encode($map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), $code);
if ($apply && $changes) {
    $backup = (array) get_option('opseo3_backup_20260929', []);
    if (!isset($backup['snippet:454:code'])) {
        $backup['snippet:454:code'] = $code;
        update_option('opseo3_backup_20260929', $backup, false);
    }
    $wpdb->update($table, ['code' => $new], ['id' => 454]);
    if (function_exists('\Code_Snippets\clean_snippets_cache')) {
        \Code_Snippets\clean_snippets_cache($table);
    }
    wp_cache_flush();
}
echo ($apply ? 'applied' : 'dry-run') . ' changed=' . implode(',', $changes) . "\n";
