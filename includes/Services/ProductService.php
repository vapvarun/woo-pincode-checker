<?php
/**
 * Product-level rules: whether the checker applies to a product.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Used by the storefront, add-to-cart and checkout validation so all three agree.
 */
final class ProductService {

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Whether the checker (and its enforcement) is off for a product: it does not ship, the
	 * owner hid the checker on it, or it is in an excluded category.
	 *
	 * @param \WC_Product $product Product (a variation is judged by its parent's settings).
	 */
	public function is_excluded( \WC_Product $product ): bool {
		$parent   = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
		$parent   = $parent ? $parent : $product;
		$excluded = ! $product->needs_shipping()
			|| $product->is_type( 'external' ) // Sold elsewhere: the store never ships it, yet WooCommerce reports needs_shipping().
			|| 'yes' === $parent->get_meta( '_wbpc_hide_checker' )
			|| array_intersect( $parent->get_category_ids(), (array) $this->settings->get( 'general', 'excluded_categories' ) );

		/**
		 * Whether the pincode checker is off for a product.
		 *
		 * @param bool        $excluded Excluded.
		 * @param \WC_Product $product  Product.
		 */
		return (bool) apply_filters( 'wbpc_is_product_excluded', (bool) $excluded, $product );
	}

	/**
	 * Category extra delivery days for a product: the largest over its categories.
	 *
	 * @param \WC_Product $product Product (a variation uses its parent's categories).
	 */
	public function extra_days( \WC_Product $product ): int {
		$rules = (array) $this->settings->get( 'category_days' );

		if ( ! $rules ) {
			return 0;
		}

		$parent = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
		$days   = array_intersect_key( $rules, array_flip( ( $parent ? $parent : $product )->get_category_ids() ) );

		return $days ? (int) max( $days ) : 0;
	}

	/**
	 * Category extra days for the cart: the slowest item decides.
	 */
	public function cart_extra_days(): int {
		$max = 0;

		foreach ( WC()->cart ? WC()->cart->get_cart() : array() as $item ) {
			if ( isset( $item['data'] ) && $item['data'] instanceof \WC_Product ) {
				$max = max( $max, $this->extra_days( $item['data'] ) );
			}
		}

		return $max;
	}
}
