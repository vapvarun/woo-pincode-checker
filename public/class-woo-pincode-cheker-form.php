<?php

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/*
	 * Display Pincode check form on product page
	 */
	public function pincode_field() {
		global $table_prefix, $wpdb,$woocommerce;

		$cookie_pin =  ( isset( $_COOKIE['valid_pincode'] ) && $_COOKIE['valid_pincode'] != '' ) ? sanitize_text_field( $_COOKIE['valid_pincode'] ) : '';


		$num_rows = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".$table_prefix."pincode_checker` where `pincode` = %s" , $cookie_pin ) );

		if($num_rows == 0) {
			$cookie_pin = '';
		}

		/* check pincode is set in cookie or not */
		if(isset($cookie_pin) && $cookie_pin != '' ) {

			$query = " SELECT * FROM `".$table_prefix."pincode_checker` where `pincode` = '$cookie_pin' ";

			$getdata = $wpdb->get_results( $query );
			foreach($getdata as $data){

				$delivery_day     =  $data->delivery_days;
				$cash_on_delivery =  $data->case_on_delivery;

			}

			/* set delivery date */
			$wpc_general_settings = get_option( 'wpc_general_settings' );
			$delivery_date_format = $wpc_general_settings['delivery_date'];
			$delivery_date = date( "$delivery_date_format", strtotime("+ $delivery_day day"));

			/* set pincode */
			$customer = new WC_Customer();

			$customer->set_shipping_postcode( $cookie_pin );

			$user_ID = get_current_user_id();

			if(isset($user_ID) && $user_ID != 0) {

				update_user_meta($user_ID, 'shipping_postcode', $cookie_pin);
			}

			?>
			<div style="clear:both;font-size:18px; font-weight:600" class="wc-delivery-time-response">

				<span class='avlpin' id='avlpin'><p><?php esc_html_e('Available at','woo-pincode-checker'); ?> <?php echo esc_html( $cookie_pin ); ?></p><a class="button" id='change_pin'><?php esc_html_e('change','woo-pincode-checker'); ?></a></span>

				<div class="pin_div pincode_check_btn" id="my_custom_checkout_field2" style="display:none;">

					<div class="error_pin" id="error_pin" style="display:none"><?php esc_html_e('Oops! We are not currently servicing your area.','woo-pincode-checker'); ?></div>

					<p id="pincode_field_idp" class="form-row my-field-class form-row-wide">

						<input type="text" required="required" value="<?php echo esc_html( $cookie_pin ); ?>" placeholder="Enter Your Pincode" id="pincode_field_id" name="pincode_field" class="input-text" />

						<a class="button" id="checkpin"><?php esc_html_e('Check','woo-pincode-checker'); ?></a>
					</p>
				</div>


				<div class="delivery-info-wrap">
					<div class="delivery-info">
						<div class="header">
						<?php if( isset( $wpc_general_settings['date_display'] ) && $wpc_general_settings['date_display'] == 'on' ) { ?>
								<h6><?php esc_html_e('Delivered By : ','woo-pincode-checker'); ?></h6>
								<div class="delivery">
									<ul class="ul-disc">
										<li>
											<?php echo esc_html( $delivery_date ); ?>
										</li>
									</ul>
								</div>
						<?php }

							 if( $cash_on_delivery == 1 ) { ?>
								<div class="cash_on_delivery"><?php esc_html_e('Cash On Delivery Available','woo-pincode-checker'); ?></div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>

			<?php

		}
		else { ?>
			<div class="pin_div pincode_check_btn" id="my_custom_checkout_field">
				<div class="error_pin" id="error_pin" style="display:none"><?php esc_html_e('Oops! We are not currently servicing your area.','woo-pincode-checker'); ?></div>

				<p id="pincode_field_idp" class="form-row my-field-class form-row-wide">
					<input type="text" required="required" value="" placeholder="Enter Your Pincode" id="pincode_field_id" name="pincode_field" class="input-text" />
					<a class="button" id="checkpin"><?php esc_html_e('Check','woo-pincode-checker'); ?></a>
				</p>
			</div>
			<?php
		}
	}


	/*
     *	set picode in cookie
	 *
	 */
	public function picodecheck_ajax_submit() {
		global $wpdb;
		$user_input_pincode = sanitize_text_field( $_POST['pin_code'] );
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}pincode_checker WHERE `pincode` = ".$user_input_pincode;
		$result = $wpdb->get_var( $sql );
		if( $result == 0 ) {
			echo "0";
		} else {
			setcookie("valid_pincode", $user_input_pincode, time() + (10 * 365 * 24 * 60 * 60),"/");
			echo "1";
		}
		wp_die();
	}

	/*
	 * css of general setting option value
	 *
	 */
	public function hook_css() {
	    $wpc_general_settings = get_option( 'wpc_general_settings' );
		?>
		<style>
			.delivery-info-wrap,
			.avlpin p { <?php if( $wpc_general_settings['textcolor'] == ''){ echo "color:#000;"; } else { echo "color:$wpc_general_settings[textcolor]".';'; }  ?> }

			.woocommerce #respond input#submit, .woocommerce #pincode_field_idp a.button, .woocommerce #avlpin a.button, .woocommerce button.button, .woocommerce input.button  { <?php if( $wpc_general_settings['buttoncolor'] == ''){ echo "background-color:#a46497;"; } else { echo "background-color:$wpc_general_settings[buttoncolor]".';'; }  ?> }

			.woocommerce #respond input#submit, .woocommerce #pincode_field_idp a.button, .woocommerce #avlpin a.button, .woocommerce button.button, .woocommerce input.button  { <?php if( $wpc_general_settings['buttontcolor'] == ''){ echo "color:#fff;"; } else { echo "color:$wpc_general_settings[buttontcolor]".';'; }  ?> }

		</style>
	<?php }
}