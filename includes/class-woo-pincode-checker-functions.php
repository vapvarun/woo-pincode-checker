<?php
/**
 * Includes Global functions.
 *
 * @package Wbcom_Woo_Sell_Services
 * @author Wbcom Designs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Woo_Pincode_Checker_Functions' ) ) :

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
		
		public function setup_plugin_global() {
			$wpc_general_settings = '';
			$new_general_settings = array();
			if ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
				$wpc_general_settings = get_site_option( 'wpc_general_settings' );
				
			} else {
				$wpc_general_settings = get_option( 'wpc_general_settings' );
			}
			
			if ( ! empty( $wpc_general_settings ) ) {
				if ( ! empty( $wpc_general_settings['delivery_date'] ) ) {
					
			 		$new_general_settings['date_display'] = isset($wpc_general_settings['date_display']) ? $wpc_general_settings['date_display'] : '';
			 		$new_general_settings['delivery_date'] = $wpc_general_settings['delivery_date'];
			 		$new_general_settings['textcolor'] = $wpc_general_settings['textcolor'];
			 		$new_general_settings['buttoncolor'] = $wpc_general_settings['buttoncolor'];
			 		$new_general_settings['buttontcolor'] = $wpc_general_settings['buttontcolor'];
			 	}
			} 
			
			$this->wpc_general_settings = $new_general_settings;
			
		}
		
		/**
		 * Save admin settings.
		 *
		 * @author Wbcom Designs
		 * @since  1.0.0
		 * @access public
		 */
		public function woo_wpc_admin_settings( $setting_key ) {
			$saved_setting = '';

			if( ! empty( $wpc_admin_settings ) && is_array( $wpc_admin_settings ) ) {	
				if( isset( $wpc_admin_settings[$setting_key] ) ) {
					$saved_setting = $wpc_admin_settings[$setting_key];
				}
			}

			return $saved_setting;
		}
	}
endif;

$GLOBALS['wpc_globals'] = Woo_Pincode_Checker_Functions::instance();