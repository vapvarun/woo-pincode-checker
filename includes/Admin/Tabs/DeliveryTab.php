<?php
/**
 * Delivery Dates tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Admin\Form;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * The delivery estimate engine's store-wide rules.
 */
final class DeliveryTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$form    = new Form( 'delivery' );
		$sample  = strtotime( '+3 days' );
		$formats = array();
		foreach ( array( 'D, M j', 'M j', 'j M', 'l, F j', 'd/m', 'm/d' ) as $format ) {
			$formats[ $format ] = wp_date( $format, $sample );
		}

		$form->open();

		Wbcom_Settings_Page::card_open( __( 'Delivery estimate', 'woo-pincode-checker' ), __( 'How the arrival date is worked out. Each area can set its own delivery days; these apply when it does not.', 'woo-pincode-checker' ) );
		$form->toggle( 'show_estimate', __( 'Show delivery dates', 'woo-pincode-checker' ) );
		$form->input(
			'default_days_min',
			__( 'Default delivery days (min)', 'woo-pincode-checker' ),
			'number',
			'',
			array(
				'min' => 0,
				'max' => 90,
			)
		);
		$form->input(
			'default_days_max',
			__( 'Default delivery days (max)', 'woo-pincode-checker' ),
			'number',
			'',
			array(
				'min' => 0,
				'max' => 90,
			)
		);
		$form->input(
			'processing_days',
			__( 'Processing days', 'woo-pincode-checker' ),
			'number',
			__( 'Working days you need before an order ships.', 'woo-pincode-checker' ),
			array(
				'min' => 0,
				'max' => 30,
			)
		);
		/* translators: %s: site timezone. */
		$form->input( 'cutoff_time', __( 'Same-day cut-off', 'woo-pincode-checker' ), 'time', sprintf( __( 'Orders after this time (%s) start processing the next working day.', 'woo-pincode-checker' ), wp_timezone_string() ) );
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'Working days and holidays', 'woo-pincode-checker' ) );
		$form->choices(
			'working_days',
			__( 'Working days', 'woo-pincode-checker' ),
			array(
				'1' => __( 'Monday', 'woo-pincode-checker' ),
				'2' => __( 'Tuesday', 'woo-pincode-checker' ),
				'3' => __( 'Wednesday', 'woo-pincode-checker' ),
				'4' => __( 'Thursday', 'woo-pincode-checker' ),
				'5' => __( 'Friday', 'woo-pincode-checker' ),
				'6' => __( 'Saturday', 'woo-pincode-checker' ),
				'7' => __( 'Sunday', 'woo-pincode-checker' ),
			)
		);
		$form->select(
			'transit_counting',
			__( 'Count delivery days as', 'woo-pincode-checker' ),
			array(
				'business' => __( 'Working days only (skip days off and holidays)', 'woo-pincode-checker' ),
				'calendar' => __( 'Calendar days (couriers deliver every day)', 'woo-pincode-checker' ),
			)
		);
		$form->textarea( 'holidays', __( 'Holidays', 'woo-pincode-checker' ), __( 'One date per line as YYYY-MM-DD, for example 2026-12-25. Past dates are removed automatically.', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'How dates look', 'woo-pincode-checker' ) );
		$form->select(
			'display',
			__( 'Show the estimate as', 'woo-pincode-checker' ),
			array(
				'range' => __( 'A date range - Arrives Oct 8 to Oct 10', 'woo-pincode-checker' ),
				'date'  => __( 'The latest date - Arrives by Oct 10', 'woo-pincode-checker' ),
				'days'  => __( 'Days - Arrives in 3-5 days', 'woo-pincode-checker' ),
			)
		);
		$form->select( 'date_format', __( 'Date format', 'woo-pincode-checker' ), $formats );
		$form->choices(
			'show_on',
			__( 'Show the estimate on', 'woo-pincode-checker' ),
			array(
				'product' => __( 'Product page', 'woo-pincode-checker' ),
				'cart'    => __( 'Cart and checkout (next to each shipping option)', 'woo-pincode-checker' ),
				'order'   => __( 'Order details', 'woo-pincode-checker' ),
				'emails'  => __( 'Order emails', 'woo-pincode-checker' ),
			)
		);
		Wbcom_Settings_Page::card_close();

		$form->close();
	}
}
