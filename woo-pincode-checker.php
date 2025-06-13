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
 * Plugin URI:        https://wbcomdesigns.com/downloads/woo-pincode-checker/
 * Description:       Woo Pincode Checker enables store owners to show product availability, delivery timelines, and COD options based on the customer's entered pincode.
 * Version:           1.3.4
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
define( 'WOO_PINCODE_CHECKER_VERSION', '1.3.4' );
define( 'WOO_PINCODE_CHECKER_PLUGIN_FILE', __FILE__ );

define( 'WPCP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include needed files if required plugin is active
 *
 * @since   1.0.0
 * @author  Wbcom Designs
 */
add_action( 'admin_init', 'wpc_plugins_files' );

/**
 * WCMP plugin requires files.
 */
function wpc_plugins_files() {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}
	if ( ! class_exists( 'WooCommerce', false ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
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

/**
 * Redirect to plugin settings page after activated.
 */
function woo_pincode_checker_activation_redirect_settings( $plugin ) {

	if ( $plugin == plugin_basename( __FILE__ ) ) {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action']  == 'activate' && isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] == $plugin) { //phpcs:ignore
			wp_redirect( admin_url( 'admin.php?page=woo-pincode-checker' ) );
			exit;
		}
	}
}
if ( class_exists( 'WooCommerce' ) ) {
	add_action( 'activated_plugin', 'woo_pincode_checker_activation_redirect_settings' );
}

/**
 * Function checks the update Mysql table - SECURE VERSION.
 *
 * @return void
 */
function wpc_check_update_mysql_db() {
	global $wpdb;
	$installed_ver = get_option( 'wpc_db_version' );
	$current_version = '1.3';
	
	if ( ! empty( $installed_ver ) && version_compare( $installed_ver, $current_version, '<' ) ) {
		$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
		
		// Check if columns already exist before adding
		$shipping_column_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM `{$pincode_checker_table_name}` LIKE %s",
			'shipping_amount'
		));
		
		$cod_column_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM `{$pincode_checker_table_name}` LIKE %s", 
			'cod_amount'
		));
		
		$queries = array();
		
		if ( empty( $shipping_column_exists ) ) {
			$queries[] = "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `delivery_days`";
		}
		
		if ( empty( $cod_column_exists ) ) {
			$queries[] = "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `cod_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `case_on_delivery`";
		}
		
		// Execute queries if needed
		foreach ( $queries as $query ) {
			$result = $wpdb->query( $query );
			if ( false === $result ) {
				// Log error but don't break execution
				error_log( 'WPC Database Update Error: ' . $wpdb->last_error );
			}
		}
		
		update_option( 'wpc_db_version', $current_version );
	}
}
add_action( 'plugins_loaded', 'wpc_check_update_mysql_db' );

/**
 * Enhanced security and monitoring functions
 */

/**
 * Log security events for monitoring
 *
 * @param string $event Event type
 * @param array $data Event data
 */
function wpc_log_security_event( $event, $data = array() ) {
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		$log_data = array(
			'timestamp' => current_time( 'mysql' ),
			'event' => $event,
			'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
			'data' => $data
		);
		error_log( '[WPC Security] ' . wp_json_encode( $log_data ) );
	}
}

/**
 * Check for suspicious activity
 */
function wpc_security_check() {
	// Monitor for potential SQL injection attempts
	$suspicious_patterns = array(
		'/union\s+select/i',
		'/drop\s+table/i',
		'/insert\s+into/i',
		'/delete\s+from/i',
		'/<script/i',
		'/javascript:/i'
	);
	
	$request_data = array_merge( $_GET, $_POST );
	
	foreach ( $request_data as $key => $value ) {
		if ( is_string( $value ) ) {
			foreach ( $suspicious_patterns as $pattern ) {
				if ( preg_match( $pattern, $value ) ) {
					wpc_log_security_event( 'suspicious_input', array(
						'field' => $key,
						'value' => substr( $value, 0, 100 ), // Log first 100 chars only
						'pattern' => $pattern
					));
					break;
				}
			}
		}
	}
}

// Only run security checks if WPC_DEBUG is enabled
if ( defined( 'WPC_DEBUG' ) && WPC_DEBUG ) {
	add_action( 'init', 'wpc_security_check' );
}

/**
 * Enhanced error handling for database operations
 */
function wpc_handle_db_error( $wpdb_error, $context = '' ) {
	if ( ! empty( $wpdb_error ) ) {
		error_log( "WPC Database Error [{$context}]: " . $wpdb_error );
		
		// In production, show generic error to users
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return __( 'A database error occurred. Please try again later.', 'woo-pincode-checker' );
		}
		
		return $wpdb_error;
	}
	
	return false;
}

/**
 * Cache management functions
 */
function wpc_clear_pincode_cache( $pincode = null ) {
	if ( $pincode ) {
		wp_cache_delete( 'wpc_pincode_' . md5( $pincode ), 'woo_pincode_checker' );
	} else {
		wp_cache_flush_group( 'woo_pincode_checker' );
	}
}

/**
 * Backup important data before major operations
 */
function wpc_backup_pincode_data() {
	global $wpdb;
	
	$backup_data = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}pincode_checker ORDER BY id",
		ARRAY_A
	);
	
	if ( $backup_data ) {
		$backup_file = WP_CONTENT_DIR . '/wpc-backup-' . date( 'Y-m-d-H-i-s' ) . '.json';
		file_put_contents( $backup_file, wp_json_encode( $backup_data ) );
		
		// Store backup location in options for recovery
		update_option( 'wpc_last_backup', $backup_file );
		
		return $backup_file;
	}
	
	return false;
}

/**
 * Performance monitoring
 */
function wpc_monitor_performance() {
	if ( defined( 'WPC_DEBUG' ) && WPC_DEBUG ) {
		add_action( 'shutdown', function() {
			global $wpdb;
			
			$query_count = $wpdb->num_queries;
			$memory_usage = memory_get_peak_usage( true );
			$execution_time = microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'];
			
			if ( $query_count > 50 || $memory_usage > 50 * 1024 * 1024 || $execution_time > 5 ) {
				error_log( sprintf(
					'[WPC Performance] High resource usage detected - Queries: %d, Memory: %s, Time: %.2fs',
					$query_count,
					size_format( $memory_usage ),
					$execution_time
				));
			}
		});
	}
}
add_action( 'init', 'wpc_monitor_performance' );

/**
 * Data validation helper
 */
function wpc_validate_data( $data, $rules ) {
	$errors = array();
	
	foreach ( $rules as $field => $rule ) {
		$value = $data[ $field ] ?? null;
		
		// Required check
		if ( isset( $rule['required'] ) && $rule['required'] && empty( $value ) ) {
			$errors[ $field ] = sprintf( __( '%s is required.', 'woo-pincode-checker' ), $rule['label'] ?? $field );
			continue;
		}
		
		// Type validation
		if ( ! empty( $value ) && isset( $rule['type'] ) ) {
			switch ( $rule['type'] ) {
				case 'pincode':
					if ( ! preg_match( '/^[A-Za-z0-9](?:[A-Za-z0-9\s]*[A-Za-z0-9])?$/', $value ) ) {
						$errors[ $field ] = sprintf( __( '%s format is invalid.', 'woo-pincode-checker' ), $rule['label'] ?? $field );
					}
					break;
				case 'positive_number':
					if ( ! is_numeric( $value ) || $value < 0 ) {
						$errors[ $field ] = sprintf( __( '%s must be a positive number.', 'woo-pincode-checker' ), $rule['label'] ?? $field );
					}
					break;
				case 'range':
					if ( isset( $rule['min'] ) && $value < $rule['min'] ) {
						$errors[ $field ] = sprintf( __( '%s must be at least %d.', 'woo-pincode-checker' ), $rule['label'] ?? $field, $rule['min'] );
					}
					if ( isset( $rule['max'] ) && $value > $rule['max'] ) {
						$errors[ $field ] = sprintf( __( '%s must not exceed %d.', 'woo-pincode-checker' ), $rule['label'] ?? $field, $rule['max'] );
					}
					break;
			}
		}
	}
	
	return $errors;
}

/**
 * AJAX rate limiting helper
 */
function wpc_check_ajax_rate_limit( $action, $limit = 10, $window = 60 ) {
	$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	$transient_key = 'wpc_rate_limit_' . md5( $user_ip . $action );
	$requests = get_transient( $transient_key );
	
	if ( $requests && $requests > $limit ) {
		wpc_log_security_event( 'rate_limit_exceeded', array(
			'action' => $action,
			'requests' => $requests,
			'limit' => $limit
		));
		return false;
	}
	
	set_transient( $transient_key, ( $requests ? $requests + 1 : 1 ), $window );
	return true;
}

/**
 * Hook to clean up old rate limiting transients
 */
function wpc_cleanup_old_transients() {
	global $wpdb;
	
	// Clean up rate limiting transients older than 1 hour
	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->options} 
		 WHERE option_name LIKE %s 
		 AND option_name LIKE %s 
		 AND UNIX_TIMESTAMP(NOW()) - SUBSTRING(option_value, LENGTH(option_value) - 9) > 3600",
		'_transient_timeout_wpc_rate_limit_%',
		'_transient_wpc_rate_limit_%'
	));
}

// Schedule cleanup twice daily
if ( ! wp_next_scheduled( 'wpc_cleanup_transients' ) ) {
	wp_schedule_event( time(), 'twicedaily', 'wpc_cleanup_transients' );
}
add_action( 'wpc_cleanup_transients', 'wpc_cleanup_old_transients' );

/**
 * Security headers (basic implementation)
 */
function wpc_add_security_headers() {
	if ( ! headers_sent() ) {
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-XSS-Protection: 1; mode=block' );
	}
}
add_action( 'send_headers', 'wpc_add_security_headers' );

/**
 * Plugin health check
 */
function wpc_health_check() {
	$health_status = array(
		'database' => false,
		'cache' => false,
		'permissions' => false
	);
	
	// Check database connection
	global $wpdb;
	$test_query = $wpdb->get_var( "SELECT 1" );
	$health_status['database'] = ( $test_query == 1 );
	
	// Check cache functionality
	wp_cache_set( 'wpc_health_test', 'test_value', 'woo_pincode_checker', 60 );
	$cache_test = wp_cache_get( 'wpc_health_test', 'woo_pincode_checker' );
	$health_status['cache'] = ( $cache_test === 'test_value' );
	
	// Check file permissions
	$health_status['permissions'] = is_writable( WP_CONTENT_DIR );
	
	update_option( 'wpc_health_status', $health_status );
	
	return $health_status;
}

// Run health check on plugin activation and daily
register_activation_hook( __FILE__, 'wpc_health_check' );
if ( ! wp_next_scheduled( 'wpc_daily_health_check' ) ) {
	wp_schedule_event( time(), 'daily', 'wpc_daily_health_check' );
}
add_action( 'wpc_daily_health_check', 'wpc_health_check' );