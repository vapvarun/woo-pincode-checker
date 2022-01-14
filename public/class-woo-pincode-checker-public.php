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
		$wpc_hide_product_page_cart_btn = wpc_hide_product_page_cart_btn_option();
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-pincode-checker-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'pincode_check',
			array(
				'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
				'hide_product_page_cart_btn' => $wpc_hide_product_page_cart_btn,
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

	/**
	 * Trigger an action so 3rd parties can add custom fees.
	 *
	 * @since 2.0.0
	 */
	public function wpc_added_wc_shipping_and_cod_amount() {
		global $table_prefix, $wpdb,$woocommerce, $product, $wpc_globals;
		$wpc_general_settings   = $wpc_globals->wpc_general_settings;
		$wpc_hide_shipping_cost = isset( $wpc_general_settings['shipping_cost'] ) ? $wpc_general_settings['shipping_cost'] : '';
		$wpc_hide_cod_cost      = isset( $wpc_general_settings['cod_cost'] ) ? $wpc_general_settings['cod_cost'] : '';
		$wpc_cod_text           = $wpc_general_settings['cod_label_text'];
		$tablename              = $wpdb->prefix . 'pincode_checker';
		$cookie_pin             = ( isset( $_COOKIE['valid_pincode'] ) && $_COOKIE['valid_pincode'] != '' ) ? sanitize_text_field( $_COOKIE['valid_pincode'] ) : '';
		$wpc_pincode            = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$cookie_pin' ";
		$wpc_records            = $wpdb->get_results( $wpc_pincode, OBJECT );
		if ( isset( $cookie_pin ) ) {
			if ( 'on' === $wpc_hide_shipping_cost ) {
				if ( $wpc_records && $wpc_records[0]->shipping_amount != 0 && ! empty( $wpc_records[0]->shipping_amount ) ) {
					$woocommerce->cart->add_fee( __( 'Shipping Amount', 'woo-pincode-checker' ), $wpc_records[0]->shipping_amount );
					add_filter( 'woocommerce_cart_ready_to_calc_shipping', array( $this, 'wpc_disable_shipping_calc_on_cart_page' ), 10, 1 );
				}
			}
			if ( 'on' === $wpc_hide_cod_cost ) {
				if ( $wpc_records[0]->cod_amount != 0 && ! empty( $wpc_records[0]->cod_amount ) ) {
					$wc_selected_payment_method = WC()->session->get( 'chosen_payment_method' );
					if ( empty( $wc_selected_payment_method ) ) {
						return;
					} else {
						if ( 'cod' === $wc_selected_payment_method ) {
							$woocommerce->cart->add_fee( esc_html__( $wpc_cod_text, 'woo-pincode-checker' ), $wpc_records[0]->cod_amount );
						}
					}
				}
			}
		}
	}

	/**
	 * Sees if the customer has entered enough data to calc the shipping yet.
	 *
	 * @param bool $show_shipping Display Shipping.
	 * @return bool
	 */
	public function wpc_disable_shipping_calc_on_cart_page( $show_shipping ) {
		if ( is_cart() ) {
			add_filter( 'woocommerce_shipping_calculator_enable_state', '__return_false' );
			add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_false' );
			add_filter( 'woocommerce_shipping_calculator_enable_country', '__return_false' );
			add_filter( 'woocommerce_shipping_calculator_enable_postcode', '__return_false' );
			add_filter(
				'woocommerce_product_needs_shipping',
				function() {
					return false;
				}
			);
			return false;
		}
		if ( is_checkout() ) {
			return false;
		}
		return $show_shipping;
	}

	/**
	 * Action is ran once when the class is first constructed.
	 */
	public function wpc_refresh_checkout_form_on_payment_method_switched() {
			wc_enqueue_js(
				"jQuery( function($){
          
        $('form.checkout').on('change', 'input[name=payment_method],#billing_postcode,#shipping_postcode', function(){
            $(document.body).trigger('update_checkout');
        });
    });"
			);
	}

}
