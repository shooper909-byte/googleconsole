<?php
/**
 * Point Code Snippet #587 ("OligoPoly Research Collections Migration 20260829")
 * at the new collection images.
 *
 *   wp eval-file patch-snippet-587.php          # dry run
 *   wp eval-file patch-snippet-587.php apply    # write
 *
 * The snippet swaps the collection products' featured image by id
 * (3474 => 3724 ...) and rewrites OP-STACK-*.png URLs in the page output to
 * the 2026/08 OP-RESEARCH-COLLECTION-*.png files. This patch:
 *  - changes the four featured-image ids to the new attachments;
 *  - appends four pairs to the end of $image_replacements so the rewritten
 *    PNG URLs end at the new WebP files (str_replace with arrays applies the
 *    pairs in order, so they run after the existing OP-STACK rules).
 * The old code is saved in the option opimg_backup_20261010_snippet587, and
 * the new code must parse before it is written.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );

global $wpdb;
$table = $wpdb->prefix . 'snippets';
$code  = $wpdb->get_var( "SELECT code FROM $table WHERE id = 587" );
if ( ! $code ) {
	WP_CLI::error( 'Snippet 587 not found' );
}

$names = array( 'METABOLIC', 'CELLULAR', 'NEURO', 'REGEN' );
$new   = array();
foreach ( $names as $n ) {
	$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value=%s", "2026/10/OP-RESEARCH-COLLECTION-$n-20261010.webp" ) );
	if ( ! $id ) {
		WP_CLI::error( "New attachment for $n not found" );
	}
	$new[ $n ] = $id;
}

$patched = $code;

// 1. Featured-image ids.
$ids = array(
	3474 => array( 3724, $new['METABOLIC'] ),
	3477 => array( 3725, $new['CELLULAR'] ),
	3480 => array( 3726, $new['NEURO'] ),
	3483 => array( 3727, $new['REGEN'] ),
);
foreach ( $ids as $product => $pair ) {
	$pattern = '/\b' . $product . '(\s*=>\s*)' . $pair[0] . '\b/';
	$count   = preg_match_all( $pattern, $patched );
	if ( 1 !== $count ) {
		WP_CLI::error( "Expected one '$product => {$pair[0]}', found $count" );
	}
	$patched = preg_replace( $pattern, $product . '${1}' . $pair[1], $patched );
}

// 2. Output rewrite: append the new pairs after the last existing one.
$anchor = "/(=>\s*'\/wp-content\/uploads\/2026\/08\/advanced-multi-pathway-research-collection\.png')(\s*\);\s*\\\$html\s*=\s*str_replace\(\s*array_keys\(\s*\\\$image_replacements\s*\))/";
$count  = preg_match_all( $anchor, $patched );
if ( 1 !== $count ) {
	WP_CLI::error( "Expected one image_replacements anchor, found $count" );
}
$pairs = '';
foreach ( $names as $n ) {
	$pairs .= ",\n\t\t'/wp-content/uploads/2026/08/OP-RESEARCH-COLLECTION-$n.png' => '/wp-content/uploads/2026/10/OP-RESEARCH-COLLECTION-$n-20261010.webp'";
}
$patched = preg_replace( $anchor, '${1}' . str_replace( '$', '\$', $pairs ) . '${2}', $patched );

// The snippet is stored without the opening tag; check that it still parses.
try {
	token_get_all( "<?php\n" . $patched, TOKEN_PARSE );
} catch ( ParseError $e ) {
	WP_CLI::error( 'Patched code does not parse: ' . $e->getMessage() );
}

WP_CLI::log( 'ids: ' . wp_json_encode( $new ) . ' bytes ' . strlen( $code ) . ' -> ' . strlen( $patched ) );

if ( $apply ) {
	if ( ! get_option( 'opimg_backup_20261010_snippet587' ) ) {
		add_option( 'opimg_backup_20261010_snippet587', $code, '', 'no' );
	}
	$wpdb->update( $table, array( 'code' => $patched ), array( 'id' => 587 ) );
	wp_cache_flush();
	WP_CLI::success( 'Snippet 587 patched.' );
} else {
	WP_CLI::success( 'Dry run OK.' );
}
