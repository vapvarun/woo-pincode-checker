<?php
/**
 * Category-Specific Delivery Rules Handler
 *
 * Manages category-specific delivery times and rules for pincodes.
 * Allows different delivery days for different product categories.
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 * @since      1.5.0
 */

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- All table names derived from $wpdb->prefix.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Category Rules Handler Class
 *
 * @since 1.5.0
 */
class Woo_Pincode_Category_Rules {

	/**
	 * Get category rules for a specific pincode
	 *
	 * @param string $pincode Pincode to get rules for.
	 * @return array|null Array of category rules or null if none exist
	 */
	public function get_pincode_category_rules( $pincode ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name from $wpdb->prefix.
		$category_rules = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT category_rules FROM {$table_name} WHERE pincode = %s",
				$pincode
			)
		);

		if ( ! empty( $category_rules ) ) {
			$decoded = json_decode( $category_rules, true );
			return is_array( $decoded ) ? $decoded : null;
		}

		return null;
	}

	/**
	 * Set category rules for a specific pincode
	 *
	 * @param string $pincode Pincode to set rules for.
	 * @param array  $rules   Array of category rules (category_id => delivery_days).
	 * @return bool True on success, false on failure
	 */
	public function set_pincode_category_rules( $pincode, $rules ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Validate rules array.
		if ( ! is_array( $rules ) ) {
			return false;
		}

		// Encode rules to JSON.
		$json_rules = wp_json_encode( $rules );

		// Update the pincode record.
		$result = $wpdb->update(
			$table_name,
			array( 'category_rules' => $json_rules ),
			array( 'pincode' => $pincode ),
			array( '%s' ),
			array( '%s' )
		);

		return false !== $result;
	}

	/**
	 * Get delivery days for a specific category and pincode
	 *
	 * @param string $pincode     Pincode.
	 * @param int    $category_id WooCommerce category ID.
	 * @return int|null Delivery days for category, or null if no rule exists
	 */
	public function get_category_delivery_days( $pincode, $category_id ) {
		$rules = $this->get_pincode_category_rules( $pincode );

		if ( ! empty( $rules ) && isset( $rules[ $category_id ] ) ) {
			return intval( $rules[ $category_id ] );
		}

		return null;
	}

	/**
	 * Get delivery days for a product at a specific pincode
	 * Considers product categories and returns category-specific delivery time if available
	 *
	 * @param int    $product_id WooCommerce product ID.
	 * @param string $pincode    Pincode.
	 * @return array Array with 'delivery_days' and 'source' (default or category_name)
	 */
	public function get_product_delivery_info( $product_id, $pincode ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Get default delivery days for this pincode.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name from $wpdb->prefix.
		$default_delivery = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT delivery_days FROM {$table_name} WHERE pincode = %s",
				$pincode
			)
		);

		if ( null === $default_delivery ) {
			return null; // Pincode doesn't exist.
		}

		// Get category rules for this pincode.
		$category_rules = $this->get_pincode_category_rules( $pincode );

		if ( empty( $category_rules ) ) {
			// No category rules, return default.
			return array(
				'delivery_days' => intval( $default_delivery ),
				'source'        => 'default',
			);
		}

		// Get product categories.
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $product_categories ) || empty( $product_categories ) ) {
			// Product has no categories, return default.
			return array(
				'delivery_days' => intval( $default_delivery ),
				'source'        => 'default',
			);
		}

		// Check if any product category has a specific rule.
		// Priority: Use the first matching category rule found.
		foreach ( $product_categories as $cat_id ) {
			if ( isset( $category_rules[ $cat_id ] ) ) {
				$category      = get_term( $cat_id, 'product_cat' );
				$category_name = $category && ! is_wp_error( $category ) ? $category->name : 'Category';

				return array(
					'delivery_days' => intval( $category_rules[ $cat_id ] ),
					'source'        => $category_name,
				);
			}
		}

		// No matching category rule found, return default.
		return array(
			'delivery_days' => intval( $default_delivery ),
			'source'        => 'default',
		);
	}

	/**
	 * Get global category delivery rules (site-wide settings)
	 *
	 * @return array Array of category_id => delivery_days
	 */
	public function get_global_category_rules() {
		$settings = get_option( 'wpc_general_settings', array() );

		if ( isset( $settings['global_category_rules'] ) && is_array( $settings['global_category_rules'] ) ) {
			return $settings['global_category_rules'];
		}

		return array();
	}

	/**
	 * Set global category delivery rules (site-wide settings)
	 *
	 * @param array $rules Array of category_id => delivery_days.
	 * @return bool True on success, false on failure
	 */
	public function set_global_category_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			return false;
		}

		$settings                          = get_option( 'wpc_general_settings', array() );
		$settings['global_category_rules'] = $rules;

		return update_option( 'wpc_general_settings', $settings );
	}

	/**
	 * Apply global category rules to all existing pincodes
	 * Useful when user sets up category rules and wants to apply them site-wide
	 *
	 * @param array $rules Array of category_id => delivery_days.
	 * @return array Result with success count and error count
	 */
	public function apply_global_rules_to_all_pincodes( $rules ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		if ( ! is_array( $rules ) || empty( $rules ) ) {
			return array(
				'success' => 0,
				'errors'  => 0,
			);
		}

		$json_rules    = wp_json_encode( $rules );
		$success_count = 0;
		$error_count   = 0;

		// Get all pincodes.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table name from $wpdb->prefix.
		$pincodes = $wpdb->get_col( "SELECT pincode FROM {$table_name}" );

		foreach ( $pincodes as $pincode ) {
			$result = $wpdb->update(
				$table_name,
				array( 'category_rules' => $json_rules ),
				array( 'pincode' => $pincode ),
				array( '%s' ),
				array( '%s' )
			);

			if ( false !== $result ) {
				++$success_count;
			} else {
				++$error_count;
			}
		}

		return array(
			'success' => $success_count,
			'errors'  => $error_count,
		);
	}

	/**
	 * Get all WooCommerce product categories
	 *
	 * @return array Array of category objects with id and name
	 */
	public function get_all_product_categories() {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $categories ) ) {
			return array();
		}

		$result = array();
		foreach ( $categories as $category ) {
			$result[] = array(
				'id'   => $category->term_id,
				'name' => $category->name,
				'slug' => $category->slug,
			);
		}

		return $result;
	}

	/**
	 * Validate category rules array
	 *
	 * @param array $rules Array of category_id => delivery_days.
	 * @return array|WP_Error Valid rules array or WP_Error on failure
	 */
	public function validate_category_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			return new WP_Error( 'invalid_rules', __( 'Rules must be an array', 'pincode-checker-for-woocommerce' ) );
		}

		$validated = array();

		foreach ( $rules as $category_id => $delivery_days ) {
			$cat_id = intval( $category_id );
			$days   = intval( $delivery_days );

			// Validate category exists.
			$category = get_term( $cat_id, 'product_cat' );
			if ( ! $category || is_wp_error( $category ) ) {
				continue; // Skip invalid categories.
			}

			// Validate delivery days range.
			if ( $days < 1 || $days > 365 ) {
				return new WP_Error( 'invalid_delivery_days', __( 'Delivery days must be between 1 and 365', 'pincode-checker-for-woocommerce' ) );
			}

			$validated[ $cat_id ] = $days;
		}

		return $validated;
	}
}
