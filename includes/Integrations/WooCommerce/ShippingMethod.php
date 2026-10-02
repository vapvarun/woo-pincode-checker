<?php
/**
 * "Pincode rate" shipping method.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Integrations\WooCommerce;

use Wbcom\PincodeChecker\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Charges base cost + the matching area's shipping fee, and offers no rate where the area is not
 * served. Works in classic and Blocks checkout like any WooCommerce method; other methods in the
 * zone are left alone.
 */
final class ShippingMethod extends \WC_Shipping_Method {

	public const ID = 'wbpc_pincode_rate';

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = self::ID;
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Pincode rate', 'woo-pincode-checker' );
		$this->method_description = __( 'Charges each delivery area\'s shipping fee (set under Pincode Checker > Service Areas) plus an optional base cost. Not offered where you do not deliver.', 'woo-pincode-checker' );
		$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );

		$this->instance_form_fields = array(
			'title'         => array(
				'title'   => __( 'Name shown to shoppers', 'woo-pincode-checker' ),
				'type'    => 'text',
				'default' => __( 'Delivery', 'woo-pincode-checker' ),
			),
			'tax_status'    => array(
				'title'   => __( 'Tax status', 'woo-pincode-checker' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'woo-pincode-checker' ),
					'none'    => _x( 'None', 'Tax status', 'woo-pincode-checker' ),
				),
			),
			'base_cost'     => array(
				'title'       => __( 'Base cost', 'woo-pincode-checker' ),
				'type'        => 'price',
				'default'     => '0',
				'description' => __( 'Added to every order, before the area fee.', 'woo-pincode-checker' ),
				'desc_tip'    => true,
			),
			'fallback_cost' => array(
				'title'       => __( 'Fee when the area has none', 'woo-pincode-checker' ),
				'type'        => 'price',
				'default'     => '0',
				'description' => __( 'Used for areas without their own shipping fee, and for postcodes delivered on default terms.', 'woo-pincode-checker' ),
				'desc_tip'    => true,
			),
		);

		$this->init_settings();
		$this->title      = (string) $this->get_option( 'title' );
		$this->tax_status = (string) $this->get_option( 'tax_status' );

		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			function (): void {
				$this->process_admin_options();
			}
		);
	}

	/**
	 * Offer a rate only where the destination is served.
	 *
	 * @param array $package Package.
	 */
	public function calculate_shipping( $package = array() ): void {
		$postcode = (string) ( $package['destination']['postcode'] ?? '' );
		$country  = (string) ( $package['destination']['country'] ?? '' );

		if ( '' === $postcode ) {
			$saved    = CustomerPostcode::from_cookie();
			$postcode = $saved['postcode'] ?? '';
			$country  = $saved['country'] ?? $country;
		}

		if ( '' === $postcode ) {
			return; // Rate appears once the shopper gives a postcode.
		}

		$result = Plugin::checker()->check( $postcode, $country );

		if ( 'available' !== $result['status'] ) {
			return;
		}

		$fee = null === $result['shipping_fee'] ? (float) wc_format_decimal( (string) $this->get_option( 'fallback_cost' ) ) : (float) $result['shipping_fee'];

		$this->add_rate(
			array(
				'id'      => $this->get_rate_id(),
				'label'   => $this->title,
				'cost'    => (float) wc_format_decimal( (string) $this->get_option( 'base_cost' ) ) + $fee,
				'package' => $package,
			)
		);
	}

	/**
	 * Whether the method is enabled in at least one zone (memoised per request).
	 */
	public static function in_use(): bool {
		static $in_use = null;

		if ( null === $in_use ) {
			$in_use  = false;
			$zones   = \WC_Shipping_Zones::get_zones();
			$zones[] = array( 'shipping_methods' => ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods( true ) );

			foreach ( $zones as $zone ) {
				foreach ( $zone['shipping_methods'] as $method ) {
					if ( self::ID === $method->id && 'yes' === $method->enabled ) {
						$in_use = true;
						break 2;
					}
				}
			}
		}

		return $in_use;
	}
}
