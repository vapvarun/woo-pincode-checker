<?php
/**
 * REST: wbpc/v1/areas.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\REST\Controller;

use Wbcom\PincodeChecker\Core\Plugin;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Thin adapter: validate the request shape, call AreaService, shape the response.
 */
final class AreasController {

	public const NS = 'wbpc/v1';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		$can = static fn(): bool => current_user_can( Plugin::cap() );

		register_rest_route(
			self::NS,
			'/areas',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list' ),
					'permission_callback' => $can,
					'args'                => $this->filter_args() + array(
						'orderby'  => array(
							'type'    => 'string',
							'enum'    => array( 'newest', 'code', 'city', 'days', 'updated' ),
							'default' => 'newest',
						),
						'order'    => array(
							'type'    => 'string',
							'enum'    => array( 'asc', 'desc' ),
							'default' => 'desc',
						),
						'page'     => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 100,
							'default' => 25,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => $can,
				),
			)
		);

		register_rest_route(
			self::NS,
			'/areas/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => $can,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => $can,
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => $can,
				),
			)
		);

		register_rest_route(
			self::NS,
			'/areas/batch-delete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'batch_delete' ),
				'permission_callback' => $can,
				'args'                => $this->filter_args() + array(
					'ids' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
					'all' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * GET /areas.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function list( WP_REST_Request $request ): WP_REST_Response {
		$filters  = $this->filters( $request );
		$per_page = (int) $request['per_page'];
		$total    = Plugin::areas()->count_where( $filters );
		$rows     = Plugin::areas()->page( $filters, (string) $request['orderby'], (string) $request['order'], $per_page, (int) $request['page'] );
		$service  = Plugin::area_service();

		$response = new WP_REST_Response( array_map( array( $service, 'format' ), $rows ) );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) max( 1, (int) ceil( $total / $per_page ) ) );

		return $response;
	}

	/**
	 * POST /areas.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$area = Plugin::area_service()->create( (array) $request->get_json_params() );

		return is_wp_error( $area ) ? $area : new WP_REST_Response( $area, 201 );
	}

	/**
	 * GET /areas/{id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function get( WP_REST_Request $request ) {
		$row = Plugin::areas()->find( (int) $request['id'] );

		return $row
			? Plugin::area_service()->format( $row )
			: new \WP_Error( 'wbpc_area_not_found', __( 'This area no longer exists. It may have been deleted already.', 'woo-pincode-checker' ), array( 'status' => 404 ) );
	}

	/**
	 * PATCH /areas/{id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function update( WP_REST_Request $request ) {
		return Plugin::area_service()->update( (int) $request['id'], (array) $request->get_json_params() );
	}

	/**
	 * DELETE /areas/{id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function delete( WP_REST_Request $request ) {
		$result = Plugin::area_service()->delete( (int) $request['id'] );

		return is_wp_error( $result ) ? $result : array( 'deleted' => 1 );
	}

	/**
	 * POST /areas/batch-delete: { ids: [...] } or { all: true, ...filters }.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|\WP_Error
	 */
	public function batch_delete( WP_REST_Request $request ) {
		if ( $request['all'] ) {
			return array( 'deleted' => Plugin::area_service()->delete_many( null, $this->filters( $request ) ) );
		}

		$ids = (array) $request['ids'];

		if ( ! $ids ) {
			return new \WP_Error( 'wbpc_nothing_selected', __( 'Select at least one area.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}

		return array( 'deleted' => Plugin::area_service()->delete_many( $ids ) );
	}

	/**
	 * List filter args, shared by list and batch delete so "delete all matching" deletes what was listed.
	 */
	private function filter_args(): array {
		return array(
			'search'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'    => array(
				'type' => 'string',
				'enum' => array( 'exact', 'prefix', 'range' ),
			),
			'status'  => array(
				'type' => 'string',
				'enum' => array( 'serviceable', 'blocked' ),
			),
			'cod'     => array(
				'type' => 'string',
				'enum' => array( 'yes', 'no' ),
			),
			'country' => array(
				'type'    => 'string',
				'pattern' => '^[A-Za-z]{2}$|^any$',
			),
		);
	}

	/**
	 * Request -> repository filters.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	private function filters( WP_REST_Request $request ): array {
		return Plugin::area_service()->filters_from( $request->get_params() );
	}
}
