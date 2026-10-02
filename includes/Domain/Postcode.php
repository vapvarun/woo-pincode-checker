<?php
/**
 * Postcode normalisation and area-rule parsing. Pure PHP, no WordPress calls.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Match types are stored as tinyints in wbpc_areas.match_type.
 */
final class Postcode {

	public const EXACT  = 1;
	public const PREFIX = 2;
	public const RANGE  = 3;

	public const MAX_LENGTH = 20;

	/**
	 * Same result as WooCommerce's wc_normalize_postcode(): uppercase, no spaces or hyphens.
	 *
	 * @param string $postcode Raw input.
	 */
	public static function normalize( string $postcode ): string {
		return (string) preg_replace( '/[\s\-]/', '', mb_strtoupper( trim( $postcode ) ) );
	}

	/**
	 * Whether a normalised code is plausible in any country: 2-20 letters/digits.
	 *
	 * @param string $norm Normalised code.
	 */
	public static function is_plausible( string $norm ): bool {
		return 1 === preg_match( '/^[A-Z0-9]{2,' . self::MAX_LENGTH . '}$/', $norm );
	}

	/**
	 * Every prefix of a code, longest first: "SW1A1AA" -> SW1A1AA, SW1A1A, ..., S.
	 *
	 * @param string $norm Normalised code.
	 * @return string[]
	 */
	public static function prefixes( string $norm ): array {
		$out = array();

		for ( $len = strlen( $norm ); $len >= 1; $len-- ) {
			$out[] = substr( $norm, 0, $len );
		}

		return $out;
	}

	/**
	 * Numeric value for range matching, or null when the code has letters.
	 *
	 * @param string $norm Normalised code.
	 */
	public static function numeric( string $norm ): ?int {
		return ( '' !== $norm && ctype_digit( $norm ) && strlen( $norm ) <= 18 ) ? (int) $norm : null;
	}

	/**
	 * Parse what an owner typed into an area rule.
	 *
	 * - "110001"            -> exact
	 * - "SW1*", "110***"    -> prefix (trailing * only)
	 * - "110001" + "110099" -> numeric range (same length, start <= end)
	 *
	 * Non-range rules get range_start/range_end 0: the columns are NOT NULL so the unique key
	 * (MySQL allows repeated NULLs in a unique index) actually rejects duplicate codes.
	 *
	 * @param string $code    Code, or range start.
	 * @param string $code_to Range end, '' for exact/prefix.
	 * @return array{match_type:int, code:string, code_to:string, code_norm:string, range_start:int, range_end:int}|string Parsed rule, or an error code.
	 */
	public static function parse_rule( string $code, string $code_to = '' ) {
		$code    = mb_strtoupper( trim( $code ) );
		$code_to = mb_strtoupper( trim( $code_to ) );

		if ( '' !== $code_to ) {
			$start = self::normalize( $code );
			$end   = self::normalize( $code_to );
			$a     = self::numeric( $start );
			$b     = self::numeric( $end );

			if ( null === $a || null === $b ) {
				return 'range_not_numeric';
			}
			if ( strlen( $start ) !== strlen( $end ) ) {
				return 'range_length_mismatch';
			}
			if ( $a > $b ) {
				return 'range_reversed';
			}

			return array(
				'match_type'  => self::RANGE,
				'code'        => $code,
				'code_to'     => $code_to,
				'code_norm'   => '',
				'range_start' => $a,
				'range_end'   => $b,
			);
		}

		if ( str_contains( $code, '*' ) ) {
			$prefix = self::normalize( rtrim( $code, '*' ) );

			if ( str_contains( $prefix, '*' ) ) {
				return 'wildcard_not_trailing';
			}
			if ( 1 !== preg_match( '/^[A-Z0-9]{1,' . self::MAX_LENGTH . '}$/', $prefix ) ) {
				return 'invalid_code';
			}

			return array(
				'match_type'  => self::PREFIX,
				'code'        => $prefix . '*',
				'code_to'     => '',
				'code_norm'   => $prefix,
				'range_start' => 0,
				'range_end'   => 0,
			);
		}

		$norm = self::normalize( $code );

		if ( ! self::is_plausible( $norm ) ) {
			return 'invalid_code';
		}

		return array(
			'match_type'  => self::EXACT,
			'code'        => $code,
			'code_to'     => '',
			'code_norm'   => $norm,
			'range_start' => 0,
			'range_end'   => 0,
		);
	}
}
