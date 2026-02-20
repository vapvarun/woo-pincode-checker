<?php
/**
 * Enhanced Import/Export functionality for WooCommerce Pincode Checker
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.3.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.WP.AlternativeFunctions.file_system_operations_readfile, WordPress.WP.AlternativeFunctions.unlink_unlink, Generic.PHP.ForbiddenFunctions.Found -- CSV import/export requires direct PHP file handling.

/**
 * Enhanced Import/Export class for bulk operations
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin
 * @author     wbcomdesigns <admin@wbcomdesigns.com>
 */
class Woo_Pincode_Checker_Import_Export {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register AJAX hooks for import/export
	 */
	public function register_ajax_hooks() {
		add_action( 'wp_ajax_wpc_export_pincodes', array( $this, 'ajax_export_pincodes' ) );
		add_action( 'wp_ajax_wpc_validate_import', array( $this, 'ajax_validate_import' ) );
		add_action( 'wp_ajax_wpc_process_import_chunk', array( $this, 'ajax_process_import_chunk' ) );
		add_action( 'wp_ajax_wpc_download_sample_csv', array( $this, 'download_sample_csv' ) );
	}

	/**
	 * Export pincodes to CSV with advanced filtering
	 */
	public function ajax_export_pincodes() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized access' );
			exit;
		}

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc-admin-nonce' ) ) {
			wp_send_json_error( 'Invalid security token' );
			exit;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Get filter parameters.
		$filters = array();
		if ( isset( $_POST['filter_state'] ) && ! empty( $_POST['filter_state'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$filters['state'] = sanitize_text_field( wp_unslash( $_POST['filter_state'] ) );
		}
		if ( isset( $_POST['filter_city'] ) && ! empty( $_POST['filter_city'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$filters['city'] = sanitize_text_field( wp_unslash( $_POST['filter_city'] ) );
		}
		if ( isset( $_POST['filter_cod'] ) && '' !== $_POST['filter_cod'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			$filters['case_on_delivery'] = intval( $_POST['filter_cod'] );
		}

		// Build query.
		$query      = "SELECT * FROM {$table_name}";
		$conditions = array();
		$params     = array();

		foreach ( $filters as $field => $value ) {
			if ( 'case_on_delivery' === $field ) {
				$conditions[] = "{$field} = %d";
				$params[]     = $value;
			} else {
				$conditions[] = "{$field} LIKE %s";
				$params[]     = '%' . $wpdb->esc_like( $value ) . '%';
			}
		}

		if ( ! empty( $conditions ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $conditions );
		}

		$query .= ' ORDER BY pincode ASC';

		// Execute query.
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query built with $wpdb->prepare().
			$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query built with $wpdb->prepare().
			$results = $wpdb->get_results( $query, ARRAY_A );
		}

		if ( empty( $results ) ) {
			wp_send_json_error( 'No pincodes found to export' );
			exit;
		}

		// Generate CSV content.
		$csv_content = $this->generate_csv_content( $results );

		// Generate filename.
		$filename = 'woo-pincodes-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

		// Save to temporary file.
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/wpc-temp/';

		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$file_path = $temp_dir . $filename;
		file_put_contents( $file_path, $csv_content );

		// Generate download URL.
		$download_url = admin_url( 'admin-ajax.php' ) . '?action=wpc_download_export&file=' . $filename . '&nonce=' . wp_create_nonce( 'wpc-download-' . $filename );

		wp_send_json_success(
			array(
				'download_url' => $download_url,
				'filename'     => $filename,
				'count'        => count( $results ),
			)
		);
	}

	/**
	 * Generate CSV content from pincode data
	 *
	 * @param array $data Pincode data array.
	 * @return string CSV content
	 */
	private function generate_csv_content( $data ) {
		$output = fopen( 'php://temp', 'r+' );

		// Add UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers.
		$headers = array(
			'Pincode',
			'City',
			'State',
			'Delivery Days',
			'Shipping Amount',
			'Cash on Delivery',
			'COD Amount',
			'Created Date',
		);
		fputcsv( $output, $headers );

		// Data rows.
		foreach ( $data as $row ) {
			$csv_row = array(
				$row['pincode'],
				$row['city'],
				$row['state'],
				$row['delivery_days'],
				$row['shipping_amount'],
				1 === (int) $row['case_on_delivery'] ? 'Yes' : 'No',
				$row['cod_amount'],
				$row['created_at'],
			);
			fputcsv( $output, $csv_row );
		}

		rewind( $output );
		$csv_content = stream_get_contents( $output );
		fclose( $output );

		return $csv_content;
	}

	/**
	 * Validate import file before processing
	 */
	public function ajax_validate_import() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized access' );
			exit;
		}

		// Verify nonce.
		check_ajax_referer( 'wpc-admin-nonce', 'nonce' );

		// Check file upload.
		if ( ! isset( $_FILES['import_file']['error'] ) || UPLOAD_ERR_OK !== $_FILES['import_file']['error'] ) {
			wp_send_json_error( 'File upload failed' );
			exit;
		}

		$file = array_map( 'sanitize_text_field', $_FILES['import_file'] );

		// Validate file type.
		$allowed_types = array( 'csv', 'txt' );
		$file_ext      = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $file_ext, $allowed_types ) ) {
			wp_send_json_error( 'Invalid file type. Please upload a CSV file.' );
			exit;
		}

		// Validate file size (max 10MB).
		$max_size = 10 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			wp_send_json_error( 'File too large. Maximum size is 10MB.' );
			exit;
		}

		// Parse CSV and validate structure.
		$validation_result = $this->validate_csv_structure( $file['tmp_name'] );

		if ( $validation_result['valid'] ) {
			// Save file temporarily.
			$upload_dir = wp_upload_dir();
			$temp_dir   = $upload_dir['basedir'] . '/wpc-temp/';

			if ( ! file_exists( $temp_dir ) ) {
				wp_mkdir_p( $temp_dir );
			}

			$temp_filename = 'import-' . uniqid() . '.csv';
			$temp_path     = $temp_dir . $temp_filename;

			if ( move_uploaded_file( $file['tmp_name'], $temp_path ) ) { // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- Required for secure file upload handling.
				wp_send_json_success(
					array(
						'temp_file'    => $temp_filename,
						'total_rows'   => $validation_result['total_rows'],
						'valid_rows'   => $validation_result['valid_rows'],
						'invalid_rows' => $validation_result['invalid_rows'],
						'preview'      => $validation_result['preview'],
					)
				);
			} else {
				wp_send_json_error( 'Failed to save temporary file' );
			}
		} else {
			wp_send_json_error( $validation_result['error'] );
		}
	}

	/**
	 * Validate CSV file structure
	 *
	 * @param string $file_path Path to CSV file.
	 * @return array Validation result
	 */
	private function validate_csv_structure( $file_path ) {
		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return array(
				'valid' => false,
				'error' => 'Cannot open file',
			);
		}

		// Check for UTF-8 BOM and skip if present.
		$bom = fread( $handle, 3 );
		if ( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) !== $bom ) {
			rewind( $handle );
		}

		// Read header.
		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return array(
				'valid' => false,
				'error' => 'Empty file or invalid format',
			);
		}

		// Expected headers (case-insensitive).
		$expected_headers = array( 'pincode', 'city', 'state' );
		$optional_headers = array( 'delivery days', 'shipping amount', 'cash on delivery', 'cod amount' );

		$header_lower = array_map( 'strtolower', array_map( 'trim', $header ) );

		// Check required headers.
		foreach ( $expected_headers as $required ) {
			if ( ! in_array( $required, $header_lower ) ) {
				fclose( $handle );
				return array(
					'valid' => false,
					'error' => "Missing required column: {$required}",
				);
			}
		}

		// Validate data rows.
		$total_rows   = 0;
		$valid_rows   = 0;
		$invalid_rows = array();
		$preview      = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			++$total_rows;

			if ( $total_rows <= 5 ) {
				$preview[] = $row;
			}

			// Validate row.
			if ( count( $row ) < 3 ) {
				$invalid_rows[] = $total_rows + 1;
				continue;
			}

			// Validate pincode (assuming first column).
			$pincode = trim( $row[0] );
			if ( empty( $pincode ) || mb_strlen( $pincode, 'UTF-8' ) > 10 ) {
				$invalid_rows[] = $total_rows + 1;
				continue;
			}

			++$valid_rows;
		}

		fclose( $handle );

		return array(
			'valid'        => true,
			'total_rows'   => $total_rows,
			'valid_rows'   => $valid_rows,
			'invalid_rows' => $invalid_rows,
			'preview'      => $preview,
		);
	}

	/**
	 * Process import in chunks for better performance
	 */
	public function ajax_process_import_chunk() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized access' );
			exit;
		}

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc-admin-nonce' ) ) {
			wp_send_json_error( 'Invalid security token' );
			exit;
		}

		$temp_file  = isset( $_POST['temp_file'] ) ? sanitize_text_field( wp_unslash( $_POST['temp_file'] ) ) : '';
		$chunk_size = isset( $_POST['chunk_size'] ) ? intval( $_POST['chunk_size'] ) : 100;
		$offset     = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$mode       = isset( $_POST['import_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['import_mode'] ) ) : 'append';

		if ( empty( $temp_file ) ) {
			wp_send_json_error( 'No import file specified' );
			exit;
		}

		// Get file path.
		$upload_dir = wp_upload_dir();
		$temp_path  = $upload_dir['basedir'] . '/wpc-temp/' . $temp_file;

		if ( ! file_exists( $temp_path ) ) {
			wp_send_json_error( 'Import file not found' );
			exit;
		}

		// Process chunk.
		$result = $this->process_import_chunk( $temp_path, $offset, $chunk_size, $mode );

		if ( $result['completed'] ) {
			// Clean up temp file.
			@unlink( $temp_path );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Process a chunk of import data
	 *
	 * @param string $file_path Path to import file.
	 * @param int    $offset Start position.
	 * @param int    $chunk_size Number of rows to process.
	 * @param string $mode Import mode (append/replace).
	 * @return array Processing result
	 */
	private function process_import_chunk( $file_path, $offset, $chunk_size, $mode ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// If replace mode and first chunk, truncate table.
		if ( 'replace' === $mode && 0 === $offset ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name from $wpdb->prefix.
			$wpdb->query( "TRUNCATE TABLE {$table_name}" );
			wp_cache_flush_group( 'woo_pincode_checker' );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return array(
				'error'     => 'Cannot open file',
				'completed' => true,
			);
		}

		// Skip BOM if present.
		$bom = fread( $handle, 3 );
		if ( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) !== $bom ) {
			rewind( $handle );
		}

		// Read header.
		$header     = fgetcsv( $handle );
		$header_map = array_flip( array_map( 'strtolower', array_map( 'trim', $header ) ) );

		// Skip to offset.
		for ( $i = 0; $i < $offset; $i++ ) {
			if ( fgetcsv( $handle ) === false ) {
				fclose( $handle );
				return array(
					'processed' => 0,
					'completed' => true,
				);
			}
		}

		// Process chunk.
		$processed = 0;
		$imported  = 0;
		$skipped   = 0;
		$errors    = array();

		while ( $processed < $chunk_size && ( $row = fgetcsv( $handle ) ) !== false ) {
			++$processed;

			// Map data.
			$pincode_data = array(
				'pincode'          => isset( $header_map['pincode'] ) ? trim( $row[ $header_map['pincode'] ] ) : '',
				'city'             => isset( $header_map['city'] ) ? trim( $row[ $header_map['city'] ] ) : '',
				'state'            => isset( $header_map['state'] ) ? trim( $row[ $header_map['state'] ] ) : '',
				'delivery_days'    => isset( $header_map['delivery days'] ) ? intval( $row[ $header_map['delivery days'] ] ) : 3,
				'shipping_amount'  => isset( $header_map['shipping amount'] ) ? floatval( $row[ $header_map['shipping amount'] ] ) : 0,
				'case_on_delivery' => isset( $header_map['cash on delivery'] ) ?
					( strtolower( trim( $row[ $header_map['cash on delivery'] ] ) ) === 'yes' ? 1 : 0 ) : 1,
				'cod_amount'       => isset( $header_map['cod amount'] ) ? floatval( $row[ $header_map['cod amount'] ] ) : 0,
				'created_at'       => current_time( 'mysql' ),
			);

			// Validate required fields.
			if ( empty( $pincode_data['pincode'] ) || empty( $pincode_data['city'] ) || empty( $pincode_data['state'] ) ) {
				++$skipped;
				continue;
			}

			// Check for duplicates.
			if ( 'append' === $mode ) {
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix.
						$pincode_data['pincode']
					)
				);

				if ( $exists > 0 ) {
					// Update existing.
					$result = $wpdb->update(
						$table_name,
						array(
							'city'             => $pincode_data['city'],
							'state'            => $pincode_data['state'],
							'delivery_days'    => $pincode_data['delivery_days'],
							'shipping_amount'  => $pincode_data['shipping_amount'],
							'case_on_delivery' => $pincode_data['case_on_delivery'],
							'cod_amount'       => $pincode_data['cod_amount'],
						),
						array( 'pincode' => $pincode_data['pincode'] ),
						array( '%s', '%s', '%d', '%f', '%d', '%f' ),
						array( '%s' )
					);
				} else {
					// Insert new.
					$result = $wpdb->insert(
						$table_name,
						$pincode_data,
						array( '%s', '%s', '%s', '%d', '%f', '%d', '%f', '%s' )
					);
				}
			} else {
				// Insert new.
				$result = $wpdb->insert(
					$table_name,
					$pincode_data,
					array( '%s', '%s', '%s', '%d', '%f', '%d', '%f', '%s' )
				);
			}

			if ( false !== $result ) {
				++$imported;
			} else {
				$errors[] = 'Row ' . ( $offset + $processed ) . ': ' . $wpdb->last_error;
			}
		}

		$has_more = ! feof( $handle );
		fclose( $handle );

		return array(
			'processed'   => $processed,
			'imported'    => $imported,
			'skipped'     => $skipped,
			'errors'      => array_slice( $errors, 0, 5 ), // Limit errors to 5.
			'completed'   => ! $has_more || $processed < $chunk_size,
			'next_offset' => $offset + $processed,
		);
	}

	/**
	 * Download sample CSV template
	 */
	public function download_sample_csv() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized access' );
		}

		// Generate sample CSV.
		$sample_data = array(
			array( 'Pincode', 'City', 'State', 'Delivery Days', 'Shipping Amount', 'Cash on Delivery', 'COD Amount' ),
			array( '110001', 'New Delhi', 'Delhi', '3', '0', 'Yes', '0' ),
			array( '400001', 'Mumbai', 'Maharashtra', '4', '50', 'Yes', '20' ),
			array( '700001', 'Kolkata', 'West Bengal', '5', '75', 'No', '0' ),
			array( '600001', 'Chennai', 'Tamil Nadu', '3', '0', 'Yes', '30' ),
			array( '560001', 'Bangalore', 'Karnataka', '2', '0', 'Yes', '0' ),
		);

		// Set headers for download.
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="pincode-import-template.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output CSV.
		$output = fopen( 'php://output', 'w' );

		// Add UTF-8 BOM for Excel.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		foreach ( $sample_data as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Handle export file download
	 */
	public function handle_export_download() {
		// This should be hooked to admin_init.
		if ( isset( $_GET['action'] ) && 'wpc_download_export' === $_GET['action'] ) {
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Unauthorized access' );
			}

			// Verify nonce.
			$filename = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';
			$nonce    = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'wpc-download-' . $filename ) ) {
				wp_die( 'Invalid security token' );
			}

			// Get file path.
			$upload_dir = wp_upload_dir();
			$file_path  = $upload_dir['basedir'] . '/wpc-temp/' . $filename;

			if ( ! file_exists( $file_path ) ) {
				wp_die( 'File not found' );
			}

			// Send file.
			header( 'Content-Type: text/csv; charset=UTF-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Content-Length: ' . filesize( $file_path ) );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			readfile( $file_path );

			// Clean up.
			@unlink( $file_path );
			exit;
		}
	}
}
