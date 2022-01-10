<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/public
 * @author     wbcomdesigns <admin@wbcomdesigns.com>
 */
class Woo_Pincode_Checker_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Pincode_Checker_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Pincode_Checker_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-pincode-checker-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Pincode_Checker_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Pincode_Checker_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$wpc_hide_cart_btn = wpc_hide_shop_page_cart_btn_option();
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-pincode-checker-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'pincode_check',
			array(
				'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
				'hide_product_page_cart_btn' => $wpc_hide_cart_btn,
			)
		);
	}

	/**
	 * Removes the add to cart button.
	 * Dependent on WPC
	 *
	 * @author wbcomdesigns
	 * @version 1.0.0
	 * @since   1.0.0
	 * @link     https://wbcomdesigns.com/
	 */
	public function wpc_hide_shop_page_cart_button() {
		$wpc_hide_cart_btn = wpc_hide_shop_page_cart_btn_option();
		if ( $wpc_hide_cart_btn ) {
			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
		}
	}

}
