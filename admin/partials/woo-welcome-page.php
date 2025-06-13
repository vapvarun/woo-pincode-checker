<?php
/**
 *
 * This file is used for rendering and saving plugin welcome settings.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}
?>
<div class="wbcom-tab-content">
	<div class="wbcom-welcome-main-wrapper">
		<div class="wbcom-welcome-head">
			<p class="wbcom-welcome-description"><?php esc_html_e( 'Transform your WooCommerce store with intelligent delivery zone management. Allow customers to check product availability, delivery dates, and shipping costs for their location before making a purchase. Perfect for businesses with specific delivery areas or varying shipping costs across different regions.', 'woo-pincode-checker' ); ?></p>
		</div><!-- .wbcom-welcome-head -->
		<div class="wbcom-welcome-content">
			<div class="wbcom-welcome-support-info">
				<h3><?php esc_html_e( 'Help &amp; Support Resources', 'woo-pincode-checker' ); ?></h3>
				<p><?php esc_html_e( 'If you need assistance, here are some helpful resources. Our documentation is a great place to start, and our support team is available if you require further help.', 'woo-pincode-checker' ); ?></p>

				<div class="wbcom-support-info-wrap">
					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-book"></span><?php esc_html_e( 'Documentation', 'woo-pincode-checker' ); ?></h3>
						<p><?php esc_html_e( 'Explore our detailed guide on Woo Pincode Checker to understand all the features and how to make the most of them.', 'woo-pincode-checker' ); ?></p>
						<a href="<?php echo esc_url( 'https://docs.wbcomdesigns.com/doc_category/woo-pincode-checker/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Read Documentation', 'woo-pincode-checker' ); ?></a>
						</div>
					</div>

					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-sos"></span><?php esc_html_e( 'Support Center', 'woo-pincode-checker' ); ?></h3>
						<p><?php esc_html_e( 'Our support team is here to assist you with any questions or issues. Feel free to contact us anytime through our support center.', 'woo-pincode-checker' ); ?></p>
						<a href="<?php echo esc_url( 'https://wbcomdesigns.com/support/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Get Support', 'woo-pincode-checker' ); ?></a>
					</div>
					</div>
					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-admin-comments"></span><?php esc_html_e( 'Share Your Feedback', 'woo-pincode-checker' ); ?></h3>
						<p><?php esc_html_e( 'We’d love to hear about your experience with the plugin. Your feedback and suggestions help us improve future updates.', 'woo-pincode-checker' ); ?></p>
						<a href="<?php echo esc_url( 'https://wbcomdesigns.com/submit-review/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Send Feedback', 'woo-pincode-checker' ); ?></a>
					</div>
					</div>
				</div>
			</div>
		</div>
	</div><!-- .wbcom-welcome-main-wrapper -->
</div><!-- .wbcom-welcome-content -->
