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
?>

				<span class='avlpin' id='avlpin'>
					<p>
						<?php
							/* Translators: %1$s: Availability Label   */
							echo sprintf( esc_html__( '%1$s', 'woo-pincode-checker' ), esc_html( $wpc_availability_label ) );
						?>
						<?php echo esc_html( $cookie_pin ); ?>
					</p>
					<p>
						<?php
							/* Translators: %1$s: Availability City Name   */
							echo sprintf( esc_html__( 'City: %1$s', 'woo-pincode-checker' ), esc_html( $city ) );
						?>
					</p>
					<p>
						<?php
							/* Translators: %1$s: Availability State Name   */
							echo sprintf( esc_html__( 'State: %1$s', 'woo-pincode-checker' ), esc_html( $state ) );
						?>
					</p>
					<div class="wpc_delivery-info-wrap">
					<div class="wpc-delivery-info">
						<h4>
						<?php
						/* Translators: %1$s: We are available and servicing at your location.   */
						esc_html_e( 'We are available and servicing at your location.', 'woo-pincode-checker' );
						?>
						</h4>					
						<div class="header">
							<?php if ( isset( $wpc_general_settings['date_display'] ) && $wpc_general_settings['date_display'] == 'on' ) { ?>		
							<div class="wpc-delivery-info-list">																	
									<div class="wpc-delivery-date">
										<div class="wpc-delivery-checked">
											<img src="<?php echo esc_attr( WPCP_PLUGIN_URL ) . 'public/image/check.svg'; ?>">
										</div>
										<img src="<?php echo esc_attr( WPCP_PLUGIN_URL ) . 'public/image/shipping-fast.svg'; ?>">
										<div class="wpc-delivery-date-label">
										<strong>
										<?php
											/* Translators: %1$s: Delivered By Label   */
											echo sprintf( esc_html__( '%1$s', 'woo-pincode-checker' ), esc_html( $wpc_delivery_date_label ) );
										?>
										</strong>
										<span><?php echo esc_html( $delivery_date ); ?></span>
										</div>
									</div>
									<?php
							}
							if ( true == $cash_on_delivery && true === $wpc_display_cod_option ) {
								?>
									<div class="wpc-delivery-info-list wpc_cash_delivery">
										<div class="wpc-delivery-checked">
											<img src="<?php echo esc_attr( WPCP_PLUGIN_URL ) . 'public/image/check.svg'; ?>">
										</div>
										<img src="<?php echo esc_attr( WPCP_PLUGIN_URL ) . 'public/image/hand-holding-usd.svg'; ?>">
										<div class="wpc_cash_on_delivery">
											<strong>
												<?php
													/* Translators: %1$s: Cash On Delivery Available Label   */
													echo sprintf( esc_html__( '%1$s', 'woo-pincode-checker' ), esc_html( $wpc_cod_label ) );
												?>
											</strong>
											<span><?php esc_html_e( 'Available', 'woo-pincode-checker' ); ?></span>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>

				<a class="button wpc-check-button" id='change_pin'>
					<?php
					/* Translators: %1$s: Change Button Text   */
					echo sprintf( esc_html__( '%1$s', 'woo-pincode-checker' ), esc_html( $wpc_change_btn_label ) );
					?>
				</a>
				</span>
				
			<div class="pin_div pincode_check_btn" id="my_custom_checkout_field2" style="display:none;">

					<div class="error_pin" id="error_pin" style="display:none"><?php esc_html_e( 'Oops! We are currently not servicing in your area.', 'woo-pincode-checker' ); ?></div>

					<p id="pincode_field_idp" class="form-row my-field-class form-row-wide">
						<input type="text" value="<?php echo esc_html( $cookie_pin ); ?>" placeholder="<?php esc_html_e( 'Enter Your Pincode', 'woo-pincode-checker' ); ?>" id="pincode_field_id" name="pincode_field" class="input-text" <?php echo esc_attr( $wpc_required ); ?>/>

						<a class="button wpc-check-button" id="checkpin">
							<?php
							/* Translators: %1$s: Check Button Text   */
							echo sprintf( esc_html__( '%1$s', 'woo-pincode-checker' ), esc_html( $wpc_check_btn_label ) );
							?>
						</a>
					</p>
				</div>				
