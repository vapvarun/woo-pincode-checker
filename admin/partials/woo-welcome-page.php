<?php
/**
 * This file is used for rendering and saving plugin welcome settings.
 *
 * @package Woo_Pincode_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly.
}
?>
<div class="wbcom-tab-content">
	<div class="wbcom-wrapper-admin">
		<div class="wbcom-admin-title-section">
			<h3><?php esc_html_e( 'Welcome to Woo Pincode Checker', 'pincode-checker-for-woocommerce' ); ?></h3>
			<p class="wbcom-welcome-description"><?php esc_html_e( 'Transform your WooCommerce store with intelligent delivery zone management. Allow customers to check product availability, delivery dates, and shipping costs for their location before making a purchase. Perfect for businesses with specific delivery areas or varying shipping costs across different regions.', 'pincode-checker-for-woocommerce' ); ?></p>
		</div>
		<div class="wbcom-welcome-content"> 
			<div class="wbcom-welcome-support-info">
				<h3><?php esc_html_e( 'Help &amp; Support Resources', 'pincode-checker-for-woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'If you need assistance, here are some helpful resources. Our documentation is a great place to start, and our support team is available if you require further help.', 'pincode-checker-for-woocommerce' ); ?></p>

				<div class="wbcom-support-info-wrap">
					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-book"></span><?php esc_html_e( 'Documentation', 'pincode-checker-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'Explore our detailed guide on Woo Pincode Checker to understand all the features and how to make the most of them.', 'pincode-checker-for-woocommerce' ); ?></p>
						<a href="<?php echo esc_url( 'https://docs.wbcomdesigns.com/doc_category/woo-pincode-checker/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Read Documentation', 'pincode-checker-for-woocommerce' ); ?></a>
						</div>
					</div>

					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-sos"></span><?php esc_html_e( 'Support Center', 'pincode-checker-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'Our support team is here to assist you with any questions or issues. Feel free to contact us anytime through our support center.', 'pincode-checker-for-woocommerce' ); ?></p>
						<a href="<?php echo esc_url( 'https://wbcomdesigns.com/support/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Get Support', 'pincode-checker-for-woocommerce' ); ?></a>
					</div>
					</div>
					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-admin-comments"></span><?php esc_html_e( 'Share Your Feedback', 'pincode-checker-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'We’d love to hear about your experience with the plugin. Your feedback and suggestions help us improve future updates.', 'pincode-checker-for-woocommerce' ); ?></p>
						<a href="<?php echo esc_url( 'https://wbcomdesigns.com/submit-review/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Send Feedback', 'pincode-checker-for-woocommerce' ); ?></a>
					</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
