<?php
/**
 * Server-side delivery rules: add to cart, classic checkout, Blocks checkout.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Integrations\WooCommerce;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Wbcom\PincodeChecker\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * The storefront script only improves the experience; these rules are the gate. All three
 * surfaces ask the same question through unserviceable().
 */
final class Enforcement {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'add_to_cart' ), 10, 4 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'classic_checkout' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'blocks_checkout' ), 5 );
	}

	/**
	 * Add to cart (product pages, shop loops and the Store API all pass through this filter).
	 *
	 * @param bool $passed       Validation so far.
	 * @param int  $product_id   Product.
	 * @param int  $quantity     Quantity.
	 * @param int  $variation_id Variation, if any.
	 * @return bool
	 */
	public function add_to_cart( $passed, $product_id, $quantity, $variation_id = 0 ) {
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $passed || ! $product || Plugin::products()->is_excluded( $product ) ) {
			return $passed;
		}

		$general     = (array) Plugin::settings()->get( 'general' );
		$destination = CustomerPostcode::current();

		if ( ! $destination ) {
			if ( $general['require_check'] ) {
				wc_add_notice( (string) Plugin::settings()->get( 'display', 'msg_required' ), 'error' );
				return false;
			}
			return $passed;
		}

		$result = Plugin::checker()->check( $destination['postcode'], $destination['country'], Plugin::products()->extra_days( $product ) );

		if ( 'unavailable' === $result['status'] && 'allow' !== $general['unavailable_action'] ) {
			wc_add_notice( Plugin::messages()->for_result( $result )['headline'], 'error' );
			return false;
		}

		if ( 'invalid' === $result['status'] && $general['require_check'] ) {
			wc_add_notice( (string) Plugin::settings()->get( 'display', 'msg_required' ), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Classic checkout: the posted shipping (or billing) address.
	 *
	 * @param array     $data   Posted checkout data.
	 * @param \WP_Error $errors Errors.
	 */
	public function classic_checkout( $data, $errors ): void {
		$shipping = ! empty( $data['ship_to_different_address'] );
		$message  = $this->unserviceable(
			(string) ( $shipping ? ( $data['shipping_country'] ?? '' ) : ( $data['billing_country'] ?? '' ) ),
			(string) ( $shipping ? ( $data['shipping_postcode'] ?? '' ) : ( $data['billing_postcode'] ?? '' ) )
		);

		if ( $message ) {
			$errors->add( 'wbpc_unserviceable', $message );
		}
	}

	/**
	 * Blocks checkout: the order's address, before payment. Throwing aborts the checkout with a notice.
	 *
	 * @param \WC_Order $order Order being placed.
	 * @throws RouteException When the destination is not served.
	 */
	public function blocks_checkout( \WC_Order $order ): void {
		$message = $this->unserviceable(
			(string) ( $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country() ),
			(string) ( $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode() )
		);

		if ( $message ) {
			throw new RouteException( 'wbpc_unserviceable', esc_html( $message ), 400 );
		}
	}

	/**
	 * Why the current cart cannot ship to a destination, or null when it can.
	 *
	 * Skipped when validation is off, the cart ships nothing, or every item is excluded.
	 * Invalid postcodes are left to WooCommerce's own address validation.
	 *
	 * @param string $country  Country.
	 * @param string $postcode Postcode.
	 */
	private function unserviceable( string $country, string $postcode ): ?string {
		if ( ! Plugin::settings()->get( 'checkout', 'validate_checkout' ) || ! WC()->cart || ! WC()->cart->needs_shipping() || '' === trim( $postcode ) ) {
			return null;
		}

		$enforced = false;
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( isset( $item['data'] ) && $item['data'] instanceof \WC_Product && ! Plugin::products()->is_excluded( $item['data'] ) ) {
				$enforced = true;
				break;
			}
		}

		if ( ! $enforced ) {
			return null;
		}

		$result = Plugin::checker()->check( $postcode, $country, Plugin::products()->cart_extra_days() );

		return 'unavailable' === $result['status'] ? Plugin::messages()->for_result( $result )['headline'] : null;
	}
}
