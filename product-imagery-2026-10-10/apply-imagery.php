<?php
/**
 * Swap the bundle hero and research-collection images for the new
 * batch-labelled composites.
 *
 *   wp eval-file apply-imagery.php            # dry run: report only
 *   wp eval-file apply-imagery.php apply      # write
 *
 * With apply:
 *  1. Sideloads each new WebP (from this repo at a pinned commit, sha256
 *     checked) as a new attachment, so the old 1-year-cached PNG URLs are
 *     not reused.
 *  2. Rewrites every reference to the old PNG (plain, JSON-escaped and
 *     resized variants) in non-revision posts, postmeta and options. In
 *     _elementor_data the attachment id next to a rewritten url is switched
 *     to the new attachment too. Serialized values are unserialized first.
 *  3. Saves every previous value in the option opimg_backup_20261010.
 * The old attachments and files are left untouched.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply  = in_array( 'apply', (array) ( $args ?? array() ), true );
$commit = 'b8a0af218ddd09e9899230863510cd00c0e78e76';
$raw    = "https://raw.githubusercontent.com/shooper909-byte/googleconsole/$commit/product-imagery-2026-10-10/out/";

$images = array(
	'BUNDLE-BUILDER-HERO'              => array( 'alt' => 'OligoPoly research vials with batch labels: Retatrutide 5 mg, Selank 5 mg and Cagrilintide 5 mg' ),
	'OP-RESEARCH-COLLECTION-METABOLIC' => array( 'alt' => 'Metabolic Systems research collection vials: Retatrutide, Tirzepatide, Semaglutide and Cagrilintide with batch labels' ),
	'OP-RESEARCH-COLLECTION-CELLULAR'  => array( 'alt' => 'Cellular Energy research collection vials: NAD+, GHK-Cu, Selank and Cagrilintide with batch labels' ),
	'OP-RESEARCH-COLLECTION-NEURO'     => array( 'alt' => 'Neurocognitive Pathways research collection vials: Selank, NAD+ and GHK-Cu with batch labels' ),
	'OP-RESEARCH-COLLECTION-REGEN'     => array( 'alt' => 'Regenerative Biology research collection vials: GHK-Cu, NAD+ and Selank with batch labels' ),
);
$sha = array(
	'BUNDLE-BUILDER-HERO'              => 'b5371650c04d749e589856df0b92f44cf99ab650b4fdcd4122f3fb833bb429a2',
	'OP-RESEARCH-COLLECTION-CELLULAR'  => 'd2fcbe2ca24b7c5b39f6535417a27b0fa712bfbc2e871ae373bc7c622265066e',
	'OP-RESEARCH-COLLECTION-METABOLIC' => '8bf29789d11d5df5c15750d136b903206b37b007a06742f0729704ac5cd9bdd0',
	'OP-RESEARCH-COLLECTION-NEURO'     => 'ae012ddd98c8fe3ea65b09971af6cf521dcbd179d2cfc2624cd3b2e2bcb60953',
	'OP-RESEARCH-COLLECTION-REGEN'     => '28b5968569aa5e5e030bc2e772d133c2121f839dda7eedb9ad3618057bad1a8b',
);

global $wpdb;

// Resolve the old attachment ids by file name.
foreach ( $images as $name => &$img ) {
	$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value=%s", "2026/08/$name.png" ) );
	if ( ! $id ) {
		WP_CLI::error( "No attachment for 2026/08/$name.png" );
	}
	$img['old_id'] = $id;
}
unset( $img );

// Existing new attachments from an earlier run are reused.
foreach ( $images as $name => &$img ) {
	$img['new_id'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1", '%/' . $wpdb->esc_like( "$name-20261010" ) . '%.webp' ) );
}
unset( $img );

if ( $apply ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	foreach ( $images as $name => &$img ) {
		if ( $img['new_id'] ) {
			continue;
		}
		$tmp = download_url( $raw . "$name-20261010.webp", 60 );
		if ( is_wp_error( $tmp ) ) {
			WP_CLI::error( "$name: " . $tmp->get_error_message() );
		}
		if ( hash_file( 'sha256', $tmp ) !== $sha[ $name ] ) {
			@unlink( $tmp );
			WP_CLI::error( "$name: sha256 mismatch" );
		}
		$id = media_handle_sideload( array( 'name' => "$name-20261010.webp", 'tmp_name' => $tmp ), 0, get_the_title( $img['old_id'] ) );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp );
			WP_CLI::error( "$name: " . $id->get_error_message() );
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $img['alt'] );
		$img['new_id'] = $id;
	}
	unset( $img );
}

// Map every old URL form (full size + generated sizes) to the new one.
$map    = array(); // old path fragment => new path fragment
$id_map = array();
foreach ( $images as $name => $img ) {
	$new_rel = $img['new_id'] ? get_post_meta( $img['new_id'], '_wp_attached_file', true ) : "2026/10/$name-20261010.webp";
	$old_meta = wp_get_attachment_metadata( $img['old_id'] );
	foreach ( (array) ( $old_meta['sizes'] ?? array() ) as $s ) {
		$map[ '2026/08/' . $s['file'] ] = $new_rel;
	}
	$map[ "2026/08/$name.png" ] = $new_rel;
	$id_map[ $img['old_id'] ]   = $img['new_id'];
}
$esc = array();
foreach ( $map as $o => $n ) {
	$esc[ str_replace( '/', '\\/', $o ) ] = str_replace( '/', '\\/', $n );
}
$all = $map + $esc;
// Longest first so a sized file name is not cut by the full-size one.
uksort( $all, function ( $a, $b ) { return strlen( $b ) - strlen( $a ); } );

$replace_str = function ( $s ) use ( $all ) {
	return strtr( $s, $all );
};
$walk = function ( $v ) use ( &$walk, $replace_str ) {
	if ( is_string( $v ) ) {
		return $replace_str( $v );
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $x ) {
			$v[ $k ] = $walk( $x );
		}
	}
	return $v;
};
// Elementor: also switch the id beside a rewritten image url.
$walk_el = function ( $v ) use ( &$walk_el, $replace_str, $id_map ) {
	if ( is_string( $v ) ) {
		return $replace_str( $v );
	}
	if ( is_array( $v ) ) {
		if ( isset( $v['url'], $v['id'] ) && is_string( $v['url'] ) && isset( $id_map[ (int) $v['id'] ] ) ) {
			$nu = $replace_str( $v['url'] );
			if ( $nu !== $v['url'] && $id_map[ (int) $v['id'] ] ) {
				$v['id'] = $id_map[ (int) $v['id'] ];
			}
		}
		foreach ( $v as $k => $x ) {
			$v[ $k ] = $walk_el( $x );
		}
	}
	return $v;
};
$transform = function ( $raw_value, $is_elementor ) use ( $walk, $walk_el, $replace_str ) {
	if ( $is_elementor ) {
		// Plain string replacement keeps the stored JSON byte-for-byte
		// elsewhere; re-encode only when an attachment id must change too.
		$plain = $replace_str( $raw_value );
		$d     = json_decode( $raw_value, true );
		if ( is_array( $d ) ) {
			$walked = $walk_el( $d );
			if ( $walked != json_decode( $plain, true ) ) {
				$enc = wp_json_encode( $walked );
				return false === $enc ? $plain : $enc;
			}
		}
		return $plain;
	}
	if ( is_serialized( $raw_value ) ) {
		$u = @unserialize( $raw_value, array( 'allowed_classes' => false ) );
		if ( false !== $u || 'b:0;' === $raw_value ) {
			return serialize( $walk( $u ) );
		}
		return $raw_value; // Leave unparseable serialized data alone.
	}
	return $replace_str( $raw_value );
};

$likes = array();
foreach ( array_keys( $images ) as $name ) {
	$likes[] = $wpdb->prepare( '%s', '%' . $wpdb->esc_like( "2026/08/$name" ) . '%' );
	$likes[] = $wpdb->prepare( '%s', '%' . $wpdb->esc_like( "2026\\/08\\/$name" ) . '%' );
}
$cond = function ( $col ) use ( $likes ) {
	return '(' . implode( ' OR ', array_map( function ( $l ) use ( $col ) { return "$col LIKE $l"; }, $likes ) ) . ')';
};

$backup  = array( 'posts' => array(), 'postmeta' => array(), 'options' => array() );
$report  = array();
$changed = 0;

foreach ( $wpdb->get_results( "SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','attachment') AND " . $cond( 'post_content' ) ) as $p ) {
	$new = $replace_str( $p->post_content );
	if ( $new !== $p->post_content ) {
		$report[] = "post {$p->ID} {$p->post_type}/{$p->post_status} post_content";
		$backup['posts'][ $p->ID ] = $p->post_content;
		if ( $apply ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $new ), array( 'ID' => $p->ID ) );
			clean_post_cache( $p->ID );
		}
		$changed++;
	}
}

foreach ( $wpdb->get_results( "SELECT m.meta_id, m.post_id, m.meta_key, m.meta_value, p.post_type, p.post_status FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.post_type NOT IN ('revision','attachment') AND " . $cond( 'm.meta_value' ) ) as $m ) {
	$new = $transform( $m->meta_value, '_elementor_data' === $m->meta_key );
	if ( $new !== $m->meta_value ) {
		$report[] = "meta {$m->post_id} {$m->post_type}/{$m->post_status} {$m->meta_key}";
		$backup['postmeta'][ $m->meta_id ] = $m->meta_value;
		if ( $apply ) {
			$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $new ), array( 'meta_id' => $m->meta_id ) );
			wp_cache_delete( $m->post_id, 'post_meta' );
			clean_post_cache( $m->post_id );
		}
		$changed++;
	}
}

foreach ( $wpdb->get_results( "SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_name NOT LIKE '\\_transient%' AND option_name NOT LIKE '\\_site\\_transient%' AND option_name <> 'opimg_backup_20261010' AND " . $cond( 'option_value' ) ) as $o ) {
	$new = $transform( $o->option_value, false );
	if ( $new !== $o->option_value ) {
		$report[] = "option {$o->option_name}";
		$backup['options'][ $o->option_name ] = $o->option_value;
		if ( $apply ) {
			$wpdb->update( $wpdb->options, array( 'option_value' => $new ), array( 'option_id' => $o->option_id ) );
			wp_cache_delete( $o->option_name, 'options' );
		}
		$changed++;
	}
}

foreach ( $images as $name => $img ) {
	WP_CLI::log( sprintf( '%s old=%d new=%s', $name, $img['old_id'], $img['new_id'] ? $img['new_id'] . ' ' . wp_get_attachment_url( $img['new_id'] ) : '(not created yet)' ) );
}
foreach ( $report as $r ) {
	WP_CLI::log( $r );
}

if ( $apply ) {
	if ( ! get_option( 'opimg_backup_20261010' ) ) {
		add_option( 'opimg_backup_20261010', $backup, '', 'no' );
	}
	wp_cache_flush();
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	WP_CLI::success( "Applied: $changed values rewritten." );
} else {
	WP_CLI::success( "Dry run: $changed values would change." );
}
