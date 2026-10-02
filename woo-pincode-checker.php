<?php
/**
 * Plugin Name:          Pincode Checker for WooCommerce
 * Plugin URI:           https://wbcomdesigns.com/downloads/woo-pincode-checker/
 * Description:          Tell shoppers whether you deliver to their pincode or postcode, when it will arrive, what shipping costs and whether cash on delivery is available - on product pages and at checkout.
 * Version:              1.6.0
 * Requires at least:    6.5
 * Requires PHP:         8.1
 * Requires Plugins:     woocommerce
 * Author:               Wbcom Designs
 * Author URI:           https://wbcomdesigns.com/
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          woo-pincode-checker
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.1
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker;

defined( 'ABSPATH' ) || exit;

define( 'WBPC_VERSION', '1.6.0' );
define( 'WBPC_FILE', __FILE__ );
define( 'WBPC_DIR', plugin_dir_path( __FILE__ ) );
define( 'WBPC_URL', plugin_dir_url( __FILE__ ) );

// PSR-4: Wbcom\PincodeChecker\Admin\SettingsPage -> includes/Admin/SettingsPage.php.
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( str_starts_with( $class_name, $prefix ) ) {
			$file = WBPC_DIR . 'includes/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';

			if ( is_readable( $file ) ) {
				require $file;
			}
		}
	}
);

// Shared Wbcom settings shell: the newest bundled copy across active Wbcom plugins is the one loaded.
if ( file_exists( WBPC_DIR . 'lib/wbcom-settings/loader.php' ) ) {
	require_once WBPC_DIR . 'lib/wbcom-settings/loader.php';
	wbcom_settings_register( '1.0.4', WBPC_DIR . 'lib/wbcom-settings/class-wbcom-settings-page.php' );
}

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WBPC_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WBPC_FILE, true );
		}
	}
);

register_activation_hook( __FILE__, array( Core\Plugin::class, 'activate' ) );
add_action( 'plugins_loaded', array( Core\Plugin::class, 'boot' ) );
