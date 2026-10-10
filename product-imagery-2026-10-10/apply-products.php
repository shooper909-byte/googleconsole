<?php
/**
 * Give the remaining old-style products a batch-labelled image.
 *
 *   wp eval-file apply-products.php          # dry run
 *   wp eval-file apply-products.php apply    # write
 *
 * With apply:
 *  1. Sideloads each new WebP (pinned commit, sha256 checked) as an attachment;
 *     an attachment from an earlier run is reused.
 *  2. Writes each product's raw _thumbnail_id. A raw write is used because
 *     Code Snippet #587 filters _thumbnail_id, so update_post_meta() can see
 *     the filtered value and skip the write.
 *  3. Points Snippet #587's featured-image map at the new Comparative Pathway
 *     image (its entries "3487 => 3728" and "70 => 3728").
 * Previous values: option opimg_backup_20261010_products.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply  = in_array( 'apply', (array) ( $args ?? array() ), true );
$commit = '0565900a6360aee75547b7807fb8c1dbed5005be';
$raw    = "https://raw.githubusercontent.com/shooper909-byte/googleconsole/$commit/product-imagery-2026-10-10/out-products/";

// product slug => array( new image, alt text )
$products = array(
	'cagrilintide-5-mg-6-vial-research-kit' => array( 'cagrilintide-5mg-6-vial-kit-20261010', 'Cagrilintide 5 mg 6-vial research kit vial with batch label' ),
	'ghk-cu-50-mg-6-vial-research-kit'      => array( 'ghk-cu-50mg-6-vial-kit-20261010', 'GHK-Cu 50 mg 6-vial research kit vial with batch SGX-OL-2026-0811-K99' ),
	'nad-500-mg-6-vial-research-kit'        => array( 'nad-500mg-6-vial-kit-20261010', 'NAD+ 500 mg 6-vial research kit vial with batch AYK20260418-NAD500' ),
	'retatrutide-20-mg-6-vial-research-kit' => array( 'retatrutide-20mg-6-vial-kit-20261010', 'Retatrutide 20 mg 6-vial research kit vial with batch label' ),
	'retatrutide-5-mg-6-vial-research-kit'  => array( 'retatrutide-5mg-6-vial-kit-20261010', 'Retatrutide 5 mg 6-vial research kit vial with batch P260628-L013' ),
	'selank-5-mg-6-vial-research-kit'       => array( 'selank-5mg-6-vial-kit-20261010', 'Selank 5 mg 6-vial research kit vial with batch OP-20260720-B001' ),
	'semaglutide-5-mg-6-vial-research-kit'  => array( 'semaglutide-5mg-6-vial-kit-20261010', 'Semaglutide 5 mg 6-vial research kit vial with batch SGX-OL-2026-0811-S88' ),
	'tirzepatide-10-mg-6-vial-research-kit' => array( 'tirzepatide-10mg-6-vial-kit-20261010', 'Tirzepatide 10 mg 6-vial research kit vial with batch 20260609' ),
	'build-your-research-bundle-3-vials'    => array( 'build-your-bundle-3-vials-20261010', 'Build your research bundle, 3 vials, with batch-labelled vials' ),
	'build-your-research-bundle-6-vials'    => array( 'build-your-bundle-6-vials-20261010', 'Build your research bundle, 6 vials, with batch-labelled vials' ),
	'build-your-research-bundle-9-vials'    => array( 'build-your-bundle-9-vials-20261010', 'Build your research bundle, 9 vials, with batch-labelled vials' ),
	'comparative-pathway-collection'        => array( 'comparative-pathway-collection-20261010', 'Comparative Pathway Collection vials: Semaglutide 5 mg and Cagrilintide 5 mg with batch labels' ),
	'telomere-biology-reference-collection' => array( 'telomere-biology-collection-20261010', 'Telomere Biology Reference Collection vials: GHK-Cu 50 mg and BPC-157 5 mg with batch labels' ),
	'klow-blend-research-peptide'           => array( 'klow-blend-front-back-testing-results-soon-20261010', 'KLOW blend research vial with batch testing label' ),
	// Existing photo, already in the media library.
	'tirzepatide-10mg-research-peptide'     => array( 'tirzepatide-10mg-front-back-batch20260609', 'Tirzepatide 10 mg research vial with batch 20260609' ),
);

$sha = array(
	'build-your-bundle-3-vials-20261010'                 => 'f6983d027ea5ac8aa7d5b0a1bd11c605a811c7f00dfa228963e254971a45aec4',
	'build-your-bundle-6-vials-20261010'                 => '5407169ad3eaa366596d3ed71e8f57663dd45988e233a1719b3527779a8c917e',
	'build-your-bundle-9-vials-20261010'                 => 'a8a1fdc04738bc0e91efbc0096d078f89c2afd11572187d5cc3bb9a26994109c',
	'cagrilintide-5mg-6-vial-kit-20261010'               => '699bc06b49f61e95ebced320dc8bf56202fa8c5ab210b5b3185bea1f8b090bb7',
	'comparative-pathway-collection-20261010'            => 'bddbcc5b9c9fa9b90fe877620dbbac9f0b0ad149ca29b793c984686e99f1d305',
	'ghk-cu-50mg-6-vial-kit-20261010'                    => '0a092aa43f94b0b167fff2b11473306a1bed8fb745bd805ba5c321210b76ed10',
	'klow-blend-front-back-testing-results-soon-20261010' => '361f64b512f33d3282462fad96e8de9479196132f3392dbfbb3ab5c29d82b23b',
	'nad-500mg-6-vial-kit-20261010'                      => '778522c068b7b90a35fb85e7275d1cae669109c84623e27889d006451824d6cc',
	'retatrutide-20mg-6-vial-kit-20261010'               => 'a04b4c0289c66921e388b6acf2fce4d2e4c57a5c9dc781f0131c6faf12517f6b',
	'retatrutide-5mg-6-vial-kit-20261010'                => 'ed123117cddbd745c24a949d32b231a4ca7d07ba2ff57854b07e61777622b261',
	'selank-5mg-6-vial-kit-20261010'                     => 'd863b947201a9c336e5e21557a3bcb7b16c43f941d18786779687739a82f8f13',
	'semaglutide-5mg-6-vial-kit-20261010'                => '5b9464a5baba43b743ca49eb92af5a74b6a696dc35131ad5ba6a17b884e6700e',
	'telomere-biology-collection-20261010'               => '5532f1237cfcfef158d5199a818a90740758fdc2b35b2388649a7a0fa33216fe',
	'tirzepatide-10mg-6-vial-kit-20261010'               => '30caaef6c1d607ce78eaaaccb2d07039ab370c98606a5d0c8856a9addec3b146',
);

global $wpdb;

$find_attachment = function ( $basename ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND (meta_value LIKE %s OR meta_value LIKE %s) ORDER BY post_id DESC LIMIT 1",
		'%/' . $wpdb->esc_like( $basename ) . '.webp',
		'%/' . $wpdb->esc_like( $basename ) . '.png'
	) );
};

if ( $apply ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
}

$plan = array();
foreach ( $products as $slug => list( $image, $alt ) ) {
	$pid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_name=%s", $slug ) );
	if ( ! $pid ) {
		WP_CLI::error( "No published product $slug" );
	}
	$old = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_thumbnail_id'", $pid ) );
	$aid = $find_attachment( $image );
	if ( ! $aid && ! isset( $sha[ $image ] ) ) {
		WP_CLI::error( "$image is not in the media library" );
	}
	if ( ! $aid && $apply ) {
		$tmp = download_url( $raw . "$image.webp", 60 );
		if ( is_wp_error( $tmp ) ) {
			WP_CLI::error( "$image: " . $tmp->get_error_message() );
		}
		if ( hash_file( 'sha256', $tmp ) !== $sha[ $image ] ) {
			@unlink( $tmp );
			WP_CLI::error( "$image: sha256 mismatch" );
		}
		$aid = media_handle_sideload( array( 'name' => "$image.webp", 'tmp_name' => $tmp ), $pid, get_the_title( $pid ) );
		if ( is_wp_error( $aid ) ) {
			@unlink( $tmp );
			WP_CLI::error( "$image: " . $aid->get_error_message() );
		}
		update_post_meta( $aid, '_wp_attachment_image_alt', $alt );
	}
	$plan[ $pid ] = array( 'slug' => $slug, 'old' => $old, 'new' => $aid );
}

// Snippet #587 forces the Comparative Pathway image for products 3487 and 70.
$snippet_code = $wpdb->get_var( "SELECT code FROM {$wpdb->prefix}snippets WHERE id = 587" );
$comparative  = 0;
foreach ( $plan as $pid => $p ) {
	if ( 'comparative-pathway-collection' === $p['slug'] ) {
		$comparative = $p['new'];
	}
}
$patched = $snippet_code;
foreach ( array( 3487, 70 ) as $product ) {
	$pattern = '/\b' . $product . '(\s*=>\s*)3728\b/';
	if ( 1 !== preg_match_all( $pattern, $patched ) ) {
		WP_CLI::error( "Snippet 587: expected one '$product => 3728'" );
	}
	if ( $comparative ) {
		$patched = preg_replace( $pattern, $product . '${1}' . $comparative, $patched );
	}
}
try {
	token_get_all( "<?php\n" . $patched, TOKEN_PARSE );
} catch ( ParseError $e ) {
	WP_CLI::error( 'Patched snippet 587 does not parse: ' . $e->getMessage() );
}

foreach ( $plan as $pid => $p ) {
	WP_CLI::log( "$pid {$p['slug']} {$p['old']} -> " . ( $p['new'] ?: '(new)' ) );
}

if ( ! $apply ) {
	WP_CLI::success( 'Dry run OK.' );
	return;
}

if ( ! get_option( 'opimg_backup_20261010_products' ) ) {
	add_option( 'opimg_backup_20261010_products', array( 'thumbs' => wp_list_pluck( $plan, 'old' ), 'snippet587' => $snippet_code ), '', 'no' );
}
foreach ( $plan as $pid => $p ) {
	if ( null === $p['old'] ) {
		$wpdb->insert( $wpdb->postmeta, array( 'post_id' => $pid, 'meta_key' => '_thumbnail_id', 'meta_value' => (string) $p['new'] ) );
	} else {
		$wpdb->update( $wpdb->postmeta, array( 'meta_value' => (string) $p['new'] ), array( 'post_id' => $pid, 'meta_key' => '_thumbnail_id' ) );
	}
	clean_post_cache( $pid );
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $pid );
	}
}
if ( $patched !== $snippet_code ) {
	$wpdb->update( "{$wpdb->prefix}snippets", array( 'code' => $patched ), array( 'id' => 587 ) );
}
wp_cache_flush();
WP_CLI::success( 'Applied to ' . count( $plan ) . ' products.' );
