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
	 * Enhanced plugin activation with better error handling and logging.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		
		// Enable error reporting for activation
		$wpdb->show_errors();
		
		$wpc_db_version = '1.3.4';
		$charset_collate = $wpdb->get_charset_collate();
		$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
		
		// Log activation start
		error_log( 'WPC: Starting plugin activation...' );
		
		// Check system requirements first
		if ( ! self::check_system_requirements() ) {
			error_log( 'WPC: System requirements not met during activation' );
			return false;
		}
		
		// Enhanced table creation with better error handling
		$table_created = self::create_pincode_table( $pincode_checker_table_name, $charset_collate );
		
		if ( $table_created ) {
			// Set default options
			self::set_default_options();
			
			// Set activation timestamp and version
			update_option( 'wpc_db_version', $wpc_db_version );
			update_option( 'wpc_activated_at', current_time( 'mysql' ) );
			update_option( 'wpc_activation_status', 'success' );
			
			// Clear any existing cache
			wp_cache_flush_group( 'woo_pincode_checker' );
			
			// Clear any previous errors
			delete_option( 'wpc_activation_error' );
			
			error_log( 'WPC: Plugin activated successfully at ' . current_time( 'mysql' ) );
		} else {
			update_option( 'wpc_activation_status', 'failed' );
			error_log( 'WPC: Plugin activation failed - table creation unsuccessful' );
		}
		
		return $table_created;
	}

	/**
	 * Check system requirements
	 */
	private static function check_system_requirements() {
		global $wpdb;
		
		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			update_option( 'wpc_activation_error', 'PHP version 7.4 or higher is required' );
			return false;
		}
		
		// Check WordPress version
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			update_option( 'wpc_activation_error', 'WordPress version 5.0 or higher is required' );
			return false;
		}
		
		// Check WooCommerce
		if ( ! class_exists( 'WooCommerce' ) ) {
			update_option( 'wpc_activation_error', 'WooCommerce is required for this plugin to work' );
			return false;
		}
		
		// Check database connection
		$test_query = $wpdb->get_var( "SELECT 1" );
		if ( $test_query != 1 ) {
			update_option( 'wpc_activation_error', 'Database connection test failed' );
			return false;
		}
		
		// Check database permissions
		if ( ! self::check_database_permissions() ) {
			update_option( 'wpc_activation_error', 'Insufficient database permissions to create tables' );
			return false;
		}
		
		return true;
	}

	/**
	 * Check database permissions
	 */
	private static function check_database_permissions() {
		global $wpdb;
		
		// Try to create a temporary table to test permissions
		$test_table = $wpdb->prefix . 'wpc_test_' . time();
		$test_sql = "CREATE TEMPORARY TABLE {$test_table} (id INT)";
		$result = $wpdb->query( $test_sql );
		
		if ( false === $result ) {
			error_log( 'WPC: Database permission test failed: ' . $wpdb->last_error );
			return false;
		}
		
		return true;
	}

	/**
	 * Create the pincode checker table with enhanced error handling
	 */
	private static function create_pincode_table( $table_name, $charset_collate ) {
		global $wpdb;
		
		error_log( 'WPC: Attempting to create table: ' . $table_name );
		
		// Check if table already exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;

		if ( $table_exists ) {
			error_log( 'WPC: Table already exists, checking structure...' );
			self::update_table_structure( $table_name );
			return true;
		}

		// Method 1: Try full featured table first
		$success = self::create_full_table( $table_name, $charset_collate );
		
		if ( ! $success ) {
			error_log( 'WPC: Full table creation failed, trying simplified structure...' );
			// Method 2: Try simplified table structure
			$success = self::create_simple_table( $table_name, $charset_collate );
		}
		
		if ( ! $success ) {
			error_log( 'WPC: Simplified table creation failed, trying basic structure...' );
			// Method 3: Try most basic table structure
			$success = self::create_basic_table( $table_name, $charset_collate );
		}
		
		if ( $success ) {
			error_log( 'WPC: Table created successfully' );
			// Insert sample data
			self::insert_sample_data( $table_name );
			return true;
		} else {
			error_log( 'WPC: All table creation methods failed' );
			update_option( 'wpc_activation_error', 'Failed to create database table. Please check database permissions.' );
			return false;
		}
	}

	/**
	 * Create full featured table
	 */
	private static function create_full_table( $table_name, $charset_collate ) {
		global $wpdb;
		
		$sql = "CREATE TABLE {$table_name} (
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
		
		// Try dbDelta first
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );
		
		// Verify table creation
		$table_created = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		if ( ! $table_created ) {
			// Try direct query
			$direct_result = $wpdb->query( $sql );
			
			if ( false !== $direct_result ) {
				$table_created = $wpdb->get_var( $wpdb->prepare( 
					"SHOW TABLES LIKE %s", 
					$table_name 
				) ) == $table_name;
			}
			
			if ( false === $direct_result || ! $table_created ) {
				error_log( 'WPC: Full table creation failed: ' . $wpdb->last_error );
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Create simplified table structure
	 */
	private static function create_simple_table( $table_name, $charset_collate ) {
		global $wpdb;
		
		$sql = "CREATE TABLE {$table_name} (
			id INT AUTO_INCREMENT PRIMARY KEY,
			pincode VARCHAR(20) NOT NULL UNIQUE,
			city VARCHAR(100) NOT NULL,
			state VARCHAR(100) NOT NULL,
			delivery_days INT DEFAULT 1,
			shipping_amount DECIMAL(10,2) DEFAULT 0.00,
			case_on_delivery TINYINT(1) DEFAULT 0,
			cod_amount DECIMAL(10,2) DEFAULT 0.00,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			KEY idx_pincode (pincode),
			KEY idx_city_state (city, state)
		) {$charset_collate};";
		
		$result = $wpdb->query( $sql );
		
		if ( false === $result ) {
			error_log( 'WPC: Simplified table creation failed: ' . $wpdb->last_error );
			return false;
		}
		
		// Verify creation
		$table_created = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		return $table_created;
	}

	/**
	 * Create most basic table structure
	 */
	private static function create_basic_table( $table_name, $charset_collate ) {
		global $wpdb;
		
		$sql = "CREATE TABLE {$table_name} (
			id INT AUTO_INCREMENT PRIMARY KEY,
			pincode VARCHAR(20) NOT NULL,
			city VARCHAR(100) NOT NULL,
			state VARCHAR(100) NOT NULL,
			delivery_days INT DEFAULT 1,
			shipping_amount DECIMAL(10,2) DEFAULT 0.00,
			case_on_delivery INT DEFAULT 0,
			cod_amount DECIMAL(10,2) DEFAULT 0.00
		);";
		
		$result = $wpdb->query( $sql );
		
		if ( false === $result ) {
			error_log( 'WPC: Basic table creation failed: ' . $wpdb->last_error );
			return false;
		}
		
		// Verify creation
		$table_created = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		// Add unique constraint separately if basic table was created
		if ( $table_created ) {
			$wpdb->query( "ALTER TABLE {$table_name} ADD UNIQUE KEY unique_pincode (pincode)" );
		}
		
		return $table_created;
	}

	/**
	 * Update existing table structure if needed
	 */
	private static function update_table_structure( $table_name ) {
		global $wpdb;
		
		$columns_to_check = array(
			'shipping_amount' => "ALTER TABLE `{$table_name}` ADD COLUMN `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_days`",
			'cod_amount' => "ALTER TABLE `{$table_name}` ADD COLUMN `cod_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `case_on_delivery`",
			'created_at' => "ALTER TABLE `{$table_name}` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `cod_amount`",
			'updated_at' => "ALTER TABLE `{$table_name}` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`"
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
		
		// Ensure unique constraint exists
		self::ensure_unique_constraint( $table_name );
	}

	/**
	 * Ensure unique constraint on pincode
	 */
	private static function ensure_unique_constraint( $table_name ) {
		global $wpdb;
		
		// Check if unique constraint exists
		$indexes = $wpdb->get_results( "SHOW INDEXES FROM {$table_name} WHERE Key_name = 'unique_pincode'" );
		
		if ( empty( $indexes ) ) {
			// Try to add unique constraint
			$result = $wpdb->query( "ALTER TABLE {$table_name} ADD UNIQUE KEY unique_pincode (pincode)" );
			
			if ( false === $result ) {
				error_log( "WPC: Failed to add unique constraint: " . $wpdb->last_error );
			}
		}
	}

	/**
	 * Insert sample data for testing
	 */
	private static function insert_sample_data( $table_name ) {
		global $wpdb;
		
		$sample_data = array(
			array(
				'pincode' => '110001',
				'city' => 'New Delhi',
				'state' => 'Delhi',
				'delivery_days' => 2,
				'shipping_amount' => 0.00,
				'case_on_delivery' => 1,
				'cod_amount' => 25.00
			),
			array(
				'pincode' => '400001',
				'city' => 'Mumbai',
				'state' => 'Maharashtra',
				'delivery_days' => 3,
				'shipping_amount' => 50.00,
				'case_on_delivery' => 1,
				'cod_amount' => 30.00
			),
			array(
				'pincode' => '560001',
				'city' => 'Bangalore',
				'state' => 'Karnataka',
				'delivery_days' => 2,
				'shipping_amount' => 0.00,
				'case_on_delivery' => 1,
				'cod_amount' => 20.00
			),
			array(
				'pincode' => '600001',
				'city' => 'Chennai',
				'state' => 'Tamil Nadu',
				'delivery_days' => 4,
				'shipping_amount' => 40.00,
				'case_on_delivery' => 0,
				'cod_amount' => 0.00
			),
			array(
				'pincode' => '700001',
				'city' => 'Kolkata',
				'state' => 'West Bengal',
				'delivery_days' => 3,
				'shipping_amount' => 35.00,
				'case_on_delivery' => 1,
				'cod_amount' => 25.00
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
	 * Set default plugin options
	 */
	private static function set_default_options() {
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
		
		error_log( 'WPC: Default settings configured.' );
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
	 * Force table creation method for emergency use
	 */
	public static function force_create_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'pincode_checker';
		$charset_collate = $wpdb->get_charset_collate();
		
		// Drop table if exists (use with caution)
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		
		// Create fresh table
		return self::create_pincode_table( $table_name, $charset_collate );
	}
}