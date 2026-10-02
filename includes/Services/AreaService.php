<?php
/**
 * Create, update and delete delivery areas.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

use Wbcom\PincodeChecker\Domain\Postcode;
use Wbcom\PincodeChecker\Repository\AreaRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Every write surface (REST, CLI, import) goes through here, so validation and hooks run once.
 */
final class AreaService {

	private const TYPES = array(
		Postcode::EXACT  => 'exact',
		Postcode::PREFIX => 'prefix',
		Postcode::RANGE  => 'range',
	);

	/**
	 * Constructor.
	 *
	 * @param AreaRepository $areas Area storage.
	 */
	public function __construct( private AreaRepository $areas ) {}

	/**
	 * Create an area.
	 *
	 * @param array $input Raw input (see validate()).
	 * @return array|WP_Error Formatted area.
	 */
	public function create( array $input ) {
		$row = $this->validate( $input );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$id = $this->areas->insert( $row );

		if ( ! $id ) {
			return new WP_Error( 'wbpc_db_error', __( 'The area could not be saved. Please try again.', 'woo-pincode-checker' ), array( 'status' => 500 ) );
		}

		return $this->saved( $id );
	}

	/**
	 * Update an area. Fields not sent keep their current value.
	 *
	 * @param int   $id    Area id.
	 * @param array $input Raw input.
	 * @return array|WP_Error Formatted area.
	 */
	public function update( int $id, array $input ) {
		$current = $this->areas->find( $id );

		if ( ! $current ) {
			return $this->not_found();
		}

		$row = $this->validate( $input + $this->format( $current ), $id );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		if ( ! $this->areas->update( $id, $row ) ) {
			return new WP_Error( 'wbpc_db_error', __( 'The area could not be saved. Please try again.', 'woo-pincode-checker' ), array( 'status' => 500 ) );
		}

		return $this->saved( $id );
	}

	/**
	 * Delete one area.
	 *
	 * @param int $id Area id.
	 * @return true|WP_Error
	 */
	public function delete( int $id ) {
		if ( ! $this->areas->delete_ids( array( $id ) ) ) {
			return $this->not_found();
		}

		/**
		 * An area was deleted.
		 *
		 * @param int[] $ids Deleted ids.
		 */
		do_action( 'wbpc_area_deleted', array( $id ) );

		return true;
	}

	/**
	 * Delete by ids, or everything matching filters.
	 *
	 * @param int[]|null $ids     Ids, or null to use filters.
	 * @param array      $filters List filters when $ids is null.
	 * @return int Deleted count.
	 */
	public function delete_many( ?array $ids, array $filters = array() ): int {
		$deleted = null === $ids ? $this->areas->delete_where( $filters ) : $this->areas->delete_ids( $ids );

		if ( $deleted ) {
			/** This action is documented in AreaService::delete(). */
			do_action( 'wbpc_area_deleted', null === $ids ? array() : $ids );
		}

		return $deleted;
	}

	/**
	 * Validate and normalise input into a storable row.
	 *
	 * @param array $input  code, code_to, country, status, city, state, days_min, days_max, shipping_fee, cod_allowed, cod_fee, note.
	 * @param int   $except          Area id being updated (excluded from the duplicate check).
	 * @param bool  $check_duplicate Query for an existing area. Imports skip it and let the unique key decide.
	 * @return array|WP_Error Row, or an error whose data holds per-field messages.
	 */
	public function validate( array $input, int $except = 0, bool $check_duplicate = true ) {
		$errors = array();
		$rule   = Postcode::parse_rule( (string) ( $input['code'] ?? '' ), (string) ( $input['code_to'] ?? '' ) );

		if ( is_string( $rule ) ) {
			$errors['code'] = $this->rule_message( $rule );
			$rule           = array();
		}

		$country = strtoupper( (string) ( $input['country'] ?? '' ) );
		if ( '' !== $country && ! isset( WC()->countries->get_countries()[ $country ] ) ) {
			$errors['country'] = __( 'Choose a country from the list.', 'woo-pincode-checker' );
		}

		$days_min = $this->int_or_null( $input['days_min'] ?? null );
		$days_max = $this->int_or_null( $input['days_max'] ?? null );
		foreach ( array(
			'days_min' => $days_min,
			'days_max' => $days_max,
		) as $key => $days ) {
			if ( null !== $days && ( $days < 0 || $days > 365 ) ) {
				$errors[ $key ] = __( 'Use a number of days between 0 and 365.', 'woo-pincode-checker' );
			}
		}
		if ( null !== $days_min && null !== $days_max && $days_max < $days_min ) {
			$errors['days_max'] = __( 'The maximum cannot be lower than the minimum.', 'woo-pincode-checker' );
		}

		$shipping_fee = $this->money_or_null( $input['shipping_fee'] ?? null );
		$cod_fee      = $this->money_or_null( $input['cod_fee'] ?? null ) ?? 0.0;
		if ( false === $shipping_fee ) {
			$errors['shipping_fee'] = __( 'Enter an amount of 0 or more, or leave it empty.', 'woo-pincode-checker' );
		}
		if ( false === $cod_fee ) {
			$errors['cod_fee'] = __( 'Enter an amount of 0 or more.', 'woo-pincode-checker' );
		}

		$row = $rule + array(
			'country'      => $country,
			'status'       => in_array( $input['status'] ?? 'serviceable', array( 'blocked', 0, '0', false ), true ) ? 0 : 1,
			'city'         => mb_substr( sanitize_text_field( (string) ( $input['city'] ?? '' ) ), 0, 100 ),
			'state'        => mb_substr( sanitize_text_field( (string) ( $input['state'] ?? '' ) ), 0, 100 ),
			'days_min'     => $days_min,
			'days_max'     => $days_max,
			'shipping_fee' => $shipping_fee,
			'cod_allowed'  => false !== filter_var( $input['cod_allowed'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ? 1 : 0,
			'cod_fee'      => $cod_fee,
			'note'         => mb_substr( sanitize_text_field( (string) ( $input['note'] ?? '' ) ), 0, 255 ),
		);

		if ( $check_duplicate && ! $errors && $rule ) {
			$existing = $this->areas->duplicate_of( $row, $except );
			if ( $existing ) {
				$errors['code']          = __( 'An area with this code already exists.', 'woo-pincode-checker' );
				$errors['_duplicate_id'] = $existing;
			}
		}

		if ( $errors ) {
			return new WP_Error(
				'wbpc_invalid_area',
				__( 'Please fix the highlighted fields.', 'woo-pincode-checker' ),
				array(
					'status' => 400,
					'fields' => $errors,
				)
			);
		}

		/**
		 * Last chance to change or reject an area before it is written.
		 *
		 * @param array $row    Row about to be stored.
		 * @param int   $except Id of the area being updated, 0 on create.
		 * @return array|WP_Error
		 */
		return apply_filters( 'wbpc_before_area_save', $row, $except );
	}

	/**
	 * List filters from request-style params (search, type, status, cod, country). Shared by the
	 * REST list, bulk delete and CSV export so all three always mean the same set of areas.
	 *
	 * @param array $params Raw params.
	 * @return array Repository filters.
	 */
	public function filters_from( array $params ): array {
		$filters = array();
		$type    = array_flip( self::TYPES )[ $params['type'] ?? '' ] ?? null;

		if ( '' !== trim( (string) ( $params['search'] ?? '' ) ) ) {
			$filters['search'] = sanitize_text_field( (string) $params['search'] );
		}
		if ( null !== $type ) {
			$filters['type'] = $type;
		}
		if ( in_array( $params['status'] ?? '', array( 'serviceable', 'blocked' ), true ) ) {
			$filters['status'] = 'serviceable' === $params['status'] ? 1 : 0;
		}
		if ( in_array( $params['cod'] ?? '', array( 'yes', 'no' ), true ) ) {
			$filters['cod'] = 'yes' === $params['cod'] ? 1 : 0;
		}
		if ( 'any' === ( $params['country'] ?? '' ) ) {
			$filters['country'] = '';
		} elseif ( 1 === preg_match( '/^[A-Za-z]{2}$/', (string) ( $params['country'] ?? '' ) ) ) {
			$filters['country'] = strtoupper( (string) $params['country'] );
		}

		return $filters;
	}

	/**
	 * Public shape of an area (REST, CLI, admin JS).
	 *
	 * @param array $row Database row.
	 */
	public function format( array $row ): array {
		return array(
			'id'           => (int) $row['id'],
			'country'      => (string) $row['country'],
			'type'         => self::TYPES[ (int) $row['match_type'] ] ?? 'exact',
			'code'         => (string) $row['code'],
			'code_to'      => (string) $row['code_to'],
			'status'       => 1 === (int) $row['status'] ? 'serviceable' : 'blocked',
			'city'         => (string) $row['city'],
			'state'        => (string) $row['state'],
			'days_min'     => null === $row['days_min'] ? null : (int) $row['days_min'],
			'days_max'     => null === $row['days_max'] ? null : (int) $row['days_max'],
			'shipping_fee' => null === $row['shipping_fee'] ? null : (float) $row['shipping_fee'],
			'cod_allowed'  => 1 === (int) $row['cod_allowed'],
			'cod_fee'      => (float) $row['cod_fee'],
			'note'         => (string) $row['note'],
			'updated_at'   => mysql_to_rfc3339( (string) $row['updated_at'] ),
		);
	}

	/**
	 * Fire the saved hook and return the stored area.
	 *
	 * @param int $id Area id.
	 */
	private function saved( int $id ): array {
		$area = $this->format( (array) $this->areas->find( $id ) );

		/**
		 * An area was created or updated.
		 *
		 * @param array $area Formatted area.
		 */
		do_action( 'wbpc_area_saved', $area );

		return $area;
	}

	/**
	 * 404 for a missing area ("already deleted" by someone else).
	 */
	private function not_found(): WP_Error {
		return new WP_Error( 'wbpc_area_not_found', __( 'This area no longer exists. It may have been deleted already.', 'woo-pincode-checker' ), array( 'status' => 404 ) );
	}

	/**
	 * Human message for a Postcode::parse_rule() error code.
	 *
	 * @param string $code Error code.
	 */
	private function rule_message( string $code ): string {
		return match ( $code ) {
			'range_not_numeric'     => __( 'Ranges work with numbers only. Use a prefix such as SW1* for postcodes with letters.', 'woo-pincode-checker' ),
			'range_length_mismatch' => __( 'Both ends of a range need the same number of digits.', 'woo-pincode-checker' ),
			'range_reversed'        => __( 'The range end must be greater than or equal to its start.', 'woo-pincode-checker' ),
			'wildcard_not_trailing' => __( 'Use * only at the end, for example 110*.', 'woo-pincode-checker' ),
			default                 => __( 'Enter a postcode of 2-20 letters or digits.', 'woo-pincode-checker' ),
		};
	}

	/**
	 * Int, or null when empty.
	 *
	 * @param mixed $value Value.
	 */
	private function int_or_null( $value ): ?int {
		return ( null === $value || '' === $value ) ? null : (int) $value;
	}

	/**
	 * Non-negative amount, null when empty, false when invalid.
	 *
	 * @param mixed $value Value.
	 * @return float|null|false
	 */
	private function money_or_null( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}

		$number = wc_format_decimal( (string) $value );

		return ( is_numeric( $number ) && (float) $number >= 0 ) ? (float) $number : false;
	}
}
