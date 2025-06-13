<?php
/**
 * The Pincode Checker Form functionality of the plugin.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/public
 */

/**
 * The Pincode Checker Form functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/public
 * @author     wbcomdesigns <admin@wbcomdesigns.com>
 */
class Woo_Pincode_Checker_Form {

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
	 * Check if database table exists
	 *
	 * @return bool
	 */
	private function check_table_exists() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'pincode_checker';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		if ( ! $table_exists ) {
			error_log( 'WPC: Table missing in form class, attempting to create...' );
			
			// Try to create table
			if ( function_exists( 'activate_woo_pincode_checker' ) ) {
				activate_woo_pincode_checker();
				
				// Check again
				$table_exists = $wpdb->get_var( $wpdb->prepare( 
					"SHOW TABLES LIKE %s", 
					$table_name 
				) ) == $table_name;
			}
		}
		
		return $table_exists;
	}

	/**
	 * Safe database query that ensures table exists
	 *
	 * @param string $query SQL query
	 * @param array $params Query parameters
	 * @return mixed Query result or false if table doesn't exist
	 */
	private function safe_db_query( $query, $params = array() ) {
		if ( ! $this->check_table_exists() ) {
			return false;
		}
		
		global $wpdb;
		
		if ( ! empty( $params ) ) {
			return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
		} else {
			return $wpdb->get_results( $query );
		}
	}

	/**
	 * Safe database get_var that ensures table exists
	 *
	 * @param string $query SQL query
	 * @param array $params Query parameters
	 * @return mixed Query result or false if table doesn't exist
	 */
	private function safe_db_get_var( $query, $params = array() ) {
		if ( ! $this->check_table_exists() ) {
			return false;
		}
		
		global $wpdb;
		
		if ( ! empty( $params ) ) {
			return $wpdb->get_var( $wpdb->prepare( $query, $params ) );
		} else {
			return $wpdb->get_var( $query );
		}
	}

	/**
	 * Safe database get_row that ensures table exists
	 *
	 * @param string $query SQL query
	 * @param array $params Query parameters
	 * @return mixed Query result or false if table doesn't exist
	 */
	private function safe_db_get_row( $query, $params = array() ) {
		if ( ! $this->check_table_exists() ) {
			return false;
		}
		
		global $wpdb;
		
		if ( ! empty( $params ) ) {
			return $wpdb->get_row( $wpdb->prepare( $query, $params ) );
		} else {
			return $wpdb->get_row( $query );
		}
	}

	/**
	 * Validate and sanitize pincode input
	 *
	 * @param string $pincode The pincode to validate
	 * @return string|false Sanitized pincode or false if invalid
	 */
	private function validate_pincode( $pincode ) {
		// Remove extra whitespace and normalize
		$pincode = trim( $pincode );
		$pincode = preg_replace('/\s+/', ' ', $pincode);
		
		// Check if empty after cleaning
		if ( empty( $pincode ) ) {
			return false;
		}
		
		// Check length (3-10 characters)
		if ( strlen( $pincode ) < 3 || strlen( $pincode ) > 10 ) {
			return false;
		}
		
		// Check format - alphanumeric with optional single spaces, but not at start/end
		if ( ! preg_match( '/^[A-Za-z0-9](?:[A-Za-z0-9\s]*[A-Za-z0-9])?$/', $pincode ) ) {
			return false;
		}
		
		// Additional security - prevent potential XSS
		$pincode = sanitize_text_field( $pincode );
		
		// Final check - ensure no malicious patterns
		if ( preg_match('/[<>"\']/', $pincode) ) {
			return false;
		}
		
		return $pincode;
	}

	/**
	 * Check rate limiting for requests
	 *
	 * @return bool True if within limits, false if exceeded
	 */
	private function check_rate_limit() {
		$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$transient_key = 'wpc_rate_limit_' . md5( $user_ip );
		$requests = get_transient( $transient_key );
		
		// Allow 10 requests per minute
		if ( $requests && $requests > 10 ) {
			return false;
		}
		
		set_transient( $transient_key, ( $requests ? $requests + 1 : 1 ), 60 );
		return true;
	}

	/**
	 * Validate and get pincode from cookie
	 *
	 * @param string $cookie_name Cookie name to check
	 * @return string Validated pincode or empty string
	 */
	private function validate_and_get_cookie_pincode( $cookie_name ) {
		if ( ! isset( $_COOKIE[ $cookie_name ] ) || empty( $_COOKIE[ $cookie_name ] ) ) {
			return '';
		}
		
		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		$validated_pincode = $this->validate_pincode( $cookie_value );
		
		if ( false === $validated_pincode ) {
			// Invalid pincode in cookie, clear it
			setcookie( $cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			return '';
		}
		
		return $validated_pincode;
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
			$table_name = $wpdb->prefix . 'pincode_checker';
			
			$cached_data = $this->safe_db_get_row(
				"SELECT * FROM {$table_name} WHERE pincode = %s",
				array( $pincode )
			);
			
			// Cache for 1 hour
			wp_cache_set( $cache_key, $cached_data, 'woo_pincode_checker', 3600 );
		}
		
		return $cached_data;
	}

	/**
	 * Get product id from product object.
	 *
	 * @param  object $obj  product object.
	 * @param  string $prop get property from boject.
	 *
	 * @return string|int    based on $prop
	 */
	public function wpc_access_protected( $obj, $prop ) {
		if ( ! empty( $obj ) ) {
			$reflection = new ReflectionClass( $obj );
			$property   = $reflection->getProperty( $prop );
			$property->setAccessible( true );
			return $property->getValue( $obj );
		}
	}

	/**
	 * Display Pincode check form on product page - Enhanced with table checks.
	 */
	public function wpc_display_pincode_field() {
		global $table_prefix, $wpdb,$woocommerce, $product;
		
		// Check if table exists first
		if ( ! $this->check_table_exists() ) {
			// Show a user-friendly message or silently fail
			if ( current_user_can( 'manage_options' ) ) {
				echo '<div style="color: red; font-size: 12px; margin: 10px 0;">';
				echo esc_html__( 'Pincode checker unavailable: Database table missing.', 'woo-pincode-checker' );
				echo ' <a href="' . esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ) . '">' . esc_html__( 'Fix this issue', 'woo-pincode-checker' ) . '</a>';
				echo '</div>';
			}
			return;
		}
		
		$wpc_exclude_category = wpc_get_products_to_pincode_checker_by_category();
		$product_id = $this->wpc_access_protected( $product, 'id' );
		$wpc_woo_terms = get_the_terms( $product_id, 'product_cat' );
		$wpc_add_pincode_checker = true;
		$wpc_zipcode = '';
		
		if ( $wpc_woo_terms ) {
			foreach ( $wpc_woo_terms as $wpc_woo_term ) {
				if ( ! empty( $wpc_exclude_category ) ) {
					if ( in_array( $wpc_woo_term->term_id, $wpc_exclude_category ) ) {
						$wpc_add_pincode_checker = false;
					}
				}
			}
		}
		
		if ( false === $wpc_add_pincode_checker ) {
			return false;
		}

		$cookie_pin = $this->validate_and_get_cookie_pincode( 'valid_pincode' );
		
		// Double check pincode exists in database
		if ( ! empty( $cookie_pin ) ) {
			$table_name = $wpdb->prefix . 'pincode_checker';
			$pincode_exists = $this->safe_db_get_var(
				"SELECT COUNT(*) FROM {$table_name} WHERE `pincode` = %s",
				array( $cookie_pin )
			);
			
			if ( $pincode_exists === false || $pincode_exists == 0 ) {
				$cookie_pin = '';
				// Clear invalid cookie
				setcookie( 'valid_pincode', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			}
		}
		
		$wpc_hide_form = get_post_meta( get_the_ID(), 'wpc_hide_pincode_checker', true );
		if ( 'yes' === $wpc_hide_form ) {
			return;
		}
		
		$wpc_check_btn_label = wpc_get_check_btn_label();
		$wpc_change_btn_label = wpc_get_change_btn_label();
		$wpc_delivery_date_label = wpc_get_delivery_date_label();
		$wpc_availability_label = wpc_get_availability_label();
		$wpc_cod_label = wpc_get_cod_label();
		$wpc_display_cod_option = wpc_display_cod_option();
		$wpc_pincode_btn_position = wpc_single_product_button_position();
		
		// Set position class
		$position_classes = array(
			'woocommerce_before_add_to_cart_button' => 'wpc_before_add_to_cart',
			'woocommerce_after_add_to_cart_button' => 'wpc_after_add_to_cart',
			'woocommerce_after_add_to_cart_quantity' => 'wpc_after_add_to_cart_quantity',
			'wpc_pincode_checker' => 'wpc_shortcode'
		);
		$wpc_position_class = $position_classes[$wpc_pincode_btn_position] ?? 'wpc_shortcode';
		
		/* set pincode */
		if( 'wpc_pincode_checker' !== $wpc_pincode_btn_position ){
			$customer = new WC_Customer();
			$customer->set_shipping_postcode( $cookie_pin );
			$customer->set_billing_postcode( $cookie_pin );
			$get_shipping_zipcode = WC()->customer->get_shipping_postcode( wc_clean( $cookie_pin ) );
			$get_billing_zipcode = WC()->customer->get_billing_postcode( wc_clean( $cookie_pin ) );
			$user_ID = get_current_user_id();
			if ( ! empty( $get_shipping_zipcode ) ) {
				$wpc_zipcode = $get_shipping_zipcode;
			} else {
				$wpc_zipcode = $get_billing_zipcode;
			}
		}
		
		$wpc_general_settings = get_option( 'wpc_general_settings' );
		$wpc_pincode_field = isset( $wpc_general_settings['pincode_field'] ) ? $wpc_general_settings['pincode_field'] : '';
		$wpc_required = ( 'on' == $wpc_pincode_field ) ? 'required' : '';
		
		/* check pincode is set in cookie or not */
		if ( isset( $cookie_pin ) && $cookie_pin != '' ) {
			$table_name = $wpdb->prefix . 'pincode_checker';
			$getdata = $this->safe_db_query(
				"SELECT * FROM {$table_name} WHERE `pincode` = %s",
				array( $cookie_pin )
			);

			if ( ! empty( $getdata ) ) {
				foreach ( $getdata as $data ) {
					$delivery_day = intval( $data->delivery_days );
					$cash_on_delivery = $data->case_on_delivery;
					$city = esc_html( $data->city );
					$state = esc_html( $data->state );
				}

				/* set delivery date */
				$wpc_general_settings = get_option( 'wpc_general_settings' );
				$delivery_date_format = isset( $wpc_general_settings['delivery_date'] ) ? $wpc_general_settings['delivery_date'] : 'M jS';
				
				// Ensure delivery day is reasonable (1-365 days)
				$delivery_day = max(1, min(365, $delivery_day));
				$delivery_date = wp_date( $delivery_date_format, strtotime( "+{$delivery_day} day" ) );
				
				if( 'wpc_pincode_checker' === $wpc_pincode_btn_position ){
					$customer = new WC_Customer();
					$customer->set_shipping_postcode( $cookie_pin );
					$user_ID = get_current_user_id();
				}
				
				if ( isset( $user_ID ) && $user_ID != 0 ) {
					update_user_meta( $user_ID, 'shipping_postcode', $cookie_pin );
				}
				?>
				<div class="pincode_loader" style="display:none">
					<img src="<?php echo esc_url( WPCP_PLUGIN_URL . 'public/image/loading-load.gif' ) ;  ?>"/>
				</div>
				<div class="wc-delivery-time-response <?php echo esc_attr( $wpc_position_class ); ?>">
				<?php
					include WPCP_PLUGIN_PATH . 'public/woo-pincode-checker-delivery-message.php';
				?>
				</div>
				<?php
			} else {
				// Cookie exists but no data found, clear cookie and show form
				setcookie( 'valid_pincode', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
				$cookie_pin = '';
			}
		}
		
		if ( empty( $cookie_pin ) ) {
			?>
			<div class="pincode_loader" style="display:none">
				<img src="<?php echo esc_url( WPCP_PLUGIN_URL . 'public/image/loading-load.gif' ) ;  ?>"/>
			</div>
			<div class="wc-delivery-time-response pin_div pincode_check_btn <?php echo esc_attr( $wpc_position_class ); ?>" id="my_custom_checkout_field">
				<div class="error_pin" id="error_pin" style="display:none"><?php esc_html_e( 'Sorry! We are currently not servicing your area.', 'woo-pincode-checker' ); ?></div>

				<p id="pincode_field_idp" class="form-row my-field-class form-row-wide">
					<input type="text" 
						   value="<?php echo esc_attr( $wpc_zipcode ); ?>" 
						   placeholder="<?php esc_attr_e( 'Enter your pincode', 'woo-pincode-checker' ); ?>" 
						   id="pincode_field_id" 
						   name="pincode_field" 
						   class="input-text" 
						   maxlength="10"
						   autocomplete="postal-code"
						   <?php echo esc_attr( $wpc_required ); ?>/>
					<a class="button wpc-check-button" id="checkpin">
						<?php echo esc_html( $wpc_check_btn_label ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Set pincode in cookie - Enhanced with table checks and better error handling.
	 */
	public function wpc_picode_check_ajax_submit() {
		// Check if table exists first
		if ( ! $this->check_table_exists() ) {
			wp_send_json_error( array( 
				'message' => __( 'Service temporarily unavailable. Please try again later.', 'woo-pincode-checker' )
			));
			return;
		}

		// Verify nonce first
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			wp_send_json_error(array( 'message' => __( 'Security check failed.', 'woo-pincode-checker' ) ));
			return;
		}

		// Rate limiting check
		if ( ! $this->check_rate_limit() ) {
			wp_send_json_error(array( 'message' => __( 'Too many requests. Please wait a moment.', 'woo-pincode-checker' ) ));
			return;
		}

		// Get and validate input
		$user_input_pincode = isset( $_POST['pin_code'] ) ? sanitize_text_field( wp_unslash( $_POST['pin_code'] ) ) : '';
		
		// Validate pincode
		$validated_pincode = $this->validate_pincode( $user_input_pincode );
		if ( false === $validated_pincode ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid pincode (3-10 alphanumeric characters only).', 'woo-pincode-checker' ) ) );
			return;
		}

		global $wpdb;
		
		// Get settings for response
		$wpc_check_btn_label = wpc_get_check_btn_label();
		$wpc_change_btn_label = wpc_get_change_btn_label();
		$wpc_delivery_date_label = wpc_get_delivery_date_label();
		$wpc_availability_label = wpc_get_availability_label();
		$wpc_cod_label = wpc_get_cod_label();
		$wpc_display_cod_option = wpc_display_cod_option();
		$wpc_pincode_btn_position = wpc_single_product_button_position();
		
		// Set position class
		$position_classes = array(
			'woocommerce_before_add_to_cart_button' => 'wpc_before_add_to_cart',
			'woocommerce_after_add_to_cart_button' => 'wpc_after_add_to_cart',
			'woocommerce_after_add_to_cart_quantity' => 'wpc_after_add_to_cart_quantity',
			'wpc_pincode_checker' => 'wpc_shortcode'
		);
		$wpc_position_class = $position_classes[$wpc_pincode_btn_position] ?? 'wpc_shortcode';
		
		$wpc_general_settings = get_option( 'wpc_general_settings' );
		$wpc_pincode_field = isset( $wpc_general_settings['pincode_field'] ) ? $wpc_general_settings['pincode_field'] : '';
		$wpc_required = ( 'on' == $wpc_pincode_field ) ? 'required' : '';

		// Check if pincode exists in database using cached method
		$pincode_record = $this->get_pincode_data_cached( $validated_pincode );

		if ( $pincode_record ) {
			// Set secure cookie
			$expiry = time() + (30 * 24 * 60 * 60); // 30 days instead of 10 years
			$cookie_set = setcookie( 
				'valid_pincode', 
				$validated_pincode, 
				$expiry, 
				COOKIEPATH, 
				COOKIE_DOMAIN, 
				is_ssl(), 
				true // httponly
			);
			
			if ( !$cookie_set ) {
				wp_send_json_error(array( 'message' => __( 'Unable to save pincode. Please check your browser settings.', 'woo-pincode-checker' ) ));
				return;
			}

			// Prepare data for response
			$delivery_day = intval( $pincode_record->delivery_days );
			$cash_on_delivery = $pincode_record->case_on_delivery;
			$city = esc_html( $pincode_record->city );
			$state = esc_html( $pincode_record->state );
			$cookie_pin = $validated_pincode;

			// Calculate delivery date safely
			$delivery_date_format = isset( $wpc_general_settings['delivery_date'] ) ? $wpc_general_settings['delivery_date'] : 'M jS';
			
			// Ensure delivery day is reasonable (1-365 days)
			$delivery_day = max(1, min(365, $delivery_day));
			$delivery_date = wp_date( $delivery_date_format, strtotime( "+{$delivery_day} day" ) );

			// Update user meta if logged in
			$user_ID = get_current_user_id();
			if ( $user_ID ) {
				update_user_meta( $user_ID, 'shipping_postcode', $validated_pincode );
			}

			// Generate response HTML
			ob_start();
			include WPCP_PLUGIN_PATH . 'public/woo-pincode-checker-delivery-message.php';
			$pincode_del_msg = ob_get_clean();
			
			wp_send_json_success(array(
				'html' => $pincode_del_msg,
				'pincode' => $validated_pincode,
				'city' => $city,
				'state' => $state,
				'delivery_days' => $delivery_day
			));
		} else {
			wp_send_json_error(array( 
				'message' => __( 'Sorry! We are currently not servicing your area.', 'woo-pincode-checker' ),
				'pincode' => $validated_pincode
			));
		}
	}

	/**
	 * CSS of general setting option value.
	 */
	public function wpc_add_custom_css() {
		$wpc_general_settings = get_option( 'wpc_general_settings' );
		$wpc_label_color = isset( $wpc_general_settings['textcolor'] ) ? $wpc_general_settings['textcolor'] : '';
		$wpc_btn_color = isset( $wpc_general_settings['buttoncolor'] ) ? $wpc_general_settings['buttoncolor'] : '';
		$wpc_btn_text_color = isset( $wpc_general_settings['buttontcolor'] ) ? $wpc_general_settings['buttontcolor'] : '';
		?>
		<style>
			.wpc_delivery-info-wrap,
			.avlpin p { 
			<?php
			if ( $wpc_label_color == '' ) {
				echo 'color:#000;';
			} else {
				echo "color:" . esc_attr( $wpc_label_color ) . " !important;";
			}
			?>
			}

			#respond input#submit, #pincode_field_idp a.button.wpc-check-button, #avlpin a.button.wpc-check-button  { 
			<?php
			if ( ! empty( $wpc_btn_color ) ) {
				echo "background-color:" . esc_attr( $wpc_btn_color ) . ";";
				echo "border-color:" . esc_attr( $wpc_btn_color ) . ";";
			}
			?>
			}

			#respond input#submit, #pincode_field_idp a.button.wpc-check-button, #avlpin a.button.wpc-check-button  { 
			<?php
			if ( ! empty( $wpc_btn_text_color ) ) {
				echo "color:" . esc_attr( $wpc_btn_text_color ) . ";";
			}
			?>
			}
		</style>
		<?php
	}

	/**
	 * Display shortcode content.
	 *
	 * @param  Array  $atts An associative array of attributes, or an empty string if no attributes are given.
	 * @param  string $content the enclosed content (if the shortcode is used in its enclosing form).
	 */
	public function wpc_display_shortcode_pincode_form( $atts, $content = null ) {
		ob_start();
		$this->wpc_display_pincode_field();
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * Set Available Pincodes into shipping and billing postcode - Enhanced with table checks.
	 */
	public function wpc_set_wc_billing_and_shipping_zipcode() {
		if ( is_admin() ) {
			return false;
		}
		
		// Check if table exists
		if ( ! $this->check_table_exists() ) {
			return false;
		}
		
		global $wpdb;
		$cookie_pin = $this->validate_and_get_cookie_pincode( 'valid_pincode' );
		
		// Double check pincode exists in database
		if ( ! empty( $cookie_pin ) ) {
			$table_name = $wpdb->prefix . 'pincode_checker';
			$num_rows = $this->safe_db_get_var(
				"SELECT COUNT(*) FROM {$table_name} WHERE `pincode` = %s",
				array( $cookie_pin )
			);

			if ( $num_rows === false || $num_rows == 0 ) {
				$cookie_pin = '';
				// Clear invalid cookie
				setcookie( 'valid_pincode', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			}
		}
		
		if ( ! empty( $cookie_pin ) ) {
			$customer = new WC_Customer();
			$customer->set_shipping_postcode( wc_clean( $cookie_pin ) );
			$customer->set_billing_postcode( wc_clean( $cookie_pin ) );
		}
	}
}