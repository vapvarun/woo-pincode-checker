<?php
/**
 * Bulk Add Pincodes by Pattern or Range
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin/partials
 * @since      1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize pattern handler
$pattern_handler = new Woo_Pincode_Pattern_Handler();
$wpc_message = '';
$message_type = '';

// Handle form submission
if ( isset( $_POST['wpc-bulk-add-submit'] ) && !empty( $_POST['wpc-bulk-add-submit'] ) ) {
	// Verify nonce
	if ( ! isset( $_POST['wpc-bulk-add-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpc-bulk-add-nonce'] ) ), 'wpc-bulk-add' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'pincode-checker-for-woocommerce' ) );
	}

	$pattern = isset( $_POST['wpc-pattern'] ) ? sanitize_text_field( wp_unslash( $_POST['wpc-pattern'] ) ) : '';
	$city = isset( $_POST['wpc-city'] ) ? sanitize_text_field( wp_unslash( $_POST['wpc-city'] ) ) : '';
	$state = isset( $_POST['wpc-state'] ) ? sanitize_text_field( wp_unslash( $_POST['wpc-state'] ) ) : '';
	$delivery_days = isset( $_POST['wpc-delivery-days'] ) ? intval( wp_unslash( $_POST['wpc-delivery-days'] ) ) : 1;
	$shipping_amount = isset( $_POST['wpc_shipping_amount'] ) ? floatval( wp_unslash( $_POST['wpc_shipping_amount'] ) ) : 0;
	$cod_enabled = isset( $_POST['wpc-case-on-delivery'] ) ? 1 : 0;
	$cod_amount = isset( $_POST['wpc_case_on_delivery_amount'] ) ? floatval( wp_unslash( $_POST['wpc_case_on_delivery_amount'] ) ) : 0;
	$auto_geocode = isset( $_POST['wpc-auto-geocode'] ) ? 1 : 0;

	// Validate required fields (city/state not required if auto-geocoding)
	if ( empty( $pattern ) ) {
		$message_type = 'error';
		$wpc_message = __( 'Pattern is required.', 'pincode-checker-for-woocommerce' );
	} elseif ( ! $auto_geocode && ( empty( $city ) || empty( $state ) ) ) {
		$message_type = 'error';
		$wpc_message = __( 'City and State are required when not using auto-detection.', 'pincode-checker-for-woocommerce' );
	} else {
		// Parse pattern to get pincodes
		$pincodes = $pattern_handler->parse_pattern( $pattern );

		if ( is_wp_error( $pincodes ) ) {
			$message_type = 'error';
			$wpc_message = $pincodes->get_error_message();
		} else {
			global $wpdb;
			$table_name = $wpdb->prefix . 'pincode_checker';

			// Check if operation is too large
			$total_pincodes = count( $pincodes );
			if ( $total_pincodes > 1000 ) {
				$message_type = 'error';
				$wpc_message = sprintf(
					__( 'Pattern generates %d pincodes. Please limit to 1000 pincodes per operation to avoid timeouts.', 'pincode-checker-for-woocommerce' ),
					$total_pincodes
				);
			} else {
				// Increase timeout for large operations
				if ( $total_pincodes > 100 ) {
					set_time_limit( 300 ); // 5 minutes
				}

				$added = 0;
				$skipped = 0;
				$errors = 0;
				$geocoded = 0;

				// Initialize nearby handler for geocoding
				$nearby_handler = new Woo_Pincode_Nearby_Suggestions();

				foreach ( $pincodes as $pincode ) {
					// Check if pincode already exists
					$existing = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s",
						$pincode
				) );

				if ( $existing > 0 ) {
					$skipped++;
					continue;
				}

				// Determine city and state
				$pincode_city = $city;
				$pincode_state = $state;
				$latitude = null;
				$longitude = null;

				if ( $auto_geocode ) {
					// Auto-detect city/state from pincode using geocoding
					$geocode_data = $nearby_handler->geocode_pincode_with_details( $pincode );

					if ( $geocode_data && isset( $geocode_data['latitude'], $geocode_data['longitude'] ) ) {
						$latitude = $geocode_data['latitude'];
						$longitude = $geocode_data['longitude'];
						$pincode_city = ! empty( $geocode_data['city'] ) ? $geocode_data['city'] : $city;
						$pincode_state = ! empty( $geocode_data['state'] ) ? $geocode_data['state'] : $state;
						$geocoded++;
					}
				}

				// Insert pincode
				$result = $wpdb->insert(
					$table_name,
					array(
						'pincode'          => $pincode,
						'city'             => $pincode_city,
						'state'            => $pincode_state,
						'delivery_days'    => $delivery_days,
						'shipping_amount'  => $shipping_amount,
						'case_on_delivery' => $cod_enabled,
						'cod_amount'       => $cod_amount,
						'latitude'         => $latitude,
						'longitude'        => $longitude,
						'created_at'       => current_time( 'mysql' ),
						'updated_at'       => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%d', '%f', '%d', '%f', '%f', '%f', '%s', '%s' )
				);

				if ( $result ) {
					$added++;
				} else {
					$errors++;
				}
				}

				$message_type = 'updated';
				if ( $auto_geocode ) {
					$wpc_message = sprintf(
						__( 'Bulk add completed! Added: %d, Skipped: %d, Errors: %d, Auto-geocoded: %d', 'pincode-checker-for-woocommerce' ),
						$added,
						$skipped,
						$errors,
						$geocoded
					);
				} else {
					$wpc_message = sprintf(
						__( 'Bulk add completed! Added: %d, Skipped (already exists): %d, Errors: %d', 'pincode-checker-for-woocommerce' ),
						$added,
						$skipped,
						$errors
					);
				}
			}
		}
	}
}
?>

<div class="wrap wpc-bulk-add-wrap">
	<h2><?php esc_html_e( 'Bulk Add Pincodes by Pattern or Range', 'pincode-checker-for-woocommerce' ); ?></h2>

	<?php if ( ! empty( $wpc_message ) ) : ?>
		<div class="<?php echo esc_attr( $message_type ); ?> below-h2 notice is-dismissible" id="message">
			<p><?php echo wp_kses_post( $wpc_message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpc-bulk-add-section">
		<p class="description">
			<?php esc_html_e( 'Add multiple pincodes at once using patterns or ranges. All generated pincodes will have the same settings (city, state, delivery days, etc.).', 'pincode-checker-for-woocommerce' ); ?>
		</p>

		<!-- Help Box -->
		<div class="wpc-pattern-help" style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px; margin: 20px 0;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Pattern Examples:', 'pincode-checker-for-woocommerce' ); ?></h3>
			<ul style="margin-left: 20px;">
				<?php foreach ( Woo_Pincode_Pattern_Handler::get_pattern_examples() as $example ) : ?>
					<li>
						<code style="background: #fff; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html( $example['pattern'] ); ?></code>
						- <?php echo esc_html( $example['description'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p style="margin-bottom: 0;">
				<strong><?php esc_html_e( 'Note:', 'pincode-checker-for-woocommerce' ); ?></strong>
				<?php esc_html_e( 'Use * for multiple digits, ? for single digit. Maximum 10,000 pincodes per pattern.', 'pincode-checker-for-woocommerce' ); ?>
			</p>
			<p style="margin-top: 10px; margin-bottom: 0; padding: 10px; background: #fffbcc; border-left: 4px solid #ffb900;">
				<strong><?php esc_html_e( 'Tip:', 'pincode-checker-for-woocommerce' ); ?></strong>
				<?php esc_html_e( 'When using patterns that span multiple cities (e.g., 110*** covers different areas of Delhi), enable "Auto-Detect City/State" below to automatically fetch the correct city/state for each pincode using free geocoding.', 'pincode-checker-for-woocommerce' ); ?>
			</p>
		</div>

		<form action="" method="post" name="wpc-bulk-add-form" id="wpc-bulk-add-form">
			<table class="form-table">
				<tbody>
					<tr>
						<th>
							<label for="wpc-pattern">
								<?php esc_html_e( 'Pattern or Range', 'pincode-checker-for-woocommerce' ); ?>
								<span class="required">*</span>
							</label>
						</th>
						<td>
							<input type="text"
								   required="required"
								   class="regular-text"
								   id="wpc-pattern"
								   name="wpc-pattern"
								   placeholder="<?php esc_attr_e( 'e.g., 110*** or 110001-110099', 'pincode-checker-for-woocommerce' ); ?>"
								   style="width: 400px;">
							<p class="description">
								<?php esc_html_e( 'Enter a pattern (with * or ?) or a range (START-END)', 'pincode-checker-for-woocommerce' ); ?>
							</p>
							<div id="wpc-pattern-preview" style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; display: none;">
								<strong><?php esc_html_e( 'Preview:', 'pincode-checker-for-woocommerce' ); ?></strong>
								<span id="wpc-pattern-preview-text"></span>
							</div>
						</td>
					</tr>
					<tr>
						<th>
							<label for="wpc-city">
								<?php esc_html_e( 'City', 'pincode-checker-for-woocommerce' ); ?>
								<span class="required">*</span>
							</label>
						</th>
						<td>
							<input type="text"
								   required="required"
								   class="regular-text"
								   id="wpc-city"
								   name="wpc-city"
								   placeholder="<?php esc_attr_e( 'Enter city name', 'pincode-checker-for-woocommerce' ); ?>">
							<p class="description">
								<?php esc_html_e( 'All generated pincodes will have this city', 'pincode-checker-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="wpc-state">
								<?php esc_html_e( 'State', 'pincode-checker-for-woocommerce' ); ?>
								<span class="required">*</span>
							</label>
						</th>
						<td>
							<input type="text"
								   required="required"
								   class="regular-text"
								   id="wpc-state"
								   name="wpc-state"
								   placeholder="<?php esc_attr_e( 'Enter state name', 'pincode-checker-for-woocommerce' ); ?>">
							<p class="description">
								<?php esc_html_e( 'All generated pincodes will have this state', 'pincode-checker-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="wpc-auto-geocode">
								<?php esc_html_e( 'Auto-Detect City/State', 'pincode-checker-for-woocommerce' ); ?>
							</label>
						</th>
						<td>
							<input type="checkbox"
								   id="wpc-auto-geocode"
								   name="wpc-auto-geocode"
								   value="1">
							<label for="wpc-auto-geocode">
								<?php esc_html_e( 'Automatically detect city and state for each pincode using geocoding (free)', 'pincode-checker-for-woocommerce' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, the system will use OpenStreetMap to determine the actual city and state for each pincode. This is slower but more accurate. City and State fields above become optional fallbacks.', 'pincode-checker-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="wpc-delivery-days">
								<?php esc_html_e( 'Delivery Days', 'pincode-checker-for-woocommerce' ); ?>
								<span class="required">*</span>
							</label>
						</th>
						<td>
							<input type="number"
								   min="1"
								   max="365"
								   required="required"
								   class="small-text"
								   id="wpc-delivery-days"
								   name="wpc-delivery-days"
								   value="1">
							<p class="description">
								<?php esc_html_e( 'Number of days for delivery (1-365)', 'pincode-checker-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="wpc-shipping-amount">
								<?php esc_html_e( 'Shipping Amount', 'pincode-checker-for-woocommerce' ); ?>
							</label>
						</th>
						<td>
							<input type="number"
								   step="0.01"
								   min="0"
								   class="regular-text"
								   id="wpc-shipping-amount"
								   name="wpc_shipping_amount"
								   value="0">
							<p class="description">
								<?php esc_html_e( 'Shipping cost for these pincodes', 'pincode-checker-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<label for="wpc-case-on-delivery">
								<?php esc_html_e( 'Cash on Delivery', 'pincode-checker-for-woocommerce' ); ?>
							</label>
						</th>
						<td>
							<input type="checkbox"
								   id="wpc-case-on-delivery"
								   name="wpc-case-on-delivery"
								   value="1">
							<label for="wpc-case-on-delivery">
								<?php esc_html_e( 'Enable COD for these pincodes', 'pincode-checker-for-woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<label for="wpc-cod-amount">
								<?php esc_html_e( 'COD Amount', 'pincode-checker-for-woocommerce' ); ?>
							</label>
						</th>
						<td>
							<input type="number"
								   step="0.01"
								   min="0"
								   class="regular-text"
								   id="wpc-cod-amount"
								   name="wpc_case_on_delivery_amount"
								   value="0">
							<p class="description">
								<?php esc_html_e( 'Additional charge for COD', 'pincode-checker-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php wp_nonce_field( 'wpc-bulk-add', 'wpc-bulk-add-nonce' ); ?>
			<?php submit_button( esc_html__( 'Generate & Add Pincodes', 'pincode-checker-for-woocommerce' ), 'primary', 'wpc-bulk-add-submit' ); ?>
		</form>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Live preview of pattern
	let previewTimeout;
	$('#wpc-pattern').on('input', function() {
		clearTimeout(previewTimeout);
		const pattern = $(this).val().trim();
		const $preview = $('#wpc-pattern-preview');
		const $previewText = $('#wpc-pattern-preview-text');

		if (pattern.length < 3) {
			$preview.hide();
			return;
		}

		previewTimeout = setTimeout(function() {
			// Make AJAX request to preview
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpc_preview_pattern',
					pattern: pattern,
					nonce: '<?php echo esc_js( wp_create_nonce( 'wpc-preview-pattern' ) ); ?>'
				},
				success: function(response) {
					if (response.success) {
						$previewText.html('This will generate <strong>' + response.data.count + '</strong> pincode(s)');
						$preview.show();
					} else {
						$previewText.html('<span style="color: #d63638;">' + response.data.message + '</span>');
						$preview.show();
					}
				},
				error: function() {
					$preview.hide();
				}
			});
		}, 500);
	});

	// Toggle city/state required attribute based on auto-geocode checkbox
	$('#wpc-auto-geocode').on('change', function() {
		const isAutoGeocode = $(this).is(':checked');
		const $cityField = $('#wpc-city');
		const $stateField = $('#wpc-state');
		const $cityLabel = $('label[for="wpc-city"] .required');
		const $stateLabel = $('label[for="wpc-state"] .required');

		if (isAutoGeocode) {
			// Remove required attribute and visual indicator
			$cityField.removeAttr('required').css('border-color', '');
			$stateField.removeAttr('required').css('border-color', '');
			$cityLabel.hide();
			$stateLabel.hide();

			// Update description text
			$cityField.next('.description').html('<?php echo esc_js( __( 'Optional fallback if geocoding fails', 'pincode-checker-for-woocommerce' ) ); ?>');
			$stateField.next('.description').html('<?php echo esc_js( __( 'Optional fallback if geocoding fails', 'pincode-checker-for-woocommerce' ) ); ?>');
		} else {
			// Add required attribute and visual indicator back
			$cityField.attr('required', 'required');
			$stateField.attr('required', 'required');
			$cityLabel.show();
			$stateLabel.show();

			// Restore original description text
			$cityField.next('.description').html('<?php echo esc_js( __( 'All generated pincodes will have this city', 'pincode-checker-for-woocommerce' ) ); ?>');
			$stateField.next('.description').html('<?php echo esc_js( __( 'All generated pincodes will have this state', 'pincode-checker-for-woocommerce' ) ); ?>');
		}
	});
});
</script>

<style>
.wpc-bulk-add-wrap .required {
	color: #d63638;
}
.wpc-bulk-add-section {
	background: #fff;
	padding: 20px;
	margin: 20px 0;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
}
</style>
