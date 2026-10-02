<?php
/**
 * Remove everything the plugin stored, unless the owner chose to keep it.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Kept on purpose: _wbpc_postcode / _wbpc_estimate on orders - they are the store's order history.
 */
final class Uninstaller {

	/**
	 * Run for every site of a network, or the single site.
	 */
	public static function run(): void {
		if ( is_multisite() ) {
			foreach ( get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			) as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::site();
				restore_current_blog();
			}
			return;
		}

		self::site();
	}

	/**
	 * Clean one site.
	 */
	private static function site(): void {
		global $wpdb;

		if ( 'yes' === get_option( 'wbpc_keep_data' ) ) {
			return;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Removing our own data; table name is internal.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wbpc_areas" );
		// Our transients only: "wbpc_" alone is a prefix other plugins use too.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_wbpc_rl_' ) . '%', $wpdb->esc_like( '_transient_timeout_wbpc_rl_' ) . '%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wbpc_hide_checker' ) );
		// phpcs:enable

		foreach ( array( 'wbpc_general', 'wbpc_delivery', 'wbpc_category_days', 'wbpc_checkout', 'wbpc_display', 'wbpc_db_version', 'wbpc_areas_version', 'wbpc_job', 'wbpc_job_history', 'wbpc_import_lock', 'wbpc_keep_data' ) as $option ) {
			delete_option( $option );
		}
		delete_transient( 'wbpc_activation_redirect' );

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( '', array(), 'wbpc' );
		}

		$dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'wbpc-private/';
		if ( is_dir( $dir ) ) {
			foreach ( (array) glob( $dir . '{,.}*', GLOB_BRACE ) as $file ) {
				if ( is_file( (string) $file ) ) {
					wp_delete_file( (string) $file );
				}
			}
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}

		wp_cache_flush_group( 'wbpc' );
	}
}
