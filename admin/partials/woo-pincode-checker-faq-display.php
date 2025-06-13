<?php
/**
 * Provide a faq admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wbcom-tab-content">      
	<div class="wbcom-faq-adming-setting">
		<div class="wbcom-admin-title-section">
			<h3><?php esc_html_e( 'Have some questions?', 'woo-pincode-checker' ); ?></h3>
		</div>
		<div class="wbcom-faq-admin-settings-block">
			<div id="wbcom-faq-settings-section" class="wbcom-faq-table">
				<div class="wbcom-faq-section-row">
					<div class="wbcom-faq-admin-row">
						<button class="wbcom-faq-accordion">
							<?php esc_html_e( 'Does this plugin require WooCommerce?', 'woo-pincode-checker' ); ?>
						</button>
						<div class="wbcom-faq-panel">
							<p> 
								<?php esc_html_e( 'Yes, this plugin requires WooCommerce to be installed and activated in order to function properly.', 'woo-pincode-checker' ); ?>
							</p>
						</div>
					</div>
				</div>
				<div class="wbcom-faq-section-row">
					<div class="wbcom-faq-admin-row">
						<button class="wbcom-faq-accordion">
							<?php esc_html_e( 'What should I do after activating the plugin?', 'woo-pincode-checker' ); ?>
						</button>
						<div class="wbcom-faq-panel">
							<p> 
								<?php esc_html_e( 'After activating the plugin, you need to add or upload pincodes to enable the delivery availability check.', 'woo-pincode-checker' ); ?>     
							</p>
						</div>
					</div>
				</div>
				<div class="wbcom-faq-section-row">
					<div class="wbcom-faq-admin-row">
						<button class="wbcom-faq-accordion">
							<?php esc_html_e( 'How to upload the Pincodes?', 'woo-pincode-checker' ); ?>
						</button>
						<div class="wbcom-faq-panel">
							<p> 
								<?php esc_html_e( 'If you want to manually upload the pincode from the backend then navigate to the WP Dashboard > Pincodes > Add Pincode. Now you can add the Pincode by providing pincode, city, state, delivery within days, and payment option ( cash on delivery ). Enable cash on delivery option if you want to provide this payment option for the particular pincode.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<?php esc_html_e( 'If you want to bulk upload the pincodes from a CSV file then Navigate to the WP Dashboard > Pincodes > upload Pincodes tab and import the CSV file to add the pincodes in bulk.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<?php esc_html_e( 'you need to add csv file column as pincode, city, state, delivery within days, cash on delivery.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<label for="upload">
									<?php esc_html_e( 'Download Sample CSV File:', 'woo-pincode-checker' ); ?>
								</label>
								<a href="<?php echo esc_url( WPCP_PLUGIN_URL . 'sample-data/sample-pincodes.csv' ); ?>"><?php esc_html_e( 'Click Here', 'woo-pincode-checker' ); ?></a>
							</p>
						</div>
					</div>
				</div>
				<div class="wbcom-faq-section-row">
					<div class="wbcom-faq-admin-row">
						<button class="wbcom-faq-accordion">
							<?php esc_html_e( 'How to configure the backend settings?', 'woo-pincode-checker' ); ?>
						</button>
						<div class="wbcom-faq-panel">
							<p> 
								<?php esc_html_e( 'To configure the backend settings, navigate to the Dashboard > WB plugins > Woo pin code checker. Now here you can find the general setting of the plugin.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<?php esc_html_e( 'Show Delivery Date: Enable this option to display the delivery date on the product page.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<?php esc_html_e( 'Select Delivery Date Format: This option allows you to select any date format from the dropdown list to display on the product page.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<?php esc_html_e( 'Select Pincode Label Text Color: Allow you to select Pincode Label Text Color.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<?php esc_html_e( 'Select Button Color: Allow you to select the “check” button color.', 'woo-pincode-checker' ); ?>     
							</p>
							<p> 
								<?php esc_html_e( 'Select Button Text Color: Allow you to select Button Text Color.', 'woo-pincode-checker' ); ?>     
							</p>
						</div>
					</div>
				</div>
				<div class="wbcom-faq-section-row">
					<div class="wbcom-faq-admin-row">
						<button class="wbcom-faq-accordion">
							<?php esc_html_e( 'Can I hide the pincode checker for specific products?', 'woo-pincode-checker' ); ?>
						</button>
						<div class="wbcom-faq-panel">
							<p> 
								<?php esc_html_e( 'Yes, you can hide the pincode checker form for individual products.', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( 'To hide the pincode checker form Navigate to the WP Dashboard > Products > Add New Product or Edit any product page and you will find the checkbox ( Check if Hide for this Product ). you can check this checkbox to hide the pincode checker form for the specific product.', 'woo-pincode-checker' ); ?>    
							</p>
						</div>
					</div>
				</div>
				<div class="wbcom-faq-section-row">
					<div class="wbcom-faq-admin-row">
						<button class="wbcom-faq-accordion">
							<?php esc_html_e( 'Where will the pincode checker appear on my website?', 'woo-pincode-checker' ); ?>
						</button>
						<div class="wbcom-faq-panel">
							<p> 
								<?php esc_html_e( 'The pincode checker form appears on individual product pages.', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( 'To use it:', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( '1) Go to your Shop Page, select a product.', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( '2) Enter your area pincode and click "Check".', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( 'The plugin will display:', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( '1) Whether the product is deliverable to the entered pincode.', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( '2) The estimated delivery date.', 'woo-pincode-checker' ); ?>    
							</p>
							<p> 
								<?php esc_html_e( '3) Whether Cash on Delivery is available at that location.', 'woo-pincode-checker' ); ?>    
							</p>
						</div>
					</div>
				</div>
				<div class="wbcom-faq-section-row">
					<div class="wbcom-faq-admin-row">
						<button class="wbcom-faq-accordion">
							<?php esc_html_e( 'Does this plugin provide any shortcode?', 'woo-pincode-checker' ); ?>
						</button>
						<div class="wbcom-faq-panel">
							<p> 
								<?php esc_html_e( 'Yes! It provides a shortcode [wpc_pincode_checker]. Using this shortcode, you can check the Pincode anywhere on the website.', 'woo-pincode-checker' ); ?>    
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

