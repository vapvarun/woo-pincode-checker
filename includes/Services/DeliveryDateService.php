<?php
/**
 * Delivery estimate engine.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

use DateTimeImmutable;

defined( 'ABSPATH' ) || exit;

/**
 * How an estimate is built.
 *
 * Start: today, or the next working day when today is off or past the cut-off.
 * Dispatch: start plus processing days (working days).
 * Min/max: dispatch plus delivery days (and category extra days), counted as working or calendar days.
 */
final class DeliveryDateService {

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settings Settings.
	 */
	public function __construct( private SettingsService $settings ) {}

	/**
	 * Estimate for an area's delivery days.
	 *
	 * @param int|null               $area_min Area minimum days, null = store default.
	 * @param int|null               $area_max Area maximum days, null = area min, or store default.
	 * @param int                    $extra    Category extra days.
	 * @param DateTimeImmutable|null $now      Clock (tests); default now in the site timezone.
	 * @return array{min:string, max:string, label:string}
	 */
	public function estimate( ?int $area_min, ?int $area_max, int $extra = 0, ?DateTimeImmutable $now = null ): array {
		$rules = (array) $this->settings->get( 'delivery' );
		$min   = $area_min ?? (int) $rules['default_days_min'];
		$max   = $area_max ?? ( null !== $area_min ? $area_min : (int) $rules['default_days_max'] );

		$dates = self::calculate( $rules, $min + $extra, max( $min, $max ) + $extra, $now ?? new DateTimeImmutable( 'now', wp_timezone() ) );

		/**
		 * Filter a delivery estimate (Pro warehouses adjust it here).
		 *
		 * @param array $estimate { min, max, label }.
		 * @param int   $extra    Category extra days included.
		 */
		return apply_filters( 'wbpc_estimate', $dates + array( 'label' => $this->label( $dates, $rules ) ), $extra );
	}

	/**
	 * PHP date format for a "Date format" setting value.
	 *
	 * @param string $key Setting value: locale, site, or a literal format.
	 */
	public static function date_format( string $key ): string {
		if ( 'locale' === $key ) {
			/* translators: Short delivery date in your language's order, e.g. "Sat, Oct 3". See https://www.php.net/manual/datetime.format.php */
			return _x( 'D, M j', 'delivery date format', 'woo-pincode-checker' );
		}

		return 'site' === $key ? (string) get_option( 'date_format' ) : $key;
	}

	/**
	 * Pure date arithmetic, covered by `wp wbpc self-test`.
	 *
	 * @param array             $rules Delivery settings (working_days, holidays, cutoff_time, processing_days, transit_counting).
	 * @param int               $min   Delivery days, minimum.
	 * @param int               $max   Delivery days, maximum.
	 * @param DateTimeImmutable $now   Now, in the site timezone.
	 * @return array{min:string, max:string}
	 */
	public static function calculate( array $rules, int $min, int $max, DateTimeImmutable $now ): array {
		$working  = array_map( 'intval', (array) $rules['working_days'] );
		$holidays = array_flip( (array) $rules['holidays'] );
		$is_open  = static fn( DateTimeImmutable $d ): bool => in_array( (int) $d->format( 'N' ), $working, true ) && ! isset( $holidays[ $d->format( 'Y-m-d' ) ] );

		$add_working = static function ( DateTimeImmutable $from, int $days ) use ( $is_open ): DateTimeImmutable {
			// Guard: at most a year of searching even with an unusual calendar.
			for ( $guard = 0; $days > 0 && $guard < 400; $guard++ ) {
				$from = $from->modify( '+1 day' );
				if ( $is_open( $from ) ) {
					--$days;
				}
			}
			return $from;
		};

		$today = $now->setTime( 0, 0 );
		$start = ( $is_open( $today ) && $now->format( 'H:i' ) < (string) $rules['cutoff_time'] ) ? $today : $add_working( $today, 1 );

		$dispatch = $add_working( $start, (int) $rules['processing_days'] );
		$add      = 'calendar' === $rules['transit_counting']
			? static fn( DateTimeImmutable $d, int $n ): DateTimeImmutable => $d->modify( '+' . $n . ' days' )
			: $add_working;

		return array(
			'min' => $add( $dispatch, $min )->format( 'Y-m-d' ),
			'max' => $add( $dispatch, $max )->format( 'Y-m-d' ),
		);
	}

	/**
	 * Text for an estimate, per the "Show the estimate as" setting, in the current locale.
	 *
	 * Orders pass $relative = false: "in 3 days" means nothing once the order is a week old,
	 * so saved estimates are always shown as dates, rebuilt in the viewer's language.
	 *
	 * @param array{min:string, max:string} $dates    Dates (Y-m-d).
	 * @param array|null                    $rules    Delivery settings; default the saved ones.
	 * @param bool                          $relative Allow "in N days" (the "Days" display).
	 */
	public function label( array $dates, ?array $rules = null, bool $relative = true ): string {
		$rules = $rules ?? (array) $this->settings->get( 'delivery' );
		$tz    = wp_timezone();
		$min   = new DateTimeImmutable( $dates['min'], $tz );
		$max   = new DateTimeImmutable( $dates['max'], $tz );
		$fmt   = self::date_format( (string) $rules['date_format'] );

		if ( 'days' === $rules['display'] && $relative ) {
			$today = new DateTimeImmutable( 'today', $tz );
			$from  = (int) $today->diff( $min )->days;
			$to    = (int) $today->diff( $max )->days;

			if ( 0 === $to ) {
				return __( 'today', 'woo-pincode-checker' );
			}

			return $from === $to
				/* translators: %d: number of days. */
				? sprintf( _n( 'in %d day', 'in %d days', $to, 'woo-pincode-checker' ), $to )
				/* translators: 1: minimum days, 2: maximum days. */
				: sprintf( __( 'in %1$d-%2$d days', 'woo-pincode-checker' ), $from, $to );
		}

		if ( 'date' === $rules['display'] || $dates['min'] === $dates['max'] ) {
			/* translators: %s: latest delivery date. */
			return sprintf( __( 'by %s', 'woo-pincode-checker' ), wp_date( $fmt, $max->getTimestamp(), $tz ) );
		}

		/* translators: 1: earliest delivery date, 2: latest delivery date. */
		return sprintf( __( '%1$s to %2$s', 'woo-pincode-checker' ), wp_date( $fmt, $min->getTimestamp(), $tz ), wp_date( $fmt, $max->getTimestamp(), $tz ) );
	}
}
