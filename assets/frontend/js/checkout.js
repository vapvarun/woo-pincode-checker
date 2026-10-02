/**
 * Classic checkout only: WooCommerce does not recalculate totals when the payment method changes,
 * so a COD fee would be charged without the shopper seeing it first. The Checkout block already does this.
 *
 * Refresh only on a real change: after every refresh WooCommerce re-clicks the selected method,
 * and refreshing on that echo would loop forever.
 */
( function ( $ ) {
	'use strict';

	var last = $( 'input[name="payment_method"]:checked' ).val();

	$( document.body ).on( 'change', 'input[name="payment_method"]', function () {
		var current = $( 'input[name="payment_method"]:checked' ).val();

		if ( current !== last ) {
			last = current;
			$( document.body ).trigger( 'update_checkout' );
		}
	} );
}( window.jQuery ) );
