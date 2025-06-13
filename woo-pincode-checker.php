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
 * Enhanced activation function with better error handling
 */
function activate_woo_pincode_checker() {
	if ( ! class_exists( 'Woo_Pincode_Checker_Activator' ) ) {
		require_once WPCP_PLUGIN_PATH . 'includes/class-woo-pincode-checker-activator.php';
	}
	
	// Run activation
	$activation_result = Woo_Pincode_Checker_Activator::activate();
	
	// Check if there were any errors
	$activation_error = get_option( 'wpc_activation_error' );
	if ( $activation_error ) {
		// Store error for display in admin
		add_action( 'admin_notices', function() use ( $activation_error ) {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p><strong>Woo Pincode Checker Activation Error:</strong> ' . esc_html( $activation_error ) . '</p>';
			echo '<p>Please check with your hosting provider about database permissions or try deactivating and reactivating the plugin.</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ) . '" class="button">Try Manual Fix</a></p>';
			echo '</div>';
		});
		
		error_log( 'WPC Activation Error: ' . $activation_error );
	}
	
	if ( ! $activation_result ) {
		error_log( 'WPC: Activation completed with issues' );
	}
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
 * Check and create table if missing during runtime
 */
function wpc_check_and_create_table() {
	// Only run in admin area
	if ( ! is_admin() ) {
		return;
	}
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'pincode_checker';
	
	// Check if table exists
	$table_exists = $wpdb->get_var( $wpdb->prepare( 
		"SHOW TABLES LIKE %s", 
		$table_name 
	) ) == $table_name;
	
	if ( ! $table_exists ) {
		error_log( 'WPC: Table missing during runtime, attempting to create...' );
		
		// Try to create the table
		activate_woo_pincode_checker();
		
		// Check again
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		if ( ! $table_exists ) {
			// Show admin notice only once per session
			if ( ! get_transient( 'wpc_table_error_shown' ) ) {
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-error is-dismissible">';
					echo '<p><strong>Woo Pincode Checker:</strong> Database table could not be created automatically.</p>';
					echo '<p>Please contact your hosting provider to ensure your WordPress database user has CREATE TABLE permissions.</p>';
					echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ) . '" class="button button-primary">Try Manual Fix</a> ';
					echo '<a href="#" onclick="location.reload();" class="button">Retry</a></p>';
					echo '</div>';
				});
				
				// Set transient to prevent repeated notices
				set_transient( 'wpc_table_error_shown', true, 12 * HOUR_IN_SECONDS );
			}
		} else {
			// Clear any error transients if table was created
			delete_transient( 'wpc_table_error_shown' );
		}
	}
}

// Add table check on admin_init with priority 5 to run early
add_action( 'admin_init', 'wpc_check_and_create_table', 5 );

/**
 * Enhanced database update check with table existence verification
 */
function wpc_check_update_mysql_db() {
	global $wpdb;
	
	$installed_ver = get_option( 'wpc_db_version' );
	$current_version = '1.3.4';
	
	// Always check if table exists first
	$table_name = $wpdb->prefix . 'pincode_checker';
	$table_exists = $wpdb->get_var( $wpdb->prepare( 
		"SHOW TABLES LIKE %s", 
		$table_name 
	) ) == $table_name;
	
	if ( ! $table_exists ) {
		error_log( 'WPC: Table missing during update check, running activation...' );
		activate_woo_pincode_checker();
		return;
	}
	
	// Check for version updates
	if ( ! empty( $installed_ver ) && version_compare( $installed_ver, $current_version, '<' ) ) {
		error_log( 'WPC: Updating database from version ' . $installed_ver . ' to ' . $current_version );
		
		// Update table structure if needed
		wpc_update_table_structure( $table_name );
		
		// Update version
		update_option( 'wpc_db_version', $current_version );
		
		// Clear cache
		wp_cache_flush_group( 'woo_pincode_checker' );
		
		error_log( 'WPC: Database update completed to version ' . $current_version );
	}
}

/**
 * Update table structure for existing installations
 */
function wpc_update_table_structure( $table_name ) {
	global $wpdb;
	
	$columns_to_add = array(
		'shipping_amount' => "ALTER TABLE `{$table_name}` ADD COLUMN `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_days`",
		'cod_amount' => "ALTER TABLE `{$table_name}` ADD COLUMN `cod_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `case_on_delivery`",
		'created_at' => "ALTER TABLE `{$table_name}` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `cod_amount`",
		'updated_at' => "ALTER TABLE `{$table_name}` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`"
	);
	
	foreach ( $columns_to_add as $column => $query ) {
		$column_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM `{$table_name}` LIKE %s",
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
	
	// Ensure indexes exist
	wpc_ensure_table_indexes( $table_name );
}

/**
 * Ensure all required indexes exist
 */
function wpc_ensure_table_indexes( $table_name ) {
	global $wpdb;
	
	$indexes_to_add = array(
		'unique_pincode' => "ALTER TABLE `{$table_name}` ADD UNIQUE KEY `unique_pincode` (`pincode`)",
		'idx_pincode_search' => "ALTER TABLE `{$table_name}` ADD INDEX `idx_pincode_search` (`pincode`)",
		'idx_city_state' => "ALTER TABLE `{$table_name}` ADD INDEX `idx_city_state` (`city`, `state`)",
		'idx_delivery_days' => "ALTER TABLE `{$table_name}` ADD INDEX `idx_delivery_days` (`delivery_days`)"
	);
	
	// Get existing indexes
	$existing_indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table_name}`" );
	$existing_index_names = array();
	
	foreach ( $existing_indexes as $index ) {
		$existing_index_names[] = $index->Key_name;
	}
	
	foreach ( $indexes_to_add as $index_name => $query ) {
		if ( ! in_array( $index_name, $existing_index_names ) ) {
			$result = $wpdb->query( $query );
			if ( false === $result ) {
				error_log( "WPC: Failed to add index {$index_name}: " . $wpdb->last_error );
			}
		}
	}
}

// Run database check on plugins_loaded with high priority
add_action( 'plugins_loaded', 'wpc_check_update_mysql_db', 15 );

/**
 * Safe database query wrapper that checks table existence
 */
function wpc_safe_db_query( $query, $params = array(), $output_type = OBJECT ) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'pincode_checker';
	
	// Check if table exists before running query
	$table_exists = $wpdb->get_var( $wpdb->prepare( 
		"SHOW TABLES LIKE %s", 
		$table_name 
	) ) == $table_name;
	
	if ( ! $table_exists ) {
		error_log( 'WPC: Attempted query on non-existent table: ' . $query );
		
		// Try to create table
		wpc_check_and_create_table();
		
		// Check again
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		if ( ! $table_exists ) {
			return false;
		}
	}
	
	// Run the query
	if ( ! empty( $params ) ) {
		$prepared_query = $wpdb->prepare( $query, $params );
		return $wpdb->get_results( $prepared_query, $output_type );
	} else {
		return $wpdb->get_results( $query, $output_type );
	}
}

/**
 * Add manual fix page for database issues
 */
function wpc_add_manual_fix_page() {
	add_submenu_page(
		null, // Hidden menu
		'WPC Manual Fix',
		'WPC Manual Fix',
		'manage_options',
		'wpc-manual-fix',
		'wpc_manual_fix_page_content'
	);
}
add_action( 'admin_menu', 'wpc_add_manual_fix_page' );

/**
 * Manual fix page content
 */
function wpc_manual_fix_page_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'pincode_checker';
	$message = '';
	$message_type = '';
	
	if ( isset( $_POST['create_table'] ) ) {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpc_manual_fix' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Force table creation
		if ( class_exists( 'Woo_Pincode_Checker_Activator' ) ) {
			$result = Woo_Pincode_Checker_Activator::force_create_table();
			
			if ( $result ) {
				$message = 'Table created successfully!';
				$message_type = 'success';
				
				// Clear any error flags
				delete_option( 'wpc_activation_error' );
				delete_transient( 'wpc_table_error_shown' );
			} else {
				$message = 'Table creation failed. Error: ' . $wpdb->last_error;
				$message_type = 'error';
			}
		}
	}
	
	if ( isset( $_POST['reactivate_plugin'] ) ) {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpc_manual_fix' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Run activation again
		activate_woo_pincode_checker();
		$message = 'Plugin reactivation attempted. Check if the table exists now.';
		$message_type = 'info';
	}
	
	$table_exists = $wpdb->get_var( $wpdb->prepare( 
		"SHOW TABLES LIKE %s", 
		$table_name 
	) ) == $table_name;
	
	?>
	<div class="wrap">
		<h1>Woo Pincode Checker - Manual Fix</h1>
		
		<?php if ( $message ): ?>
		<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php endif; ?>
		
		<div class="card">
			<h2>Database Table Status</h2>
			<p>Table Name: <code><?php echo esc_html( $table_name ); ?></code></p>
			<p>Status: 
				<?php if ( $table_exists ): ?>
					<span style="color: green; font-weight: bold;">✓ EXISTS</span>
				<?php else: ?>
					<span style="color: red; font-weight: bold;">✗ MISSING</span>
				<?php endif; ?>
			</p>
			
			<?php if ( $table_exists ): ?>
				<?php
				$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
				$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
				?>
				<p>Records: <strong><?php echo esc_html( $row_count ); ?></strong></p>
				<p>Columns: <strong><?php echo esc_html( count( $columns ) ); ?></strong></p>
				
				<h3>Table Structure</h3>
				<table class="widefat">
					<thead>
						<tr>
							<th>Column</th>
							<th>Type</th>
							<th>Null</th>
							<th>Key</th>
							<th>Default</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $columns as $column ): ?>
						<tr>
							<td><?php echo esc_html( $column->Field ); ?></td>
							<td><?php echo esc_html( $column->Type ); ?></td>
							<td><?php echo esc_html( $column->Null ); ?></td>
							<td><?php echo esc_html( $column->Key ); ?></td>
							<td><?php echo esc_html( $column->Default ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		
		<div class="card">
			<h2>Fix Options</h2>
			
			<?php if ( ! $table_exists ): ?>
			<form method="post">
				<?php wp_nonce_field( 'wpc_manual_fix' ); ?>
				<p>
					<input type="submit" name="create_table" value="Create Table" class="button button-primary">
					<span class="description">Force create the database table</span>
				</p>
			</form>
			
			<form method="post">
				<?php wp_nonce_field( 'wpc_manual_fix' ); ?>
				<p>
					<input type="submit" name="reactivate_plugin" value="Reactivate Plugin" class="button">
					<span class="description">Run the plugin activation process again</span>
				</p>
			</form>
			<?php else: ?>
			<p style="color: green;">✓ Table exists! The plugin should work correctly now.</p>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=pincode_lists' ) ); ?>" class="button button-primary">View Pincodes</a></p>
			<?php endif; ?>
		</div>
		
		<div class="card">
			<h2>Manual SQL (Advanced Users Only)</h2>
			<p>If automatic creation fails, you can run this SQL manually in your database:</p>
			<textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;">
CREATE TABLE <?php echo esc_html( $table_name ); ?> (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pincode VARCHAR(20) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  delivery_days TINYINT UNSIGNED NOT NULL DEFAULT 1,
  shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  case_on_delivery BOOLEAN NOT NULL DEFAULT FALSE,
  cod_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_pincode (pincode),
  INDEX idx_pincode_search (pincode),
  INDEX idx_city_state (city, state),
  INDEX idx_delivery_days (delivery_days),
  INDEX idx_created_at (created_at)
) <?php echo $wpdb->get_charset_collate(); ?> ENGINE=InnoDB;
			</textarea>
		</div>
	</div>
	<?php
}

/**
 * Enhanced error handling and monitoring functions
 */

/**
 * Log security events for monitoring
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
 * Handle database errors
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
 * Handle plugin deactivation cleanup
 */
function wpc_handle_plugin_deactivation() {
	// Clear scheduled events
	wp_clear_scheduled_hook( 'wpc_cleanup_transients' );
	
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