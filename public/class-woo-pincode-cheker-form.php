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
	 * Display Pincode check form on product page.
	 */
	public function wpc_display_pincode_field() {
		global $table_prefix, $wpdb,$woocommerce, $product;
		$wpc_exclude_category    = wpc_get_products_to_pincode_checker_by_category();
		$product_id              = $this->wpc_access_protected( $product, 'id' );
		$wpc_woo_terms           = get_the_terms( $product_id, 'product_cat' );
		$wpc_add_pincode_checker = true;
		$wpc_zipcode          = '';
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

		$cookie_pin = ( isset( $_COOKIE['valid_pincode'] ) && $_COOKIE['valid_pincode'] != '' ) ? sanitize_text_field( wp_unslash( $_COOKIE['valid_pincode'] ) ) : '';
		$num_rows   = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $table_prefix . 'pincode_checker` where `pincode` = %s', $cookie_pin ) );

		if ( $num_rows == 0 ) {
			$cookie_pin = '';
		}
		$wpc_hide_form = get_post_meta( get_the_ID(), 'wpc_hide_pincode_checker', true );
		if ( 'yes' === $wpc_hide_form ) {
			return;
		}
		$wpc_check_btn_label      = wpc_get_check_btn_label();
		$wpc_change_btn_label     = wpc_get_change_btn_label();
		$wpc_delivery_date_label  = wpc_get_delivery_date_label();
		$wpc_availability_label   = wpc_get_availability_label();
		$wpc_cod_label            = wpc_get_cod_label();
		$wpc_display_cod_option   = wpc_display_cod_option();
		$wpc_pincode_btn_position = wpc_single_product_button_position();
		if ( 'woocommerce_before_add_to_cart_button' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_before_add_to_cart';
		} elseif ( 'woocommerce_after_add_to_cart_button' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_after_add_to_cart';
		} elseif ( 'woocommerce_after_add_to_cart_quantity' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_after_add_to_cart_quantity';
		} elseif ( 'wpc_pincode_checker' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_shortcode';
		}
		/* set pincode */
		$wpc_pincode_btn_position = wpc_single_product_button_position();
		if( 'wpc_pincode_checker' !== $wpc_pincode_btn_position ){
			$customer = new WC_Customer();
			$customer->set_shipping_postcode( $cookie_pin );
			$customer->set_billing_postcode( $cookie_pin );
			$get_shipping_zipcode = WC()->customer->get_shipping_postcode( wc_clean( $cookie_pin ) );
			$get_billing_zipcode  = WC()->customer->get_billing_postcode( wc_clean( $cookie_pin ) );
			$user_ID              = get_current_user_id();
			if ( ! empty( $get_shipping_zipcode ) ) {
				$wpc_zipcode = $get_shipping_zipcode;
			} else {
				$wpc_zipcode = $get_billing_zipcode;
			}
		}
		$wpc_general_settings 	= get_option( 'wpc_general_settings' );
		$wpc_pincode_field 		= isset( $wpc_general_settings['pincode_field'] ) ? $wpc_general_settings['pincode_field'] : '';
		$wpc_required 			= '';
		if( 'on' == $wpc_pincode_field ){
			$wpc_required = 'required';
		}
		/* check pincode is set in cookie or not */
		if ( isset( $cookie_pin ) && $cookie_pin != '' ) {
			$query = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$cookie_pin' ";

			$getdata = $wpdb->get_results( $query );
			foreach ( $getdata as $data ) {

				$delivery_day     = $data->delivery_days;
				$cash_on_delivery = $data->case_on_delivery;
				$city             = $data->city;
				$state            = $data->state;
			}

			/* set delivery date */
			$wpc_general_settings = get_option( 'wpc_general_settings' );
			$delivery_date_format = $wpc_general_settings['delivery_date'];
			$delivery_date        = date( "$delivery_date_format", strtotime( "+ $delivery_day day" ) );
			if( 'wpc_pincode_checker' === $wpc_pincode_btn_position ){
				$customer = new WC_Customer();
				$customer->set_shipping_postcode( $cookie_pin );
				$user_ID = get_current_user_id();
			}
			if ( isset( $user_ID ) && $user_ID != 0 ) {

				update_user_meta( $user_ID, 'shipping_postcode', $cookie_pin );
			}?>
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
			?>
			<div class="pincode_loader" style="display:none">
				<img src="<?php echo esc_url( WPCP_PLUGIN_URL . 'public/image/loading-load.gif' ) ;  ?>"/>
			</div>
			<div class="wc-delivery-time-response  pin_div pincode_check_btn  <?php echo esc_attr( $wpc_position_class ); ?>" id="my_custom_checkout_field">
				<div class="error_pin" id="error_pin" style="display:none"><?php esc_html_e( 'Oops! We are currently not servicing in your area.', 'woo-pincode-checker' ); ?></div>

				<p id="pincode_field_idp" class="form-row my-field-class form-row-wide">
					<input type="text" value="<?php echo esc_attr( $wpc_zipcode ); ?>" placeholder="<?php esc_html_e( 'Enter Your Pincode', 'woo-pincode-checker' ); ?>" id="pincode_field_id" name="pincode_field" class="input-text" <?php echo esc_attr( $wpc_required );?>/>
					<a class="button wpc-check-button" id="checkpin">
						<?php
							/* Translators: %1$s: Check Button Text   */
							echo sprintf( esc_html__( '%1$s', 'woo-pincode-checker' ), esc_html( $wpc_check_btn_label ) );
						?>
					</a>
				</p>
			</div>
			<?php
		}
	}
	/**
	 * Set pincode in cookie.
	 */
	public function wpc_picode_check_ajax_submit() {
		global $wpdb,$table_prefix;
		$wpc_check_btn_label      = wpc_get_check_btn_label();
		$wpc_change_btn_label     = wpc_get_change_btn_label();
		$wpc_delivery_date_label  = wpc_get_delivery_date_label();
		$wpc_availability_label   = wpc_get_availability_label();
		$wpc_cod_label            = wpc_get_cod_label();
		$wpc_display_cod_option   = wpc_display_cod_option();
		$wpc_pincode_btn_position = wpc_single_product_button_position();
		if ( 'woocommerce_before_add_to_cart_button' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_before_add_to_cart';
		} elseif ( 'woocommerce_after_add_to_cart_button' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_after_add_to_cart';
		} elseif ( 'woocommerce_after_add_to_cart_quantity' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_after_add_to_cart_quantity';
		} elseif ( 'wpc_pincode_checker' === $wpc_pincode_btn_position ) {
			$wpc_position_class = 'wpc_shortcode';
		}
		$wpc_general_settings 	= get_option( 'wpc_general_settings' );
		$wpc_pincode_field 		= isset( $wpc_general_settings['pincode_field'] ) ? $wpc_general_settings['pincode_field'] : '';
		$wpc_required 			= '';
		if( 'on' == $wpc_pincode_field ){
			$wpc_required = 'required';
		}
		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ajax-nonce' ) ) {
			exit();
		}
		$user_input_pincode = isset( $_POST['pin_code'] ) ? sanitize_text_field( wp_unslash( $_POST['pin_code'] ) ) : '';
		$sql                = $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->prefix}pincode_checker` WHERE `pincode` LIKE %s", '%' . $user_input_pincode . '%' );
		$result             = $wpdb->get_var( $sql );

		if ( ! empty( $result ) ) {
			$set_cookie = setcookie( 'valid_pincode', $user_input_pincode, time() + ( 10 * 365 * 24 * 60 * 60 ), '/' );
			$query = 'SELECT * FROM `' . $table_prefix . "pincode_checker` where `pincode` = '$user_input_pincode' ";
			$getdata = $wpdb->get_results( $query );
			foreach ( $getdata as $data ) {
				$delivery_day     = $data->delivery_days;
				$cash_on_delivery = $data->case_on_delivery;
				$city             = $data->city;
				$state            = $data->state;
			}

			/* set delivery date */
			$wpc_general_settings = get_option( 'wpc_general_settings' );
			$delivery_date_format = $wpc_general_settings['delivery_date'];
			$delivery_date        = date( "$delivery_date_format", strtotime( "+ $delivery_day day" ) );

			if ( isset( $user_ID ) && $user_ID != 0 ) {

				update_user_meta( $user_ID, 'shipping_postcode', $cookie_pin );
			}
			ob_start();
			$cookie_pin = isset( $_POST['pin_code'] ) ? sanitize_text_field( wp_unslash( $_POST['pin_code'] ) ) : '';
			include WPCP_PLUGIN_PATH . 'public/woo-pincode-checker-delivery-message.php';
			$pincode_del_msg = ob_get_contents();
			ob_get_clean();
			wp_send_json_success(
				array(
					'html' => $pincode_del_msg,
				)
			);
			}else{
				wp_send_json_error();
			}
	}

	/**
	 * CSS of general setting option value.
	 */
	public function wpc_add_custom_css() {
		$wpc_general_settings = get_option( 'wpc_general_settings' );
		$wpc_label_color      = isset( $wpc_general_settings['textcolor'] ) ? $wpc_general_settings['textcolor'] : '';
		$wpc_btn_color        = isset( $wpc_general_settings['buttoncolor'] ) ? $wpc_general_settings['buttoncolor'] : '';
		$wpc_btn_text_color   = isset( $wpc_general_settings['buttontcolor'] ) ? $wpc_general_settings['buttontcolor'] : '';
		?>
		<style>
			.wpc_delivery-info-wrap,
			.avlpin p { 
			<?php
			if ( $wpc_label_color == '' ) {
				echo 'color:#000;';
			} else {
				echo "color:$wpc_label_color !important" . ';'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			}

			#respond input#submit, #pincode_field_idp a.button.wpc-check-button, #avlpin a.button.wpc-check-button  { 
			<?php
			if ( ! empty( $wpc_btn_color ) ) {
				echo "background-color:$wpc_btn_color" . ';'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "border-color:$wpc_btn_color" . ';'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			}

			#respond input#submit, #pincode_field_idp a.button.wpc-check-button, #avlpin a.button.wpc-check-button  { 
			<?php
			if ( ! empty( $wpc_btn_text_color ) ) {
				echo "color:$wpc_btn_text_color" . ';'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
	 * Set Available Pincodes into shipping and billing postcode.
	 */
	public function wpc_set_wc_billing_and_shipping_zipcode() {
		if ( is_admin() ) {
			return false;
		}
		global $table_prefix, $wpdb,$woocommerce, $product;
		$cookie_pin = ( isset( $_COOKIE['valid_pincode'] ) && $_COOKIE['valid_pincode'] != '' ) ? sanitize_text_field( wp_unslash( $_COOKIE['valid_pincode'] ) ) : '';
		$num_rows   = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $table_prefix . 'pincode_checker` where `pincode` = %s', $cookie_pin ) );
		if ( $num_rows == 0 ) {
			$cookie_pin = '';
		}
		if ( ! empty( $cookie_pin ) ) {
			$customer = new WC_Customer();
			$customer->set_shipping_postcode( wc_clean( $cookie_pin ) );
			$customer->set_billing_postcode( wc_clean( $cookie_pin ) );
		}
	}


}
