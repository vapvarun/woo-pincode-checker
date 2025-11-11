<?php
/**
 * Provide a Geocoding & Suggestions view for the plugin
 *
 * Nearby pincode suggestions and geocoding settings page.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.5.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpc_globals;
$general_settings = $wpc_globals->wpc_general_settings;
?>
<div class="wbcom-tab-content">
	<div class="wbcom-wrapper-admin">
		<div class="wbcom-admin-title-section">
			<?php settings_errors(); ?>
			<h3><?php esc_html_e( 'Geocoding & Nearby Suggestions', 'pincode-checker-for-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Show nearby serviceable pincodes when a customer\'s pincode is not available. Geocode your pincodes using free OpenStreetMap service for accurate distance-based suggestions.', 'pincode-checker-for-woocommerce' ); ?></p>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<div class="form-table">
				<div class="wbcom-settings-section-wrap">
					<div class="wbcom-settings-section-options-heading">
						<label style="font-size: 16px; font-weight: 600;">
							<?php esc_html_e( 'Geocoding Setup', 'pincode-checker-for-woocommerce' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Geocode your pincodes to enable distance-based nearby suggestions. This is a one-time setup using 100% free OpenStreetMap service.', 'pincode-checker-for-woocommerce' ); ?></p>
					</div>
					<div class="wbcom-settings-section-options">
						<div id="wpc-geocoding-container" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
							<?php
							$nearby_handler = new Woo_Pincode_Nearby_Suggestions();
							$stats = $nearby_handler->get_geocoding_stats();
							?>

							<div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
								<h4 style="margin-top: 0;"><?php esc_html_e( 'Geocoding Status', 'pincode-checker-for-woocommerce' ); ?></h4>

								<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 15px 0;">
									<div style="text-align: center; padding: 10px; background: #f0f0f1; border-radius: 3px;">
										<div style="font-size: 24px; font-weight: bold; color: #2271b1;"><?php echo esc_html( $stats['total'] ); ?></div>
										<div style="font-size: 12px; color: #666;"><?php esc_html_e( 'Total Pincodes', 'pincode-checker-for-woocommerce' ); ?></div>
									</div>
									<div style="text-align: center; padding: 10px; background: #f0f0f1; border-radius: 3px;">
										<div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo esc_html( $stats['geocoded'] ); ?></div>
										<div style="font-size: 12px; color: #666;"><?php esc_html_e( 'Geocoded', 'pincode-checker-for-woocommerce' ); ?></div>
									</div>
									<div style="text-align: center; padding: 10px; background: #f0f0f1; border-radius: 3px;">
										<div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo esc_html( $stats['remaining'] ); ?></div>
										<div style="font-size: 12px; color: #666;"><?php esc_html_e( 'Remaining', 'pincode-checker-for-woocommerce' ); ?></div>
									</div>
								</div>

								<?php if ( $stats['total'] > 0 ) : ?>
									<div style="margin: 15px 0;">
										<div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
											<div style="background: #00a32a; height: 100%; width: <?php echo esc_attr( $stats['percentage'] ); ?>%; transition: width 0.3s;"></div>
										</div>
										<p style="text-align: center; margin: 5px 0 0 0; font-size: 14px;">
											<strong><?php echo esc_html( $stats['percentage'] ); ?>%</strong> <?php esc_html_e( 'Complete', 'pincode-checker-for-woocommerce' ); ?>
										</p>
									</div>
								<?php endif; ?>

								<?php if ( $stats['remaining'] > 0 ) : ?>
									<div style="text-align: center; margin-top: 15px;">
										<button type="button" id="wpc-start-geocoding" class="button button-primary button-large">
											<span class="dashicons dashicons-location" style="margin-top: 3px;"></span>
											<?php esc_html_e( 'Start Geocoding', 'pincode-checker-for-woocommerce' ); ?>
										</button>
										<p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">
											<?php
											$estimated_time = ceil( $stats['remaining'] / 60 ); // 1 per second
											printf(
												esc_html__( 'Estimated time: %d minutes (using free OpenStreetMap service)', 'pincode-checker-for-woocommerce' ),
												$estimated_time
											);
											?>
										</p>
									</div>

									<div id="wpc-geocoding-progress" style="display: none; margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
										<p style="margin: 0 0 10px 0; font-weight: 600;"><?php esc_html_e( 'Geocoding in progress...', 'pincode-checker-for-woocommerce' ); ?></p>
										<div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
											<div id="wpc-geocoding-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
										</div>
										<p id="wpc-geocoding-status" style="margin: 10px 0 0 0; text-align: center; font-size: 14px;"></p>
									</div>
								<?php else : ?>
									<div style="text-align: center; margin-top: 15px; padding: 15px; background: #d5f5e3; border: 1px solid #00a32a; border-radius: 4px;">
										<span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size: 24px;"></span>
										<p style="margin: 10px 0 0 0; color: #00a32a; font-weight: 600;">
											<?php esc_html_e( 'All pincodes geocoded! Nearby suggestions are active.', 'pincode-checker-for-woocommerce' ); ?>
										</p>
									</div>
								<?php endif; ?>
							</div>

							<div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
								<h4 style="margin-top: 0;"><?php esc_html_e( 'How It Works', 'pincode-checker-for-woocommerce' ); ?></h4>
								<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
									<div>
										<h5 style="margin: 0 0 8px 0; color: #2271b1;">
											<span class="dashicons dashicons-admin-settings" style="font-size: 18px;"></span>
											<?php esc_html_e( 'Setup Process', 'pincode-checker-for-woocommerce' ); ?>
										</h5>
										<ul style="margin: 0 0 0 20px; font-size: 13px;">
											<li><?php esc_html_e( 'Click "Start Geocoding" button', 'pincode-checker-for-woocommerce' ); ?></li>
											<li><?php esc_html_e( 'Process runs in background (keep page open)', 'pincode-checker-for-woocommerce' ); ?></li>
											<li><?php esc_html_e( 'Fetches latitude/longitude for each pincode', 'pincode-checker-for-woocommerce' ); ?></li>
											<li><?php esc_html_e( 'One-time setup, no maintenance needed', 'pincode-checker-for-woocommerce' ); ?></li>
										</ul>
									</div>
									<div>
										<h5 style="margin: 0 0 8px 0; color: #00a32a;">
											<span class="dashicons dashicons-yes-alt" style="font-size: 18px;"></span>
											<?php esc_html_e( 'Customer Experience', 'pincode-checker-for-woocommerce' ); ?>
										</h5>
										<ul style="margin: 0 0 0 20px; font-size: 13px;">
											<li><?php esc_html_e( 'Customer enters unavailable pincode', 'pincode-checker-for-woocommerce' ); ?></li>
											<li><?php esc_html_e( 'See nearby serviceable pincodes (within 20km)', 'pincode-checker-for-woocommerce' ); ?></li>
											<li><?php esc_html_e( 'Distance shown for each suggestion', 'pincode-checker-for-woocommerce' ); ?></li>
											<li><?php esc_html_e( 'Reduces cart abandonment', 'pincode-checker-for-woocommerce' ); ?></li>
										</ul>
									</div>
								</div>
							</div>

							<div style="padding: 12px; background: #e7f5fe; border-left: 4px solid #2271b1; border-radius: 3px;">
								<p style="margin: 0;"><strong><?php esc_html_e( 'Free & Unlimited:', 'pincode-checker-for-woocommerce' ); ?></strong></p>
								<ul style="margin: 10px 0 0 20px; font-size: 13px;">
									<li><?php esc_html_e( '100% free using OpenStreetMap Nominatim service', 'pincode-checker-for-woocommerce' ); ?></li>
									<li><?php esc_html_e( 'No API key required, no credit card needed', 'pincode-checker-for-woocommerce' ); ?></li>
									<li><?php esc_html_e( 'Shows accurate nearby pincodes based on distance', 'pincode-checker-for-woocommerce' ); ?></li>
									<li><?php esc_html_e( 'Works automatically on product pages and checkout', 'pincode-checker-for-woocommerce' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Handle geocoding start button
	$('#wpc-start-geocoding').on('click', function() {
		var button = $(this);
		button.prop('disabled', true);
		$('#wpc-geocoding-progress').show();

		wpcGeocodeBatch();
	});

	// Geocode in batches
	function wpcGeocodeBatch() {
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpc_geocode_batch',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpc-geocode-batch' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					var processed = response.data.processed;
					var remaining = response.data.remaining;
					var total = processed + remaining;
					var percentage = total > 0 ? Math.round((processed / total) * 100) : 0;

					jQuery('#wpc-geocoding-progress-bar').css('width', percentage + '%');
					jQuery('#wpc-geocoding-status').text('Processed: ' + processed + ' / ' + total + ' pincodes');

					if (!response.data.completed) {
						// Continue processing
						setTimeout(wpcGeocodeBatch, 1000);
					} else {
						// Done!
						jQuery('#wpc-geocoding-status').html('<strong style="color: #00a32a;">Geocoding completed!</strong>');
						setTimeout(function() {
							location.reload();
						}, 2000);
					}
				} else {
					jQuery('#wpc-geocoding-status').html('<strong style="color: #d63638;">Error: ' + response.data.message + '</strong>');
					jQuery('#wpc-start-geocoding').prop('disabled', false);
				}
			},
			error: function() {
				jQuery('#wpc-geocoding-status').html('<strong style="color: #d63638;">Network error. Please try again.</strong>');
				jQuery('#wpc-start-geocoding').prop('disabled', false);
			}
		});
	}
});
</script>
