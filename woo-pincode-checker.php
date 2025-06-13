<?php
/**
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
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 * WC requires at least: 3.0
 * WC tested up to:   8.0
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
 * Check if WooCommerce is active and load plugin files
 *
 * @since   1.0.0
 * @author  Wbcom Designs
 */
add_action( 'plugins_loaded', 'wpc_check_woocommerce_and_load_plugin' );

/**
 * Check WooCommerce dependency and load plugin
 */
function wpc_check_woocommerce_and_load_plugin() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	
	// Check if WooCommerce is active
	if ( ! class_exists( 'WooCommerce', false ) ) {
		add_action( 'admin_notices', 'wpc_woocommerce_missing_notice' );
		return;
	}
	
	// Check PHP version
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		add_action( 'admin_notices', 'wpc_php_version_notice' );
		return;
	}
	
	// Check WordPress version
	if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
		add_action( 'admin_notices', 'wpc_wordpress_version_notice' );
		return;
	}
	
	// All checks passed, load the plugin
	wpc_load_plugin_files();
}

/**
 * Load plugin files and initialize
 */
function wpc_load_plugin_files() {
	// Load required files
	require_once WPCP_PLUGIN_PATH . 'includes/class-woo-pincode-checker-activator.php';
	require_once WPCP_PLUGIN_PATH . 'includes/class-woo-pincode-checker-deactivator.php';
	require_once WPCP_PLUGIN_PATH . 'includes/class-woo-pincode-checker.php';
	
	// Register activation and deactivation hooks
	register_activation_hook( __FILE__, 'activate_woo_pincode_checker' );
	register_deactivation_hook( __FILE__, 'deactivate_woo_pincode_checker' );
	
	// Initialize the plugin
	add_action( 'init', 'run_woo_pincode_checker' );
	
	// Add activation redirect
	if ( class_exists( 'WooCommerce' ) ) {
		add_action( 'activated_plugin', 'woo_pincode_checker_activation_redirect_settings' );
	}
}

/**
 * WooCommerce missing notice
 */
function wpc_woocommerce_missing_notice() {
	$woo_plugin = esc_html__( 'WooCommerce', 'woo-pincode-checker' );
	$wpc_plugin = esc_html__( 'Woo Pincode Checker', 'woo-pincode-checker' );

	echo '<div class="notice notice-error is-dismissible">';
	echo '<p><strong>' . esc_html__( 'Plugin Activation Error:', 'woo-pincode-checker' ) . '</strong> ';
	echo sprintf( 
		esc_html__( '%1$s requires %2$s to be installed and activated before it can function properly.', 'woo-pincode-checker' ), 
		'<em>' . esc_html( $wpc_plugin ) . '</em>', 
		'<em>' . esc_html( $woo_plugin ) . '</em>' 
	);
	echo '</p>';
	
	if ( ! class_exists( 'WooCommerce' ) ) {
		$install_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'install-plugin',
					'plugin' => 'woocommerce',
				),
				admin_url( 'update.php' )
			),
			'install-plugin_woocommerce'
		);
		
		echo '<p>';
		echo '<a href="' . esc_url( $install_url ) . '" class="button button-primary">' . 
			 esc_html__( 'Install WooCommerce', 'woo-pincode-checker' ) . '</a>';
		echo '</p>';
	}
	
	echo '</div>';
}

/**
 * PHP version notice
 */
function wpc_php_version_notice() {
	echo '<div class="notice notice-error is-dismissible">';
	echo '<p><strong>' . esc_html__( 'PHP Version Error:', 'woo-pincode-checker' ) . '</strong> ';
	echo sprintf( 
		esc_html__( 'Woo Pincode Checker requires PHP version 7.4 or higher. You are running PHP %s.', 'woo-pincode-checker' ), 
		PHP_VERSION 
	);
	echo '</p>';
	echo '</div>';
}

/**
 * WordPress version notice
 */
function wpc_wordpress_version_notice() {
	echo '<div class="notice notice-error is-dismissible">';
	echo '<p><strong>' . esc_html__( 'WordPress Version Error:', 'woo-pincode-checker' ) . '</strong> ';
	echo sprintf( 
		esc_html__( 'Woo Pincode Checker requires WordPress version 5.0 or higher. You are running WordPress %s.', 'woo-pincode-checker' ), 
		get_bloginfo( 'version' ) 
	);
	echo '</p>';
	echo '</div>';
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-pincode-checker-activator.php
 */
function activate_woo_pincode_checker() {
	if ( ! class_exists( 'Woo_Pincode_Checker_Activator' ) ) {
		require_once WPCP_PLUGIN_PATH . 'includes/class-woo-pincode-checker-activator.php';
	}
	Woo_Pincode_Checker_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-pincode-checker-deactivator.php
 */
function deactivate_woo_pincode_checker() {
	if ( ! class_exists( 'Woo_Pincode_Checker_Deactivator' ) ) {
		require_once WPCP_PLUGIN_PATH . 'includes/class-woo-pincode-checker-deactivator.php';
	}
	Woo_Pincode_Checker_Deactivator::deactivate();
}

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
	if ( ! class_exists( 'Woo_Pincode_Checker' ) ) {
		return;
	}
	
	$plugin = new Woo_Pincode_Checker();
	$plugin->run();
}

/**
 * Redirect to plugin settings page after activation.
 */
function woo_pincode_checker_activation_redirect_settings( $plugin ) {
	if ( $plugin === plugin_basename( __FILE__ ) ) {
		$action = filter_input( INPUT_REQUEST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$plugin_param = filter_input( INPUT_REQUEST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		
		if ( 'activate' === $action && plugin_basename( __FILE__ ) === $plugin_param ) {
			wp_safe_redirect( admin_url( 'admin.php?page=woo-pincode-checker' ) );
			exit;
		}
	}
}

/**
 * Enhanced database table update check with security improvements
 *
 * @return void
 */
function wpc_check_update_mysql_db() {
	global $wpdb;
	$installed_ver = get_option( 'wpc_db_version' );
	$current_version = '1.3.4';
	
	if ( ! empty( $installed_ver ) && version_compare( $installed_ver, $current_version, '<' ) ) {
		$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$pincode_checker_table_name 
		) ) === $pincode_checker_table_name;
		
		if ( ! $table_exists ) {
			// Table doesn't exist, run full activation
			activate_woo_pincode_checker();
			return;
		}
		
		// Check and add missing columns
		$columns_to_add = array(
			'shipping_amount' => "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `shipping_amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 AFTER `delivery_days`",
			'cod_amount' => "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `cod_amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 AFTER `case_on_delivery`",
			'created_at' => "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `cod_amount`",
			'updated_at' => "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
			'created_by' => "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `created_by` INT UNSIGNED DEFAULT NULL AFTER `updated_at`",
			'status' => "ALTER TABLE `{$pincode_checker_table_name}` ADD COLUMN `status` ENUM('active', 'inactive', 'deleted') DEFAULT 'active' AFTER `created_by`"
		);
		
		foreach ( $columns_to_add as $column => $query ) {
			$column_exists = $wpdb->get_results( $wpdb->prepare(
				"SHOW COLUMNS FROM `{$pincode_checker_table_name}` LIKE %s",
				$column
			));
			
			if ( empty( $column_exists ) ) {
				$result = $wpdb->query( $query );
				if ( false === $result ) {
					error_log( "WPC Database Update Error for column {$column}: " . $wpdb->last_error );
				} else {
					error_log( "WPC: Successfully added column {$column}" );
				}
			}
		}
		
		// Add/update indexes for better performance
		$indexes_to_add = array(
			'idx_pincode_status' => "CREATE INDEX `idx_pincode_status` ON `{$pincode_checker_table_name}` (`pincode`, `status`)",
			'idx_location_lookup' => "CREATE INDEX `idx_location_lookup` ON `{$pincode_checker_table_name}` (`city`(20), `state`(20))",
			'idx_delivery_active' => "CREATE INDEX `idx_delivery_active` ON `{$pincode_checker_table_name}` (`delivery_days`, `status`)",
			'idx_created_by' => "CREATE INDEX `idx_created_by` ON `{$pincode_checker_table_name}` (`created_by`)"
		);
		
		foreach ( $indexes_to_add as $index_name => $query ) {
			// Check if index exists
			$index_exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
				 WHERE table_schema = DATABASE() 
				 AND table_name = %s 
				 AND index_name = %s",
				$pincode_checker_table_name,
				$index_name
			));
			
			if ( ! $index_exists ) {
				$result = $wpdb->query( $query );
				if ( false === $result ) {
					error_log( "WPC Index Creation Error for {$index_name}: " . $wpdb->last_error );
				}
			}
		}
		
		// Update existing records to have active status if status column was added
		$wpdb->query( "UPDATE `{$pincode_checker_table_name}` SET `status` = 'active' WHERE `status` IS NULL OR `status` = ''" );
		
		update_option( 'wpc_db_version', $current_version );
		
		// Clear any cached data
		wp_cache_flush_group( 'woo_pincode_checker' );
		
		error_log( 'WPC: Database update completed to version ' . $current_version );
	}
}
add_action( 'plugins_loaded', 'wpc_check_update_mysql_db', 15 );

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
			'user_id' => get_current_user_id(),
			'data' => $data
		);
		error_log( '[WPC Security] ' . wp_json_encode( $log_data ) );
	}
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
 * Data validation helper
 */
function wpc_validate_data( $data, $rules ) {
	$errors = array();
	
	foreach ( $rules as $field => $rule ) {
		$value = $data[ $field ] ?? null;
		$label = $rule['label'] ?? $field;
		
		// Required check
		if ( isset( $rule['required'] ) && $rule['required'] && empty( $value ) ) {
			$errors[ $field ] = sprintf( __( '%s is required.', 'woo-pincode-checker' ), $label );
			continue;
		}
		
		// Type validation
		if ( ! empty( $value ) && isset( $rule['type'] ) ) {
			switch ( $rule['type'] ) {
				case 'pincode':
					if ( ! preg_match( '/^[A-Za-z0-9](?:[A-Za-z0-9\s]*[A-Za-z0-9])?$/', $value ) ) {
						$errors[ $field ] = sprintf( __( '%s format is invalid.', 'woo-pincode-checker' ), $label );
					}
					break;
				case 'positive_number':
					if ( ! is_numeric( $value ) || $value < 0 ) {
						$errors[ $field ] = sprintf( __( '%s must be a positive number.', 'woo-pincode-checker' ), $label );
					}
					break;
				case 'range':
					if ( isset( $rule['min'] ) && $value < $rule['min'] ) {
						$errors[ $field ] = sprintf( __( '%s must be at least %d.', 'woo-pincode-checker' ), $label, $rule['min'] );
					}
					if ( isset( $rule['max'] ) && $value > $rule['max'] ) {
						$errors[ $field ] = sprintf( __( '%s must not exceed %d.', 'woo-pincode-checker' ), $label, $rule['max'] );
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
 * Cleanup functions
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

/**
 * Handle plugin deactivation cleanup
 */
function wpc_handle_plugin_deactivation() {
	// Clear scheduled events
	wp_clear_scheduled_hook( 'wpc_cleanup_transients' );
	wp_clear_scheduled_hook( 'wpc_daily_health_check' );
	
	// Clear cache
	wp_cache_flush_group( 'woo_pincode_checker' );
	
	// Log deactivation
	wpc_log_security_event( 'plugin_deactivated', array(
		'user_id' => get_current_user_id(),
		'timestamp' => current_time( 'mysql' )
	));
}
register_deactivation_hook( __FILE__, 'wpc_handle_plugin_deactivation' );

/**
 * Add plugin action links
 */
function wpc_add_plugin_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=woo-pincode-checker' ) . '">' . __( 'Settings', 'woo-pincode-checker' ) . '</a>',
		'<a href="' . admin_url( 'admin.php?page=pincode_lists' ) . '">' . __( 'Manage Codes', 'woo-pincode-checker' ) . '</a>',
	);
	
	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpc_add_plugin_action_links' );

/**
 * Add plugin meta links
 */
function wpc_add_plugin_meta_links( $links, $file ) {
	if ( $file === plugin_basename( __FILE__ ) ) {
		$meta_links = array(
			'<a href="https://docs.wbcomdesigns.com/doc_category/woo-pincode-checker/" target="_blank">' . __( 'Documentation', 'woo-pincode-checker' ) . '</a>',
			'<a href="https://wbcomdesigns.com/support/" target="_blank">' . __( 'Support', 'woo-pincode-checker' ) . '</a>',
		);
		
		return array_merge( $links, $meta_links );
	}
	
	return $links;
}
add_filter( 'plugin_row_meta', 'wpc_add_plugin_meta_links', 10, 2 );