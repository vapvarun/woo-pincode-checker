<?php
/**
 * Server render for wbpc/pincode-checker: same renderer as the shortcode and product hook.
 *
 * @package Wbcom\PincodeChecker
 *
 * @var WP_Block $block Block instance.
 */

defined( 'ABSPATH' ) || exit;

$wbpc_product = ( 'product' === ( $block->context['postType'] ?? '' ) ) ? (int) ( $block->context['postId'] ?? 0 ) : 0;

echo \Wbcom\PincodeChecker\Core\Plugin::renderer()->render( $wbpc_product, get_block_wrapper_attributes() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template.
