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
