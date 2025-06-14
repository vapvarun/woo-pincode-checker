<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin/partials
 */

/* add Wp-color-picker */
wp_enqueue_script( 'wp-color-picker' );
wp_enqueue_style( 'wp-color-picker' );

global $wpc_globals;
global $wp_roles;
$general_settings                   = $wpc_globals->wpc_general_settings;
$products_categories                = wpc_get_wc_categories();
$general_settings['date_display']   = isset( $general_settings['date_display'] ) ? $general_settings['date_display'] : '';
$general_settings['pincode_field']  = isset( $general_settings['pincode_field'] ) ? $general_settings['pincode_field'] : '';
$general_settings['delivery_date']  = isset( $general_settings['delivery_date'] ) ? $general_settings['delivery_date'] : '';
$general_settings['textcolor']      = isset( $general_settings['textcolor'] ) ? $general_settings['textcolor'] : '';
$general_settings['buttoncolor']    = isset( $general_settings['buttoncolor'] ) ? $general_settings['buttoncolor'] : '';
$general_settings['buttontcolor']   = isset( $general_settings['buttontcolor'] ) ? $general_settings['buttontcolor'] : '';
$class                              = '';
if ( false == $general_settings['date_display'] ) {
	$class = 'hide';
}
?>
<div class="wbcom-tab-content">
	<div class="wbcom-wrapper-admin">
		
		<div class="wbcom-admin-title-section">
			<?php settings_errors(); ?>
			<h3><?php esc_html_e( 'General Settings', 'woo-pincode-checker' ); ?></h3>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpc_general_settings' );
				do_settings_sections( 'wpc_general_settings' );
				?>
				<div class="form-table">
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Show Delivery Date', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable this option to display the estimated delivery date on the product page.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label class="wb-switch">
									<input type="checkbox" id="wpc_date_display" name="wpc_general_settings[date_display]" <?php checked( esc_attr( $general_settings['date_display'] ), 'on' ); ?> value="on">
									<div class="wb-slider wb-round"></div>
								</label>
							</div>
							
						</div>
						<div id="wbwss-wpc-deliver-date" class="wbcom-settings-section-wrap <?php echo esc_attr( $class ); ?>"
							<?php
							if ( ! isset( $general_settings['date_display'] ) ) {
							?>
							style="display:none" <?php } ?>>
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Select Delivery Date Format', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Choose the format in which the delivery date will be displayed to users.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options"
							<?php
							if ( ! isset( $general_settings['date_display'] ) ) {
							?>
							style="display:none" <?php } ?>>
								<label class="wpc-delivery_date">
									<select id="wpc_delivery_date" name="wpc_general_settings[delivery_date]" >
										<option value=""><?php esc_html_e( 'Select Delivery Date Format', 'woo-pincode-checker' ); ?></option>
										<option value="M jS" <?php selected( $general_settings['delivery_date'], 'M jS' ); ?>><?php esc_html_e( 'M jS - July 1st', 'woo-pincode-checker' ); ?></option>
										<option value="D, jS M" <?php selected( $general_settings['delivery_date'], 'D, jS M' ); ?>><?php esc_html_e( 'D, jS M  – Mon, 25th Nov', 'woo-pincode-checker' ); ?></option>
										<option value="D, M d" <?php selected( $general_settings['delivery_date'], 'D, M d' ); ?>><?php esc_html_e( 'D, M d  – Sat, Nov 23', 'woo-pincode-checker' ); ?></option>
										<option value="M d" <?php selected( $general_settings['delivery_date'], 'M d' ); ?>><?php esc_html_e( 'M d  – Nov 23', 'woo-pincode-checker' ); ?></option>
									</select>
								</label>
							</div>
						</div>
						<div id="wbwss-wpc-deliver-date-text" class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Delivery Date Label', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Customize the label shown before the estimated delivery date.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label>
									<input type="text"  name="wpc_general_settings[delivery_date_label_text]" value="<?php echo ( isset( $general_settings['delivery_date_label_text'] ) ) ? esc_attr( $general_settings['delivery_date_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Delivery Date', 'woo-pincode-checker' ); ?>">
								</label>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Make pincode field required', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Make the pincode input field mandatory before the customer can proceed with adding the product to the cart.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label class="wb-switch">
									<input type="checkbox" id="wpc_pincode_field" name="wpc_general_settings[pincode_field]" <?php checked( esc_attr( $general_settings['pincode_field'] ), 'on' ); ?> value="on">
									<div class="wb-slider wb-round"></div>
								</label>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Add to cart button behavior for unavailable pincodes', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Choose how the Add to Cart button should behave when the entered pincode is not serviceable.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label>
									<select id="wpc_add_to_cart_option" name="wpc_general_settings[add_to_cart_option]" >
										<option value="add_to_cart_hide"<?php selected( $general_settings['add_to_cart_option'], 'add_to_cart_hide' ); ?>><?php esc_html_e( 'Hide', 'woo-pincode-checker' ); ?></option>
										<option value="add_to_cart_disable"<?php selected( $general_settings['add_to_cart_option'], 'add_to_cart_disable' ); ?>><?php esc_html_e( 'Disabled', 'woo-pincode-checker' ); ?></option>										
									</select>
								</label>
							</div>
						</div>
						
						<div class="wbcom-settings-section-wrap wcpq-pro-products">
							<div class="wbcom-settings-section-options-heading">
								<label for="blogname">
									<?php esc_html_e( 'Exclude category for shipping availability', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Choose how the Add to Cart button should behave when the entered pincode is not serviceable.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<select id="wpc-exclude-category"  name="wpc_general_settings[categories_for_shipping][]" multiple>
									<?php if ( ! empty( $products_categories ) ) { ?>
										<?php
										foreach ( $products_categories as $products_category ) {

											$selected = '';
											if ( isset( $general_settings['categories_for_shipping'] ) && in_array( $products_category->term_id, $general_settings['categories_for_shipping'] ) ) {
												$selected = 'selected';
											}
											?>
											<option value="<?php echo esc_attr( $products_category->term_id ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $products_category->name ); ?></option>
										<?php } ?>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Pincode Availability Check Position', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Choose where the pincode availability check box should appear on the product page.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label>
									<select id="wpc_pincode_position" name="wpc_general_settings[pincode_position]" >
										<option value="woocommerce_before_add_to_cart_button"<?php selected( $general_settings['pincode_position'], 'woocommerce_before_add_to_cart_button' ); ?>><?php esc_html_e( 'Before Add to Cart button', 'woo-pincode-checker' ); ?></option>
										<option value="woocommerce_after_add_to_cart_button"<?php selected( $general_settings['pincode_position'], 'woocommerce_after_add_to_cart_button' ); ?>><?php esc_html_e( 'After Add to Cart Button', 'woo-pincode-checker' ); ?></option>
										<option value="woocommerce_after_add_to_cart_quantity"<?php selected( $general_settings['pincode_position'], 'woocommerce_after_add_to_cart_quantity' ); ?>><?php esc_html_e( 'After Add to Cart Quantity', 'woo-pincode-checker' ); ?></option>
										<option value="wpc_pincode_checker"<?php selected( $general_settings['pincode_position'], 'wpc_pincode_checker' ); ?>><?php esc_html_e( 'Use Shortcode', 'woo-pincode-checker' ); ?></option>
									</select>
								</label>
								<p class="wpc-display-shortcode-note" style="
								<?php
								if ( 'wpc_pincode_checker' === $general_settings['pincode_position'] ) {
									echo 'display:none;'; }
								?>
								">
									<label><?php esc_html_e( 'Note :', 'woo-pincode-checker' ); ?>
										<?php esc_html_e( 'You can use shortcode [wpc_pincode_checker] to place it anywhere you like to use in website and select "Use Shortcode" in above select option.', 'woo-pincode-checker' ); ?>
									</label>
								</p>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Show Cash on Delivery Option', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable this setting to display the Cash on Delivery (COD) option if it’s available for the entered pincode.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label class="wb-switch">
									<input type="checkbox"  name="wpc_general_settings[cod_display]" <?php checked( esc_attr( $general_settings['cod_display'] ), 'on' ); ?> value="on">
									<div class="wb-slider wb-round"></div>
								</label>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Check Availability Button Text', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enter the text to display on the button used to check delivery availability.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label>
									<input type="text"  name="wpc_general_settings[check_btn_text]" value="<?php echo ( isset( $general_settings['check_btn_text'] ) ) ? esc_attr( $general_settings['check_btn_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Check', 'woo-pincode-checker' ); ?>">
								</label>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Change Pincode Button Text', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enter the text to display on the button that allows users to re-enter or change the pincode.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label>
									<input type="text"  name="wpc_general_settings[change_btn_text]" value="<?php echo ( isset( $general_settings['change_btn_text'] ) ) ? esc_attr( $general_settings['change_btn_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Change', 'woo-pincode-checker' ); ?>">
								</label>
							</div>
						</div>						
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Cash on Delivery label text', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Customize the label shown for the Cash on Delivery option', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label>
									<input type="text"  name="wpc_general_settings[cod_label_text]" value="<?php echo ( isset( $general_settings['cod_label_text'] ) ) ? esc_attr( $general_settings['cod_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Cash On Delivery', 'woo-pincode-checker' ); ?>">
								</label>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Pincode Check Label', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Set the label text shown before the pincode input field.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label>
									<input type="text"  name="wpc_general_settings[availability_label_text]" value="<?php echo ( isset( $general_settings['availability_label_text'] ) ) ? esc_attr( $general_settings['availability_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Available at', 'woo-pincode-checker' ); ?>">
								</label>
							</div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Pincode Label Text Color', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Choose the text color for the pincode label displayed on the product page.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options"><input type="text" name="wpc_general_settings[textcolor]" class="regular-text" id="textcolor" value="<?php echo esc_attr( $general_settings['textcolor'] ); ?>"></div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Check Button Background Color', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Select the background color of the "Check" button.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options"><input type="text" name="wpc_general_settings[buttoncolor]" class="regular-text" id="buttoncolor" value="<?php echo esc_attr( $general_settings['buttoncolor'] ); ?>"></div>
						</div>
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label>
									<?php esc_html_e( 'Select Button Text Color', 'woo-pincode-checker' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Select the text color for the "Check" button.', 'woo-pincode-checker' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options"><input type="text" class="regular-text" id="buttontcolor" name="wpc_general_settings[buttontcolor]" value="<?php echo esc_attr( $general_settings['buttontcolor'] ); ?>"></div>
						</div>
					</div>
				<?php submit_button();
				?>
			</form>
		</div>
		<script>

			jQuery(document).ready(function( ) {
			jQuery("#textcolor").wpColorPicker();
			jQuery("#buttoncolor").wpColorPicker();
			jQuery("#buttontcolor").wpColorPicker();
			});

		</script>
	</div>
</div>
