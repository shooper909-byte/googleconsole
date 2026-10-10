<?php
/**
 * Read-only: fetch every sitemap URL from the server and list the old-style
 * images each rendered page still shows.
 *
 *   wp eval-file scan-pages.php <offset> <count>
 *
 * Runs in batches (server jobs have a time limit). Each batch appends
 * "<image> <page path>" lines to /tmp/opimg-scan.txt and failures to
 * /tmp/opimg-scan-failed.txt, then prints "<done>/<total>".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$keep = '/front-back|20261010|logo|icon|coa|lot-test|web-hero|batch-side|cropped|category\.webp|laborator|quality-|documentation|analytical|lab_|record-review|scientist|phase8|catalog-banner|luxury-dna|supplement-lineup|Apr-1-2026|RESEARCH-COLLECTION|premium-label/i';

$urls = array();
foreach ( array( 'post', 'page', 'product', 'category', 'product_cat' ) as $map ) {
	$r = wp_remote_get( home_url( "/$map-sitemap.xml" ), array( 'timeout' => 30 ) );
	preg_match_all( '~<loc>([^<]+)</loc>~', wp_remote_retrieve_body( $r ), $m );
	foreach ( $m[1] as $u ) {
		if ( ! preg_match( '/\.(png|jpe?g|webp)$/i', $u ) ) {
			$urls[] = $u;
		}
	}
}
$urls   = array_values( array_unique( $urls ) );
$total  = count( $urls );
$offset = (int) ( $args[0] ?? 0 );
$urls   = array_slice( $urls, $offset, (int) ( $args[1] ?? 40 ) );

$found  = array();
$failed = array();
foreach ( $urls as $u ) {
	$r    = wp_remote_get( add_query_arg( 'opimgscan', time(), $u ), array( 'timeout' => 30, 'user-agent' => 'OligoPoly image scan' ) );
	$body = wp_remote_retrieve_body( $r );
	if ( 200 !== wp_remote_retrieve_response_code( $r ) || strlen( $body ) < 20000 ) {
		$failed[] = wp_parse_url( $u, PHP_URL_PATH ) . ':' . wp_remote_retrieve_response_code( $r );
		continue;
	}
	preg_match_all( '~wp-content/uploads/([^"\'\s),]+?\.(?:png|jpe?g|webp))~i', $body, $m );
	foreach ( array_unique( $m[1] ) as $img ) {
		$img = preg_replace( '/-\d+x\d+(\.\w+)$/', '$1', $img );
		if ( ! preg_match( $keep, $img ) ) {
			$found[ $img ][ wp_parse_url( $u, PHP_URL_PATH ) ] = true;
		}
	}
	usleep( 300000 );
}

$lines = array();
foreach ( $found as $img => $pages ) {
	foreach ( array_keys( $pages ) as $page ) {
		$lines[] = "$img $page";
	}
}
file_put_contents( '/tmp/opimg-scan.txt', $lines ? implode( "\n", $lines ) . "\n" : '', FILE_APPEND );
file_put_contents( '/tmp/opimg-scan-failed.txt', $failed ? implode( "\n", $failed ) . "\n" : '', FILE_APPEND );
echo ( $offset + count( $urls ) ) . "/$total\n";
