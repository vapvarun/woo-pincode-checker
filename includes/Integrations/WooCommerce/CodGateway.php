<?php
/**
 * Cash on delivery: offered only where the area allows it, with the area's COD fee.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Integrations\WooCommerce;

use Wbcom\PincodeChecker\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * The woocommerce_available_payment_gateways filter drives both the classic payment list and the Blocks
 * checkout (the Store API cart lists available methods from it), and it is re-checked when the
 * order is placed.
 */
final class CodGateway {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'gate' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'fee' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'classic_refresh' ) );
	}

	/**
	 * Classic checkout: refresh totals on payment-method change so the COD fee shows before ordering.
	 */
	public function classic_refresh(): void {
		if ( ! is_checkout() || is_order_received_page() || ! Plugin::settings()->get( 'checkout', 'cod_fee' ) || has_block( 'woocommerce/checkout', wc_get_page_id( 'checkout' ) ) ) {
			return;
		}

		wp_enqueue_script( 'wbpc-checkout', WBPC_URL . 'assets/frontend/js/checkout.js', array( 'jquery', 'wc-checkout' ), WBPC_VERSION, array( 'in_footer' => true ) );
	}

	/**
	 * Remove COD where the destination's area disallows it (or is not served).
	 *
	 * @param mixed $gateways Available gateways (other plugins may pass anything).
	 * @return mixed
	 */
	public function gate( $gateways ) {
		if ( ! is_array( $gateways ) || ! isset( $gateways['cod'] ) || ( is_admin() && ! wp_doing_ajax() ) || ! Plugin::settings()->get( 'checkout', 'cod_gating' ) ) {
			return $gateways;
		}

		$result = $this->result();

		if ( $result && ! $result['cod']['allowed'] ) {
			unset( $gateways['cod'] );
		}

		return $gateways;
	}

	/**
	 * The area's COD fee when COD is the chosen payment method.
	 *
	 * @param \WC_Cart $cart Cart.
	 */
	public function fee( \WC_Cart $cart ): void {
		if ( ! Plugin::settings()->get( 'checkout', 'cod_fee' ) || ! WC()->session || 'cod' !== WC()->session->get( 'chosen_payment_method' ) ) {
			return;
		}

		$result = $this->result();

		if ( $result && $result['cod']['allowed'] && $result['cod']['fee'] > 0 ) {
			$cart->add_fee( (string) Plugin::settings()->get( 'checkout', 'cod_fee_label' ), (float) $result['cod']['fee'], true );
		}
	}

	/**
	 * Check result for the shopper's destination; null when unknown or the cart ships nothing.
	 */
	private function result(): ?array {
		if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return null;
		}

		$destination = CustomerPostcode::current();

		return $destination ? Plugin::checker()->check( $destination['postcode'], $destination['country'] ) : null;
	}
}
