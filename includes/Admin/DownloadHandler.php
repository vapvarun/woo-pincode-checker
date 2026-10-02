<?php
/**
 * File downloads via admin-post: area export, sample CSV, import error report.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom\PincodeChecker\Services\CsvService;

defined( 'ABSPATH' ) || exit;

/**
 * Downloads are plain form/link targets, so they work without JavaScript.
 */
final class DownloadHandler {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'admin_post_wbpc_export', array( $this, 'export' ) );
		add_action( 'admin_post_wbpc_sample_csv', array( $this, 'sample' ) );
		add_action( 'admin_post_wbpc_import_errors', array( $this, 'errors' ) );
	}

	/**
	 * Signed URL for a download action.
	 *
	 * @param string $action admin-post action.
	 * @param array  $args   Extra query args.
	 */
	public static function url( string $action, array $args = array() ): string {
		return wp_nonce_url( add_query_arg( array( 'action' => $action ) + $args, admin_url( 'admin-post.php' ) ), $action );
	}

	/**
	 * Stream every area matching the filters as CSV. Constant memory: 2,000 rows per query.
	 */
	public function export(): void {
		$this->guard( 'wbpc_export' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified in guard().
		$filters = Plugin::area_service()->filters_from( wp_unslash( $_GET ) );
		$output  = $this->open( 'pincode-areas-' . gmdate( 'Y-m-d' ) . '.csv' );
		$service = Plugin::area_service();
		$csv     = Plugin::csv();
		$last    = 0;

		CsvService::write( $output, CsvService::COLUMNS );

		do {
			$rows = Plugin::areas()->after_id( $last, $filters );
			foreach ( $rows as $row ) {
				CsvService::write( $output, $csv->to_row( $service->format( $row ) ) );
				$last = (int) $row['id'];
			}
			flush();
		} while ( $rows );

		exit;
	}

	/**
	 * The sample file.
	 */
	public function sample(): void {
		$this->guard( 'wbpc_sample_csv' );
		$output = $this->open( 'pincode-areas-sample.csv' );
		fwrite( $output, Plugin::csv()->sample() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		exit;
	}

	/**
	 * An import's error report.
	 */
	public function errors(): void {
		$this->guard( 'wbpc_import_errors' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified in guard().
		$path = Plugin::importer()->error_file( sanitize_key( wp_unslash( $_GET['job'] ?? '' ) ) );

		if ( '' === $path ) {
			wp_die( esc_html__( 'This error report is no longer available.', 'woo-pincode-checker' ), '', array( 'response' => 404 ) );
		}

		$output = $this->open( 'pincode-import-errors.csv' );
		fwrite( $output, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Capability + nonce, or stop.
	 *
	 * @param string $action Nonce action.
	 */
	private function guard( string $action ): void {
		if ( ! current_user_can( Plugin::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to download this file.', 'woo-pincode-checker' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * Send download headers and return the output stream.
	 *
	 * @param string $filename File name.
	 * @return resource
	 */
	private function open( string $filename ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		return fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	}
}
