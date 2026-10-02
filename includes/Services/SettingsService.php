<?php
/**
 * Settings: one schema for defaults, sanitizing and reading.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Each group is its own option (wbpc_general, wbpc_delivery, ...) saved by its own tab, so saving
 * one tab can never touch another. Defaults are never written: reads merge saved values over
 * defaults, so default labels follow the site language.
 */
final class SettingsService {

	public const GROUPS = array( 'general', 'delivery', 'category_days', 'checkout', 'display' );

	/**
	 * Option name of a group.
	 *
	 * @param string $group Group.
	 */
	public static function option( string $group ): string {
		return 'wbpc_' . $group;
	}

	/**
	 * Field schema per group. Types: bool, int, enum, enum_list, ids, country, time, dates, text, color.
	 *
	 * @return array<string, array<string, array>>
	 */
	public function schema(): array {
		$days = array( '1', '2', '3', '4', '5', '6', '7' ); // ISO-8601: 1 = Monday.

		return array(
			'general'  => array(
				'placement'           => array( 'enum', 'before_add_to_cart', array( 'before_add_to_cart', 'after_add_to_cart', 'after_quantity', 'product_summary', 'manual' ) ),
				'require_check'       => array( 'bool', false ),
				'unavailable_action'  => array( 'enum', 'disable', array( 'disable', 'hide', 'allow' ) ),
				'unknown_policy'      => array( 'enum', 'auto', array( 'auto', 'unavailable', 'available' ) ), // auto: available until the first serviceable area exists, so a fresh install never blocks orders.
				'default_country'     => array( 'country', '' ),
				'excluded_categories' => array( 'ids', array() ),
				'show_nearby'         => array( 'bool', true ),
				'remember_days'       => array( 'int', 30, 1, 365 ),
			),
			'delivery' => array(
				'show_estimate'    => array( 'bool', true ),
				'default_days_min' => array( 'int', 3, 0, 90 ),
				'default_days_max' => array( 'int', 5, 0, 90 ),
				'processing_days'  => array( 'int', 0, 0, 30 ),
				'cutoff_time'      => array( 'time', '14:00' ),
				'working_days'     => array( 'enum_list', array( '1', '2', '3', '4', '5', '6' ), $days ),
				'transit_counting' => array( 'enum', 'business', array( 'business', 'calendar' ) ),
				'holidays'         => array( 'dates', array() ),
				'display'          => array( 'enum', 'range', array( 'range', 'date', 'days' ) ),
				'date_format'      => array( 'enum', 'locale', array( 'locale', 'site', 'D, M j', 'M j', 'j M', 'l, F j', 'd/m', 'm/d' ) ), // locale: the short format translators set for their language.
				'show_on'          => array( 'enum_list', array( 'product', 'cart', 'order', 'emails' ), array( 'product', 'cart', 'order', 'emails' ) ), // cart = cart and checkout (shipping rate labels).
			),
			'checkout' => array(
				'validate_checkout' => array( 'bool', true ),
				'cod_gating'        => array( 'bool', true ),
				'cod_fee'           => array( 'bool', true ),
				'cod_fee_label'     => array( 'text', __( 'Cash on delivery fee', 'woo-pincode-checker' ) ),
				'prefill_postcode'  => array( 'bool', true ),
			),
			'display'  => array(
				'title'           => array( 'text', __( 'Check delivery', 'woo-pincode-checker' ) ),
				'placeholder'     => array( 'text', __( 'Enter pincode', 'woo-pincode-checker' ) ),
				'check_label'     => array( 'text', _x( 'Check', 'button: check delivery to a pincode', 'woo-pincode-checker' ) ),
				'change_label'    => array( 'text', _x( 'Change', 'button: enter a different pincode', 'woo-pincode-checker' ) ),
				'msg_available'   => array( 'text', __( 'Delivery available to {city}', 'woo-pincode-checker' ) ),
				'msg_estimate'    => array( 'text', __( 'Arrives {estimate}', 'woo-pincode-checker' ) ),
				'msg_unavailable' => array( 'text', __( "Sorry, we don't deliver to {postcode} yet.", 'woo-pincode-checker' ) ),
				'msg_invalid'     => array( 'text', __( 'Please enter a valid pincode.', 'woo-pincode-checker' ) ),
				'msg_required'    => array( 'text', __( 'Check your pincode to add this product to the cart.', 'woo-pincode-checker' ) ),
				'cod_available'   => array( 'text', __( 'Cash on delivery available', 'woo-pincode-checker' ) ),
				'cod_unavailable' => array( 'text', __( 'Cash on delivery not available', 'woo-pincode-checker' ) ),
				'shipping_label'  => array( 'text', __( 'Shipping: {amount}', 'woo-pincode-checker' ) ),
				'show_cod_line'   => array( 'bool', true ),
				'style'           => array( 'enum', 'theme', array( 'theme', 'custom' ) ),
				'accent_color'    => array( 'color', '#2271b1' ),
			),
		);
	}

	/**
	 * Read a setting, or a whole group, with defaults filled in.
	 *
	 * @param string      $group Group.
	 * @param string|null $key   Key, or null for the group.
	 * @return mixed
	 */
	public function get( string $group, ?string $key = null ) {
		$saved = get_option( self::option( $group ), array() );
		$saved = is_array( $saved ) ? $saved : array();

		if ( 'category_days' === $group ) {
			return null === $key ? $saved : ( $saved[ $key ] ?? 0 );
		}

		$values = array_merge( $this->defaults( $group ), array_intersect_key( $saved, $this->defaults( $group ) ) );

		return null === $key ? $values : ( $values[ $key ] ?? null );
	}

	/**
	 * Defaults of a group.
	 *
	 * @param string $group Group.
	 */
	public function defaults( string $group ): array {
		return array_map( static fn( $field ) => $field[1], $this->schema()[ $group ] ?? array() );
	}

	/**
	 * Sanitize a submitted group. Missing booleans are unchecked boxes (false); every other missing
	 * key keeps its default. Enums are checked against their allowed values.
	 *
	 * @param string $group Group.
	 * @param mixed  $input Submitted value.
	 */
	public function sanitize( string $group, $input ): array {
		$input = is_array( $input ) ? $input : array();

		if ( 'category_days' === $group ) {
			// The tab's "add a category" row posts _new_term/_new_days inside the same array.
			if ( ! empty( $input['_new_term'] ) && ! empty( $input['_new_days'] ) ) {
				$input[ (int) $input['_new_term'] ] = $input['_new_days'];
			}
			unset( $input['_new_term'], $input['_new_days'] );

			$out = array();
			foreach ( $input as $term_id => $days ) {
				$days = (int) $days;
				if ( $days > 0 && term_exists( (int) $term_id, 'product_cat' ) ) {
					$out[ (int) $term_id ] = min( 60, $days );
				}
			}
			ksort( $out );
			return $out;
		}

		$out = array();

		foreach ( $this->schema()[ $group ] ?? array() as $key => $field ) {
			[ $type, $default ] = $field;
			$value              = $input[ $key ] ?? null;

			$out[ $key ] = match ( $type ) {
				'bool'      => ! empty( $value ),
				'int'       => null === $value || '' === $value ? $default : max( $field[2], min( $field[3], (int) $value ) ),
				'enum'      => in_array( (string) $value, $field[2], true ) ? (string) $value : $default,
				'enum_list' => array_values( array_intersect( $field[2], array_map( 'strval', (array) $value ) ) ),
				'ids'       => $this->category_ids( (array) $value ),
				'country'   => isset( WC()->countries->get_countries()[ (string) $value ] ) ? (string) $value : '',
				'time'      => 1 === preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value ) ? (string) $value : $default,
				'dates'     => $this->dates( $value ),
				'color'     => sanitize_hex_color( (string) $value ) ?? $default,
				default     => $this->text( $value, (string) $default ),
			};
		}

		// The date engine walks forward to the next working day: an empty week would never end.
		if ( 'delivery' === $group && ! $out['working_days'] ) {
			$out['working_days'] = $this->defaults( 'delivery' )['working_days'];
			add_settings_error( self::option( $group ), 'wbpc_working_days', __( 'Choose at least one working day. Monday to Saturday was kept.', 'woo-pincode-checker' ), 'warning' );
		}

		if ( 'delivery' === $group && $out['default_days_max'] < $out['default_days_min'] ) {
			$out['default_days_max'] = $out['default_days_min'];
			add_settings_error( self::option( $group ), 'wbpc_days', __( 'The default maximum delivery days was raised to match the minimum.', 'woo-pincode-checker' ), 'warning' );
		}

		return $out;
	}

	/**
	 * A text setting; an emptied label falls back to its default.
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $fallback Default text.
	 */
	private function text( $value, string $fallback ): string {
		$value = trim( (string) $value );

		return '' === $value ? $fallback : sanitize_text_field( $value );
	}

	/**
	 * Product category ids that exist. absint() alone would turn "-3" into category 3.
	 *
	 * @param array $values Raw values.
	 * @return int[]
	 */
	private function category_ids( array $values ): array {
		$ids = array_filter( array_map( 'intval', array_filter( $values, 'is_numeric' ) ), static fn( int $id ) => $id > 0 );

		if ( ! $ids ) {
			return array();
		}

		$found = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'include'    => $ids,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		return is_array( $found ) ? array_values( array_map( 'intval', $found ) ) : array();
	}

	/**
	 * Holidays: one Y-m-d per line (or an array). Invalid and past dates are dropped, max 366.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	private function dates( $value ): array {
		$lines = is_array( $value ) ? $value : preg_split( '/[\s,]+/', (string) $value );
		$today = wp_date( 'Y-m-d' );
		$out   = array();

		foreach ( (array) $lines as $line ) {
			$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', trim( (string) $line ) );
			if ( $date && $date->format( 'Y-m-d' ) === trim( (string) $line ) && $date->format( 'Y-m-d' ) >= $today ) {
				$out[] = $date->format( 'Y-m-d' );
			}
		}

		$out = array_values( array_unique( $out ) );
		sort( $out );

		return array_slice( $out, 0, 366 );
	}
}
