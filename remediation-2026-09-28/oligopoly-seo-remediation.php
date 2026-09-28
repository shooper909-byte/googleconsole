<?php
/**
 * Plugin Name: OligoPoly SEO Remediation 2026-09-28
 * Description: Direct 301s for confirmed broken legacy URLs (the corrupted "Stack → Research Panel" links and legacy product URLs whose current redirect ends in a 404 or takes more than one hop). Product destinations are resolved by WooCommerce SKU at request time.
 * Version: 2026.09.28.2
 *
 * Why this runs at MU-plugin load time: the site already has three older redirect
 * layers (Code Snippets #593–#595 run as soon as Code Snippets loads, and the
 * "oligopoly-seo-remediation-v2-active" plugin runs on init at -1000). Several of
 * their rules send these URLs to drafted products (404) or through a second hop.
 * Only the URLs listed below are handled here; every other legacy URL keeps its
 * existing, already-working redirect.
 *
 * The PDF X-Robots-Tag is NOT set here: Code Snippet #153, which generates the
 * ?opl7_pdf= responses, already sends "X-Robots-Tag: noindex" (verified live).
 */
if (!defined('ABSPATH')) { exit; }

/**
 * Legacy source => destination. A destination is either ['sku', SKU] (current
 * published product permalink) or ['path', '/hub/'] (a live hub page).
 * Keys are lower-case, URL-decoded paths without a trailing slash.
 */
function opseo_20260928_path_map(): array {
    $longevity   = ['sku', 'OP-STK-LONGEVITY'];          // Telomere Biology Reference Collection
    $neuro       = ['sku', 'OP-STACK-NEURO'];            // CNS Receptor Reference Collection
    $metabolic   = ['sku', 'OP-STACK-METABOLIC'];        // Metabolic Pathways Research Collection
    $comparative = ['sku', 'OP-STK-ADVANCED-MULTIPATHWAY']; // Comparative Pathway Collection
    $mito        = ['sku', 'OP-STACK-CELLULAR'];         // Mitochondrial Signaling Collection
    $tissue      = ['sku', 'OP-STACK-REGEN'];            // Tissue Signaling Collection
    $collections = ['path', '/research-collections/'];
    $recovery    = ['path', '/recovery-research-blends/'];

    return [
        // Corrupted "Stack → Research Panel" URLs (Search Console, crawled September 2026).
        '/research panels'                                            => $collections,
        '/product-category/research panels'                           => $collections,
        '/product-category/research-research panels-2'                => $collections,
        '/research-peptide-research panels-guide'                     => ['path', '/research-peptide-research-panels-guide/'],
        '/best-peptide-research panels-fat-loss'                      => ['path', '/metabolic-research-blends/'],
        '/products/performance-recovery-research-research panel'      => $comparative,
        '/products/advanced-multi-pathway-research-research panel'    => $comparative,
        '/products/recovery-support-research-research panel'          => $recovery,
        '/products/recovery-cellular-research-research panel'         => $collections,
        '/products/recovery-research-research panel'                  => $collections,
        '/products/cognitive-research-research panel'                 => $neuro,
        '/products/neuropeptide-signaling-research panel'             => $neuro,
        '/products/metabolic-research-research panel'                 => $metabolic,
        '/products/endocrine-receptor-ligands-research panel'         => $metabolic,
        '/products/longevity-research-research panel'                 => $longevity,
        '/products/senescence-telomere-signaling-research panel'      => $longevity,
        '/products/anti-aging-repair-research-research panel'         => $longevity,
        '/products/recovery-mitochondrial-redox-signaling-research panel'    => $mito,
        '/products/extracellular-matrix-signaling-research panel'            => $tissue,
        '/products/performance-extracellular-matrix-signaling-research panel' => $tissue,
        '/products/immune-optimization-research-research panel'       => $collections, // retired, no equivalent collection
        '/products/starter-research-research panel'                   => $collections, // retired, no equivalent collection
        '/products/gh-axis-research-research panel'                   => $collections, // retired, no equivalent collection

        // The same links before the corruption, where today's redirect is broken or two hops.
        '/products/longevity-research-stack'                          => $longevity,
        '/products/senescence-telomere-signaling-stack'               => $longevity,
        '/products/anti-aging-repair-research-stack'                  => $longevity,
        '/products/metabolic-research-stack'                          => $metabolic,
        '/products/endocrine-receptor-ligands-stack'                  => $metabolic,
        '/products/neuropeptide-signaling-stack'                      => $neuro,
        '/products/recovery-mitochondrial-redox-signaling-stack'      => $mito,
        '/products/extracellular-matrix-signaling-stack'              => $tissue,
        '/products/performance-extracellular-matrix-signaling-stack'  => $tissue,
        '/products/immune-optimization-research-stack'                => $collections,
        '/products/starter-research-stack'                            => $collections,
        '/products/gh-axis-research-stack'                            => $collections,

        // Legacy product URLs whose current redirect ends in a 404 or takes two hops.
        '/products/ara-290'                                           => $recovery, // ARA-290 is drafted (retired)
        '/products/ara-290-10mg-research-peptide'                     => $recovery,
        '/products/mots-c-ss-31-blend'                                => ['sku', 'OP-AUX-MOTSC-10MG'], // blend drafted; MOTS-c 10 mg is live
        '/advanced-research-stack'                                    => $comparative,
        '/products/metabolic-stack'                                   => $metabolic,
        '/epitalon-telomere-research'                                 => ['path', '/senescence-biology-hub/'],
        '/product-category/research-blends'                           => $collections,
    ];
}

/** Legacy "/?product=<slug>" links on the home URL. */
function opseo_20260928_query_map(): array {
    $metabolic   = ['sku', 'OP-STACK-METABOLIC'];
    $comparative = ['sku', 'OP-STK-ADVANCED-MULTIPATHWAY'];
    $collections = ['path', '/research-collections/'];
    $recovery    = ['path', '/recovery-research-blends/'];
    return [
        'metabolic-research-research panel'                           => $metabolic,
        'metabolic-research-stack'                                    => $metabolic,
        'advanced-multi-pathway-research panel'                       => $comparative,
        'advanced-multi-pathway-stack'                                => $comparative,
        'starter-research-research panel-bpc-157-tb-500-ghk-cu'       => $collections,
        'starter-research-stack-bpc-157-tb-500-ghk-cu'                => $collections,
        'recovery-cellular-research panel-bpc-157-tb-500-ghk-cu-ss-31' => $collections,
        'recovery-cellular-stack-bpc-157-tb-500-ghk-cu-ss-31'         => $collections,
        'recovery-support-research panel-bpc-157-tb-500-ara-290'      => $recovery,
        'recovery-support-stack-bpc-157-tb-500-ara-290'               => $recovery,
    ];
}

/** Published product URL for a SKU, or null. Works before WooCommerce loads. */
function opseo_20260928_product_url(string $sku): ?string {
    global $wpdb;
    $id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_sku'
         WHERE m.meta_value = %s AND p.post_type = 'product' AND p.post_status = 'publish' LIMIT 1",
        $sku
    ));
    if (!$id) { return null; }
    $slug = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM {$wpdb->posts} WHERE ID = %d", $id));
    if (!$slug) { return null; }
    $permalinks = (array) get_option('woocommerce_permalinks', []);
    $base = trim((string) ($permalinks['product_base'] ?? '/products/'), '/');
    return home_url('/' . ($base !== '' ? $base . '/' : '') . $slug . '/');
}

/** Resolve a destination tuple to an absolute URL, or null if it cannot be resolved safely. */
function opseo_20260928_resolve(array $dest): ?string {
    if ($dest[0] === 'sku') {
        $url = opseo_20260928_product_url($dest[1]);
        if (!$url) {
            error_log('[opseo-20260928] SKU not published, redirect skipped: ' . $dest[1]);
        }
        return $url;
    }
    return home_url($dest[1]);
}

/** Destination for a request path/query, or null when this plugin does not handle it. */
function opseo_20260928_target_for(string $path, array $query = []): ?string {
    $key = strtolower(rawurldecode($path));
    $key = rtrim($key, '/');
    if ($key === '') {
        if (isset($query['product'])) {
            $q = strtolower(rawurldecode((string) $query['product']));
            $map = opseo_20260928_query_map();
            return isset($map[$q]) ? opseo_20260928_resolve($map[$q]) : null;
        }
        return null;
    }
    $map = opseo_20260928_path_map();
    return isset($map[$key]) ? opseo_20260928_resolve($map[$key]) : null;
}

/** Serve the redirect as early as possible, ahead of the older redirect layers. */
(function () {
    if (PHP_SAPI === 'cli' || is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') { return; }

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $query = [];
    parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

    $target = opseo_20260928_target_for($path, $query);
    if (!$target) { return; }

    // Never redirect a URL to itself.
    if (rtrim(strtolower((string) parse_url($target, PHP_URL_PATH)), '/') === rtrim(strtolower(rawurldecode($path)), '/') && $query === []) {
        return;
    }

    status_header(301);
    header('X-Redirect-By: OligoPoly SEO Remediation 2026-09-28');
    header('Cache-Control: max-age=3600');
    header('Location: ' . $target, true, 301);
    exit;
})();
