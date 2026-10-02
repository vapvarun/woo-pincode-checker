<?php
/**
 * Delivery Dates tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Admin\Form;
use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom\PincodeChecker\Services\DeliveryDateService;
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
		global $wp_locale;

		$form    = new Form( 'delivery' );
		$sample  = strtotime( '+3 days' );
		$formats = array(
			/* translators: %s: a sample date in the short format for the site language. */
			'locale' => sprintf( __( 'Short date for your language (%s)', 'woo-pincode-checker' ), wp_date( DeliveryDateService::date_format( 'locale' ), $sample ) ),
			/* translators: %s: a sample date in the site's date format (Settings > General). */
			'site'   => sprintf( __( 'Site date format (%s)', 'woo-pincode-checker' ), wp_date( DeliveryDateService::date_format( 'site' ), $sample ) ),
		);
		foreach ( array( 'D, M j', 'M j', 'j M', 'l, F j', 'd/m', 'm/d' ) as $format ) {
			$formats[ $format ] = wp_date( $format, $sample );
		}

		// Examples for "Show the estimate as", built by the same code that writes the real text.
		$rules    = (array) Plugin::settings()->get( 'delivery' );
		$example  = array(
			'min' => wp_date( 'Y-m-d', strtotime( '+3 days' ) ),
			'max' => wp_date( 'Y-m-d', strtotime( '+5 days' ) ),
		);
		$examples = array();
		foreach ( array( 'range', 'date', 'days' ) as $display ) {
			$examples[ $display ] = Plugin::dates()->label( $example, array( 'display' => $display ) + $rules );
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
				// ISO day numbers (1 = Monday); names come from WordPress core's translations.
				'1' => $wp_locale->get_weekday( 1 ),
				'2' => $wp_locale->get_weekday( 2 ),
				'3' => $wp_locale->get_weekday( 3 ),
				'4' => $wp_locale->get_weekday( 4 ),
				'5' => $wp_locale->get_weekday( 5 ),
				'6' => $wp_locale->get_weekday( 6 ),
				'7' => $wp_locale->get_weekday( 0 ),
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
				/* translators: %s: example estimate, e.g. "Oct 8 to Oct 10". */
				'range' => sprintf( __( 'A date range (%s)', 'woo-pincode-checker' ), $examples['range'] ),
				/* translators: %s: example estimate, e.g. "by Oct 10". */
				'date'  => sprintf( __( 'The latest date (%s)', 'woo-pincode-checker' ), $examples['date'] ),
				/* translators: %s: example estimate, e.g. "in 3-5 days". */
				'days'  => sprintf( __( 'Days (%s)', 'woo-pincode-checker' ), $examples['days'] ),
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
