<?php
/**
 * Shopper-facing text for a check result, from the Display settings.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Frontend;

use Wbcom\PincodeChecker\Services\SettingsService;

defined( 'ABSPATH' ) || exit;

/**
 * Built on the server so the storefront script only displays text, and every surface words it the same way.
 */
final class Messages {

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Lines to show for a result.
	 *
	 * @param array $result CheckService result (+ optional 'estimate' from the delivery engine).
	 * @return array{headline:string, estimate:?string, shipping:?string, cod:?string}
	 */
	public function for_result( array $result ): array {
		$d      = (array) $this->settings->get( 'display' );
		$tokens = array(
			'{postcode}' => $result['postcode'],
			'{city}'     => '' !== $result['city'] ? $result['city'] : $result['postcode'],
			'{state}'    => $result['state'],
		);

		if ( 'invalid' === $result['status'] ) {
			return array(
				'headline' => $d['msg_invalid'],
				'estimate' => null,
				'shipping' => null,
				'cod'      => null,
			);
		}

		if ( 'unavailable' === $result['status'] ) {
			return array(
				'headline' => strtr( $d['msg_unavailable'], $tokens ),
				'estimate' => null,
				'shipping' => null,
				'cod'      => null,
			);
		}

		$estimate = in_array( 'product', (array) $this->settings->get( 'delivery', 'show_on' ), true ) ? ( $result['estimate']['label'] ?? null ) : null;

		return array(
			'headline' => strtr( $d['msg_available'], $tokens ),
			'estimate' => $estimate ? str_replace( '{estimate}', $estimate, $d['msg_estimate'] ) : null,
			// Only promise a fee the cart will actually charge: area fees are charged by the Pincode rate method.
			'shipping' => null === $result['shipping_fee'] || ! \Wbcom\PincodeChecker\Integrations\WooCommerce\ShippingMethod::in_use() ? null : str_replace( '{amount}', $this->money( (float) $result['shipping_fee'] ), $d['shipping_label'] ),
			'cod'      => $d['show_cod_line'] ? ( $result['cod']['allowed'] ? $d['cod_available'] : $d['cod_unavailable'] ) : null,
		);
	}

	/**
	 * Store-formatted amount as plain text.
	 *
	 * @param float $amount Amount.
	 */
	private function money( float $amount ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}
}
