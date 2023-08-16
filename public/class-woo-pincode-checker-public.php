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
		$wpc_general_settings           = get_option( 'wpc_general_settings' );
		$wpc_hide_disabled_add_cart_btn = ( isset( $wpc_general_settings['add_to_cart_option'] ) && ! empty( $wpc_general_settings['add_to_cart_option'] ) ) ? $wpc_general_settings['add_to_cart_option'] : '';
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-pincode-checker-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'pincode_check',
			array(
				'ajaxurl'                               => admin_url( 'admin-ajax.php' ),
				'hide_disable_product_page_cart_btn'    => $wpc_hide_disabled_add_cart_btn,
				'wpc_nonce'                             => wp_create_nonce( 'ajax-nonce' ),
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
		$cookie_pin             = ( isset( $_COOKIE['valid_pincode'] ) && $_COOKIE['valid_pincode'] != '' ) ? sanitize_text_field( wp_unslash( $_COOKIE['valid_pincode'] ) ) : '';
		$checkout_pin           = ( isset( $_COOKIE['pincode'] ) && $_COOKIE['pincode'] != '' ) ? sanitize_text_field( wp_unslash( $_COOKIE['pincode'] ) ) : '';
		if ( ! empty( $checkout_pin ) && ( $checkout_pin !== $cookie_pin ) ) {
			$wpc_pincode = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$checkout_pin' ";
			$wpc_records = $wpdb->get_results( $wpc_pincode, OBJECT );
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
							$woocommerce->cart->add_fee( esc_html_e( $wpc_cod_text, 'woo-pincode-checker' ), $wpc_records[0]->cod_amount );
						}
					}
				}
			}
		} else {
			$wpc_pincode = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$cookie_pin' ";
			$wpc_records = $wpdb->get_results( $wpc_pincode, OBJECT );
			if ( 'on' === $wpc_hide_shipping_cost ) {
				if ( $wpc_records && $wpc_records[0]->shipping_amount != 0 && ! empty( $wpc_records[0]->shipping_amount ) ) {
					$woocommerce->cart->add_fee( __( 'Shipping Amount', 'woo-pincode-checker' ), $wpc_records[0]->shipping_amount );
					add_filter( 'woocommerce_cart_ready_to_calc_shipping', array( $this, 'wpc_disable_shipping_calc_on_cart_page' ), 10, 1 );
				}
			}
			if ( 'on' === $wpc_hide_cod_cost && is_array( $wpc_records ) && ! empty( $wpc_records ) ) {
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

	/**
	 * Check pincode on checkout page.
	 *
	 * @return void
	 */
	public function wpc_check_checkout_page_pincode() {
		global $table_prefix, $wpdb,$woocommerce, $product, $wpc_globals;
		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			exit();
		}
		if ( isset( $_REQUEST['pincode'] ) && $_REQUEST['pincode'] != '' ) {
			$pincode = sanitize_text_field( wp_unslash( $_REQUEST['pincode'] ) );
			$expiry  = strtotime( '+7 day' );
			setcookie( 'pincode', $pincode, $expiry, COOKIEPATH, COOKIE_DOMAIN );
			$wpc_pincode = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$pincode' ";
			$wpc_records = $wpdb->get_results( $wpc_pincode, OBJECT );
			if ( ! empty( $wpc_records[0]->pincode ) ) {
				echo '1';
			} else {
				echo '0';
			}
			wp_die();
		}
	}

	/**
	 * Validates that the checkout has enough info to proceed.
	 *
	 * @since  3.0.0
	 * @param  array    $data   An array of posted data.
	 * @param  WP_Error $errors Validation errors.
	 */
	public function wpc_add_pincode_checker_validation_on_checkout_page( $data, $errors ) {
		global $table_prefix, $wpdb,$woocommerce, $product, $wpc_globals;
		$tablename    = $wpdb->prefix . 'pincode_checker';
		$cookie_pin   = ( isset( $_COOKIE['valid_pincode'] ) && $_COOKIE['valid_pincode'] != '' ) ? sanitize_text_field( wp_unslash( $_COOKIE['valid_pincode'] ) ) : '';
		$checkout_pin = ( isset( $_COOKIE['pincode'] ) && $_COOKIE['pincode'] != '' ) ? sanitize_text_field( wp_unslash( $_COOKIE['pincode'] ) ) : '';
		$wpc_pincode  = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$cookie_pin' ";
		$wpc_records  = $wpdb->get_results( $wpc_pincode, OBJECT );
		if ( $cookie_pin == $checkout_pin ) {
			$wpc_pincode = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$cookie_pin' ";
			$wpc_records = $wpdb->get_results( $wpc_pincode, OBJECT );
			if ( $wpc_records[0]->pincode != $cookie_pin ) {
				/* translators: %1$s: Zipcode */
				$errors->add( 'validation', sprintf( esc_html__( 'Delivery to %1$s is currently not available for this item.', 'woo-pincode-checker' ), '<strong>' . $cookie_pin . '</strong>' ) );
			}
		} else {
			$wpc_pincode = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$checkout_pin' ";
			$wpc_records = $wpdb->get_results( $wpc_pincode, OBJECT );
			if ( $wpc_records[0]->pincode != $checkout_pin ) {
				/* translators: %1$s: Zipcode */
				$errors->add( 'validation', sprintf( esc_html__( 'Delivery to %1$s is currently not available for this item.', 'woo-pincode-checker' ), '<strong>' . $checkout_pin . '</strong>' ) );
			}
		}

	}

}
