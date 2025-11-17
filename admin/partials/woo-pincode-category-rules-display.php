<?php
/**
 * Provide a Category Rules view for the plugin
 *
 * Category-specific delivery rules settings page.
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
			<h3><?php esc_html_e( 'Category-Specific Delivery Rules', 'pincode-checker-for-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Set different delivery times for different product categories. These rules apply site-wide to all pincodes unless overridden in individual pincode settings.', 'pincode-checker-for-woocommerce' ); ?></p>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpc_general_settings' );
				?>
				<div class="form-table">
					<div class="wbcom-settings-section-wrap">
						<?php
						$category_rules_handler = new Woo_Pincode_Category_Rules();
						$all_categories = $category_rules_handler->get_all_product_categories();
						$global_rules = $category_rules_handler->get_global_category_rules();

						if ( ! empty( $all_categories ) ) {
							?>
							<div style="margin-bottom: 15px;">
								<p style="margin: 0; font-size: 14px; color: #646970;">
									<?php esc_html_e( 'Set different delivery times for specific product categories. Enable a category and enter the number of days for delivery.', 'pincode-checker-for-woocommerce' ); ?>
								</p>
							</div>

							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th class="column-primary" style="width: 40%;"><?php esc_html_e( 'Category', 'pincode-checker-for-woocommerce' ); ?></th>
										<th style="width: 35%;"><?php esc_html_e( 'Delivery Days', 'pincode-checker-for-woocommerce' ); ?></th>
										<th style="width: 25%;"><?php esc_html_e( 'Enable Rule', 'pincode-checker-for-woocommerce' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $all_categories as $category ) : ?>
										<?php
										$cat_id = $category['id'];
										$is_enabled = isset( $global_rules[ $cat_id ] );
										$delivery_days = $is_enabled ? $global_rules[ $cat_id ] : 1;
										?>
										<tr <?php echo ! $is_enabled ? 'style="opacity: 0.6;"' : ''; ?>>
											<td class="column-primary" data-colname="<?php esc_attr_e( 'Category', 'pincode-checker-for-woocommerce' ); ?>">
												<strong style="font-size: 14px;"><?php echo esc_html( $category['name'] ); ?></strong>
												<?php if ( isset( $category['count'] ) && $category['count'] > 0 ) : ?>
													<br>
													<span class="description" style="font-size: 13px; color: #646970;">
														<?php
														printf(
															_n( '%d product', '%d products', $category['count'], 'pincode-checker-for-woocommerce' ),
															$category['count']
														);
														?>
													</span>
												<?php endif; ?>
											</td>
											<td data-colname="<?php esc_attr_e( 'Delivery Days', 'pincode-checker-for-woocommerce' ); ?>">
												<input type="number"
													   name="wpc_general_settings[global_category_rules][<?php echo esc_attr( $cat_id ); ?>][days]"
													   value="<?php echo esc_attr( $delivery_days ); ?>"
													   min="1"
													   max="365"
													   class="small-text wpc-category-delivery-input"
													   data-category-id="<?php echo esc_attr( $cat_id ); ?>"
													   style="width: 60px;"
													   <?php echo ! $is_enabled ? 'disabled' : ''; ?>>
												<span style="margin-left: 5px; color: #646970;"><?php esc_html_e( 'days', 'pincode-checker-for-woocommerce' ); ?></span>
											</td>
											<td data-colname="<?php esc_attr_e( 'Enable Rule', 'pincode-checker-for-woocommerce' ); ?>">
												<label class="wb-switch" style="display: inline-block;">
													<input type="checkbox"
														   name="wpc_general_settings[global_category_rules][<?php echo esc_attr( $cat_id ); ?>][enabled]"
														   value="1"
														   class="wpc-category-enabled-checkbox"
														   data-category-id="<?php echo esc_attr( $cat_id ); ?>"
														   <?php checked( $is_enabled, true ); ?>>
													<div class="wb-slider wb-round"></div>
												</label>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>

							<div style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
								<div style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
									<h4 style="margin: 0 0 8px 0; font-size: 14px;">
										<span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle; color: #f0b429;"></span>
										<?php esc_html_e( 'Priority', 'pincode-checker-for-woocommerce' ); ?>
									</h4>
									<p style="margin: 0; font-size: 13px; line-height: 1.6;">
										<?php esc_html_e( 'Category-specific delivery times override default pincode delivery times. For products in multiple categories, the first matching rule applies.', 'pincode-checker-for-woocommerce' ); ?>
									</p>
								</div>

								<div style="padding: 15px; background: #e7f5fe; border-left: 4px solid #2271b1; border-radius: 4px;">
									<h4 style="margin: 0 0 8px 0; font-size: 14px;">
										<span class="dashicons dashicons-yes-alt" style="font-size: 16px; vertical-align: middle; color: #2271b1;"></span>
										<?php esc_html_e( 'Example', 'pincode-checker-for-woocommerce' ); ?>
									</h4>
									<p style="margin: 0; font-size: 13px; line-height: 1.6;">
										<?php esc_html_e( 'Electronics = 5 days, Groceries = 1 day. Rules apply instantly to all products in those categories across all pincodes.', 'pincode-checker-for-woocommerce' ); ?>
									</p>
								</div>
							</div>
							<?php
						} else {
							?>
							<div style="padding: 40px 20px; background: white; border: 2px dashed #c3c4c7; border-radius: 4px; text-align: center;">
								<span class="dashicons dashicons-category" style="font-size: 64px; color: #c3c4c7; width: 64px; height: 64px;"></span>
								<h3 style="margin: 15px 0 10px 0; font-size: 18px; font-weight: 600;">
									<?php esc_html_e( 'No Product Categories Found', 'pincode-checker-for-woocommerce' ); ?>
								</h3>
								<p style="margin: 0 0 20px 0; font-size: 14px; color: #646970;">
									<?php esc_html_e( 'Create product categories in WooCommerce to set category-specific delivery times.', 'pincode-checker-for-woocommerce' ); ?>
								</p>
								<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>" class="button button-primary button-large">
									<span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
									<?php esc_html_e( 'Create Product Categories', 'pincode-checker-for-woocommerce' ); ?>
								</a>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Handle category rules enable/disable
	$('.wpc-category-enabled-checkbox').on('change', function() {
		var categoryId = $(this).data('category-id');
		var deliveryInput = $('.wpc-category-delivery-input[data-category-id="' + categoryId + '"]');

		if ($(this).is(':checked')) {
			deliveryInput.prop('disabled', false);
		} else {
			deliveryInput.prop('disabled', true);
		}
	});
});
</script>

<style>
#wpc-category-rules-container .wp-list-table th,
#wpc-category-rules-container .wp-list-table td {
	vertical-align: middle;
}
</style>
