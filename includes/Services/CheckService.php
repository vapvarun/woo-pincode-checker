<?php
/**
 * Resolve a postcode to a check result: the single object every surface uses.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

use Wbcom\PincodeChecker\Domain\Postcode;
use Wbcom\PincodeChecker\Repository\AreaRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Matching precedence: exact > longest prefix > narrowest range; a specific country beats "any";
 * at equal specificity a blocked area beats a serviceable one. No match -> unknown-postcode policy.
 */
final class CheckService {

	/**
	 * Constructor.
	 *
	 * @param AreaRepository      $areas    Area storage.
	 * @param SettingsService     $settings Settings.
	 * @param DeliveryDateService $dates    Delivery estimate engine.
	 */
	public function __construct( private AreaRepository $areas, private SettingsService $settings, private DeliveryDateService $dates ) {}

	/**
	 * Check a postcode.
	 *
	 * @param string $postcode Raw input.
	 * @param string $country  ISO country; '' = store base country.
	 * @param int    $extra    Category extra delivery days (product or cart).
	 * @return array{status:string, postcode:string, country:string, area_id:int, city:string, state:string, days_min:?int, days_max:?int, shipping_fee:?float, cod:array{allowed:bool, fee:float}, nearby:array, estimate:?array{min:string, max:string, label:string}}
	 */
	public function check( string $postcode, string $country = '', int $extra = 0 ): array {
		$norm    = Postcode::normalize( $postcode );
		$country = strtoupper( '' !== $country ? $country : $this->default_country() );
		$result  = array(
			'status'       => 'invalid',
			'postcode'     => $norm,
			'country'      => $country,
			'area_id'      => 0,
			'city'         => '',
			'state'        => '',
			'days_min'     => null,
			'days_max'     => null,
			'shipping_fee' => null,
			'cod'          => array(
				'allowed' => false,
				'fee'     => 0.0,
			),
			'nearby'       => array(),
			'estimate'     => null,
		);

		if ( ! Postcode::is_plausible( $norm ) || ! \WC_Validation::is_postcode( $norm, $country ) ) {
			return $result;
		}

		$area    = $this->match( $norm, $country );
		$general = (array) $this->settings->get( 'general' );

		if ( $area ) {
			$available = 1 === (int) $area['status'];
			$result    = array_merge(
				$result,
				array(
					'area_id'      => (int) $area['id'],
					'city'         => (string) $area['city'],
					'state'        => (string) $area['state'],
					'days_min'     => null === $area['days_min'] ? null : (int) $area['days_min'],
					'days_max'     => null === $area['days_max'] ? null : (int) $area['days_max'],
					'shipping_fee' => null === $area['shipping_fee'] ? null : (float) $area['shipping_fee'],
					'cod'          => array(
						'allowed' => $available && 1 === (int) $area['cod_allowed'],
						'fee'     => (float) $area['cod_fee'],
					),
				)
			);
		} else {
			$available = $this->unknown_is_available( (string) $general['unknown_policy'] );
			// Stores that list only exceptions deliver everywhere else on default terms, COD included.
			$result['cod']['allowed'] = $available;
		}

		$result['status'] = $available ? 'available' : 'unavailable';

		if ( $available && $this->settings->get( 'delivery', 'show_estimate' ) ) {
			$result['estimate'] = $this->dates->estimate( $result['days_min'], $result['days_max'], $extra );
		}

		if ( ! $available && $general['show_nearby'] ) {
			$result['nearby'] = $this->areas->nearby( $norm, $country );
		}

		/**
		 * Fires after every check that resolved to a matching area or not. Pro analytics and the
		 * waitlist hook here.
		 *
		 * @param array $result Check result.
		 */
		if ( $area ) {
			do_action( 'wbpc_check_matched', $result );
		} else {
			/**
			 * Fires after a check that matched no area (Pro waitlist and demand analytics).
			 *
			 * @param array $result Check result.
			 */
			do_action( 'wbpc_check_not_matched', $result );
		}

		/**
		 * Filter a check result before any surface uses it.
		 *
		 * @param array $result Check result.
		 */
		return apply_filters( 'wbpc_check_result', $result );
	}

	/**
	 * Whether a postcode with no matching area is deliverable.
	 *
	 * "auto" (default) = yes until the store lists its first serviceable area, then no. Plug and play:
	 * a fresh install shows delivery dates and blocks nothing; once areas exist they act as the list.
	 *
	 * @param string $policy auto|available|unavailable.
	 */
	public function unknown_is_available( string $policy ): bool {
		if ( 'auto' === $policy ) {
			return 0 === $this->areas->count_where( array( 'status' => 1 ) );
		}

		return 'available' === $policy;
	}

	/**
	 * Country used when the shopper has none: the setting, else the store's country.
	 */
	public function default_country(): string {
		$country = (string) $this->settings->get( 'general', 'default_country' );

		return '' !== $country ? $country : WC()->countries->get_base_country();
	}

	/**
	 * The winning area for a normalised code, cached per areas version.
	 *
	 * @param string $norm    Normalised code.
	 * @param string $country ISO country.
	 * @return array<string, mixed>|null
	 */
	public function match( string $norm, string $country ): ?array {
		$key  = 'check:' . $this->areas->version() . ':' . $country . ':' . $norm;
		$area = wp_cache_get( $key, AreaRepository::CACHE_GROUP );

		if ( false === $area ) {
			$area = self::pick( $this->areas->candidates( $norm, $country ), $norm ) ?? 0;
			wp_cache_set( $key, $area, AreaRepository::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return $area ? $area : null;
	}

	/**
	 * Choose the winning area from candidates. Pure: no I/O, covered by `wp wbpc self-test`.
	 *
	 * @param array<int, array<string, mixed>> $candidates Rows from AreaRepository::candidates().
	 * @param string                           $norm       Normalised code being checked.
	 * @return array<string, mixed>|null
	 */
	public static function pick( array $candidates, string $norm ): ?array {
		$best      = null;
		$best_rank = null;

		foreach ( $candidates as $row ) {
			$type = (int) $row['match_type'];

			// A range only covers codes of its own length: 01000-01999 must not match "1500".
			if ( Postcode::RANGE === $type && strlen( Postcode::normalize( (string) $row['code'] ) ) !== strlen( $norm ) ) {
				continue;
			}

			$rank = array(
				array(
					Postcode::RANGE  => 1,
					Postcode::PREFIX => 2,
					Postcode::EXACT  => 3,
				)[ $type ] ?? 0,
				match ( $type ) {
					Postcode::PREFIX => strlen( (string) $row['code_norm'] ),
					Postcode::RANGE  => -( (int) $row['range_end'] - (int) $row['range_start'] ),
					default          => 0,
				},
				'' === (string) $row['country'] ? 0 : 1,
				0 === (int) $row['status'] ? 1 : 0,
			);

			if ( null === $best_rank || $rank > $best_rank ) {
				$best      = $row;
				$best_rank = $rank;
			}
		}

		return $best;
	}
}
