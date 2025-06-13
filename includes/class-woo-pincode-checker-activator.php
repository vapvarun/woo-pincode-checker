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
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$wpc_db_version  = '1.0';
		$charset_collate = $wpdb->get_charset_collate();

		/* Create EDD Sell Message table */
		$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $pincode_checker_table_name ) ) != $pincode_checker_table_name ) {
        
			$sql = "CREATE TABLE {$pincode_checker_table_name} (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT,
				pincode VARCHAR(20) NOT NULL,
				city VARCHAR(100) NOT NULL,
				state VARCHAR(100) NOT NULL,
				delivery_days TINYINT UNSIGNED NOT NULL DEFAULT 1,
				shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
				case_on_delivery BOOLEAN NOT NULL DEFAULT FALSE,
				cod_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY unique_pincode (pincode),
				INDEX idx_pincode_search (pincode),
				INDEX idx_city_state (city, state)
			) {$charset_collate};";
			
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			
			// Check if table was created successfully
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $pincode_checker_table_name ) ) == $pincode_checker_table_name ) {
				add_option( 'wpc-db-version', $wpc_db_version );
			}
   		}
    
		// Set default options
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
		);
		
		$existing_settings = get_option( 'wpc_general_settings', array() );
		$merged_settings = array_merge( $default_settings, $existing_settings );
		update_option( 'wpc_general_settings', $merged_settings );
	}
}
