<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 * @author     wbcomdesigns <admin@wbcomdesigns.com>
 */
class Woo_Pincode_Checker_Activator {

	/**
	 * Plugin activation with enhanced security and error handling.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$wpc_db_version = '1.3';
		$charset_collate = $wpdb->get_charset_collate();

		// Create Pincode Checker table with enhanced security
		$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
		
		// Check if table already exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$pincode_checker_table_name 
		) ) == $pincode_checker_table_name;

		if ( ! $table_exists ) {
			$sql = "CREATE TABLE {$pincode_checker_table_name} (
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
			) {$charset_collate} ENGINE=InnoDB;";
			
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$result = dbDelta( $sql );
			
			// Check if table was created successfully
			$table_created = $wpdb->get_var( $wpdb->prepare( 
				"SHOW TABLES LIKE %s", 
				$pincode_checker_table_name 
			) ) == $pincode_checker_table_name;
			
			if ( $table_created ) {
				add_option( 'wpc-db-version', $wpc_db_version );
				
				// Log successful table creation
				error_log( 'WPC: Database table created successfully during activation.' );
				
				// Insert sample data for testing (optional)
				self::insert_sample_data();
				
			} else {
				// Log error if table creation failed
				error_log( 'WPC Error: Failed to create database table during activation. SQL Error: ' . $wpdb->last_error );
				
				// Don't fail activation, but log the issue
				add_option( 'wpc_activation_error', 'Database table creation failed: ' . $wpdb->last_error );
			}
		} else {
			// Table exists, update version
			update_option( 'wpc-db-version', $wpc_db_version );
			
			// Check and add missing columns if needed
			self::update_table_structure( $pincode_checker_table_name );
		}

		// Set default options with enhanced security settings
		$default_settings = array(
			'date_display'             => 'on',
			'delivery_date'            => 'M jS',
			'cod_display'              => 'on',
			'shipping_cost'            => 'on',
			'cod_cost'                 => 'on',
			'hide_shop_btn'            => 'on',
			'check_btn_text'           => __( 'Check', 'woo-pincode-checker' ),
			'change_btn_text'          => __( 'Change', 'woo-pincode-checker' ),
			'delivery_date_label_text' => __( 'Delivery Date', 'woo-pincode-checker' ),
			'cod_label_text'           => __( 'Cash on Delivery', 'woo-pincode-checker' ),
			'availability_label_text'  => __( 'Available at', 'woo-pincode-checker' ),
			'textcolor'                => '#141414',
			'buttoncolor'              => '#dd3333',
			'buttontcolor'             => '#ffffff',
			'pincode_position'         => 'woocommerce_before_add_to_cart_button',
			'add_to_cart_option'       => 'add_to_cart_disable',
			'pincode_field'            => '',
			'categories_for_shipping'  => array(),
		);
		
		$existing_settings = get_option( 'wpc_general_settings', array() );
		$merged_settings = array_merge( $default_settings, $existing_settings );
		update_option( 'wpc_general_settings', $merged_settings );

		// Set activation timestamp for monitoring
		update_option( 'wpc_activated_at', current_time( 'mysql' ) );
		
		// Clear any existing cache
		wp_cache_flush_group( 'woo_pincode_checker' );
		
		// Schedule cleanup events
		self::schedule_cleanup_events();
		
		// Run initial health check
		self::initial_health_check();
		
		// Log successful activation
		error_log( 'WPC: Plugin activated successfully at ' . current_time( 'mysql' ) );
	}

	/**
	 * Update table structure if needed
	 *
	 * @param string $table_name Table name
	 */
	private static function update_table_structure( $table_name ) {
		global $wpdb;
		
		// Check for missing columns and add them
		$columns_to_check = array(
			'shipping_amount' => "ALTER TABLE `{$table_name}` ADD COLUMN `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_days`",
			'cod_amount'      => "ALTER TABLE `{$table_name}` ADD COLUMN `cod_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `case_on_delivery`",
			'created_at'      => "ALTER TABLE `{$table_name}` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `cod_amount`",
			'updated_at'      => "ALTER TABLE `{$table_name}` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`"
		);
		
		foreach ( $columns_to_check as $column => $query ) {
			$column_exists = $wpdb->get_results( $wpdb->prepare(
				"SHOW COLUMNS FROM `{$table_name}` LIKE %s",
				$column
			));
			
			if ( empty( $column_exists ) ) {
				$result = $wpdb->query( $query );
				if ( false === $result ) {
					error_log( "WPC: Failed to add column {$column}: " . $wpdb->last_error );
				} else {
					error_log( "WPC: Successfully added column {$column}" );
				}
			}
		}
		
		// Check and add missing indexes
		self::ensure_table_indexes( $table_name );
	}

	/**
	 * Ensure all required indexes exist
	 *
	 * @param string $table_name Table name
	 */
	private static function ensure_table_indexes( $table_name ) {
		global $wpdb;
		
		$indexes_to_check = array(
			'unique_pincode'    => "ALTER TABLE `{$table_name}` ADD UNIQUE KEY `unique_pincode` (`pincode`)",
			'idx_pincode_search' => "ALTER TABLE `{$table_name}` ADD INDEX `idx_pincode_search` (`pincode`)",
			'idx_city_state'    => "ALTER TABLE `{$table_name}` ADD INDEX `idx_city_state` (`city`, `state`)",
			'idx_delivery_days' => "ALTER TABLE `{$table_name}` ADD INDEX `idx_delivery_days` (`delivery_days`)",
			'idx_created_at'    => "ALTER TABLE `{$table_name}` ADD INDEX `idx_created_at` (`created_at`)"
		);
		
		// Get existing indexes
		$existing_indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table_name}`" );
		$existing_index_names = array();
		
		foreach ( $existing_indexes as $index ) {
			$existing_index_names[] = $index->Key_name;
		}
		
		foreach ( $indexes_to_check as $index_name => $query ) {
			if ( ! in_array( $index_name, $existing_index_names ) ) {
				$result = $wpdb->query( $query );
				if ( false === $result ) {
					error_log( "WPC: Failed to add index {$index_name}: " . $wpdb->last_error );
				} else {
					error_log( "WPC: Successfully added index {$index_name}" );
				}
			}
		}
	}

	/**
	 * Insert sample data for testing
	 */
	private static function insert_sample_data() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'pincode_checker';
		
		// Sample data for common Indian cities
		$sample_data = array(
			array(
				'pincode'          => '110001',
				'city'             => 'New Delhi',
				'state'            => 'Delhi',
				'delivery_days'    => 2,
				'shipping_amount'  => 0.00,
				'case_on_delivery' => 1,
				'cod_amount'       => 25.00
			),
			array(
				'pincode'          => '400001',
				'city'             => 'Mumbai',
				'state'            => 'Maharashtra',
				'delivery_days'    => 3,
				'shipping_amount'  => 50.00,
				'case_on_delivery' => 1,
				'cod_amount'       => 30.00
			),
			array(
				'pincode'          => '560001',
				'city'             => 'Bangalore',
				'state'            => 'Karnataka',
				'delivery_days'    => 2,
				'shipping_amount'  => 0.00,
				'case_on_delivery' => 1,
				'cod_amount'       => 20.00
			),
			array(
				'pincode'          => '600001',
				'city'             => 'Chennai',
				'state'            => 'Tamil Nadu',
				'delivery_days'    => 4,
				'shipping_amount'  => 40.00,
				'case_on_delivery' => 0,
				'cod_amount'       => 0.00
			),
			array(
				'pincode'          => '700001',
				'city'             => 'Kolkata',
				'state'            => 'West Bengal',
				'delivery_days'    => 3,
				'shipping_amount'  => 35.00,
				'case_on_delivery' => 1,
				'cod_amount'       => 25.00
			)
		);
		
		foreach ( $sample_data as $data ) {
			// Check if pincode already exists
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s",
				$data['pincode']
			));
			
			if ( ! $exists ) {
				$result = $wpdb->insert(
					$table_name,
					$data,
					array( '%s', '%s', '%s', '%d', '%f', '%d', '%f' )
				);
				
				if ( false === $result ) {
					error_log( "WPC: Failed to insert sample data for pincode {$data['pincode']}: " . $wpdb->last_error );
				}
			}
		}
		
		error_log( 'WPC: Sample data insertion completed.' );
	}

	/**
	 * Schedule cleanup events
	 */
	private static function schedule_cleanup_events() {
		// Schedule rate limiting cleanup
		if ( ! wp_next_scheduled( 'wpc_cleanup_transients' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'wpc_cleanup_transients' );
		}
		
		// Schedule daily health check
		if ( ! wp_next_scheduled( 'wpc_daily_health_check' ) ) {
			wp_schedule_event( time(), 'daily', 'wpc_daily_health_check' );
		}
		
		// Schedule weekly cache cleanup
		if ( ! wp_next_scheduled( 'wpc_weekly_cache_cleanup' ) ) {
			wp_schedule_event( time(), 'weekly', 'wpc_weekly_cache_cleanup' );
		}
		
		error_log( 'WPC: Scheduled events configured successfully.' );
	}

	/**
	 * Run initial health check
	 */
	private static function initial_health_check() {
		global $wpdb;
		
		$health_status = array(
			'database' => false,
			'table_structure' => false,
			'permissions' => false,
			'cache' => false
		);
		
		// Check database connection
		$test_query = $wpdb->get_var( "SELECT 1" );
		$health_status['database'] = ( $test_query == 1 );
		
		// Check table structure
		$table_name = $wpdb->prefix . 'pincode_checker';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		$health_status['table_structure'] = $table_exists;
		
		// Check cache functionality
		wp_cache_set( 'wpc_health_test', 'test_value', 'woo_pincode_checker', 60 );
		$cache_test = wp_cache_get( 'wpc_health_test', 'woo_pincode_checker' );
		$health_status['cache'] = ( $cache_test === 'test_value' );
		
		// Check file permissions
		$health_status['permissions'] = is_writable( WP_CONTENT_DIR );
		
		// Store health status
		update_option( 'wpc_health_status', $health_status );
		update_option( 'wpc_last_health_check', current_time( 'mysql' ) );
		
		// Log any issues
		foreach ( $health_status as $check => $status ) {
			if ( ! $status ) {
				error_log( "WPC Health Check: {$check} check failed during activation." );
			}
		}
		
		return $health_status;
	}

	/**
	 * Create backup during activation
	 */
	private static function create_activation_backup() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'pincode_checker';
		
		// Check if table has data
		$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		
		if ( $row_count > 0 ) {
			$backup_data = $wpdb->get_results( "SELECT * FROM {$table_name}", ARRAY_A );
			
			if ( $backup_data ) {
				$backup_file = WP_CONTENT_DIR . '/wpc-activation-backup-' . date( 'Y-m-d-H-i-s' ) . '.json';
				$backup_result = file_put_contents( $backup_file, wp_json_encode( $backup_data, JSON_PRETTY_PRINT ) );
				
				if ( $backup_result ) {
					update_option( 'wpc_activation_backup', $backup_file );
					error_log( "WPC: Activation backup created at {$backup_file}" );
				} else {
					error_log( 'WPC: Failed to create activation backup.' );
				}
			}
		}
	}

	/**
	 * Set up security options
	 */
	private static function setup_security_options() {
		// Set security-related options
		$security_options = array(
			'wpc_security_log_enabled' => true,
			'wpc_rate_limiting_enabled' => true,
			'wpc_max_requests_per_minute' => 10,
			'wpc_security_scan_enabled' => true,
			'wpc_auto_backup_enabled' => true
		);
		
		foreach ( $security_options as $option => $value ) {
			if ( ! get_option( $option ) ) {
				add_option( $option, $value );
			}
		}
		
		error_log( 'WPC: Security options configured.' );
	}

	/**
	 * Check system requirements
	 */
	private static function check_system_requirements() {
		$requirements = array(
			'php_version' => version_compare( PHP_VERSION, '7.4', '>=' ),
			'mysql_version' => true, // Will check below
			'wp_version' => version_compare( get_bloginfo( 'version' ), '5.0', '>=' ),
			'woocommerce' => class_exists( 'WooCommerce' ),
			'memory_limit' => true // Will check below
		);
		
		// Check MySQL version
		global $wpdb;
		$mysql_version = $wpdb->db_version();
		$requirements['mysql_version'] = version_compare( $mysql_version, '5.6', '>=' );
		
		// Check memory limit
		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$requirements['memory_limit'] = ( $memory_limit >= 64 * 1024 * 1024 ); // 64MB minimum
		
		// Store requirements check
		update_option( 'wpc_system_requirements', $requirements );
		
		// Log any failed requirements
		foreach ( $requirements as $requirement => $met ) {
			if ( ! $met ) {
				error_log( "WPC: System requirement not met: {$requirement}" );
				
				// Add admin notice for critical requirements
				if ( in_array( $requirement, array( 'php_version', 'wp_version', 'woocommerce' ) ) ) {
					add_option( 'wpc_requirement_error', "System requirement not met: {$requirement}" );
				}
			}
		}
		
		return $requirements;
	}

	/**
	 * Validate activation environment
	 */
	private static function validate_activation_environment() {
		$validation_errors = array();
		
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			$validation_errors[] = 'WooCommerce is required but not active.';
		}
		
		// Check database permissions
		global $wpdb;
		$can_create_table = $wpdb->query( "CREATE TEMPORARY TABLE wpc_test_table (id INT)" );
		if ( false === $can_create_table ) {
			$validation_errors[] = 'Insufficient database permissions to create tables.';
		} else {
			$wpdb->query( "DROP TEMPORARY TABLE wpc_test_table" );
		}
		
		// Check file system permissions
		if ( ! is_writable( WP_CONTENT_DIR ) ) {
			$validation_errors[] = 'Content directory is not writable.';
		}
		
		// Store validation results
		if ( ! empty( $validation_errors ) ) {
			update_option( 'wpc_activation_errors', $validation_errors );
			error_log( 'WPC Activation Errors: ' . implode( ', ', $validation_errors ) );
		}
		
		return empty( $validation_errors );
	}

	/**
	 * Enhanced activation with full validation and setup
	 */
	public static function enhanced_activate() {
		try {
			// Validate environment first
			if ( ! self::validate_activation_environment() ) {
				error_log( 'WPC: Activation failed due to environment validation errors.' );
				return false;
			}
			
			// Check system requirements
			$requirements = self::check_system_requirements();
			$critical_failed = ! $requirements['php_version'] || ! $requirements['wp_version'] || ! $requirements['woocommerce'];
			
			if ( $critical_failed ) {
				error_log( 'WPC: Activation failed due to unmet system requirements.' );
				return false;
			}
			
			// Create backup if data exists
			self::create_activation_backup();
			
			// Run main activation
			self::activate();
			
			// Setup security options
			self::setup_security_options();
			
			// Final validation
			$health = self::initial_health_check();
			
			if ( $health['database'] && $health['table_structure'] ) {
				update_option( 'wpc_activation_status', 'success' );
				error_log( 'WPC: Enhanced activation completed successfully.' );
				return true;
			} else {
				update_option( 'wpc_activation_status', 'partial' );
				error_log( 'WPC: Activation completed with some issues.' );
				return false;
			}
			
		} catch ( Exception $e ) {
			update_option( 'wpc_activation_status', 'failed' );
			error_log( 'WPC: Activation failed with exception: ' . $e->getMessage() );
			return false;
		}
	}
}