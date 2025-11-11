<?php
/**
 * Pincode Pattern Handler
 *
 * Handles parsing and expansion of pincode patterns and ranges.
 * Supports wildcards (*), single character wildcards (?), and ranges.
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 * @since      1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pincode Pattern Handler Class
 *
 * @since 1.6.0
 */
class Woo_Pincode_Pattern_Handler {

	/**
	 * Maximum number of pincodes that can be generated from a pattern
	 *
	 * @var int
	 */
	private $max_pincodes = 10000;

	/**
	 * Parse a pincode pattern or range and return all matching pincodes
	 *
	 * @param string $pattern Pattern to parse (e.g., "110***", "110001-110099")
	 * @return array|WP_Error Array of pincodes or WP_Error on failure
	 */
	public function parse_pattern( $pattern ) {
		$pattern = trim( $pattern );

		if ( empty( $pattern ) ) {
			return new WP_Error( 'empty_pattern', __( 'Pattern cannot be empty', 'pincode-checker-for-woocommerce' ) );
		}

		// Check if it's a range pattern (e.g., "110001-110099")
		if ( strpos( $pattern, '-' ) !== false ) {
			return $this->parse_range( $pattern );
		}

		// Check if it contains wildcards
		if ( strpos( $pattern, '*' ) !== false || strpos( $pattern, '?') !== false ) {
			return $this->parse_wildcard( $pattern );
		}

		// Single pincode
		return array( $pattern );
	}

	/**
	 * Parse a range pattern (e.g., "110001-110099")
	 *
	 * @param string $pattern Range pattern
	 * @return array|WP_Error Array of pincodes or WP_Error on failure
	 */
	private function parse_range( $pattern ) {
		$parts = explode( '-', $pattern );

		if ( count( $parts ) !== 2 ) {
			return new WP_Error( 'invalid_range', __( 'Invalid range format. Use format: START-END (e.g., 110001-110099)', 'pincode-checker-for-woocommerce' ) );
		}

		$start = trim( $parts[0] );
		$end = trim( $parts[1] );

		// Validate both are numeric
		if ( ! ctype_digit( $start ) || ! ctype_digit( $end ) ) {
			return new WP_Error( 'invalid_range_values', __( 'Range values must be numeric', 'pincode-checker-for-woocommerce' ) );
		}

		// Validate same length
		if ( strlen( $start ) !== strlen( $end ) ) {
			return new WP_Error( 'range_length_mismatch', __( 'Start and end of range must have same length', 'pincode-checker-for-woocommerce' ) );
		}

		$start_num = intval( $start );
		$end_num = intval( $end );

		if ( $start_num > $end_num ) {
			return new WP_Error( 'invalid_range_order', __( 'Start value must be less than or equal to end value', 'pincode-checker-for-woocommerce' ) );
		}

		$count = $end_num - $start_num + 1;

		if ( $count > $this->max_pincodes ) {
			return new WP_Error(
				'range_too_large',
				sprintf(
					__( 'Range generates %d pincodes. Maximum allowed is %d. Please use smaller ranges.', 'pincode-checker-for-woocommerce' ),
					$count,
					$this->max_pincodes
				)
			);
		}

		$pincodes = array();
		$length = strlen( $start );

		for ( $i = $start_num; $i <= $end_num; $i++ ) {
			$pincodes[] = str_pad( $i, $length, '0', STR_PAD_LEFT );
		}

		return $pincodes;
	}

	/**
	 * Parse a wildcard pattern (e.g., "110***", "400*2?")
	 *
	 * @param string $pattern Wildcard pattern
	 * @return array|WP_Error Array of pincodes or WP_Error on failure
	 */
	private function parse_wildcard( $pattern ) {
		// Count wildcards
		$star_count = substr_count( $pattern, '*' );
		$question_count = substr_count( $pattern, '?' );

		// Calculate possible combinations
		$combinations = pow( 10, $star_count ) * pow( 10, $question_count );

		if ( $combinations > $this->max_pincodes ) {
			return new WP_Error(
				'pattern_too_broad',
				sprintf(
					__( 'Pattern generates %d pincodes. Maximum allowed is %d. Please use more specific patterns.', 'pincode-checker-for-woocommerce' ),
					$combinations,
					$this->max_pincodes
				)
			);
		}

		return $this->expand_wildcard( $pattern );
	}

	/**
	 * Expand wildcard pattern into all possible pincodes
	 *
	 * @param string $pattern Wildcard pattern
	 * @return array Array of pincodes
	 */
	private function expand_wildcard( $pattern ) {
		$pincodes = array( '' );

		for ( $i = 0; $i < strlen( $pattern ); $i++ ) {
			$char = $pattern[ $i ];
			$new_pincodes = array();

			if ( $char === '*' || $char === '?' ) {
				// Replace with 0-9
				foreach ( $pincodes as $pincode ) {
					for ( $j = 0; $j <= 9; $j++ ) {
						$new_pincodes[] = $pincode . $j;
					}
				}
			} else {
				// Keep the character as-is
				foreach ( $pincodes as $pincode ) {
					$new_pincodes[] = $pincode . $char;
				}
			}

			$pincodes = $new_pincodes;
		}

		return $pincodes;
	}

	/**
	 * Validate a pincode pattern
	 *
	 * @param string $pattern Pattern to validate
	 * @return bool|WP_Error True if valid, WP_Error otherwise
	 */
	public function validate_pattern( $pattern ) {
		$pattern = trim( $pattern );

		if ( empty( $pattern ) ) {
			return new WP_Error( 'empty_pattern', __( 'Pattern cannot be empty', 'pincode-checker-for-woocommerce' ) );
		}

		// Check length (minimum 3, maximum 10)
		if ( strlen( $pattern ) < 3 || strlen( $pattern ) > 10 ) {
			return new WP_Error( 'invalid_length', __( 'Pattern must be between 3 and 10 characters', 'pincode-checker-for-woocommerce' ) );
		}

		// Check for invalid characters (only alphanumeric, *, ?, and - allowed)
		if ( ! preg_match( '/^[a-zA-Z0-9\*\?\-]+$/', $pattern ) ) {
			return new WP_Error( 'invalid_characters', __( 'Pattern can only contain letters, numbers, *, ?, and -', 'pincode-checker-for-woocommerce' ) );
		}

		// If it's a range, validate range format
		if ( strpos( $pattern, '-' ) !== false ) {
			$parts = explode( '-', $pattern );
			if ( count( $parts ) !== 2 ) {
				return new WP_Error( 'invalid_range', __( 'Invalid range format', 'pincode-checker-for-woocommerce' ) );
			}
		}

		return true;
	}

	/**
	 * Preview how many pincodes will be generated from a pattern
	 *
	 * @param string $pattern Pattern to preview
	 * @return int|WP_Error Number of pincodes or WP_Error on failure
	 */
	public function preview_count( $pattern ) {
		$validation = $this->validate_pattern( $pattern );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$pincodes = $this->parse_pattern( $pattern );

		if ( is_wp_error( $pincodes ) ) {
			return $pincodes;
		}

		return count( $pincodes );
	}

	/**
	 * Get examples of patterns for help text
	 *
	 * @return array Array of example patterns with descriptions
	 */
	public static function get_pattern_examples() {
		return array(
			array(
				'pattern' => '110***',
				'description' => __( 'Generates 1000 pincodes from 110000 to 110999', 'pincode-checker-for-woocommerce' ),
			),
			array(
				'pattern' => '400*',
				'description' => __( 'Generates 10 pincodes from 4000 to 4009', 'pincode-checker-for-woocommerce' ),
			),
			array(
				'pattern' => '11001?',
				'description' => __( 'Generates 10 pincodes from 110010 to 110019', 'pincode-checker-for-woocommerce' ),
			),
			array(
				'pattern' => '110001-110099',
				'description' => __( 'Generates 99 pincodes from 110001 to 110099', 'pincode-checker-for-woocommerce' ),
			),
		);
	}
}
