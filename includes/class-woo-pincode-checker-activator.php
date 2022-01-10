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

		$charset_collate = $wpdb->get_charset_collate();

		/* Create EDD Sell Message table */
		$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
		if ( $wpdb->get_var( "show tables like '$pincode_checker_table_name'" ) != $pincode_checker_table_name ) {

			$edd_sql = "CREATE TABLE $pincode_checker_table_name (
						id mediumint(11) NOT NULL AUTO_INCREMENT,
						pincode varchar(255) NOT NULL,
						city  varchar(255) NOT NULL, 
						state  varchar(255) NOT NULL,
						delivery_days int(11)   NOT NULL,
						case_on_delivery tinyint(2) NULL default '0' ,
						UNIQUE KEY id (id)
			) $charset_collate;";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $edd_sql );
		}

		$wpc_general_settings = get_option( 'wpc_general_settings' );

		$wpc_added_default_option = array(
			'date_display'             => 'on',
			'delivery_date'            => 'M jS',
			'cod_display'              => 'on',
			'hide_shop_btn'            => 'on',
			'check_btn_text'           => 'Check',
			'change_btn_text'          => 'Change',
			'delivery_date_label_text' => 'Delivery Date',
			'cod_label_text'           => 'Cash On Delivery',
			'availability_label_text'  => 'Available at',
			'textcolor'                => '#141414',
			'buttoncolor'              => '#dd3333',
			'buttontcolor'             => '#ffffff',
			'pincode_position'         => 'woocommerce_before_add_to_cart_button',
		);
		update_option( 'wpc_general_settings', $wpc_added_default_option );
	}
}
