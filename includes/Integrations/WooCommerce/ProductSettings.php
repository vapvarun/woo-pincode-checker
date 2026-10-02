<?php
/**
 * Product edit screen: "Hide pincode checker" in the Shipping tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Stored as product meta _wbpc_hide_checker (yes/no); read by ProductService::is_excluded().
 */
final class ProductSettings {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'woocommerce_product_options_shipping', array( $this, 'field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save' ) );
	}

	/**
	 * The checkbox.
	 */
	public function field(): void {
		woocommerce_wp_checkbox(
			array(
				'id'          => '_wbpc_hide_checker',
				'label'       => __( 'Pincode checker', 'woo-pincode-checker' ),
				'description' => __( 'Hide the pincode checker and skip delivery-area checks for this product', 'woo-pincode-checker' ),
			)
		);
	}

	/**
	 * Save (nonce and capability are checked by WooCommerce before this hook).
	 *
	 * @param \WC_Product $product Product being saved.
	 */
	public function save( \WC_Product $product ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the product form nonce.
		$hide = isset( $_POST['_wbpc_hide_checker'] ) ? 'yes' : 'no';

		if ( 'yes' === $hide ) {
			$product->update_meta_data( '_wbpc_hide_checker', 'yes' );
		} else {
			$product->delete_meta_data( '_wbpc_hide_checker' ); // No row for the default.
		}
	}
}
