<?php
/**
 * Overview tab: is the plugin working, and what is it doing?
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Status rows grow as features land (areas, shipping zones, last import).
 */
final class OverviewTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$checkout_id = (int) wc_get_page_id( 'checkout' );
		$is_blocks   = $checkout_id > 0 && has_block( 'woocommerce/checkout', $checkout_id );

		Wbcom_Settings_Page::card_open(
			__( 'Store status', 'woo-pincode-checker' ),
			__( 'What Pincode Checker sees on your store right now.', 'woo-pincode-checker' )
		);

		$this->row(
			__( 'WooCommerce', 'woo-pincode-checker' ),
			__( 'Pincode Checker needs WooCommerce 8.0 or newer.', 'woo-pincode-checker' ),
			$this->badge( WC()->version, version_compare( WC()->version, '8.0', '>=' ) ? 'success' : 'danger' )
		);

		$areas = Plugin::areas()->count();
		$this->row(
			__( 'Delivery areas', 'woo-pincode-checker' ),
			__( 'Postcodes, prefixes and ranges you deliver to or block.', 'woo-pincode-checker' ),
			$this->badge(
				/* translators: %s: number of delivery areas. */
				sprintf( _n( '%s area', '%s areas', $areas, 'woo-pincode-checker' ), number_format_i18n( $areas ) ),
				$areas > 0 ? 'success' : 'warn'
			)
		);

		$this->row(
			__( 'Pincode rate shipping', 'woo-pincode-checker' ),
			__( 'Charges each area\'s shipping fee. Add it to a shipping zone to use area fees.', 'woo-pincode-checker' ),
			\Wbcom\PincodeChecker\Integrations\WooCommerce\ShippingMethod::in_use()
				? $this->badge( __( 'Enabled', 'woo-pincode-checker' ), 'success' )
				: sprintf( '<a class="button wbcom-btn" href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ), esc_html__( 'Add to a shipping zone', 'woo-pincode-checker' ) )
		);

		$this->row(
			__( 'Checkout type', 'woo-pincode-checker' ),
			__( 'Both the Checkout block and the classic checkout shortcode are supported.', 'woo-pincode-checker' ),
			$checkout_id > 0
				? $this->badge( $is_blocks ? __( 'Checkout block', 'woo-pincode-checker' ) : __( 'Classic checkout', 'woo-pincode-checker' ), 'success' )
				: $this->badge( __( 'No checkout page set', 'woo-pincode-checker' ), 'warn' )
		);

		$this->row(
			__( 'Version', 'woo-pincode-checker' ),
			__( 'Installed Pincode Checker version.', 'woo-pincode-checker' ),
			$this->badge( WBPC_VERSION, 'muted' )
		);

		Wbcom_Settings_Page::card_close();

		$base = WC()->countries->get_base_country();
		Wbcom_Settings_Page::card_open(
			__( 'Test a postcode', 'woo-pincode-checker' ),
			__( 'See exactly what a shopper sees for a postcode, and which area decided it.', 'woo-pincode-checker' )
		);
		?>
		<form class="wbpc-tester" data-wbpc-tester>
			<label class="screen-reader-text" for="wbpc-test-postcode"><?php esc_html_e( 'Postcode', 'woo-pincode-checker' ); ?></label>
			<input type="text" id="wbpc-test-postcode" name="postcode" class="wbcom-input" placeholder="<?php esc_attr_e( 'Postcode', 'woo-pincode-checker' ); ?>" required autocomplete="off">
			<label class="screen-reader-text" for="wbpc-test-country"><?php esc_html_e( 'Country', 'woo-pincode-checker' ); ?></label>
			<select id="wbpc-test-country" name="country" class="wbcom-select">
				<?php foreach ( WC()->countries->get_countries() as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $base ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button wbcom-btn wbcom-btn--primary"><i data-lucide="search"></i><?php esc_html_e( 'Test', 'woo-pincode-checker' ); ?></button>
		</form>
		<div class="wbpc-tester__result" data-wbpc-tester-result role="status" aria-live="polite"></div>
		<?php
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'Support & resources', 'woo-pincode-checker' ) );
		?>
		<ul class="wbcom-feature-list">
			<li><i data-lucide="book-open"></i><a href="https://docs.wbcomdesigns.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'woo-pincode-checker' ); ?></a></li>
			<li><i data-lucide="life-buoy"></i><a href="https://wbcomdesigns.com/support/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support center', 'woo-pincode-checker' ); ?></a></li>
		</ul>
		<?php
		Wbcom_Settings_Page::card_close();
	}

	/**
	 * One label / description / control row in the shell's field layout.
	 *
	 * @param string $label   Label.
	 * @param string $desc    Description.
	 * @param string $control Escaped control HTML.
	 */
	private function row( string $label, string $desc, string $control ): void {
		?>
		<div class="wbcom-field wbcom-field-group">
			<div class="wbcom-field-info">
				<label><?php echo esc_html( $label ); ?></label>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			</div>
			<div class="wbcom-field-control"><?php echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts by badge(). ?></div>
		</div>
		<?php
	}

	/**
	 * Status badge HTML.
	 *
	 * @param string $text Text.
	 * @param string $tone success|warn|danger|muted.
	 */
	private function badge( string $text, string $tone ): string {
		return sprintf( '<span class="wbcom-badge wbcom-badge--%s">%s</span>', esc_attr( $tone ), esc_html( $text ) );
	}
}
