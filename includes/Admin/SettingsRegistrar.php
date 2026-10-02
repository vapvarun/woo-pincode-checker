<?php
/**
 * Registers every settings group, from the schema.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom\PincodeChecker\Services\SettingsService;

defined( 'ABSPATH' ) || exit;

/**
 * One option + option group per tab. Saving via options.php checks our capability, not manage_options.
 */
final class SettingsRegistrar {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register each group.
	 */
	public function register_settings(): void {
		foreach ( SettingsService::GROUPS as $group ) {
			$option = SettingsService::option( $group );

			register_setting(
				$option,
				$option,
				array(
					'type'              => 'array',
					'sanitize_callback' => static fn( $input ) => Plugin::settings()->sanitize( $group, $input ),
					'show_in_rest'      => false,
				)
			);

			// options.php asks manage_options by default; Shop Managers manage this plugin.
			add_filter( 'option_page_capability_' . $option, array( Plugin::class, 'cap' ) );
		}
	}
}
