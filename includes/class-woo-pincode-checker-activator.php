<?php

/**
 * Fired during plugin activation - FIXED VERSION
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.5.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 */

class Woo_Pincode_Checker_Activator {

	/**
	 * Plugin activation with improved database table creation.
	 *
	 * @since    1.5.0
	 */
	public static function activate() {
		global $wpdb;

		$wpc_db_version = '1.5.0';
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Check if table already exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

		if ( ! $table_exists ) {
			// Create table with better compatibility
			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				id int(11) NOT NULL AUTO_INCREMENT,
				pincode varchar(20) NOT NULL,
				city varchar(100) NOT NULL,
				state varchar(100) NOT NULL,
				delivery_days int(11) DEFAULT 1,
				shipping_amount decimal(10,2) DEFAULT 0.00,
				case_on_delivery tinyint(1) DEFAULT 0,
				cod_amount decimal(10,2) DEFAULT 0.00,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP,
				category_rules TEXT NULL DEFAULT NULL,
				latitude DECIMAL(10,8) NULL DEFAULT NULL,
				longitude DECIMAL(11,8) NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY unique_pincode (pincode),
				KEY idx_city (city),
				KEY idx_state (state)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			// Verify table was created
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

			if ( ! $table_exists ) {
				// Try alternative approach without dbDelta
				$wpdb->query( $sql );
				$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
			}

			if ( $table_exists ) {
				// Insert sample data
				self::insert_sample_data( $table_name );
			}
		}

		// Set default options
		self::set_default_options();

		// Update database version
		update_option( 'wpc_db_version', $wpc_db_version );

		// Clear any cache
		wp_cache_flush();

		return $table_exists;
	}

	/**
	 * Insert sample data for testing
	 */
	private static function insert_sample_data( $table_name ) {
		global $wpdb;

		// Check if table is empty
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		if ( $count == 0 ) {
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
				$wpdb->insert(
					$table_name,
					$data,
					array( '%s', '%s', '%s', '%d', '%f', '%d', '%f' )
				);
			}
		}
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
			'hide_shop_btn'            => '',
			'check_btn_text'           => __( 'Check', 'pincode-checker-for-woocommerce' ),
			'change_btn_text'          => __( 'Change', 'pincode-checker-for-woocommerce' ),
			'delivery_date_label_text' => __( 'Delivery Date', 'pincode-checker-for-woocommerce' ),
			'cod_label_text'           => __( 'Cash on Delivery', 'pincode-checker-for-woocommerce' ),
			'availability_label_text'  => __( 'Available at', 'pincode-checker-for-woocommerce' ),
			'textcolor'                => '#141414',
			'buttoncolor'              => '#dd3333',
			'buttontcolor'             => '#ffffff',
			'pincode_position'         => 'woocommerce_before_add_to_cart_button',
			'add_to_cart_option'       => '',
			'pincode_field'            => '',
			'categories_for_shipping'  => array(),
		);

		$existing_settings = get_option( 'wpc_general_settings', array() );
		if ( empty( $existing_settings ) ) {
			update_option( 'wpc_general_settings', $default_settings );
		}
	}
}