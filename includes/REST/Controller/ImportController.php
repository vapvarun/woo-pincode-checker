<?php
/**
 * REST: wbpc/v1/import.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\REST\Controller;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom\PincodeChecker\Services\ImportService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Upload + preview, progress, start, cancel.
 */
final class ImportController {

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		$can = static fn(): bool => current_user_can( Plugin::cap() );

		register_rest_route(
			AreasController::NS,
			'/import',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'status' ),
					'permission_callback' => $can,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'stage' ),
					'permission_callback' => $can,
					'args'                => array(
						'mode'    => array(
							'type'    => 'string',
							'enum'    => ImportService::MODES,
							'default' => 'skip',
						),
						'country' => array(
							'type'    => 'string',
							'pattern' => '^[A-Za-z]{2}$|^$',
							'default' => '',
						),
					),
				),
			)
		);

		foreach ( array( 'start', 'cancel' ) as $action ) {
			register_rest_route(
				AreasController::NS,
				'/import/' . $action,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, $action ),
					'permission_callback' => $can,
				)
			);
		}
	}

	/**
	 * GET /import: current job + history.
	 */
	public function status(): array {
		$importer = Plugin::importer();
		$importer->run_for( 4.0 ); // No-op unless a job is queued or running.
		$job = $importer->current();

		return array(
			'job'     => $job ? $importer->public_job( $job ) : null,
			'history' => array_map( array( $importer, 'public_job' ), $importer->history() ),
		);
	}

	/**
	 * POST /import (multipart, field "file"): validate, store privately, preview.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function stage( WP_REST_Request $request ) {
		$file = $request->get_file_params()['file'] ?? null;

		if ( ! $file || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			return new WP_Error( 'wbpc_import_upload', __( 'Choose a CSV file to upload.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}

		return Plugin::importer()->stage( (string) $file['tmp_name'], (string) $file['name'], (string) $request['mode'], strtoupper( (string) $request['country'] ) );
	}

	/**
	 * POST /import/start.
	 *
	 * @return array|WP_Error
	 */
	public function start() {
		return Plugin::importer()->start();
	}

	/**
	 * POST /import/cancel.
	 *
	 * @return array|WP_Error
	 */
	public function cancel() {
		return Plugin::importer()->cancel();
	}
}
