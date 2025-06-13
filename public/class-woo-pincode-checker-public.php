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
	 * Validate and sanitize pincode input
	 *
	 * @param string $pincode The pincode to validate
	 * @return string|false Sanitized pincode or false if invalid
	 */
	private function validate_pincode( $pincode ) {
		// Remove extra whitespace
		$pincode = trim( $pincode );
		
		// Check length (3-10 characters)
		if ( strlen( $pincode ) < 3 || strlen( $pincode ) > 10 ) {
			return false;
		}
		
		// Check format (alphanumeric and spaces only)
		if ( ! preg_match( '/^[A-Za-z0-9\s]+$/', $pincode ) ) {
			return false;
		}
		
		return sanitize_text_field( $pincode );
	}

	/**
	 * Check rate limiting for AJAX requests
	 *
	 * @param string $action The action being rate limited
	 * @return bool True if within limits, false if exceeded
	 */
	private function check_rate_limit( $action ) {
		$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$transient_key = 'wpc_rate_limit_' . md5( $user_ip . $action );
		$requests = get_transient( $transient_key );
		
		// Allow 10 requests per minute
		if ( $requests && $requests > 10 ) {
			return false;
		}
		
		set_transient( $transient_key, ( $requests ? $requests + 1 : 1 ), 60 );
		return true;
	}

	/**
	 * Get pincode data with caching
	 *
	 * @param string $pincode The pincode to lookup
	 * @return object|null Pincode data or null if not found
	 */
	private function get_pincode_data_cached( $pincode ) {
		$cache_key = 'wpc_pincode_' . md5( $pincode );
		$cached_data = wp_cache_get( $cache_key, 'woo_pincode_checker' );
		
		if ( false === $cached_data ) {
			global $wpdb;
			$cached_data = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pincode_checker WHERE pincode = %s",
				$pincode
			) );
			
			// Cache for 1 hour
			wp_cache_set( $cache_key, $cached_data, 'woo_pincode_checker', 3600 );
		}
		
		return $cached_data;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 
			$this->plugin_name, 
			plugin_dir_url( __FILE__ ) . 'css/woo-pincode-checker-public.css', 
			array(), 
			$this->version, 
			'all' 
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$wpc_general_settings = get_option( 'wpc_general_settings' );
		$wpc_hide_disabled_add_cart_btn = ( isset( $wpc_general_settings['add_to_cart_option'] ) && ! empty( $wpc_general_settings['add_to_cart_option'] ) ) ? $wpc_general_settings['add_to_cart_option'] : '';
		$wpc_required_pincode_field_btn = ( isset( $wpc_general_settings['pincode_field'] ) && ! empty( $wpc_general_settings['pincode_field'] ) ) ? $wpc_general_settings['pincode_field'] : '';
		
		wp_enqueue_script( 
			$this->plugin_name, 
			plugin_dir_url( __FILE__ ) . 'js/woo-pincode-checker-public.js', 
			array( 'jquery', 'wp-i18n' ), 
			$this->version, 
			false 
		);
		
		wp_localize_script(
			$this->plugin_name,
			'pincode_check',
			array(
				'ajaxurl'                               => admin_url( 'admin-ajax.php' ),
				'hide_disable_product_page_cart_btn'    => $wpc_hide_disabled_add_cart_btn,
				'required_pincode_field_btn'            => $wpc_required_pincode_field_btn,
				'wpc_nonce'                             => wp_create_nonce( 'ajax-nonce' ),
				'messages' => array(
					'enter_pincode'      => __( 'Please enter a pincode.', 'woo-pincode-checker' ),
					'invalid_format'     => __( 'Please enter a valid pincode.', 'woo-pincode-checker' ),
					'not_serviceable'    => __( 'Sorry! We are currently not servicing your area.', 'woo-pincode-checker' ),
					'network_error'      => __( 'Connection issue. Please check your internet and try again.', 'woo-pincode-checker' ),
					'server_error'       => __( 'Something went wrong. Please try again.', 'woo-pincode-checker' ),
					'rate_limit'         => __( 'Too many requests. Please wait a moment.', 'woo-pincode-checker' ),
				)
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
		global $wpdb, $woocommerce, $wpc_globals;
		
		$wpc_general_settings = $wpc_globals->wpc_general_settings;
		$wpc_cod_text = $wpc_general_settings['cod_label_text'];
		$cookie_pin = isset( $_COOKIE['valid_pincode'] ) ? $this->validate_pincode( wp_unslash( $_COOKIE['valid_pincode'] ) ) : '';
		$checkout_pin = isset( $_COOKIE['pincode'] ) ? $this->validate_pincode( wp_unslash( $_COOKIE['pincode'] ) ) : '';
		$wc_selected_payment_method = WC()->session->get( 'chosen_payment_method' );

		// Validate pincode from cookies
		if ( false === $cookie_pin ) {
			$cookie_pin = '';
		}
		if ( false === $checkout_pin ) {
			$checkout_pin = '';
		}

		$pincode_to_check = ! empty( $checkout_pin ) && ( $checkout_pin !== $cookie_pin ) ? $checkout_pin : $cookie_pin;

		if ( ! empty( $pincode_to_check ) ) {
			$wpc_records = $this->get_pincode_data_cached( $pincode_to_check );

			if ( $wpc_records && is_object( $wpc_records ) ) {
				// Add shipping fee if applicable
				if ( ! empty( $wpc_records->shipping_amount ) && $wpc_records->shipping_amount > 0 ) {
					$woocommerce->cart->add_fee( 
						__( 'Shipping Amount', 'woo-pincode-checker' ), 
						floatval( $wpc_records->shipping_amount ) 
					);
					add_filter( 'woocommerce_cart_ready_to_calc_shipping', array( $this, 'wpc_disable_shipping_calc_on_cart_page' ), 10, 1 );
				}

				// Add COD fee if applicable
				if ( ! empty( $wpc_records->cod_amount ) && $wpc_records->cod_amount > 0 ) {
					if ( ! empty( $wc_selected_payment_method ) && 'cod' === $wc_selected_payment_method ) {
						$woocommerce->cart->add_fee( 
							esc_html( $wpc_cod_text ), 
							floatval( $wpc_records->cod_amount ) 
						);
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
		// Check rate limiting
		if ( ! $this->check_rate_limit( 'checkout_pincode_check' ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'woo-pincode-checker' ) ) );
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woo-pincode-checker' ) ) );
			return;
		}

		// Validate input
		if ( ! isset( $_REQUEST['pincode'] ) || empty( $_REQUEST['pincode'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Pincode is required.', 'woo-pincode-checker' ) ) );
			return;
		}

		$pincode = $this->validate_pincode( wp_unslash( $_REQUEST['pincode'] ) );
		
		// Validate pincode format
		if ( false === $pincode ) {
			wp_send_json_error( array( 'message' => __( 'Invalid pincode format.', 'woo-pincode-checker' ) ) );
			return;
		}

		// Set secure cookie
		$expiry = time() + ( 7 * 24 * 60 * 60 ); // 7 days
		setcookie( 'pincode', $pincode, $expiry, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

		$wpc_records = $this->get_pincode_data_cached( $pincode );
		
		if ( $wpc_records && ! empty( $wpc_records->pincode ) ) {
			wp_send_json_success( array( 'available' => true ) );
		} else {
			wp_send_json_success( array( 'available' => false ) );
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
		$cookie_pin = isset( $_COOKIE['valid_pincode'] ) ? $this->validate_pincode( wp_unslash( $_COOKIE['valid_pincode'] ) ) : '';
		$checkout_pin = isset( $_COOKIE['pincode'] ) ? $this->validate_pincode( wp_unslash( $_COOKIE['pincode'] ) ) : '';

		// Validate pincode from cookies
		if ( false === $cookie_pin ) {
			$cookie_pin = '';
		}
		if ( false === $checkout_pin ) {
			$checkout_pin = '';
		}

		$pincode_to_validate = $cookie_pin == $checkout_pin ? $cookie_pin : $checkout_pin;

		if ( ! empty( $pincode_to_validate ) ) {
			$wpc_records = $this->get_pincode_data_cached( $pincode_to_validate );
			
			if ( ! $wpc_records || $wpc_records->pincode != $pincode_to_validate ) {
				$errors->add( 
					'validation', 
					sprintf( 
						esc_html__( 'Delivery to %1$s is currently not available for this item.', 'woo-pincode-checker' ), 
						'<strong>' . esc_html( $pincode_to_validate ) . '</strong>' 
					) 
				);
			}
		}
	}