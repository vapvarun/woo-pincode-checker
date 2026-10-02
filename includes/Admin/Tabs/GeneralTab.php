<?php
/**
 * General tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Admin\Form;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Where the checker shows and what happens around Add to cart.
 */
final class GeneralTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$form       = new Form( 'general' );
		$base       = WC()->countries->get_base_country();
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		);

		$form->open();

		Wbcom_Settings_Page::card_open( __( 'Product page', 'woo-pincode-checker' ), __( 'Where shoppers check their pincode, and what Add to cart does with the answer.', 'woo-pincode-checker' ) );
		$form->select(
			'placement',
			__( 'Show the checker', 'woo-pincode-checker' ),
			array(
				'before_add_to_cart' => __( 'Above the Add to cart button', 'woo-pincode-checker' ),
				'after_add_to_cart'  => __( 'Below the Add to cart button', 'woo-pincode-checker' ),
				'after_quantity'     => __( 'Right after the quantity box', 'woo-pincode-checker' ),
				'product_summary'    => __( 'In the product summary, below the price', 'woo-pincode-checker' ),
				'manual'             => __( 'Only where I add the block or [wbpc_pincode_checker] shortcode', 'woo-pincode-checker' ),
			)
		);
		$form->toggle( 'require_check', __( 'Require a pincode check', 'woo-pincode-checker' ), __( 'Shoppers must check a pincode you deliver to before they can add the product to the cart.', 'woo-pincode-checker' ) );
		$form->select(
			'unavailable_action',
			__( 'When you do not deliver there', 'woo-pincode-checker' ),
			array(
				'disable' => __( 'Disable the Add to cart button', 'woo-pincode-checker' ),
				'hide'    => __( 'Hide the Add to cart button', 'woo-pincode-checker' ),
				'allow'   => __( 'Keep Add to cart, show a warning', 'woo-pincode-checker' ),
			),
			__( 'Add to cart is also checked on the server. Checkout blocks orders to postcodes you do not deliver to while "Block orders to areas you do not serve" is on (Checkout and COD tab).', 'woo-pincode-checker' )
		);
		$form->choices( 'excluded_categories', __( 'Skip these categories', 'woo-pincode-checker' ), is_array( $categories ) ? $categories : array(), __( 'Products in these categories show no checker and are never blocked (digital goods, gift cards).', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'Postcode matching', 'woo-pincode-checker' ) );
		$form->select(
			'unknown_policy',
			__( 'Postcodes with no area', 'woo-pincode-checker' ),
			array(
				'auto'        => __( 'Automatic - available until I add my first delivery area, then only listed areas', 'woo-pincode-checker' ),
				'unavailable' => __( 'Not available - I list every area I deliver to', 'woo-pincode-checker' ),
				'available'   => __( 'Available on default terms - I only list exceptions', 'woo-pincode-checker' ),
			),
			__( 'Automatic keeps a new store selling before any areas are set up.', 'woo-pincode-checker' )
		);
		$form->select(
			'default_country',
			__( 'Default country', 'woo-pincode-checker' ),
			/* translators: %s: store base country name. */
			array( '' => sprintf( __( 'Store country (%s)', 'woo-pincode-checker' ), WC()->countries->get_countries()[ $base ] ?? $base ) ) + WC()->countries->get_countries(),
			__( 'Used to read a postcode when the shopper has not chosen a country yet.', 'woo-pincode-checker' )
		);
		$form->toggle( 'show_nearby', __( 'Suggest nearby areas', 'woo-pincode-checker' ), __( 'When a postcode is not served, list served postcodes that start the same way.', 'woo-pincode-checker' ) );
		$form->input(
			'remember_days',
			__( 'Remember the pincode for', 'woo-pincode-checker' ),
			'number',
			__( 'Days the shopper\'s last checked pincode is remembered on this device.', 'woo-pincode-checker' ),
			array(
				'min' => 1,
				'max' => 365,
			)
		);
		Wbcom_Settings_Page::card_close();

		$form->close();
	}
}
