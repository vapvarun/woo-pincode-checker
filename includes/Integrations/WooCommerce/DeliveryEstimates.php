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
 * Cart and checkout: appended to each delivery rate's label (not local pickup), which the classic templates and the
 * Cart/Checkout blocks both render with no extra script. Order: saved once at checkout, so the
 * date the customer was promised never changes afterwards.
 */
final class DeliveryEstimates {

	/**
	 * Shipping methods where the customer collects the order: "arrives on" does not apply.
	 * local_pickup = classic checkout, pickup_location = Checkout block local pickup.
	 */
	private const PICKUP_METHODS = array( 'local_pickup', 'pickup_location' );

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
			$rate_estimate = $this->for_method( $estimate, (string) $rate->get_method_id(), (int) $rate->get_instance_id() );
			if ( ! $rate_estimate ) {
				continue;
			}

			$rate->add_meta_data( 'wbpc_base_label', $rate->get_label() );
			/* translators: 1: shipping method name, 2: delivery estimate such as "Oct 8 to Oct 10". */
			$rate->set_label( sprintf( __( '%1$s (arrives %2$s)', 'woo-pincode-checker' ), $rate->get_label(), $rate_estimate['label'] ) );
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

		// The promise follows the shipping method the customer chose (none for local pickup).
		$shipping = current( $order->get_shipping_methods() );
		if ( $estimate && $shipping ) {
			$estimate = $this->for_method( $estimate, (string) $shipping->get_method_id(), (int) $shipping->get_instance_id() );
		}

		if ( $estimate ) {
			$order->update_meta_data( '_wbpc_estimate', $estimate );
		} else {
			$order->delete_meta_data( '_wbpc_estimate' );
		}
	}

	/**
	 * The estimate for one shipping method: none for pickup, otherwise filterable so a store can
	 * shift it (express) or remove it for a method.
	 *
	 * @param array  $estimate    Estimate for the destination (min, max, label).
	 * @param string $method_id   Shipping method id, e.g. flat_rate.
	 * @param int    $instance_id Shipping method instance id (per zone).
	 */
	private function for_method( array $estimate, string $method_id, int $instance_id ): ?array {
		$estimate = in_array( $method_id, self::PICKUP_METHODS, true ) ? null : $estimate;

		/**
		 * Filters the delivery estimate shown for a shipping method and saved on the order.
		 *
		 * @param array|null $estimate    Estimate (min, max as Y-m-d, label), or null for none.
		 * @param string     $method_id   Shipping method id.
		 * @param int        $instance_id Shipping method instance id.
		 */
		$estimate = apply_filters( 'wbpc_shipping_estimate', $estimate, $method_id, $instance_id );

		return is_array( $estimate ) ? $estimate : null;
	}

	/**
	 * Thank-you page and My Account order view.
	 *
	 * @param \WC_Order $order Order.
	 */
	public function order_details( \WC_Order $order ): void {
		$label = $this->order_label( $order );

		if ( '' !== $label && $this->shows( 'order' ) ) {
			/* translators: %s: delivery estimate, e.g. "Sat, Oct 3 to Mon, Oct 5". */
			printf( '<p class="wbpc-order-estimate">%s</p>', esc_html( sprintf( __( 'Estimated delivery: %s', 'woo-pincode-checker' ), $label ) ) );
		}
	}

	/**
	 * Admin order screen (always shown to staff).
	 *
	 * @param \WC_Order $order Order.
	 */
	public function admin_order( \WC_Order $order ): void {
		$estimate = $order->get_meta( '_wbpc_estimate' );

		if ( is_array( $estimate ) && isset( $estimate['min'], $estimate['max'] ) ) {
			// Staff see full dates in the site's date format and their own language.
			$format = (string) get_option( 'date_format' );
			$min    = wp_date( $format, (int) strtotime( $estimate['min'] . ' 12:00' ) );
			$max    = wp_date( $format, (int) strtotime( $estimate['max'] . ' 12:00' ) );
			/* translators: 1: earliest delivery date, 2: latest delivery date. */
			$dates = $estimate['min'] === $estimate['max'] ? $max : sprintf( __( '%1$s to %2$s', 'woo-pincode-checker' ), $min, $max );

			printf( '<p><strong>%s</strong><br>%s</p>', esc_html__( 'Promised delivery', 'woo-pincode-checker' ), esc_html( $dates ) );
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
		$label = $order instanceof \WC_Order ? $this->order_label( $order ) : '';

		if ( '' === $label || ! $this->shows( 'emails' ) ) {
			return;
		}

		/* translators: %s: delivery estimate, e.g. "Sat, Oct 3 to Mon, Oct 5". */
		$text = sprintf( __( 'Estimated delivery: %s', 'woo-pincode-checker' ), $label );

		if ( $plain_text ) {
			echo "\n" . esc_html( $text ) . "\n";
			return;
		}

		printf( '<p style="margin:0 0 16px;">%s</p>', esc_html( $text ) );
	}

	/**
	 * The saved promise as text, built now: in the language of whoever is reading (customer,
	 * staff, or the email's locale) and always as dates, since "in 3 days" goes stale.
	 *
	 * @param \WC_Order $order Order.
	 */
	private function order_label( \WC_Order $order ): string {
		$estimate = $order->get_meta( '_wbpc_estimate' );

		return is_array( $estimate ) && isset( $estimate['min'], $estimate['max'] )
			? Plugin::dates()->label( $estimate, null, false )
			: '';
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
