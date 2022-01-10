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

/**
 * Function for get single product page button position.
 */
function wpc_single_product_button_position() {
	$wpc_general_settings       = get_option( 'wpc_general_settings' );
	$wpc_single_button_position = ( isset( $wpc_general_settings['pincode_position'] ) && ! empty( $wpc_general_settings['pincode_position'] ) ) ? $wpc_general_settings['pincode_position'] : '';

	return apply_filters( 'alter_wpc_single_product_button_position', $wpc_single_button_position );
}

/**
 * Function for add check button label.
 */
function wpc_get_check_btn_label() {
	$wpc_general_settings = get_option( 'wpc_general_settings' );
	$wpc_check_btn_label  = ( isset( $wpc_general_settings['check_btn_text'] ) && ! empty( $wpc_general_settings['check_btn_text'] ) ) ? $wpc_general_settings['check_btn_text'] : 'Check';
	return apply_filters( 'alter_wpc_get_check_btn_label', $wpc_check_btn_label );
}

/**
 * Function for add change button label.
 */
function wpc_get_change_btn_label() {
	$wpc_general_settings = get_option( 'wpc_general_settings' );
	$wpc_change_btn_label = ( isset( $wpc_general_settings['change_btn_text'] ) && ! empty( $wpc_general_settings['change_btn_text'] ) ) ? $wpc_general_settings['change_btn_text'] : 'Change';
	return apply_filters( 'alter_wpc_get_change_btn_label', $wpc_change_btn_label );
}

/**
 * Function for add delivered by label.
 */
function wpc_get_delivery_date_label() {
	$wpc_general_settings    = get_option( 'wpc_general_settings' );
	$wpc_delivery_date_label = ( isset( $wpc_general_settings['delivery_date_label_text'] ) && ! empty( $wpc_general_settings['delivery_date_label_text'] ) ) ? $wpc_general_settings['delivery_date_label_text'] : 'Delivery Date';
	return apply_filters( 'alter_wpc_get_delivery_date_label', $wpc_delivery_date_label );
}

/**
 * Function for add available at label.
 */
function wpc_get_availability_label() {
	$wpc_general_settings   = get_option( 'wpc_general_settings' );
	$wpc_availability_label = ( isset( $wpc_general_settings['availability_label_text'] ) && ! empty( $wpc_general_settings['availability_label_text'] ) ) ? $wpc_general_settings['availability_label_text'] : 'Available at';
	return apply_filters( 'alter_wpc_get_availability_label', $wpc_availability_label );
}

/**
 * Function for add cod at label.
 */
function wpc_get_cod_label() {
	$wpc_general_settings = get_option( 'wpc_general_settings' );
	$wpc_cod_label        = ( isset( $wpc_general_settings['cod_label_text'] ) && ! empty( $wpc_general_settings['cod_label_text'] ) ) ? $wpc_general_settings['cod_label_text'] : 'Cash On Delivery Available';
	return apply_filters( 'alter_wpc_get_cod_label', $wpc_cod_label );
}

/**
 * Function for display cod option.
 *
 * @return Array
 */
function wpc_display_cod_option() {
	$wpc_general_settings   = get_option( 'wpc_general_settings' );
	$wpc_display_cod_option = ( isset( $wpc_general_settings['cod_display'] ) && 'on' === $wpc_general_settings['cod_display'] ) ? true : false;

	return apply_filters( 'alter_wpc_display_cod_option', $wpc_display_cod_option );
}

/**
 * Function for hide shop page add to cart option.
 *
 * @return Array
 */
function wpc_hide_shop_page_cart_btn_option() {
	$wpc_general_settings = get_option( 'wpc_general_settings' );
	$wpc_hide_cart_btn    = ( isset( $wpc_general_settings['hide_shop_btn'] ) && 'on' === $wpc_general_settings['hide_shop_btn'] ) ? true : false;

	return apply_filters( 'alter_wpc_hide_shop_page_cart_btn_option', $wpc_hide_cart_btn );
}
