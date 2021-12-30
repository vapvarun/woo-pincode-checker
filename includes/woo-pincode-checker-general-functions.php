<?php
/**
 * The file that defines the general functions.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 */

/**
 * Get an array of the categories.
 *
 * @return array
 */
function wpc_get_wc_categories() {
	$args                = array(
		'taxonomy' => 'product_cat',
	);
	$products_categories = get_terms( $args );
	if ( ! empty( $products_categories ) ) {
		return $products_categories;
	}
}

/**
 * Function for exclude category for shipping.
 */
function wpc_get_products_to_pincode_checker_by_category() {
	$wpc_general_settings     = get_option( 'wpc_general_settings' );
	$wpc_pinocode_by_category = ( isset( $wpc_general_settings['categories_for_shipping'] ) ) ? $wpc_general_settings['categories_for_shipping'] : array();

	return apply_filters( 'alter_wpc_get_products_to_pincode_checker_by_category', $wpc_pinocode_by_category );
}
