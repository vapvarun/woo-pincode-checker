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
wp_enqueue_script('wp-color-picker');
wp_enqueue_style( 'wp-color-picker' );

global $wpc_globals;
global $wp_roles; 
$general_settings = $wpc_globals->wpc_general_settings;

$general_settings['date_display'] = isset( $general_settings['date_display'] ) ? $general_settings['date_display'] : '';
$general_settings['delivery_date'] = isset( $general_settings['delivery_date'] ) ? $general_settings['delivery_date'] : '';
$general_settings['textcolor'] = isset( $general_settings['textcolor'] ) ? $general_settings['textcolor'] : '';
$general_settings['buttoncolor'] = isset( $general_settings['buttoncolor'] ) ? $general_settings['buttoncolor'] : '';
$general_settings['buttontcolor'] = isset( $general_settings['buttontcolor'] ) ? $general_settings['buttontcolor'] : '';
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
					    <label class="wbwss-switch">
							<input type="checkbox" id="wpc_date_display" name="wpc_general_settings[date_display]" <?php checked( esc_attr( $general_settings['date_display'] ), 'on' ); ?> value="on">
							<div class="wbwss-slider wbwss-round"></div>
						</label>
					</td>
					
				</tr>
				
				<tr id="wbwss-wpc-deliver-date" <?php if ( !isset($general_settings['date_display'])) {?> style="display:none" <?php }?>>
					<th scope="row">
						<label><?php esc_html_e( 'Select Delivery Date Format', 'woo-pincode-checker' ); ?></label>
					</th>
					<td>
						<label class="wpc-delivery_date">
							<select name="wpc_general_settings[delivery_date]" >
								<option value=""><?php esc_html_e( 'Select Delivery Date Format', 'woo-pincode-checker');?></option>
								<option value="M jS" <?php selected($general_settings['delivery_date'], 'M jS');?>><?php esc_html_e( 'M jS - July 1st', 'woo-pincode-checker' ); ?></option>
								<option value="D, jS M" <?php selected($general_settings['delivery_date'], 'D, jS M');?>><?php esc_html_e( 'D, jS M  – Mon, 25th Nov', 'woo-pincode-checker' ); ?></option>
								<option value="D, M d" <?php selected($general_settings['delivery_date'], 'D, M d');?>><?php esc_html_e( 'D, M d  – Sat, Nov 23', 'woo-pincode-checker' ); ?></option>
								<option value="M d" <?php selected($general_settings['delivery_date'], 'M d');?>><?php esc_html_e( 'M d  – Nov 23', 'woo-pincode-checker' ); ?></option>								
							</select>
						</label>						
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Check Pincode Label Text Color', 'woo-pincode-checker' ); ?></label>
					</th>
					<td><input type="text" name="wpc_general_settings[textcolor]" class="regular-text" id="textcolor" value="<?php echo $general_settings['textcolor'];?>"></td>
					
				</tr>
				
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Check Button Color', 'woo-pincode-checker' ); ?></label>
					</th>
					<td><input type="text" name="wpc_general_settings[buttoncolor]" class="regular-text" id="buttoncolor" value="<?php echo $general_settings['buttoncolor'];?>"></td>
					
				</tr>
				
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Check Button Text Color', 'woo-pincode-checker' ); ?></label>
					</th>
					<td><input type="text" class="regular-text" id="buttontcolor" name="wpc_general_settings[buttontcolor]" value="<?php echo $general_settings['buttontcolor'];?>"></td>
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