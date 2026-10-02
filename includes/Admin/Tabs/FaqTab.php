<?php
/**
 * FAQ tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Short answers to what store owners ask first.
 */
final class FaqTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$faqs = array(
			__( 'Do I have to add areas before the store works?', 'woo-pincode-checker' ) => __( 'No. With the default "Automatic" setting every postcode is deliverable on your default delivery days until you add your first area. After that, only the areas you list are deliverable.', 'woo-pincode-checker' ),
			__( 'What can an area be?', 'woo-pincode-checker' ) => __( 'An exact postcode (110001), a prefix ending in * that covers every postcode starting that way (SW1* or 110*), or a numeric range (110001 to 110099). Mark an area "Blocked" to carve exceptions out of a prefix or range.', 'woo-pincode-checker' ),
			__( 'Which area wins when several match?', 'woo-pincode-checker' ) => __( 'The most specific one: an exact postcode beats a prefix, a longer prefix beats a shorter one, a narrower range beats a wider one, and an area for a specific country beats "Any country". Use the "Test a postcode" tool on the Overview to see the winner.', 'woo-pincode-checker' ),
			__( 'Does it work with UK, US and other postcodes?', 'woo-pincode-checker' ) => __( 'Yes. Postcodes are compared without spaces or hyphens and checked against the country\'s format. Use prefixes for letter postcodes (SW1*, M1*) and ranges for numeric ones.', 'woo-pincode-checker' ),
			__( 'How do I charge shipping per area?', 'woo-pincode-checker' ) => __( 'Add the "Pincode rate" method to a shipping zone (WooCommerce > Settings > Shipping). It charges the area\'s shipping fee plus an optional base cost, and is only offered where you deliver.', 'woo-pincode-checker' ),
			__( 'Does it work with the Cart and Checkout blocks?', 'woo-pincode-checker' ) => __( 'Yes, and with the classic shortcodes. Orders to areas you do not serve are blocked in both, cash on delivery follows each area, and delivery dates appear next to each shipping option.', 'woo-pincode-checker' ),
			__( 'Will page caching break it?', 'woo-pincode-checker' ) => __( 'No. The product page is the same for every visitor; the answer is fetched separately, so full-page caches and CDNs can cache your pages safely.', 'woo-pincode-checker' ),
			__( 'Where do I place the checker?', 'woo-pincode-checker' ) => __( 'Choose a position on the General tab, or add the Pincode Checker block or the [wbpc_pincode_checker] shortcode anywhere. On a product it also controls Add to cart; elsewhere it is a general delivery check.', 'woo-pincode-checker' ),
		);

		Wbcom_Settings_Page::card_open( __( 'Frequently asked questions', 'woo-pincode-checker' ) );
		foreach ( $faqs as $question => $answer ) {
			printf( '<details class="wbcom-faq__item"><summary>%s</summary><p>%s</p></details>', esc_html( $question ), esc_html( $answer ) );
		}
		Wbcom_Settings_Page::card_close();
	}
}
