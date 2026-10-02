<?php
/**
 * WP-CLI: wp wbpc ...
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\CLI;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom\PincodeChecker\Domain\Postcode;
use Wbcom\PincodeChecker\Services\CheckService;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Manage Pincode Checker delivery areas.
 */
final class Commands {

	/**
	 * Check a postcode the way the storefront does.
	 *
	 * ## OPTIONS
	 *
	 * <postcode>
	 * : Postcode to check.
	 *
	 * [--country=<country>]
	 * : ISO country code. Default: store base country.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wbpc check 110001
	 *     wp wbpc check "SW1A 1AA" --country=GB
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function check( array $args, array $assoc_args ): void {
		$result = Plugin::checker()->check( $args[0], (string) ( $assoc_args['country'] ?? '' ) );
		WP_CLI::line( (string) wp_json_encode( $result, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Show the number of delivery areas.
	 */
	public function count(): void {
		WP_CLI::line( (string) Plugin::areas()->count() );
	}

	/**
	 * Import areas from a CSV file (same rules as the admin importer; runs to the end in this process).
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : CSV file with a header row. Download the sample from WB Plugins > Pincode Checker > Import / Export.
	 *
	 * [--mode=<mode>]
	 * : What to do with areas that already exist.
	 * ---
	 * default: skip
	 * options:
	 *   - skip
	 *   - update
	 *   - replace
	 * ---
	 *
	 * [--country=<country>]
	 * : Country for rows without one. Default: any country.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wbpc import areas.csv --mode=update --country=IN
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function import( array $args, array $assoc_args ): void {
		$importer = Plugin::importer();
		$staged   = $importer->stage( $args[0], basename( $args[0] ), (string) ( $assoc_args['mode'] ?? 'skip' ), strtoupper( (string) ( $assoc_args['country'] ?? '' ) ) );

		if ( is_wp_error( $staged ) ) {
			WP_CLI::error( $staged->get_error_message() );
		}

		$bar = \WP_CLI\Utils\make_progress_bar( 'Importing', max( 1, $staged['job']['total'] ) );
		$at  = 0;
		$job = $importer->run_now(
			static function ( array $job ) use ( $bar, &$at ): void {
				$bar->tick( (int) $job['processed'] - $at );
				$at = (int) $job['processed'];
			}
		);
		$bar->finish();

		$summary = sprintf( 'Rows: %d. Added: %d. Updated: %d. Skipped (already existed): %d. Failed: %d.', $job['total'] ?? 0, $job['added'] ?? 0, $job['updated'] ?? 0, $job['skipped'] ?? 0, $job['failed'] ?? 0 );

		if ( 'done' !== ( $job['status'] ?? '' ) ) {
			WP_CLI::error( ( $job['message'] ?? 'Import failed.' ) . ' ' . $summary );
		}
		if ( ! empty( $job['failed'] ) ) {
			WP_CLI::warning( 'Some rows were rejected. Download the error report from WB Plugins > Pincode Checker > Import / Export.' );
		}

		WP_CLI::success( $summary );
	}

	/**
	 * Export areas as CSV.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Output file. Default: standard output.
	 *
	 * [--country=<country>]
	 * : Only this country ("any" = areas without a country).
	 *
	 * [--type=<type>]
	 * : exact, prefix or range.
	 *
	 * [--status=<status>]
	 * : serviceable or blocked.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function export( array $args, array $assoc_args ): void {
		$out     = isset( $args[0] ) ? fopen( $args[0], 'w' ) : fopen( 'php://stdout', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$service = Plugin::area_service();
		$filters = $service->filters_from( $assoc_args );
		$last    = 0;
		$count   = 0;

		\Wbcom\PincodeChecker\Services\CsvService::write( $out, \Wbcom\PincodeChecker\Services\CsvService::COLUMNS );

		do {
			$rows = Plugin::areas()->after_id( $last, $filters );
			foreach ( $rows as $row ) {
				\Wbcom\PincodeChecker\Services\CsvService::write( $out, Plugin::csv()->to_row( $service->format( $row ) ) );
				$last = (int) $row['id'];
				++$count;
			}
		} while ( $rows );

		if ( isset( $args[0] ) ) {
			fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			WP_CLI::success( sprintf( 'Exported %d areas to %s.', $count, $args[0] ) );
		}
	}

	/**
	 * Insert random areas for big-site testing (development only).
	 *
	 * Mix: 85% exact Indian PINs, 8% prefixes, 4% numeric ranges, 3% UK outward-code prefixes;
	 * 5% of all rows blocked.
	 *
	 * ## OPTIONS
	 *
	 * <count>
	 * : Number of areas to insert.
	 *
	 * @param array $args Positional args.
	 */
	public function seed( array $args ): void {
		$total = max( 1, (int) $args[0] );
		$rows  = array();
		$uk    = array( 'SW', 'EC', 'W', 'N', 'E', 'SE', 'NW', 'M', 'B', 'L', 'G', 'EH' );

		for ( $i = 0; $i < $total; $i++ ) {
			$roll = wp_rand( 1, 100 );

			if ( $roll <= 85 ) {
				$rule    = Postcode::parse_rule( (string) wp_rand( 110001, 999999 ) );
				$country = 'IN';
			} elseif ( $roll <= 93 ) {
				$rule    = Postcode::parse_rule( wp_rand( 1100, 9999 ) . '**' );
				$country = 'IN';
			} elseif ( $roll <= 97 ) {
				$start   = wp_rand( 110000, 990000 );
				$rule    = Postcode::parse_rule( (string) $start, (string) ( $start + wp_rand( 1, 999 ) ) );
				$country = 'IN';
			} else {
				$rule    = Postcode::parse_rule( $uk[ array_rand( $uk ) ] . wp_rand( 1, 20 ) . '*' );
				$country = 'GB';
			}

			if ( ! is_array( $rule ) ) {
				continue;
			}

			$days   = wp_rand( 1, 7 );
			$rows[] = $rule + array(
				'country'      => $country,
				'status'       => wp_rand( 1, 100 ) <= 5 ? 0 : 1,
				'city'         => 'Seed city ' . wp_rand( 1, 500 ),
				'state'        => 'Seed state ' . wp_rand( 1, 30 ),
				'days_min'     => $days,
				'days_max'     => $days + wp_rand( 0, 3 ),
				'shipping_fee' => wp_rand( 0, 3 ) ? wp_rand( 0, 150 ) : null,
				'cod_allowed'  => wp_rand( 1, 100 ) <= 80 ? 1 : 0,
				'cod_fee'      => wp_rand( 0, 50 ),
			);
		}

		$inserted = Plugin::areas()->insert_many( $rows );
		WP_CLI::success( sprintf( 'Inserted %d areas (%d skipped as duplicates). Total: %d.', $inserted, count( $rows ) - $inserted, Plugin::areas()->count() ) );
	}

	/**
	 * Run the built-in checks for postcode parsing and matching precedence.
	 *
	 * @subcommand self-test
	 */
	public function self_test(): void {
		$fail = array();
		$eq   = static function ( string $name, $actual, $expected ) use ( &$fail ): void {
			if ( $actual !== $expected ) {
				$fail[] = $name . ': expected ' . wp_json_encode( $expected ) . ', got ' . wp_json_encode( $actual );
			}
		};

		// Normalisation and parsing.
		$eq( 'normalize UK', Postcode::normalize( ' sw1a 1aa ' ), 'SW1A1AA' );
		$eq( 'normalize BR hyphen', Postcode::normalize( '01310-100' ), '01310100' );
		$eq( 'prefixes', Postcode::prefixes( 'ABC' ), array( 'ABC', 'AB', 'A' ) );
		$eq( 'numeric letters', Postcode::numeric( 'SW1' ), null );
		$eq( 'exact', Postcode::parse_rule( '110 001' )['code_norm'] ?? null, '110001' );
		$eq( 'prefix stars', Postcode::parse_rule( '110***' )['code_norm'] ?? null, '110' );
		$eq( 'prefix UK', Postcode::parse_rule( 'sw1*' )['match_type'] ?? null, Postcode::PREFIX );
		$eq( 'wildcard inside', Postcode::parse_rule( '1*0' ), 'wildcard_not_trailing' );
		$eq( 'range', Postcode::parse_rule( '110001', '110099' )['range_end'] ?? null, 110099 );
		$eq( 'range reversed', Postcode::parse_rule( '110099', '110001' ), 'range_reversed' );
		$eq( 'range length', Postcode::parse_rule( '11001', '110099' ), 'range_length_mismatch' );
		$eq( 'range letters', Postcode::parse_rule( 'AB1', 'AB9' ), 'range_not_numeric' );
		$eq( 'too short', Postcode::parse_rule( 'A' ), 'invalid_code' );
		$eq( 'non-range zeros', Postcode::parse_rule( '110001' )['range_start'] ?? null, 0 );

		// Precedence.
		$row  = static fn( int $id, int $type, string $code, string $norm, int $start = 0, int $end = 0, int $status = 1, string $country = '' ): array => array(
			'id'          => $id,
			'match_type'  => $type,
			'code'        => $code,
			'code_norm'   => $norm,
			'range_start' => $start,
			'range_end'   => $end,
			'status'      => $status,
			'country'     => $country,
		);
		$pick = static fn( array $rows, string $norm ) => CheckService::pick( $rows, $norm )['id'] ?? 0;

		$eq( 'exact beats prefix and range', $pick( array( $row( 1, 2, '110*', '110' ), $row( 2, 1, '110005', '110005' ), $row( 3, 3, '110000', '', 110000, 110999 ) ), '110005' ), 2 );
		$eq( 'longest prefix wins', $pick( array( $row( 1, 2, '110*', '110', 0, 0, 0 ), $row( 2, 2, '1100*', '1100' ) ), '110005' ), 2 );
		$eq( 'blocked beats serviceable at equal specificity', $pick( array( $row( 1, 2, '110*', '110' ), $row( 2, 2, '110*', '110', 0, 0, 0 ) ), '110005' ), 2 );
		$eq( 'country beats any', $pick( array( $row( 1, 2, '110*', '110' ), $row( 2, 2, '110*', '110', 0, 0, 1, 'IN' ) ), '110005' ), 2 );
		$eq( 'narrowest range wins', $pick( array( $row( 1, 3, '110000', '', 110000, 110999 ), $row( 2, 3, '110000', '', 110000, 110099 ) ), '110050' ), 2 );
		$eq( 'range needs same length', $pick( array( $row( 1, 3, '01000', '', 1000, 1999 ) ), '1500' ), 0 );
		$eq( 'prefix beats range', $pick( array( $row( 1, 3, '110000', '', 110000, 110999 ), $row( 2, 2, '11*', '11' ) ), '110050' ), 2 );
		$eq( 'no candidates', $pick( array(), '110001' ), 0 );

		// Delivery dates: Mon-Fri working, 14:00 cut-off, Christmas off. 2026-10-05 is a Monday.
		$rules = array(
			'working_days'     => array( 1, 2, 3, 4, 5 ),
			'holidays'         => array( '2026-12-25' ),
			'cutoff_time'      => '14:00',
			'processing_days'  => 0,
			'transit_counting' => 'business',
		);
		$at    = static fn( string $when ) => new \DateTimeImmutable( $when, new \DateTimeZone( 'UTC' ) );
		$dates = static fn( array $r, int $min, int $max, string $when ) => implode( ' / ', \Wbcom\PincodeChecker\Services\DeliveryDateService::calculate( $r, $min, $max, $at( $when ) ) );

		$eq( 'before cut-off', $dates( $rules, 2, 3, '2026-10-05 10:00' ), '2026-10-07 / 2026-10-08' );
		$eq( 'after cut-off starts tomorrow', $dates( $rules, 2, 3, '2026-10-05 15:00' ), '2026-10-08 / 2026-10-09' );
		$eq( 'Friday after cut-off skips the weekend', $dates( $rules, 1, 1, '2026-10-09 15:00' ), '2026-10-13 / 2026-10-13' );
		$eq( 'ordered on Saturday', $dates( $rules, 1, 1, '2026-10-10 09:00' ), '2026-10-13 / 2026-10-13' );
		$eq( 'holiday is skipped', $dates( $rules, 1, 1, '2026-12-24 10:00' ), '2026-12-28 / 2026-12-28' );
		$eq( 'calendar days count weekends', $dates( array( 'transit_counting' => 'calendar' ) + $rules, 1, 2, '2026-10-09 10:00' ), '2026-10-10 / 2026-10-11' );
		$eq( 'processing day before dispatch', $dates( array( 'processing_days' => 1 ) + $rules, 0, 0, '2026-10-05 10:00' ), '2026-10-06 / 2026-10-06' );
		$eq( 'same-day delivery', $dates( $rules, 0, 0, '2026-10-05 13:59' ), '2026-10-05 / 2026-10-05' );

		if ( $fail ) {
			WP_CLI::error( "Self-test failed:\n" . implode( "\n", $fail ) );
		}

		WP_CLI::success( '30 checks passed.' );
	}
}
