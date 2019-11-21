jQuery( document ).ready(function() {
    jQuery("#checkpin").click(function() {
        var pin_code = jQuery('#pincode_field_id').val();
		console.log( pin_code );
		if(pin_code != '') {
			jQuery.ajax({
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
						jQuery('#error_pin').show();
						jQuery('.delivery_msg').hide();
					  }
					}
			}); 

		}
	});
	
	jQuery("#change_pin").click(function(){
	
			jQuery('#my_custom_checkout_field2').show();

			jQuery('#avlpin').hide();
			jQuery('.delivery-info-wrap').hide();


		});
});


