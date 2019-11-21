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
	
	public function pincode_field() { ?>
	   <?php global $table_prefix, $wpdb,$woocommerce; 
			if( isset( $_COOKIE['valid_pincode'] ) ) {
				
				$cookie_pin = isset($_COOKIE['valid_pincode'])?sanitize_text_field( $_COOKIE['valid_pincode'] ):'';
				
			}
			else
			{
				
				$cookie_pin = '';
				
			}
			
			$num_rows = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".$table_prefix."pincode_checker` where `pincode` = %s" , $cookie_pin ) );
	
			if($num_rows == 0)
			{

				$cookie_pin = '';

			}
			
			if(isset($cookie_pin) && $cookie_pin != '') {
				
				//$qry22 = $wpdb->get_results( "SELECT * FROM `".$table_prefix."pincode_setting_p` ORDER BY `id` ASC  limit 1" ,ARRAY_A);
				
				$query = " SELECT * FROM `".$table_prefix."pincode_checker` where `pincode` = '$cookie_pin' ";
			

				$getdata = $wpdb->get_results( $query );
				foreach($getdata as $data){

				    $delivery_day     =  $data->delivery_days;
					$cash_on_delivery =  $data->case_on_delivery;

				}
                
				$delivery_date = date("D, jS M", strtotime("+ $delivery_day day"));

				$customer = new WC_Customer();

				$customer->set_shipping_postcode( $cookie_pin );
				
				$user_ID = get_current_user_id();
				
				if(isset($user_ID) && $user_ID != 0) {
					
					update_user_meta($user_ID, 'shipping_postcode', $cookie_pin); //for setting shipping postcode
					
				}

				?>
				<div style="clear:both;font-size:14px;" class="wc-delivery-time-response">
					
				<span class='avlpin' id='avlpin'><p><?php esc_html_e('Available at','woo-pincode-checker'); ?> <?php echo esc_html( $cookie_pin ); ?></p><a class="button" id='change_pin'><?php esc_html_e('change','pho-pincode-zipcode-cod'); ?></a></span>

				<div class="pin_div" id="my_custom_checkout_field2" style="display:none;">

						<div class="error_pin" id="error_pin" style="display:none"><?php esc_html_e('Oops! We are not currently servicing your area1.','woo-pincode-checker'); ?></div>

						<p id="pincode_field_idp" class="form-row my-field-class form-row-wide">

							<label class="" for="pincode_field_id"><?php esc_html_e('Check Availability At','woo-pincode-checker'); ?></label>

							<input type="text" required="required" value="<?php echo esc_html( $cookie_pin ); ?>" placeholder="Enter Your Pincode" id="pincode_field_id" name="pincode_field" class="input-text" />

							<a class="button" id="checkpin"><?php esc_html_e('Check','woo-pincode-checker'); ?></a>

							
						</p>
				</div>
				
				
				<div class="delivery-info-wrap">

					<div class="delivery-info">

							<div class="header">

								<span><h6><?php esc_html_e('Delivered By','woo-pincode-checker'); ?></h6></span>
								
								<?php
									/* if($qry22[0]['del_date'] == 1){
										?>
										<a id="delivery_help_a" class="delivery-help-icon">?</a>
										
										<div class="delivery_help_text_main width_class" style="display:none">
										
											<a id="delivery_help_x" class="delivery-help-cross">x</a>
												
											<div class="delivery_help_text width_class" >
																	
																			
												<?php
												
													echo esc_html( $qry22[0]['del_help_text'] );
												
												?>
											
											</div>
										
										</div>
										<?php
									} */
								?>
														
								<div class="delivery">
		
									<ul class="ul-disc">
		
										<li>
		
											<?php echo esc_html( $delivery_date ); ?>
		
										</li>
		
									</ul>
		
								</div>
								
								<?php if( $cash_on_delivery == 1 ) { ?>
									<div class="cash_on_delivery"><?php esc_html_e('Cash On Delivery Available','woo-pincode-checker'); ?></div>
							    <?php } ?>
							</div>

					</div>

				 </div>

				</div>

				<?php

			}
			else
			{
				$qry22 = $wpdb->get_results( "SELECT * FROM `".$table_prefix."pincode_checker` ORDER BY `id` ASC  limit 1" ,ARRAY_A);	
				
				?>

				<div class="pin_div" id="my_custom_checkout_field">
					
						<div class="error_pin" id="error_pin" style="display:none"><?php esc_html_e('Oops! We are not currently servicing your area2.','woo-pincode-checker'); ?></div>

						<p id="pincode_field_idp" class="form-row my-field-class form-row-wide">

							<label class="" for="pincode_field_id"><?php esc_html_e('Check Availability At','woo-pincode-checker'); ?></label>

							<input type="text" required="required" value="" placeholder="Enter Your Pincode" id="pincode_field_id" name="pincode_field" class="input-text" />

							<a class="button" id="checkpin"><?php esc_html_e('Check','woo-pincode-checker'); ?></a>

								
						</p>

				</div>

				<?php

			}
			   
	} 	
	
	
	/* set picode in cookie */
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
		exit;
	}
}