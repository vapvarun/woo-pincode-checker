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
			<h3><?php esc_html_e( 'General Settings', 'pincode-checker-for-woocommerce' ); ?></h3>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpc_general_settings' );
				?>
				<div class="form-table">
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Show Delivery Date', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this option to display the estimated delivery date on the product page.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" id="wpc_date_display" name="wpc_general_settings[date_display]" <?php checked( esc_attr( $general_settings['date_display'] ), 'on' ); ?> value="on">
								<div class="wb-slider wb-round"></div>
							</label>
						</div>
					</div>
					<div id="wbwss-wpc-deliver-date" class="wbcom-settings-section-wrap <?php echo esc_attr( $class ); ?>" <?php if ( ! isset( $general_settings['date_display'] ) ) { ?>style="display:none"<?php } ?>>
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Select Delivery Date Format', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Choose the format in which the delivery date will be displayed to users.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<select id="wpc_delivery_date" name="wpc_general_settings[delivery_date]" >
								<option value=""><?php esc_html_e( 'Select Delivery Date Format', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="M jS" <?php selected( $general_settings['delivery_date'], 'M jS' ); ?>><?php esc_html_e( 'M jS - July 1st', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="D, jS M" <?php selected( $general_settings['delivery_date'], 'D, jS M' ); ?>><?php esc_html_e( 'D, jS M  – Mon, 25th Nov', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="D, M d" <?php selected( $general_settings['delivery_date'], 'D, M d' ); ?>><?php esc_html_e( 'D, M d  – Sat, Nov 23', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="M d" <?php selected( $general_settings['delivery_date'], 'M d' ); ?>><?php esc_html_e( 'M d  – Nov 23', 'pincode-checker-for-woocommerce' ); ?></option>
							</select>
						</div>
					</div>
					<div id="wbwss-wpc-deliver-date-text" class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Delivery Date Label', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Customize the label shown before the estimated delivery date.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text" name="wpc_general_settings[delivery_date_label_text]" value="<?php echo ( isset( $general_settings['delivery_date_label_text'] ) ) ? esc_attr( $general_settings['delivery_date_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Delivery Date', 'pincode-checker-for-woocommerce' ); ?>" class="regular-text">
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Make pincode field required', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Make the pincode input field mandatory before the customer can proceed with adding the product to the cart.', 'pincode-checker-for-woocommerce' ); ?></p>
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
								<?php esc_html_e( 'Add to cart button behavior for unavailable pincodes', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Choose how the Add to Cart button should behave when the entered pincode is not serviceable.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<select id="wpc_add_to_cart_option" name="wpc_general_settings[add_to_cart_option]" >
								<option value="add_to_cart_hide"<?php selected( $general_settings['add_to_cart_option'], 'add_to_cart_hide' ); ?>><?php esc_html_e( 'Hide', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="add_to_cart_disable"<?php selected( $general_settings['add_to_cart_option'], 'add_to_cart_disable' ); ?>><?php esc_html_e( 'Disabled', 'pincode-checker-for-woocommerce' ); ?></option>
							</select>
						</div>
					</div>

					<div class="wbcom-settings-section-wrap wcpq-pro-products">
						<div class="wbcom-settings-section-options-heading">
							<label for="blogname">
								<?php esc_html_e( 'Exclude category for shipping availability', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Select product categories that should be excluded from pincode availability checking. Products in these categories will not display the pincode checker.', 'pincode-checker-for-woocommerce' ); ?></p>
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
								<?php esc_html_e( 'Pincode Availability Check Position', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Choose where the pincode availability check box should appear on the product page. Note: You can use shortcode [wpc_pincode_checker] to place it anywhere by selecting "Use Shortcode" option.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<select id="wpc_pincode_position" name="wpc_general_settings[pincode_position]" >
								<option value="woocommerce_before_add_to_cart_button"<?php selected( $general_settings['pincode_position'], 'woocommerce_before_add_to_cart_button' ); ?>><?php esc_html_e( 'Before Add to Cart button', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="woocommerce_after_add_to_cart_button"<?php selected( $general_settings['pincode_position'], 'woocommerce_after_add_to_cart_button' ); ?>><?php esc_html_e( 'After Add to Cart Button', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="woocommerce_after_add_to_cart_quantity"<?php selected( $general_settings['pincode_position'], 'woocommerce_after_add_to_cart_quantity' ); ?>><?php esc_html_e( 'After Add to Cart Quantity', 'pincode-checker-for-woocommerce' ); ?></option>
								<option value="wpc_pincode_checker"<?php selected( $general_settings['pincode_position'], 'wpc_pincode_checker' ); ?>><?php esc_html_e( 'Use Shortcode', 'pincode-checker-for-woocommerce' ); ?></option>
							</select>
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Show Cash on Delivery Option', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this setting to display the Cash on Delivery (COD) option if it is available for the entered pincode.', 'pincode-checker-for-woocommerce' ); ?></p>
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
								<?php esc_html_e( 'Check Availability Button Text', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enter the text to display on the button used to check delivery availability.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text"  name="wpc_general_settings[check_btn_text]" value="<?php echo ( isset( $general_settings['check_btn_text'] ) ) ? esc_attr( $general_settings['check_btn_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Check', 'pincode-checker-for-woocommerce' ); ?>" class="regular-text">
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Change Pincode Button Text', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enter the text to display on the button that allows users to re-enter or change the pincode.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text"  name="wpc_general_settings[change_btn_text]" value="<?php echo ( isset( $general_settings['change_btn_text'] ) ) ? esc_attr( $general_settings['change_btn_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Change', 'pincode-checker-for-woocommerce' ); ?>" class="regular-text">
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Cash on Delivery label text', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Customize the label shown for the Cash on Delivery option', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text"  name="wpc_general_settings[cod_label_text]" value="<?php echo ( isset( $general_settings['cod_label_text'] ) ) ? esc_attr( $general_settings['cod_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Cash On Delivery', 'pincode-checker-for-woocommerce' ); ?>" class="regular-text">
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Pincode Check Label', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Set the label text shown before the pincode input field.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text"  name="wpc_general_settings[availability_label_text]" value="<?php echo ( isset( $general_settings['availability_label_text'] ) ) ? esc_attr( $general_settings['availability_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Available at', 'pincode-checker-for-woocommerce' ); ?>" class="regular-text">
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Pincode Label Text Color', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Choose the text color for the pincode label displayed on the product page.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options"><input type="text" name="wpc_general_settings[textcolor]" class="regular-text" id="textcolor" value="<?php echo esc_attr( $general_settings['textcolor'] ); ?>"></div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Check Button Background Color', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Select the background color of the "Check" button.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options"><input type="text" name="wpc_general_settings[buttoncolor]" class="regular-text" id="buttoncolor" value="<?php echo esc_attr( $general_settings['buttoncolor'] ); ?>"></div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label>
								<?php esc_html_e( 'Select Button Text Color', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Select the text color for the "Check" button.', 'pincode-checker-for-woocommerce' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options"><input type="text" class="regular-text" id="buttontcolor" name="wpc_general_settings[buttontcolor]" value="<?php echo esc_attr( $general_settings['buttontcolor'] ); ?>"></div>
					</div>
				</div>
				<?php submit_button(); ?>
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
