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
$lines = [];
$say = function (string $line) use (&$lines) { $lines[] = $line; echo $line . "\n"; };
$check = function (string $label, array $dest) use (&$fail, $say) {
    if ($dest[0] === 'sku') {
        $url = opseo_20260928_product_url($dest[1]);
        if (!$url) { $say("FAIL {$label}: SKU {$dest[1]} is not a published product"); $fail++; return; }
    } else {
        // Hubs are pages or posts (e.g. /research-peptide-research-panels-guide/ is a post).
        $page = get_page_by_path(trim($dest[1], '/'), OBJECT, ['page', 'post']);
        if (!$page || $page->post_status !== 'publish') { $say("FAIL {$label}: hub {$dest[1]} is not a published page or post"); $fail++; return; }
        $url = home_url($dest[1]);
    }
    $src = rtrim(strtolower($label), '/');
    if (rtrim(strtolower((string) parse_url($url, PHP_URL_PATH)), '/') === $src) { $say("FAIL {$label}: redirects to itself"); $fail++; return; }
    $say("OK   {$label} -> {$url}");
};
foreach (opseo_20260928_path_map() as $src => $dest) { $check($src, $dest); }
foreach (opseo_20260928_query_map() as $q => $dest) { $check("/?product={$q}", $dest); }
$say("Preflight complete. Failures: {$fail}");
foreach ($args ?? [] as $a) { if (strpos($a, '--report=') === 0) { file_put_contents(substr($a, 9), implode("\n", $lines) . "\n"); } }
exit($fail ? 1 : 0);
