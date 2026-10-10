<?php
/**
 * Read-only: fetch every sitemap URL from the server and list the old-style
 * images each rendered page still shows.
 *
 *   wp eval-file scan-pages.php
 *
 * Writes /tmp/opimg-scan.txt: "<image> <count> <page paths>" per line.
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
$urls = array_unique( $urls );

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

uasort( $found, function ( $a, $b ) {
	return count( $b ) - count( $a );
} );
$lines = array();
foreach ( $found as $img => $pages ) {
	$lines[] = $img . ' ' . count( $pages ) . ' ' . implode( ' ', array_keys( $pages ) );
}
$lines[] = 'FAILED ' . implode( ' ', $failed );
file_put_contents( '/tmp/opimg-scan.txt', implode( "\n", $lines ) . "\n" );
WP_CLI::success( count( $urls ) . ' pages, ' . count( $found ) . ' old images, ' . count( $failed ) . ' failed' );
