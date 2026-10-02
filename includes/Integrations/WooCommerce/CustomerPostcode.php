<?php
/**
 * Where the shopper's order is going.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping address first, then billing, then the pincode the shopper checked on a product page
 * (wbpc_postcode cookie). Never writes anything back.
 */
final class CustomerPostcode {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'prefill_guest' ) );
	}

	/**
	 * Give a guest's checked pincode to the cart session, so shipping rates, COD and the checkout
	 * form start from it. Guests only: a logged-in customer's saved address is never touched.
	 */
	public function prefill_guest(): void {
		$customer = WC()->customer;

		if ( is_user_logged_in() || ! $customer || '' !== (string) $customer->get_shipping_postcode() || ! \Wbcom\PincodeChecker\Core\Plugin::settings()->get( 'checkout', 'prefill_postcode' ) ) {
			return;
		}

		$saved = self::from_cookie();
		if ( ! $saved ) {
			return;
		}

		// Change only what the shopper told us. Resetting the state too made billing and shipping
		// differ, so the Checkout block unticked "Use same address for billing".
		foreach ( array( 'shipping', 'billing' ) as $type ) {
			if ( $customer->{"get_{$type}_country"}() !== $saved['country'] ) {
				$customer->{"set_{$type}_country"}( $saved['country'] );
				$customer->{"set_{$type}_state"}( '' );
			}
			$customer->{"set_{$type}_postcode"}( $saved['postcode'] );
		}
	}

	/**
	 * Destination for the current shopper.
	 *
	 * @return array{country:string, postcode:string}|null
	 */
	public static function current(): ?array {
		$customer = WC()->customer;

		if ( $customer ) {
			$postcode = $customer->get_shipping_postcode() ? $customer->get_shipping_postcode() : $customer->get_billing_postcode();
			$country  = $customer->get_shipping_country() ? $customer->get_shipping_country() : $customer->get_billing_country();

			if ( '' !== (string) $postcode ) {
				return array(
					'country'  => (string) $country,
					'postcode' => (string) $postcode,
				);
			}
		}

		return self::from_cookie();
	}

	/**
	 * The pincode saved by the product-page checker.
	 *
	 * @return array{country:string, postcode:string}|null
	 */
	public static function from_cookie(): ?array {
		$raw   = isset( $_COOKIE['wbpc_postcode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['wbpc_postcode'] ) ) : '';
		$parts = explode( ':', $raw, 2 );

		if ( 2 !== count( $parts ) || 1 !== preg_match( '/^[A-Z]{2}$/', $parts[0] ) || '' === $parts[1] ) {
			return null;
		}

		return array(
			'country'  => $parts[0],
			'postcode' => $parts[1],
		);
	}
}
