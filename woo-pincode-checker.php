<?php
/**
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wbcomdesigns.com/plugins
 * @since             1.0.0
 * @package           Woo_Pincode_Checker
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Pincode Checker
 * Plugin URI:        https://wbcomdesigns.com
 * Description:       Allows the site admin to add the pincode availability feature on their sites with woo pincode checker plugin.
 * Version:           1.1.0
 * Author:            wbcomdesigns
 * Author URI:        https://wbcomdesigns.com/plugins
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-pincode-checker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOO_PINCODE_CHECKER_VERSION', '1.1.0' );
define( 'WOO_PINCODE_CHECKER_PLUGIN_FILE', __FILE__ );

/**
 * Include needed files if required plugin is active
 *
 * @since   1.0.0
 * @author  Wbcom Designs
 */
add_action( 'plugins_loaded', 'wpc_plugins_files' );

/**
 * WCMP plugin requires files.
 */
function wpc_plugins_files() {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}
	
	if ( ! class_exists( 'WooCommerce', false ) ) {
		add_action( 'admin_notices', 'wpc_admin_notice' );
	} else {
		register_activation_hook( __FILE__, 'activate_woo_pincode_checker' );
	}
}

/**
 * Give the notice if plugin is not activated.
 */
function wpc_admin_notice() {
	$woo_plugin  = esc_html__( 'WooCommerce', 'woo-pincode-checker' );
	$wcmp_plugin = esc_html__( 'Woo Pincode Checker', 'woo-pincode-checker' );

	/* translators: %1$s: WooCommerce plugin, %2$s: WooCommerce Custom My Account Page plugin */
	echo '<div class="error notice is-dismissible" id="message"><p>' . sprintf( esc_html__( '%1$s requires %2$s to be installed and active.', 'woo-pincode-checker' ), '<strong>' . esc_attr( $wcmp_plugin ) . '</strong>', '<strong>' . esc_attr( $woo_plugin ) . '</strong>' ) . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' .
	esc_html__( 'Dismiss this notice.', 'woo-pincode-checker' ) . '</span></button></div>';
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-pincode-checker-activator.php
 */
function activate_woo_pincode_checker() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-pincode-checker-activator.php';
	Woo_Pincode_Checker_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-pincode-checker-deactivator.php
 */
function deactivate_woo_pincode_checker() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-pincode-checker-deactivator.php';
	Woo_Pincode_Checker_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woo_pincode_checker' );
register_deactivation_hook( __FILE__, 'deactivate_woo_pincode_checker' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woo-pincode-checker.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_pincode_checker() {

	$plugin = new Woo_Pincode_Checker();
	$plugin->run();

}
run_woo_pincode_checker();
