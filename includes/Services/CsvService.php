<?php
/**
 * CSV format for delivery areas: header mapping, row conversion, sample file, private storage.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Columns: country, code, code_to, status, city, state, days_min, days_max, shipping_fee,
 * cod_allowed, cod_fee, note. Only `code` is required; `*` in code = prefix, code_to = range end.
 */
final class CsvService {

	public const COLUMNS = array( 'country', 'code', 'code_to', 'status', 'city', 'state', 'days_min', 'days_max', 'shipping_fee', 'cod_allowed', 'cod_fee', 'note' );

	/**
	 * Header names people commonly use for our columns.
	 */
	private const ALIASES = array(
		'pincode'       => 'code',
		'pin_code'      => 'code',
		'postcode'      => 'code',
		'post_code'     => 'code',
		'postal_code'   => 'code',
		'zip'           => 'code',
		'zipcode'       => 'code',
		'zip_code'      => 'code',
		'to'            => 'code_to',
		'code_end'      => 'code_to',
		'range_end'     => 'code_to',
		'end'           => 'code_to',
		'zip_end'       => 'code_to',
		'zip_to'        => 'code_to',
		'pincode_to'    => 'code_to',
		'pincode_end'   => 'code_to',
		'postcode_to'   => 'code_to',
		'cod'           => 'cod_allowed',
		'shipping'      => 'shipping_fee',
		'delivery_days' => 'days_min',
	);

	/**
	 * Read one CSV line. Explicit separator, enclosure and escape: PHP 8.4+ deprecates relying on the default escape.
	 *
	 * @param resource $handle File handle.
	 * @return array|false
	 */
	public static function read( $handle ) {
		return fgetcsv( $handle, 0, ',', '"', '\\' );
	}

	/**
	 * Write one CSV line. Text starting with = + - @ (or tab/CR) is prefixed with an apostrophe so
	 * spreadsheet apps do not run it as a formula (CSV injection); every file we produce goes through here.
	 *
	 * @param resource $handle File handle.
	 * @param array    $fields Values.
	 */
	public static function write( $handle, array $fields ): void {
		$fields = array_map(
			static fn( $value ) => is_string( $value ) && 1 === preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value,
			$fields
		);

		fputcsv( $handle, $fields, ',', '"', '\\' );
	}

	/**
	 * Map a header row to our column names. Unknown columns map to null and are ignored.
	 *
	 * @param array $header Raw header cells.
	 * @return array<int, string|null>
	 */
	public function map_header( array $header ): array {
		return array_map(
			static function ( $cell ) {
				$key = strtolower( trim( (string) preg_replace( '/^\xEF\xBB\xBF/', '', (string) $cell ) ) ); // Strip a UTF-8 BOM.
				$key = (string) preg_replace( '/[\s\-]+/', '_', $key );
				$key = self::ALIASES[ $key ] ?? $key;

				return in_array( $key, self::COLUMNS, true ) ? $key : null;
			},
			$header
		);
	}

	/**
	 * Turn one CSV row into AreaService input.
	 *
	 * @param array<int, string|null> $map      From map_header().
	 * @param array                   $values   Row cells.
	 * @param string                  $country  Fallback country when the row has none.
	 */
	public function to_input( array $map, array $values, string $country ): array {
		$input = array();

		foreach ( $map as $i => $key ) {
			if ( null !== $key && isset( $values[ $i ] ) ) {
				$input[ $key ] = trim( (string) $values[ $i ] );
			}
		}

		if ( '' === ( $input['country'] ?? '' ) ) {
			$input['country'] = $country;
		}

		if ( isset( $input['status'] ) ) {
			$input['status'] = in_array( strtolower( $input['status'] ), array( 'blocked', 'block', 'no', '0', 'false' ), true ) ? 'blocked' : 'serviceable';
		}

		// A blank cell means "not specified": fall back to the default (COD allowed) instead of reading as "no".
		if ( isset( $input['cod_allowed'] ) && '' === $input['cod_allowed'] ) {
			unset( $input['cod_allowed'] );
		} elseif ( isset( $input['cod_allowed'] ) ) {
			$input['cod_allowed'] = in_array( strtolower( $input['cod_allowed'] ), array( 'yes', 'y', '1', 'true', 'allowed' ), true );
		}

		return $input;
	}

	/**
	 * One area as a CSV row (export).
	 *
	 * @param array $area Formatted area (AreaService::format()).
	 */
	public function to_row( array $area ): array {
		return array(
			$area['country'],
			$area['code'],
			$area['code_to'],
			$area['status'],
			$area['city'],
			$area['state'],
			$area['days_min'] ?? '',
			$area['days_max'] ?? '',
			$area['shipping_fee'] ?? '',
			$area['cod_allowed'] ? 'yes' : 'no',
			$area['cod_fee'],
			$area['note'],
		);
	}

	/**
	 * Sample file contents: Indian PINs, a UK prefix and a US ZIP range.
	 */
	public function sample(): string {
		$rows = array(
			self::COLUMNS,
			array( 'IN', '110001', '', 'serviceable', 'New Delhi', 'Delhi', '1', '2', '40', 'yes', '25', '' ),
			array( 'IN', '110*', '', 'serviceable', 'Delhi NCR', 'Delhi', '2', '4', '60', 'yes', '30', 'All Delhi PINs' ),
			array( 'IN', '110099', '', 'blocked', '', '', '', '', '', 'no', '0', 'No courier service' ),
			array( 'GB', 'SW1*', '', 'serviceable', 'Westminster', 'London', '1', '1', '4.99', 'no', '0', '' ),
			array( 'US', '10001', '10099', 'serviceable', 'New York', 'NY', '2', '3', '', 'no', '0', 'Manhattan ZIPs' ),
		);

		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory stream.
		foreach ( $rows as $row ) {
			self::write( $handle, $row );
		}
		rewind( $handle );
		$csv = (string) stream_get_contents( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $csv;
	}

	/**
	 * Private folder for staged imports and error reports: random names, no directory listing,
	 * direct access denied where the server honours .htaccess.
	 */
	public function private_dir(): string {
		$dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'wbpc-private/';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			file_put_contents( $dir . 'index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $dir . '.htaccess', "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return $dir;
	}
}
