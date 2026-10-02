<?php
/**
 * Display and Messages tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Admin\Form;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Every word the shopper sees, plus the accent colour.
 */
final class DisplayTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$form   = new Form( 'display' );
		$tokens = __( 'You can use {postcode}, {city} and {state}.', 'woo-pincode-checker' );

		$form->open();

		Wbcom_Settings_Page::card_open( __( 'Checker labels', 'woo-pincode-checker' ) );
		$form->input( 'title', __( 'Heading', 'woo-pincode-checker' ) );
		$form->input( 'placeholder', __( 'Input placeholder', 'woo-pincode-checker' ) );
		$form->input( 'check_label', __( 'Check button', 'woo-pincode-checker' ) );
		$form->input( 'change_label', __( 'Change button', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'Messages', 'woo-pincode-checker' ), __( 'Leave a message empty to restore its default.', 'woo-pincode-checker' ) );
		$form->input( 'msg_available', __( 'Delivery available', 'woo-pincode-checker' ), 'text', $tokens );
		$form->input( 'msg_estimate', __( 'Delivery estimate', 'woo-pincode-checker' ), 'text', __( '{estimate} becomes the date, range or days set on the Delivery Dates tab.', 'woo-pincode-checker' ) );
		$form->input( 'msg_unavailable', __( 'Not delivered', 'woo-pincode-checker' ), 'text', $tokens );
		$form->input( 'msg_invalid', __( 'Invalid pincode', 'woo-pincode-checker' ) );
		$form->input( 'msg_required', __( 'Check required', 'woo-pincode-checker' ), 'text', __( 'Shown when Add to cart needs a pincode check first.', 'woo-pincode-checker' ) );
		$form->input( 'shipping_label', __( 'Shipping fee', 'woo-pincode-checker' ), 'text', __( '{amount} becomes the area\'s shipping fee.', 'woo-pincode-checker' ) );
		$form->toggle( 'show_cod_line', __( 'Show cash on delivery availability', 'woo-pincode-checker' ) );
		$form->input( 'cod_available', __( 'COD available', 'woo-pincode-checker' ) );
		$form->input( 'cod_unavailable', __( 'COD not available', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'Style', 'woo-pincode-checker' ) );
		$form->select(
			'style',
			__( 'Colours', 'woo-pincode-checker' ),
			array(
				'theme'  => __( 'Match my theme', 'woo-pincode-checker' ),
				'custom' => __( 'Use my accent colour', 'woo-pincode-checker' ),
			)
		);
		$form->input( 'accent_color', __( 'Accent colour', 'woo-pincode-checker' ), 'color', __( 'Used for the Check button when "Use my accent colour" is selected.', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		$form->close();
	}
}
