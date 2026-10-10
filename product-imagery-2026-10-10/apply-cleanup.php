<?php
/**
 * Remove the remaining old-style vial images from product pages and the
 * sitewide mega menu.
 *
 *   wp eval-file apply-cleanup.php          # dry run
 *   wp eval-file apply-cleanup.php apply    # write
 *
 * With apply:
 *  1. Product galleries (published products): drop attachments that are not
 *     batch-labelled photos, COA images or the new composites. The featured
 *     image already shows the batch-labelled photo.
 *  2. Rank Math social images (rank_math_facebook_image / twitter): an old
 *     image is pointed at the product's featured image.
 *  3. Code Snippet #100 (mega menu): the Sema/Cagri feature image is replaced
 *     with the batch-labelled Sema + Cagri blend photo (same 800x534 slot).
 * Previous values: option opimg_backup_20261010_cleanup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply  = in_array( 'apply', (array) ( $args ?? array() ), true );
$commit = 'ebbef81a06a09903e9190ed5b3628c67875aaf18';
$keep   = '/front-back|coa|lot-test|web-hero|batch-side|20261010|RESEARCH-COLLECTION/i';
$mega   = array(
	'name' => 'semaglutide-cagrilintide-blend-mega-menu-20261010',
	'sha'  => 'ebf002ea00d9cd3e7059b880c40679d01b419f5598fd0de0951832123d8031e8',
	'old'  => '/wp-content/uploads/2026/07/semaglutide-cagrilintide-research-blend-vial.webp',
);

global $wpdb;
$backup = array( 'gallery' => array(), 'social' => array(), 'snippet100' => null );
$report = array();

$file_of = function ( $id ) {
	return basename( (string) get_post_meta( (int) $id, '_wp_attached_file', true ) );
};

// 1. Galleries.
$rows = $wpdb->get_results( "SELECT m.meta_id, p.ID, m.meta_value FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_product_image_gallery' WHERE p.post_type = 'product' AND p.post_status = 'publish' AND m.meta_value <> ''" );
$gallery_new = array();
foreach ( $rows as $r ) {
	$ids  = array_filter( array_map( 'intval', explode( ',', $r->meta_value ) ) );
	$kept = array_values( array_filter( $ids, function ( $id ) use ( $keep, $file_of ) {
		return (bool) preg_match( $keep, $file_of( $id ) );
	} ) );
	if ( count( $kept ) !== count( $ids ) ) {
		$backup['gallery'][ $r->meta_id ] = $r->meta_value;
		$gallery_new[ $r->meta_id ]       = array( $r->ID, implode( ',', $kept ) );
		$report[] = "gallery {$r->ID}: " . count( $ids ) . ' -> ' . count( $kept );
	}
}

// 2. Rank Math social images.
$social_new = array();
foreach ( array( 'rank_math_facebook_image', 'rank_math_twitter_image' ) as $key ) {
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT m.meta_id, p.ID, m.meta_value FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_type = 'product' AND p.post_status = 'publish' AND m.meta_value <> ''", $key ) );
	foreach ( $rows as $r ) {
		if ( preg_match( $keep, basename( $r->meta_value ) ) ) {
			continue;
		}
		$thumb = (int) get_post_thumbnail_id( $r->ID );
		$url   = $thumb ? wp_get_attachment_url( $thumb ) : '';
		if ( ! $url ) {
			continue;
		}
		$id_row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $r->ID, $key . '_id' ) );
		$backup['social'][ $r->meta_id ] = $r->meta_value;
		$social_new[ $r->meta_id ]       = array( $r->ID, $url );
		if ( $id_row ) {
			$backup['social'][ $id_row->meta_id ] = $id_row->meta_value;
			$social_new[ $id_row->meta_id ]       = array( $r->ID, (string) $thumb );
		}
		$report[] = "$key {$r->ID}: " . basename( $r->meta_value ) . ' -> ' . basename( $url );
	}
}

// 3. Mega menu image in snippet #100.
$code = $wpdb->get_var( "SELECT code FROM {$wpdb->prefix}snippets WHERE id = 100" );
$hits = substr_count( (string) $code, $mega['old'] );
$report[] = "snippet 100: $hits reference(s) to the old mega-menu image";

foreach ( $report as $line ) {
	WP_CLI::log( $line );
}
if ( ! $apply ) {
	WP_CLI::success( 'Dry run OK.' );
	return;
}

if ( ! get_option( 'opimg_backup_20261010_cleanup' ) ) {
	$backup['snippet100'] = $code;
	add_option( 'opimg_backup_20261010_cleanup', $backup, '', 'no' );
}

foreach ( $gallery_new as $meta_id => list( $pid, $value ) ) {
	$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $value ), array( 'meta_id' => $meta_id ) );
	clean_post_cache( $pid );
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $pid );
	}
}
foreach ( $social_new as $meta_id => list( $pid, $value ) ) {
	$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $value ), array( 'meta_id' => $meta_id ) );
	clean_post_cache( $pid );
}

if ( $hits ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$aid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1", '%/' . $wpdb->esc_like( $mega['name'] ) . '.webp' ) );
	if ( ! $aid ) {
		$tmp = download_url( "https://raw.githubusercontent.com/shooper909-byte/googleconsole/$commit/product-imagery-2026-10-10/out-products/{$mega['name']}.webp", 60 );
		if ( is_wp_error( $tmp ) || hash_file( 'sha256', $tmp ) !== $mega['sha'] ) {
			WP_CLI::error( 'Mega-menu image download failed or sha256 mismatch' );
		}
		$aid = media_handle_sideload( array( 'name' => $mega['name'] . '.webp', 'tmp_name' => $tmp ), 0, 'Semaglutide + Cagrilintide blend' );
		if ( is_wp_error( $aid ) ) {
			WP_CLI::error( $aid->get_error_message() );
		}
		update_post_meta( $aid, '_wp_attachment_image_alt', 'Semaglutide + Cagrilintide research blend vial with batch testing label' );
	}
	$new_path = wp_make_link_relative( wp_get_attachment_url( $aid ) );
	$patched  = str_replace( $mega['old'], $new_path, $code );
	try {
		token_get_all( "<?php\n" . $patched, TOKEN_PARSE );
	} catch ( ParseError $e ) {
		WP_CLI::error( 'Patched snippet 100 does not parse: ' . $e->getMessage() );
	}
	$wpdb->update( "{$wpdb->prefix}snippets", array( 'code' => $patched ), array( 'id' => 100 ) );
	WP_CLI::log( "snippet 100 -> $new_path" );
}

wp_cache_flush();
WP_CLI::success( count( $gallery_new ) . ' galleries, ' . count( $social_new ) . ' social meta values, snippet 100: ' . ( $hits ? 'patched' : 'unchanged' ) . '.' );
