<div class="wrap">
	<div class="wbcom-bb-plugins-offer-wrapper">
		<div id="wb_admin_logo">
		</div>
	</div>
	<div class="wbcom-wrap wbcom-plugin-wrapper">
		<div class="wbcom_admin_header-wrapper">
			<div id="wb_admin_plugin_name">
				<?php esc_html_e( 'Woo Pincode Checker', 'pincode-checker-for-woocommerce' ); ?>
				<span>
					<?php
					/* translators: %s: */
					printf( esc_html__( 'Version %s', 'pincode-checker-for-woocommerce' ), esc_html( WOO_PINCODE_CHECKER_VERSION ) );
					?>
					</span>
			</div>
			<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
		</div>
		<div class="wbcom-all-addons-plugins-wrap">
		<h4 class="wbcom-support-section"><?php esc_html_e( 'Plugin License', 'pincode-checker-for-woocommerce' ); ?></h4>
		<div class="wb-plugins-license-tables-wrap">
			<div class="wbcom-license-support-wrapp">
			<table class="form-table wb-license-form-table desktop-license-headings">
				<thead>
					<tr>
						<th class="wb-product-th"><?php esc_html_e( 'Product', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-version-th"><?php esc_html_e( 'Version', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-key-th"><?php esc_html_e( 'Key', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-status-th"><?php esc_html_e( 'Status', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-action-th"><?php esc_html_e( 'Action', 'pincode-checker-for-woocommerce' ); ?></th>
					</tr>
				</thead>
			</table>
			<?php do_action( 'wbcom_add_plugin_license_code' ); ?>
			<table class="form-table wb-license-form-table">
				<tfoot>
					<tr>
						<th class="wb-product-th"><?php esc_html_e( 'Product', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-version-th"><?php esc_html_e( 'Version', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-key-th"><?php esc_html_e( 'Key', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-status-th"><?php esc_html_e( 'Status', 'pincode-checker-for-woocommerce' ); ?></th>
						<th class="wb-action-th"><?php esc_html_e( 'Action', 'pincode-checker-for-woocommerce' ); ?></th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
	</div>
	</div><!-- .wbcom-wrap -->
</div><!-- .wrap -->
