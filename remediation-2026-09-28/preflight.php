<?php
/**
 * Preflight for the 2026-09-28 remediation. Run after the MU plugin is in place:
 *   wp eval-file preflight.php
 * Exits non-zero if any destination cannot be resolved to a published product
 * or a published hub page, or if any source would redirect to itself.
 */
if (!function_exists('opseo_20260928_path_map')) {
    fwrite(STDERR, "FAIL: MU plugin not loaded.\n");
    exit(1);
}
$fail = 0;
$check = function (string $label, array $dest) use (&$fail) {
    if ($dest[0] === 'sku') {
        $url = opseo_20260928_product_url($dest[1]);
        if (!$url) { echo "FAIL {$label}: SKU {$dest[1]} is not a published product\n"; $fail++; return; }
    } else {
        $page = get_page_by_path(trim($dest[1], '/'));
        if (!$page || $page->post_status !== 'publish') { echo "FAIL {$label}: hub {$dest[1]} is not a published page\n"; $fail++; return; }
        $url = home_url($dest[1]);
    }
    $src = rtrim(strtolower($label), '/');
    if (rtrim(strtolower((string) parse_url($url, PHP_URL_PATH)), '/') === $src) { echo "FAIL {$label}: redirects to itself\n"; $fail++; return; }
    echo "OK   {$label} -> {$url}\n";
};
foreach (opseo_20260928_path_map() as $src => $dest) { $check($src, $dest); }
foreach (opseo_20260928_query_map() as $q => $dest) { $check("/?product={$q}", $dest); }
echo "Preflight complete. Failures: {$fail}\n";
exit($fail ? 1 : 0);
