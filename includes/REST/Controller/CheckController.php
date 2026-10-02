<?php
/**
 * REST: GET wbpc/v1/check - the public storefront lookup.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\REST\Controller;

use Wbcom\PincodeChecker\Core\Plugin;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only and nonce-free on purpose: pages stay fully cacheable (a nonce baked into a cached page
 * goes stale), and nothing here changes state. Abuse is damped by a per-client rate limit.
 */
final class CheckController {

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			AreasController::NS,
			'/check',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'check' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'postcode'   => array(
						'type'              => 'string',
						'required'          => true,
						'maxLength'         => 32,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'country'    => array(
						'type'    => 'string',
						'pattern' => '^[A-Za-z]{2}$|^$',
						'default' => '',
					),
					'product_id' => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
				),
			)
		);
	}

	/**
	 * GET /check.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function check( WP_REST_Request $request ): WP_REST_Response {
		if ( ! Plugin::rate_limiter()->hit( 'check' ) ) {
			// rest_ensure_response() returns a WP_Error unchanged, which has no header(): convert it.
			$response = rest_convert_error_to_response( new WP_Error( 'wbpc_rate_limited', __( 'Too many checks. Please wait a minute and try again.', 'woo-pincode-checker' ), array( 'status' => 429 ) ) );
			$response->header( 'Retry-After', '60' );
			return $response;
		}

		$product = $request['product_id'] ? wc_get_product( (int) $request['product_id'] ) : null;
		$result  = Plugin::checker()->check( (string) $request['postcode'], strtoupper( (string) $request['country'] ), $product ? Plugin::products()->extra_days( $product ) : 0 );

		$response = new WP_REST_Response(
			array_merge(
				$result,
				array(
					'messages' => Plugin::messages()->for_result( $result ),
					'can_add'  => $this->can_add( $result, (int) $request['product_id'] ),
				)
			)
		);

		// Personal to the shopper: browsers may reuse it briefly, shared caches and CDNs must not.
		$response->header( 'Cache-Control', 'private, max-age=60' );

		return $response;
	}

	/**
	 * Whether the product's Add to cart should stay usable for this result.
	 *
	 * @param array $result     Check result.
	 * @param int   $product_id Product, 0 when checked outside a product page.
	 */
	private function can_add( array $result, int $product_id ): bool {
		$product = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product || Plugin::products()->is_excluded( $product ) || 'available' === $result['status'] ) {
			return true;
		}

		$general = (array) Plugin::settings()->get( 'general' );

		// A typo should not lock the button unless a check is required; a real "no" follows the setting.
		return 'invalid' === $result['status'] ? ! $general['require_check'] : 'allow' === $general['unavailable_action'];
	}
}
