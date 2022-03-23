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
$general_settings                  = $wpc_globals->wpc_general_settings;
$products_categories               = wpc_get_wc_categories();
$general_settings['date_display']  = isset( $general_settings['date_display'] ) ? $general_settings['date_display'] : '';
$general_settings['delivery_date'] = isset( $general_settings['delivery_date'] ) ? $general_settings['delivery_date'] : '';
$general_settings['textcolor']     = isset( $general_settings['textcolor'] ) ? $general_settings['textcolor'] : '';
$general_settings['buttoncolor']   = isset( $general_settings['buttoncolor'] ) ? $general_settings['buttoncolor'] : '';
$general_settings['buttontcolor']  = isset( $general_settings['buttontcolor'] ) ? $general_settings['buttontcolor'] : '';
$class                             = '';
if ( false == $general_settings['date_display'] ) {
	$class = 'hide';
}
?>
<div class="wbcom-tab-content">
	<form method="post" action="options.php">
		<?php
		settings_fields( 'wpc_general_settings' );
		do_settings_sections( 'wpc_general_settings' );
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Show Delivery Date', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" id="wpc_date_display" name="wpc_general_settings[date_display]" <?php checked( esc_attr( $general_settings['date_display'] ), 'on' ); ?> value="on">
							<div class="wb-slider wb-round"></div>
						</label>
					</td>
				</tr>
				<tr id="wbwss-wpc-deliver-date" class="<?php echo esc_attr( $class ); ?>"
				<?php
				if ( ! isset( $general_settings['date_display'] ) ) {
					?>
  style="display:none" <?php } ?>>
					<th scope="row">
						<label><?php esc_html_e( 'Select Delivery Date Format', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wpc-delivery_date">
							<select id="wpc_delivery_date" name="wpc_general_settings[delivery_date]" >
								<option value=""><?php esc_html_e( 'Select Delivery Date Format', 'woo-pincode-checker' ); ?></option>
								<option value="M jS" <?php selected( $general_settings['delivery_date'], 'M jS' ); ?>><?php esc_html_e( 'M jS - July 1st', 'woo-pincode-checker' ); ?></option>
								<option value="D, jS M" <?php selected( $general_settings['delivery_date'], 'D, jS M' ); ?>><?php esc_html_e( 'D, jS M  – Mon, 25th Nov', 'woo-pincode-checker' ); ?></option>
								<option value="D, M d" <?php selected( $general_settings['delivery_date'], 'D, M d' ); ?>><?php esc_html_e( 'D, M d  – Sat, Nov 23', 'woo-pincode-checker' ); ?></option>
								<option value="M d" <?php selected( $general_settings['delivery_date'], 'M d' ); ?>><?php esc_html_e( 'M d  – Nov 23', 'woo-pincode-checker' ); ?></option>
							</select>
						</label>
					</td>
				</tr>
				<tr class="wcpq-pro-products">
					<th scope="row"><label for="blogname"><?php esc_html_e( 'Exclude category for shipping availbility', 'woo-pincode-checker' ); ?></label></th>
					<td>
						<select id="wpc-exclude-category"  name="wpc_general_settings[categories_for_shipping][]" multiple>
							<?php if ( ! empty( $products_categories ) ) { ?>
								<?php
								foreach ( $products_categories as $products_category ) {

									$selected = '';
									if ( isset( $general_settings['categories_for_shipping'] ) && in_array( $products_category->term_id, $general_settings['categories_for_shipping'] ) ) {
										$selected = 'selected';
									}
									?>
									<option value="<?php echo esc_attr( $products_category->term_id ); ?>" <?php echo esc_html( $selected ); ?>><?php echo esc_html( $products_category->name ); ?></option>
								<?php } ?>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Pincode Availability Check Position', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
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
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Show Shipping Cost', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wb-switch">
							<input type="checkbox"  name="wpc_general_settings[shipping_cost]" <?php checked( esc_attr( $general_settings['shipping_cost'] ), 'on' ); ?> value="on">
							<div class="wb-slider wb-round"></div>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Show COD Cost', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wb-switch">
							<input type="checkbox"  name="wpc_general_settings[cod_cost]" <?php checked( esc_attr( $general_settings['cod_cost'] ), 'on' ); ?> value="on">
							<div class="wb-slider wb-round"></div>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Show Cash On Delivery Option', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wb-switch">
							<input type="checkbox"  name="wpc_general_settings[cod_display]" <?php checked( esc_attr( $general_settings['cod_display'] ), 'on' ); ?> value="on">
							<div class="wb-slider wb-round"></div>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Hide Add to cart button on Shop Page', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wb-switch">
							<input type="checkbox"  name="wpc_general_settings[hide_shop_btn]" <?php checked( esc_attr( $general_settings['hide_shop_btn'] ), 'on' ); ?> value="on">
							<div class="wb-slider wb-round"></div>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Hide Add to cart Button on Single Product Page', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wb-switch">
							<input type="checkbox"  name="wpc_general_settings[hide_product_page_btn]" <?php checked( esc_attr( $general_settings['hide_product_page_btn'] ), 'on' ); ?> value="on">
							<div class="wb-slider wb-round"></div>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Check Button Text', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label>
							<input type="text"  name="wpc_general_settings[check_btn_text]" value="<?php echo ( isset( $general_settings['check_btn_text'] ) ) ? esc_html( $general_settings['check_btn_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Check', 'woo-pincode-checker' ); ?>">
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Change Button Text', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label>
							<input type="text"  name="wpc_general_settings[change_btn_text]" value="<?php echo ( isset( $general_settings['change_btn_text'] ) ) ? esc_html( $general_settings['change_btn_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Change', 'woo-pincode-checker' ); ?>">
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Delivery Date Text', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label>
							<input type="text"  name="wpc_general_settings[delivery_date_label_text]" value="<?php echo ( isset( $general_settings['delivery_date_label_text'] ) ) ? esc_html( $general_settings['delivery_date_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Delivery Date', 'woo-pincode-checker' ); ?>">
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Cash On Delivery label text', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label>
							<input type="text"  name="wpc_general_settings[cod_label_text]" value="<?php echo ( isset( $general_settings['cod_label_text'] ) ) ? esc_html( $general_settings['cod_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Cash On Delivery', 'woo-pincode-checker' ); ?>">
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Check Availability At label Text', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label>
							<input type="text"  name="wpc_general_settings[availability_label_text]" value="<?php echo ( isset( $general_settings['availability_label_text'] ) ) ? esc_html( $general_settings['availability_label_text'] ) : ''; ?>" placeholder="<?php esc_html_e( 'Available at', 'woo-pincode-checker' ); ?>">
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Select Pincode Label Text Color', 'woo-pincode-checker' ); ?></label>
					</th>
					<td><input type="text" name="wpc_general_settings[textcolor]" class="regular-text" id="textcolor" value="<?php echo esc_attr( $general_settings['textcolor'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Select Button Color', 'woo-pincode-checker' ); ?></label>
					</th>
					<td><input type="text" name="wpc_general_settings[buttoncolor]" class="regular-text" id="buttoncolor" value="<?php echo esc_attr( $general_settings['buttoncolor'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Select Button Text Color', 'woo-pincode-checker' ); ?></label>
					</th>
					<td><input type="text" class="regular-text" id="buttontcolor" name="wpc_general_settings[buttontcolor]" value="<?php echo esc_attr( $general_settings['buttontcolor'] ); ?>"></td>
				</tr>
				</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
	<script>

		jQuery(document).ready(function( ) {
		jQuery("#textcolor").wpColorPicker();
		jQuery("#buttoncolor").wpColorPicker();
		jQuery("#buttontcolor").wpColorPicker();
		});

	</script>
</div>
