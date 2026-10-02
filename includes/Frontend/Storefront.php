<?php
/**
 * Storefront wiring: product placement, shortcode, block, assets.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Frontend;

use Wbcom\PincodeChecker\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Assets load only where a checker can appear.
 */
final class Storefront {

	private const PLACEMENTS = array(
		'before_add_to_cart' => 'woocommerce_before_add_to_cart_button',
		'after_add_to_cart'  => 'woocommerce_after_add_to_cart_button',
		'after_quantity'     => 'woocommerce_after_add_to_cart_quantity',
		'product_summary'    => 'woocommerce_before_add_to_cart_form', // Fires in classic and block product templates alike.
	);

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( 'wbpc_pincode_checker', array( $this, 'shortcode' ) );
		add_action( 'wp', array( $this, 'place' ) ); // Settings carry translated defaults: read them after init.
	}

	/**
	 * Attach the product checker to the hook chosen in settings.
	 */
	public function place(): void {
		$hook = self::PLACEMENTS[ Plugin::settings()->get( 'general', 'placement' ) ] ?? null;

		if ( $hook ) {
			add_action( $hook, array( $this, 'product_checker' ) );
		}
	}

	/**
	 * Product page placement.
	 */
	public function product_checker(): void {
		global $product;

		if ( $product instanceof \WC_Product ) {
			echo Plugin::renderer()->render( $product->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template.
		}
	}

	/**
	 * [wbpc_pincode_checker product_id="123"]. On a product page it binds to that product.
	 *
	 * @param array|string $atts Attributes.
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'product_id' => 0 ), (array) $atts, 'wbpc_pincode_checker' );

		return Plugin::renderer()->render( (int) $atts['product_id'] ? (int) $atts['product_id'] : ( is_product() ? (int) get_the_ID() : 0 ) );
	}

	/**
	 * Server-rendered block sharing the same renderer.
	 */
	public function register_block(): void {
		register_block_type( WBPC_DIR . 'src/blocks/pincode-checker' );
	}

	/**
	 * Register assets; enqueue up front where a checker is known to appear so CSS lands in <head>.
	 */
	public function register_assets(): void {
		wp_register_style( 'wbpc-checker', WBPC_URL . 'assets/frontend/css/checker.css', array(), WBPC_VERSION );
		wp_register_script(
			'wbpc-checker',
			WBPC_URL . 'assets/frontend/js/checker.js',
			array(),
			WBPC_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		$general = (array) Plugin::settings()->get( 'general' );
		$display = (array) Plugin::settings()->get( 'display' );

		wp_localize_script(
			'wbpc-checker',
			'wbpcChecker',
			array(
				'endpoint'     => esc_url_raw( rest_url( 'wbpc/v1/check' ) ),
				'country'      => Plugin::checker()->default_country(),
				'cookieDays'   => (int) $general['remember_days'],
				'requireCheck' => (bool) $general['require_check'],
				'action'       => (string) $general['unavailable_action'],
				'locale'       => determine_locale(), // The REST answer is written in the page's language.
				'i18n'         => array(
					'checking' => __( 'Checking...', 'woo-pincode-checker' ),
					'required' => $display['msg_required'],
					'error'    => __( 'Could not check right now. Please try again.', 'woo-pincode-checker' ),
					'retry'    => __( 'Try again', 'woo-pincode-checker' ),
					'nearby'   => __( 'We deliver nearby:', 'woo-pincode-checker' ),
				),
			)
		);

		if ( 'custom' === $display['style'] ) {
			wp_add_inline_style( 'wbpc-checker', '.wbpc-checker{--wbpc-accent:' . sanitize_hex_color( $display['accent_color'] ) . '}' );
		}

		$post = get_post();

		/**
		 * Load the checker assets on this request.
		 *
		 * @param bool $load Default: product pages and posts containing the block or shortcode.
		 */
		if ( apply_filters( 'wbpc_load_public_assets', is_product() || ( $post && ( has_shortcode( $post->post_content, 'wbpc_pincode_checker' ) || has_block( 'wbpc/pincode-checker', $post ) ) ) ) ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueue (safe to call late: the renderer calls it for widgets and template parts).
	 */
	public static function enqueue(): void {
		wp_enqueue_style( 'wbpc-checker' );
		wp_enqueue_script( 'wbpc-checker' );
	}
}
