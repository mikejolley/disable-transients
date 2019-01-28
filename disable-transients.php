<?php
/**
 * Plugin Name: Disable Transients
 * Plugin URI: https://mikejolley.com
 * Description: Bypasses WP transient caching. Do not use unless you understand what this is doing! I'm using it to test page performance without transients affecting the raw results.
 * Version: 1.0.0
 * Author: Mike Jolley
 * Author URI: https://mikejolley.com
 *
 * @package disable-transients
 */

defined( 'ABSPATH' ) || exit;

/**
 * No mercy.
 *
 * @param string $transient Transient name being set.
 */
function setted_transient_nope( $transient ) {
	delete_transient( $transient );
}

add_action( 'setted_transient', 'setted_transient_nope', 20 );

/**
 * Destroy existing transients on activation.
 */
function destroy_all_transients() {
	global $wpdb;

	$wpdb->query( // phpcs:ignore
		$wpdb->prepare(
			"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )",
			$wpdb->esc_like( '_transient_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_' ) . '%'
		)
	);

	if ( ! is_multisite() ) {
		// non-Multisite stores site transients in the options table.
		$wpdb->query( // phpcs:ignore
			$wpdb->prepare(
				"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )",
				$wpdb->esc_like( '_site_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%'
			)
		);
	} elseif ( is_multisite() && is_main_site() && is_main_network() ) {
		// Multisite stores site transients in the sitemeta table.
		$wpdb->query( // phpcs:ignore
			$wpdb->prepare(
				"DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
				WHERE a.meta_key LIKE %s
				AND a.meta_key NOT LIKE %s
				AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )",
				$wpdb->esc_like( '_site_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%'
			)
		);
	}
}

register_activation_hook( __FILE__, 'destroy_all_transients' );
