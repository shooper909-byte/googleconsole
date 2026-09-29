<?php
/**
 * Plugin Name: OligoPoly Robots — Disallow WooCommerce AJAX endpoints
 * Description: Adds "Disallow: /*?wc-ajax=" to robots.txt so crawlers stop requesting
 *              WooCommerce AJAX endpoints such as ?wc-ajax=ppc-create-setup-token, which
 *              intentionally answer 400 to unauthenticated requests and therefore show up
 *              in Search Console as "Blocked due to other 4xx issue".
 * Version:     1.0.0
 * Author:      OligoPoly SEO Remediation
 *
 * Nothing about checkout, PayPal or the nonce check is touched: robots.txt is a crawler
 * directive only and is never consulted by a browser or by PayPal's servers.
 *
 * Use this file as a plugin (wp-content/plugins/) or as a must-use plugin
 * (wp-content/mu-plugins/). To run it from WPCode or Code Snippets instead, paste
 * everything below the `defined( 'ABSPATH' )` line as a PHP snippet set to "Run everywhere".
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'robots_txt',
	function ( $output ) {
		$rule = 'Disallow: /*?wc-ajax=';

		// Idempotent: never add the rule twice, whoever else is filtering robots.txt.
		if ( false !== strpos( (string) $output, $rule ) ) {
			return $output;
		}

		$lines     = preg_split( '/\R/', (string) $output );
		$insert_at = null;

		// Preferred anchor: straight after the existing query-string rule, so the new rule
		// sits inside the same "User-agent: *" group and next to its siblings.
		foreach ( $lines as $i => $line ) {
			if ( preg_match( '#^\s*Disallow:\s*/\*\?add-to-cart=#i', $line ) ) {
				$insert_at = $i + 1;
				break;
			}
		}

		// Fallback: after the last Disallow that appears before any Sitemap line.
		if ( null === $insert_at ) {
			foreach ( $lines as $i => $line ) {
				if ( preg_match( '#^\s*Sitemap:#i', $line ) ) {
					break;
				}
				if ( preg_match( '#^\s*Disallow:#i', $line ) ) {
					$insert_at = $i + 1;
				}
			}
		}

		if ( null === $insert_at ) {
			return rtrim( (string) $output, "\r\n" ) . "\nUser-agent: *\n" . $rule . "\n";
		}

		array_splice( $lines, $insert_at, 0, $rule );

		return implode( "\n", $lines );
	},
	PHP_INT_MAX - 10 // Run after Rank Math and after the existing SEO remediation plugin.
);
