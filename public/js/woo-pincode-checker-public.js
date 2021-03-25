(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	$( document ).ready(function() {
		
		/* default pincode form button */
		$("#checkpin").click(function() {
			var pin_code = $('#pincode_field_id').val();
			console.log( pin_code );
			if(pin_code != '') {
				$.ajax({
						url : pincode_check.ajaxurl,
						type : 'post',
						data : {
							action   : 'picodecheck_ajax_submit',
							pin_code : pin_code
						},
						success : function( response ) {
						if(response == 1) {
								location.reload();
						  } else {
							$('#error_pin').show();
							$('.delivery_msg').hide();
							$('.add_to_cart_button, .single_add_to_cart_button').remove();
						}
						}
				}); 

			}
		});
		/* already pincode checking form */ 
		$("#change_pin").click(function(){
		
				$('#my_custom_checkout_field2').show();
				$('#avlpin').hide();
				$('.delivery-info-wrap').hide();
			});
	});
})( jQuery );