<?php
/**
 * REST: wbpc/v1/tools/*  - owner utilities.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\REST\Controller;

use Wbcom\PincodeChecker\Core\Plugin;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * The Overview "Test a postcode" tool.
 */
final class ToolsController {

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			AreasController::NS,
			'/tools/test',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test' ),
				'permission_callback' => static fn(): bool => current_user_can( Plugin::cap() ),
				'args'                => array(
					'postcode' => array(
						'type'     => 'string',
						'required' => true,
					),
					'country'  => array(
						'type'    => 'string',
						'pattern' => '^[A-Za-z]{2}$|^$',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * POST /tools/test: the shopper's result plus which area decided it.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function test( WP_REST_Request $request ): array {
		$result = Plugin::checker()->check( (string) $request['postcode'], (string) $request['country'] );
		$area   = $result['area_id'] ? Plugin::areas()->find( $result['area_id'] ) : null;

		return array(
			'result' => $result,
			'area'   => $area ? Plugin::area_service()->format( $area ) : null,
		);
	}
}
