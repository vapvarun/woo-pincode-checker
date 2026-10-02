<?php
/**
 * Checkout and COD tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Admin\Form;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout enforcement and cash on delivery.
 */
final class CheckoutTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$form = new Form( 'checkout' );

		$form->open();

		Wbcom_Settings_Page::card_open( __( 'Checkout', 'woo-pincode-checker' ) );
		$form->toggle( 'validate_checkout', __( 'Block orders to areas you do not serve', 'woo-pincode-checker' ), __( 'Checks the shipping postcode when the order is placed, on both the Checkout block and the classic checkout.', 'woo-pincode-checker' ) );
		$form->toggle( 'prefill_postcode', __( 'Fill in the checked pincode at checkout', 'woo-pincode-checker' ), __( 'Saves guests from typing it twice. A customer\'s saved address is never changed.', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'Cash on delivery', 'woo-pincode-checker' ) );
		$form->toggle( 'cod_gating', __( 'Offer COD only where allowed', 'woo-pincode-checker' ), __( 'Hides the Cash on delivery payment method for areas that do not allow it.', 'woo-pincode-checker' ) );
		$form->toggle( 'cod_fee', __( 'Charge the area\'s COD fee', 'woo-pincode-checker' ), __( 'Adds the fee set on the area when the shopper pays by cash on delivery.', 'woo-pincode-checker' ) );
		$form->input( 'cod_fee_label', __( 'Fee name shown in the cart', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		$form->close();

		Wbcom_Settings_Page::card_open( __( 'Shipping cost by pincode', 'woo-pincode-checker' ), __( 'Charge each area\'s shipping fee with the "Pincode rate" shipping method.', 'woo-pincode-checker' ) );
		?>
		<ol class="wbcom-steps">
			<li class="wbcom-step"><span class="wbcom-step__num">1</span><div class="wbcom-step__body"><p><?php esc_html_e( 'Go to WooCommerce > Settings > Shipping and open a shipping zone.', 'woo-pincode-checker' ); ?></p></div></li>
			<li class="wbcom-step"><span class="wbcom-step__num">2</span><div class="wbcom-step__body"><p><?php esc_html_e( 'Add the "Pincode rate" method. It charges the area\'s fee (plus an optional base cost) and only appears for postcodes you deliver to.', 'woo-pincode-checker' ); ?></p></div></li>
		</ol>
		<p><a class="button wbcom-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><i data-lucide="truck"></i><?php esc_html_e( 'Open shipping zones', 'woo-pincode-checker' ); ?></a></p>
		<?php
		Wbcom_Settings_Page::card_close();
	}
}
