<?php
/**
 * Includes Global functions.
 *
 * @package Woo_Pincode_Checker
 * @author Wbcom Designs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Woo_Pincode_Checker_Functions' ) ) :

	/**
	 * Includes Global functions.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Woo_Pincode_Checker
	 * @subpackage Woo_Pincode_Checker/includes
	 * @author     wbcomdesigns <admin@wbcomdesigns.com>
	 */
	class Woo_Pincode_Checker_Functions {
		/**
		 * The single instance of the class.
		 *
		 * @var Wbcom_WSS_Global_Functions
		 */
		protected static $_instance = null;
		/**
		 * General settings.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      array $wss_general_settings
		 */
		public $wpc_general_settings;

		/**
		 * Main Wbcom_WSS_Global_Functions Instance.
		 * Ensures only one instance of Wbcom_WSS_Global_Functions is loaded or can be loaded.
		 *
		 * @return Wbcom_WSS_Global_Functions - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function __construct() {
			$this->setup_plugin_global();
		}

		/**
		 * Function setup the global setting of this plugin.
		 */
		public function setup_plugin_global() {
			$wpc_general_settings = '';
			$new_general_settings = array();
			
			if ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
				$wpc_general_settings = get_site_option( 'wpc_general_settings' );
			} else {
				$wpc_general_settings = get_option( 'wpc_general_settings' );
			}

			// Initialize with defaults
			$default_settings = array(
				'date_display'             => '',
				'delivery_date'            => '',
				'cod_display'              => '',
				'pincode_field'            => '',
				'textcolor'                => '',
				'buttoncolor'              => '',
				'buttontcolor'             => '',
				'check_btn_text'           => '',
				'change_btn_text'          => '',
				'delivery_date_label_text' => '',
				'cod_label_text'           => '',
				'availability_label_text'  => '',
				'shipping_cost'            => '',
				'cod_cost'                 => '',
				'hide_shop_btn'            => '',
				'hide_product_page_btn'    => '',
				'categories_for_shipping'  => array(),
				'pincode_position'         => '',
				'add_to_cart_option'       => '',
			);

			if ( ! empty( $wpc_general_settings ) && is_array( $wpc_general_settings ) ) {
				// Safely merge settings with null checks
				foreach ( $default_settings as $key => $default_value ) {
					$new_general_settings[ $key ] = isset( $wpc_general_settings[ $key ] ) 
						? $wpc_general_settings[ $key ] 
						: $default_value;
				}
				
				// Handle special cases for backwards compatibility
				if ( ! empty( $wpc_general_settings['delivery_date'] ) ) {
					$new_general_settings['date_display'] = isset( $wpc_general_settings['date_display'] ) 
						? $wpc_general_settings['date_display'] 
						: '';
					$new_general_settings['delivery_date'] = $wpc_general_settings['delivery_date'];
				}
			} else {
				$new_general_settings = $default_settings;
			}
			
			$this->wpc_general_settings = $new_general_settings;
		}

		/**
		 * Save admin settings.
		 *
		 * @author Wbcom Designs
		 * @since  1.0.0
		 *
		 * @param array $setting_key Get a setting key.
		 * @access public
		 */
		public function woo_wpc_admin_settings( $setting_key ) {
			$saved_setting = '';

			if ( ! empty( $this->wpc_general_settings ) && is_array( $this->wpc_general_settings ) ) {
				if ( isset( $this->wpc_general_settings[ $setting_key ] ) ) {
					$saved_setting = $this->wpc_general_settings[ $setting_key ];
				}
			}

			return $saved_setting;
		}
	}
endif;

$GLOBALS['wpc_globals'] = Woo_Pincode_Checker_Functions::instance();