<?php
/**
 * Schema install and version-gated upgrades.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Bump DB_VERSION with every schema change; maybe_upgrade() re-runs dbDelta once per site.
 */
final class Installer {

	public const DB_VERSION = '1';

	/**
	 * Run on activation, and on load when the stored version is behind.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( 'wbpc_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Create or update tables. Idempotent.
	 */
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = $wpdb->prefix . 'wbpc_areas';
		$charset = $wpdb->get_charset_collate();

		// dbDelta is whitespace-sensitive: two spaces after PRIMARY KEY, one definition per line.
		dbDelta(
			"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			country char(2) NOT NULL DEFAULT '',
			match_type tinyint(3) unsigned NOT NULL DEFAULT 1,
			code varchar(20) NOT NULL,
			code_to varchar(20) NOT NULL DEFAULT '',
			code_norm varchar(20) NOT NULL DEFAULT '',
			range_start bigint(20) unsigned NOT NULL DEFAULT 0,
			range_end bigint(20) unsigned NOT NULL DEFAULT 0,
			status tinyint(3) unsigned NOT NULL DEFAULT 1,
			city varchar(100) NOT NULL DEFAULT '',
			state varchar(100) NOT NULL DEFAULT '',
			days_min smallint(5) unsigned DEFAULT NULL,
			days_max smallint(5) unsigned DEFAULT NULL,
			shipping_fee decimal(12,4) DEFAULT NULL,
			cod_allowed tinyint(3) unsigned NOT NULL DEFAULT 1,
			cod_fee decimal(12,4) NOT NULL DEFAULT 0,
			note varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY area (country,match_type,code_norm,range_start),
			KEY lookup (code_norm,match_type,country),
			KEY lookup_range (match_type,range_start,range_end),
			KEY state (state),
			KEY updated (updated_at)
			) ENGINE=InnoDB {$charset};"
		);

		update_option( 'wbpc_db_version', self::DB_VERSION );
	}
}
