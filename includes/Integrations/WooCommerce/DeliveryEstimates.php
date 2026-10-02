<?php
/**
 * Delivery estimates in the cart, checkout, order and emails.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Integrations\WooCommerce;

use Wbcom\PincodeChecker\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Cart and checkout: appended to every shipping rate label, which the classic templates and the
 * Cart/Checkout blocks both render with no extra script. Order: saved once at checkout, so the
 * date the customer was promised never changes afterwards.
 */
final class DeliveryEstimates {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'stamp_packages' ) );
		add_filter( 'woocommerce_package_rates', array( $this, 'label_rates' ), 100, 2 );
		add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'clean_shipping_item' ) );

		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_order' ) );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'save_order' ) );

		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'admin_order' ) );
		add_action( 'woocommerce_email_order_meta', array( $this, 'email' ), 20, 3 );
	}

	/**
	 * WooCommerce caches rates per package for the session; adding today's date makes that cache
	 * expire daily, so "arrives Oct 8" never survives into Oct 8.
	 *
	 * @param array $packages Packages.
	 */
	public function stamp_packages( array $packages ): array {
		foreach ( $packages as &$package ) {
			$package['wbpc_day'] = wp_date( 'Y-m-d' );
		}

		return $packages;
	}

	/**
	 * Append the estimate to each rate's label.
	 *
	 * @param \WC_Shipping_Rate[] $rates   Rates.
	 * @param array               $package Package.
	 */
	public function label_rates( array $rates, array $package ): array {
		if ( ! $rates || ! $this->shows( 'cart' ) ) {
			return $rates;
		}

		$estimate = $this->for_destination( (string) ( $package['destination']['country'] ?? '' ), (string) ( $package['destination']['postcode'] ?? '' ) );

		if ( ! $estimate ) {
			return $rates;
		}

		foreach ( $rates as $rate ) {
			$rate->add_meta_data( 'wbpc_base_label', $rate->get_label() );
			/* translators: 1: shipping method name, 2: delivery estimate such as "Oct 8 to Oct 10". */
			$rate->set_label( sprintf( __( '%1$s (arrives %2$s)', 'woo-pincode-checker' ), $rate->get_label(), $estimate['label'] ) );
		}

		return $rates;
	}

	/**
	 * Orders keep the plain method name ("Flat rate"): the label suffix is for shoppers, and a dated
	 * name would split shipping reports by day. The estimate itself is saved as _wbpc_estimate.
	 *
	 * @param \WC_Order_Item_Shipping $item Shipping line being created (classic + Blocks checkout).
	 */
	public function clean_shipping_item( \WC_Order_Item_Shipping $item ): void {
		$base = $item->get_meta( 'wbpc_base_label' );

		if ( '' !== (string) $base ) {
			$item->set_name( (string) $base );
			$item->set_method_title( (string) $base );
			$item->delete_meta_data( 'wbpc_base_label' );
		}
	}

	/**
	 * Store the promised dates on the order (classic + Blocks checkout).
	 *
	 * @param \WC_Order $order Order being created.
	 */
	public function save_order( \WC_Order $order ): void {
		$postcode = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode();
		$country  = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();

		if ( '' === (string) $postcode ) {
			return;
		}

		$order->update_meta_data( '_wbpc_postcode', strtoupper( $country . ':' . $postcode ) );

		$estimate = $this->for_destination( (string) $country, (string) $postcode );
		if ( $estimate ) {
			$order->update_meta_data( '_wbpc_estimate', $estimate );
		}
	}

	/**
	 * Thank-you page and My Account order view.
	 *
	 * @param \WC_Order $order Order.
	 */
	public function order_details( \WC_Order $order ): void {
		$estimate = $order->get_meta( '_wbpc_estimate' );

		if ( is_array( $estimate ) && $this->shows( 'order' ) ) {
			printf( '<p class="wbpc-order-estimate"><strong>%s</strong> %s</p>', esc_html__( 'Estimated delivery:', 'woo-pincode-checker' ), esc_html( (string) $estimate['label'] ) );
		}
	}

	/**
	 * Admin order screen (always shown to staff).
	 *
	 * @param \WC_Order $order Order.
	 */
	public function admin_order( \WC_Order $order ): void {
		$estimate = $order->get_meta( '_wbpc_estimate' );

		if ( is_array( $estimate ) ) {
			/* translators: 1: estimate label, 2: earliest date Y-m-d, 3: latest date Y-m-d. */
			printf( '<p><strong>%s</strong><br>%s</p>', esc_html__( 'Promised delivery', 'woo-pincode-checker' ), esc_html( sprintf( __( '%1$s (%2$s to %3$s)', 'woo-pincode-checker' ), $estimate['label'], $estimate['min'], $estimate['max'] ) ) );
		}
	}

	/**
	 * Order emails.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $sent_to_admin Admin email.
	 * @param bool      $plain_text    Plain-text email.
	 */
	public function email( $order, $sent_to_admin, $plain_text ): void {
		$estimate = $order instanceof \WC_Order ? $order->get_meta( '_wbpc_estimate' ) : null;

		if ( ! is_array( $estimate ) || ! $this->shows( 'emails' ) ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Estimated delivery:', 'woo-pincode-checker' ) . ' ' . esc_html( (string) $estimate['label'] ) . "\n";
			return;
		}

		printf( '<p style="margin:0 0 16px;"><strong>%s</strong> %s</p>', esc_html__( 'Estimated delivery:', 'woo-pincode-checker' ), esc_html( (string) $estimate['label'] ) );
	}

	/**
	 * Estimate for a destination and the current cart, or null when not deliverable / turned off.
	 *
	 * @param string $country  Country.
	 * @param string $postcode Postcode.
	 */
	private function for_destination( string $country, string $postcode ): ?array {
		if ( '' === $postcode ) {
			$saved    = CustomerPostcode::from_cookie();
			$country  = $saved['country'] ?? $country;
			$postcode = $saved['postcode'] ?? '';
		}

		if ( '' === $postcode ) {
			return null;
		}

		$result = Plugin::checker()->check( $postcode, $country, Plugin::products()->cart_extra_days() );

		return 'available' === $result['status'] && is_array( $result['estimate'] ) ? $result['estimate'] : null;
	}

	/**
	 * Whether the estimate shows on a surface.
	 *
	 * @param string $surface product|cart|order|emails.
	 */
	private function shows( string $surface ): bool {
		return (bool) Plugin::settings()->get( 'delivery', 'show_estimate' ) && in_array( $surface, (array) Plugin::settings()->get( 'delivery', 'show_on' ), true );
	}
}
