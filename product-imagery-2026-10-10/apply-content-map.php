<?php
/**
 * Point old-style product images used inside page/post content, snippets
 * and options at the batch-labelled photo of the same product.
 *
 *   wp eval-file apply-content-map.php          # dry run: references per image
 *   wp eval-file apply-content-map.php apply    # write
 *
 * Only images of products that are sold and have a batch-labelled photo are
 * mapped. Retired products and supplements have no new photo and are left
 * alone. Serialized values are unserialized first; JSON-escaped paths are
 * handled. Previous values: option opimg_backup_20261010_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );
$u     = '/wp-content/uploads/';

$fb = function ( $name ) {
	return "2026/10/$name";
};
$new = array(
	'aod'      => $fb( 'aod-9604-10mg-front-back-testing-results-soon.png' ),
	'bpc10'    => $fb( 'bpc-157-10mg-front-back-testing-results-soon.png' ),
	'bpckpv'   => $fb( 'bpc-157-kpv-blend-front-back-testing-results-soon.png' ),
	'bpctb'    => $fb( 'bpc-157-tb-500-blend-front-back-testing-results-soon.png' ),
	'cjc'      => $fb( 'cjc-1295-no-dac-5mg-front-back-testing-results-soon.png' ),
	'dsip'     => $fb( 'dsip-5mg-front-back-testing-results-soon.png' ),
	'ghk'      => $fb( 'ghk-cu-50mg-front-back-batch-SGX-OL-2026-0811-K99.png' ),
	'kpv'      => $fb( 'kpv-10mg-front-back-testing-results-soon.png' ),
	'motsc'    => $fb( 'mots-c-10mg-front-back-testing-results-soon.png' ),
	'nad'      => $fb( 'nad-500mg-front-back-batch-AYK20260418-NAD500.png' ),
	'pt141'    => $fb( 'pt-141-10mg-front-back-testing-results-soon.png' ),
	'reta5'    => $fb( 'retatrutide-5mg-front-back-batch-P260628-L013.png' ),
	'selank'   => $fb( 'selank-5mg-front-back-batch-OP-20260720-B001.png' ),
	'sema5'    => $fb( 'semaglutide-5mg-front-back-batch-SGX-OL-2026-0811-S88.png' ),
	'semax'    => $fb( 'semax-10mg-front-back-testing-results-soon.png' ),
	'tb500'    => $fb( 'tb-500-10mg-front-back-testing-results-soon.png' ),
	'tirz10'   => '2026/08/tirzepatide-10mg-front-back-batch20260609.png',
	'lineup'   => '2026/10/BUNDLE-BUILDER-HERO-20261010.webp',
);

// Old image (path under uploads, no size suffix) => new image key.
$map = array(
	'2026/07/aod-9604-10mg-research-peptide-premium-vial-square-20260708.webp'       => 'aod',
	'2026/07/bpc-157-10mg-research-peptide-premium-vial-square-20260708.webp'        => 'bpc10',
	'2026/07/bpc-157-kpv-blend-research-peptide-premium-vial-square-20260708.webp'   => 'bpckpv',
	'2026/07/bpc-157-tb-500-research-peptide-blend-premium-vial-square-20260708.webp' => 'bpctb',
	'2026/07/dsip-5mg-research-peptide-premium-vial-square-20260708.webp'            => 'dsip',
	'2026/07/ghk-cu-50mg-research-peptide-premium-vial-square-20260708.webp'         => 'ghk',
	'2026/07/kpv-10mg-research-peptide-premium-vial-square-20260708.webp'            => 'kpv',
	'2026/07/mots-c-10mg-research-peptide-premium-vial-square-20260708.webp'         => 'motsc',
	'2026/07/nad-plus-500mg-research-peptide-premium-vial-square-20260708.webp'      => 'nad',
	'2026/07/pt-141-10mg-research-peptide-premium-vial-square-20260708.webp'         => 'pt141',
	'2026/07/selank-5mg-research-peptide-premium-vial-square-20260708.webp'          => 'selank',
	'2026/07/semax-10mg-research-peptide-premium-vial-square-20260708.webp'          => 'semax',
	'2026/07/tb-500-10mg-research-peptide-premium-vial-square-20260708.webp'         => 'tb500',
	'oligopoly/aod9604.webp'                                                         => 'aod',
	'oligopoly/bpc157.webp'                                                          => 'bpc10',
	'oligopoly/bpctb.webp'                                                           => 'bpctb',
	'oligopoly/cjc1295_nodac.webp'                                                   => 'cjc',
	'oligopoly/ghk_cu_branded.webp'                                                  => 'ghk',
	'oligopoly/retatrutide.webp'                                                     => 'reta5',
	'oligopoly/selank.webp'                                                          => 'selank',
	'oligopoly/semaglutide.webp'                                                     => 'sema5',
	'oligopoly/tb500.webp'                                                           => 'tb500',
	'oligopoly/tirzepatide.webp'                                                     => 'tirz10',
	'oligopoly/products_hero.webp'                                                   => 'lineup',
	'2026/06/ChatGPT_Image_May_25__2026__06_10_26_PM.webp'                           => 'ghk',
	'2026/06/ChatGPT_Image_May_20__2026__05_26_46_PM.webp'                           => 'ghk',
	'2026/06/ChatGPT-Image-May-25-2026-05_28_52-PM.webp'                             => 'cjc',
	'2026/06/ChatGPT_Image_May_20__2026__05_24_46_PM.jpg'                            => 'reta5',
	'2026/06/ChatGPT_Image_May_20__2026__05_23_28_PM.jpg'                            => 'tirz10',
	'2026/06/ChatGPT_Image_May_14__2026__05_47_16_PM.webp'                           => 'reta5',
	'2026/06/ChatGPT_Image_Jun_9__2026__02_16_05_PM.jpg'                             => 'bpc10',
	'2026/05/ChatGPT-Image-Jun-9-2026-02_30_14-PM.png'                               => 'ghk',
	'2026/06/01KSGMTTSWHDA9642H4GKGECDQ.webp'                                        => 'bpctb',
	'2026/06/01KSGMS4WXEXV7NHGA7AVWP5PS.webp'                                        => 'pt141',
	'2026/06/01KSGMRRFBQ0Y8KWQ4CZRPEPYR.webp'                                        => 'dsip',
);

// Every new file must exist before anything points at it.
foreach ( array_unique( array_values( $map ) ) as $k ) {
	if ( ! file_exists( WP_CONTENT_DIR . '/uploads/' . $new[ $k ] ) ) {
		WP_CLI::error( "Missing new image {$new[$k]}" );
	}
}

// Old path => (stem, extension, new path).
$pairs = array();
foreach ( $map as $old => $k ) {
	$info = pathinfo( $old );
	$stem = $u . $info['dirname'] . '/' . $info['filename'];
	$pairs[ $old ] = array( $stem, $info['extension'], $u . $new[ $k ] );
}

// Regex per old image: the stem, an optional -WxH suffix, the extension.
$rx = array();
foreach ( $pairs as $old => list( $stem, $ext, $to ) ) {
	foreach ( array( false, true ) as $escaped ) {
		$s = $escaped ? str_replace( '/', '\\/', $stem ) : $stem;
		$t = $escaped ? str_replace( '/', '\\/', $to ) : $to;
		$rx[] = array( '~' . preg_quote( $s, '~' ) . '(?:-\d+x\d+)?\.' . preg_quote( $ext, '~' ) . '~', $t, $old );
	}
}

$hits    = array();
$replace = function ( $s ) use ( $rx, &$hits ) {
	foreach ( $rx as list( $pattern, $to, $old ) ) {
		$s = preg_replace_callback( $pattern, function () use ( $to, $old, &$hits ) {
			$hits[ $old ] = ( $hits[ $old ] ?? 0 ) + 1;
			return $to;
		}, $s );
	}
	return $s;
};
$walk = function ( $v ) use ( &$walk, $replace ) {
	if ( is_string( $v ) ) {
		return $replace( $v );
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $x ) {
			$v[ $k ] = $walk( $x );
		}
	}
	return $v;
};
$transform = function ( $value ) use ( $walk, $replace ) {
	if ( is_serialized( $value ) ) {
		$un = @unserialize( $value, array( 'allowed_classes' => false ) );
		return false === $un ? $value : serialize( $walk( $un ) );
	}
	return $replace( $value );
};

global $wpdb;
$likes = array();
foreach ( array_keys( $map ) as $old ) {
	$name    = pathinfo( $old, PATHINFO_FILENAME );
	$likes[] = $wpdb->prepare( '%s', '%' . $wpdb->esc_like( $name ) . '%' );
}
$cond = function ( $col ) use ( $likes ) {
	return '(' . implode( ' OR ', array_map( function ( $l ) use ( $col ) {
		return "$col LIKE $l";
	}, $likes ) ) . ')';
};

$backup  = array( 'posts' => array(), 'postmeta' => array(), 'options' => array(), 'snippets' => array() );
$writes  = array();
$report  = array();

foreach ( $wpdb->get_results( "SELECT ID, post_type, post_content FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','attachment') AND " . $cond( 'post_content' ) ) as $r ) {
	$v = $replace( $r->post_content );
	if ( $v !== $r->post_content ) {
		$backup['posts'][ $r->ID ] = $r->post_content;
		$writes[] = array( $wpdb->posts, array( 'post_content' => $v ), array( 'ID' => $r->ID ), $r->ID );
		$report[] = "post {$r->ID} ({$r->post_type})";
	}
}
foreach ( $wpdb->get_results( "SELECT m.meta_id, m.post_id, m.meta_key, m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE p.post_type NOT IN ('revision','attachment') AND " . $cond( 'm.meta_value' ) ) as $r ) {
	$v = $transform( $r->meta_value );
	if ( $v !== $r->meta_value ) {
		$backup['postmeta'][ $r->meta_id ] = $r->meta_value;
		$writes[] = array( $wpdb->postmeta, array( 'meta_value' => $v ), array( 'meta_id' => $r->meta_id ), $r->post_id );
		$report[] = "meta {$r->post_id} {$r->meta_key}";
	}
}
foreach ( $wpdb->get_results( "SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_name NOT LIKE 'opimg\\_%' AND option_name NOT LIKE '%backup%' AND option_name NOT LIKE '\\_%transient%' AND " . $cond( 'option_value' ) ) as $r ) {
	$v = $transform( $r->option_value );
	if ( $v !== $r->option_value ) {
		$backup['options'][ $r->option_name ] = $r->option_value;
		$writes[] = array( $wpdb->options, array( 'option_value' => $v ), array( 'option_id' => $r->option_id ), 0 );
		$report[] = "option {$r->option_name}";
	}
}
foreach ( $wpdb->get_results( "SELECT id, code FROM {$wpdb->prefix}snippets WHERE " . $cond( 'code' ) ) as $r ) {
	$v = $replace( $r->code );
	if ( $v !== $r->code ) {
		try {
			token_get_all( "<?php\n" . $v, TOKEN_PARSE );
		} catch ( ParseError $e ) {
			WP_CLI::error( "Snippet {$r->id} would not parse" );
		}
		$backup['snippets'][ $r->id ] = $r->code;
		$writes[] = array( "{$wpdb->prefix}snippets", array( 'code' => $v ), array( 'id' => $r->id ), 0 );
		$report[] = "snippet {$r->id}";
	}
}

WP_CLI::log( 'rows: ' . implode( '; ', $report ) );
WP_CLI::log( 'images: ' . count( $hits ) . ' of ' . count( $map ) . ' referenced' );

if ( ! $apply ) {
	WP_CLI::success( 'Dry run OK: ' . count( $writes ) . ' rows would change.' );
	return;
}
if ( ! get_option( 'opimg_backup_20261010_content' ) ) {
	add_option( 'opimg_backup_20261010_content', $backup, '', 'no' );
}
foreach ( $writes as list( $table, $data, $where, $pid ) ) {
	$wpdb->update( $table, $data, $where );
	if ( $pid ) {
		clean_post_cache( $pid );
	}
}
wp_cache_flush();
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
WP_CLI::success( count( $writes ) . ' rows rewritten.' );
