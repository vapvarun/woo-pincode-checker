<?php
/**
 * Pincode checker.
 *
 * Override by copying to yourtheme/woocommerce/wbpc/checker.php. Keep the data-wbpc-* attributes:
 * the script uses them. Never print shopper-specific data here - the page may be cached.
 *
 * @package Wbcom\PincodeChecker
 * @version 1.6.0
 *
 * @var int    $product_id Product, 0 for a general check.
 * @var array  $labels     Display settings.
 * @var string $wrapper    Extra wrapper attributes (escaped).
 * @var bool   $numeric    Numeric postcodes in the default country.
 */

defined( 'ABSPATH' ) || exit;

$wbpc_id = wp_unique_id( 'wbpc-checker-' );
?>
<div class="wbpc-checker" data-wbpc-checker data-product="<?php echo esc_attr( (string) $product_id ); ?>" <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() output. ?>>
	<p class="wbpc-checker__title" id="<?php echo esc_attr( $wbpc_id ); ?>-title"><?php echo esc_html( $labels['title'] ); ?></p>

	<?php /* A group, not a <form>: this usually sits inside WooCommerce's add-to-cart form, and forms cannot nest. */ ?>
	<div class="wbpc-checker__entry" role="group" aria-labelledby="<?php echo esc_attr( $wbpc_id ); ?>-title" data-wbpc-entry>
		<label class="screen-reader-text" for="<?php echo esc_attr( $wbpc_id ); ?>-input"><?php echo esc_html( $labels['placeholder'] ); ?></label>
		<input type="text" id="<?php echo esc_attr( $wbpc_id ); ?>-input" class="wbpc-checker__input" data-wbpc-input
			placeholder="<?php echo esc_attr( $labels['placeholder'] ); ?>" autocomplete="postal-code" maxlength="20"
			inputmode="<?php echo $numeric ? 'numeric' : 'text'; ?>" aria-describedby="<?php echo esc_attr( $wbpc_id ); ?>-result">
		<button type="button" class="wbpc-checker__button" data-wbpc-check><?php echo esc_html( $labels['check_label'] ); ?></button>
	</div>

	<div class="wbpc-checker__result" id="<?php echo esc_attr( $wbpc_id ); ?>-result" data-wbpc-result role="status" aria-live="polite"></div>

	<button type="button" class="wbpc-checker__change" data-wbpc-change hidden><?php echo esc_html( $labels['change_label'] ); ?></button>

	<?php
	/**
	 * After the checker markup (Pro adds the notify-me form here).
	 *
	 * @param int $product_id Product.
	 */
	do_action( 'wbpc_checker_form_after', $product_id );
	?>
</div>
