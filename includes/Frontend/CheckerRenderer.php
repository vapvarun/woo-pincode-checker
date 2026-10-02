<?php
/**
 * The one renderer behind the product hook, the shortcode and the block.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Frontend;

use Wbcom\PincodeChecker\Services\ProductService;
use Wbcom\PincodeChecker\Services\SettingsService;

defined( 'ABSPATH' ) || exit;

/**
 * Output is the same for every visitor (no pincode, no nonce), so pages stay cacheable.
 */
final class CheckerRenderer {

	/**
	 * Products already rendered on this page, so a block plus the product hook do not show two.
	 *
	 * @var array<int, bool>
	 */
	private array $rendered = array();

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings.
	 * @param ProductService  $products Product rules.
	 */
	public function __construct( private SettingsService $settings, private ProductService $products ) {}

	/**
	 * Checker HTML, or '' when it does not apply.
	 *
	 * @param int    $product_id Product, 0 for a general check (shortcode/block outside a product).
	 * @param string $wrapper    Extra wrapper attributes (block supports), already escaped.
	 */
	public function render( int $product_id = 0, string $wrapper = '' ): string {
		if ( $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || $this->products->is_excluded( $product ) || isset( $this->rendered[ $product_id ] ) ) {
				return '';
			}
			$this->rendered[ $product_id ] = true;
		}

		Storefront::enqueue();

		return wc_get_template_html(
			'checker.php',
			array(
				'product_id' => $product_id,
				'labels'     => (array) $this->settings->get( 'display' ),
				'wrapper'    => $wrapper,
				'numeric'    => in_array( $this->country(), array( 'IN', 'US', 'DE', 'FR', 'IT', 'ES', 'AU', 'ZA', 'MX', 'BR', 'RU', 'ID', 'MY', 'TH', 'VN' ), true ),
			),
			'woocommerce/wbpc/',
			WBPC_DIR . 'templates/'
		);
	}

	/**
	 * Default country for the input mode.
	 */
	private function country(): string {
		$country = (string) $this->settings->get( 'general', 'default_country' );

		return '' !== $country ? $country : WC()->countries->get_base_country();
	}
}
