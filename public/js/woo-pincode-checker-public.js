(function($) {
    "use strict";

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
    $(document).ready(function() {
        /* default pincode form button */
        $(document).on("click", "#checkpin", function() {
            $(".pincode_loader").css('display', 'block');
            var pin_code = $("#pincode_field_id").val();
            if (pin_code != "") {
                $.ajax({
                    url: pincode_check.ajaxurl,
                    type: "post",
                    data: {
                        action: "wpc_picode_check_ajax_submit",
                        pin_code: pin_code,
                        nonce: pincode_check.wpc_nonce,
                    },
                    success: function(response) {
                        if (true == response.success) {
                            $(".pincode_loader").css('display', 'none');
                            $(".wc-delivery-time-response").show();
                            $(".wc-delivery-time-response").html(response.data.html);
                            $("#my_custom_checkout_field2").css('display', 'none');
                            jQuery(".single_add_to_cart_button").show();
                        } else {
                            $(".pincode_loader").css('display', 'none');
                            $("#error_pin").show();
                            $(".delivery_msg").hide();
                            if ( "add_to_cart_disable" == pincode_check.hide_disable_product_page_cart_btn ) {
                                jQuery(".single_add_to_cart_button").prop("disabled", true);
                            }else if( "add_to_cart_hide" == pincode_check.hide_disable_product_page_cart_btn ){
                                  jQuery(".single_add_to_cart_button").hide();
                            }
                        }
                    },
                });
            }
        });
        /* already pincode checking form */
        $(document).on("click", "#change_pin", function(e) {
            e.preventDefault();
            $("#my_custom_checkout_field2").css('display', 'block');
            $("#avlpin").hide();
            $(".single_add_to_cart_button").hide();
            $(".wpc_delivery-info-wrap").hide();
            $("#error_pin").hide();
        });
        jQuery("body").on("blur", "#billing_postcode", function() {
            if (jQuery("#ship-to-different-address-checkbox").prop("checked")) {
                var pincode = jQuery("#shipping_postcode").val();
            } else {
                var pincode = jQuery(this).val();
            }
            if (pincode !== "") {
                jQuery.ajax({
                    type: "POST",
                    url: pincode_check.ajaxurl,
                    data: {
                        action: "wpc_check_checkout_page_pincode",
                        pincode: pincode,
                        nonce: pincode_check.wpc_nonce,
                    },
                    success: function(response) {
                        jQuery("body").trigger("update_checkout");
                    },
                });
            }
        });
        jQuery("body").on("blur", "#shipping_postcode", function() {
            var pincode = jQuery(this).val();

            if (pincode !== "") {
                jQuery.ajax({
                    type: "POST",
                    url: pincode_check.ajaxurl,
                    data: {
                        action: "wpc_check_checkout_page_pincode",
                        pincode: pincode,
                        nonce: pincode_check.wpc_nonce,
                    },
                    success: function(response) {
                        jQuery("body").trigger("update_checkout");
                    },
                });
            }
        });
        jQuery("body").on(
            "click",
            "#ship-to-different-address-checkbox",
            function() {
                if (jQuery(this).prop("checked")) {
                    var pincode = jQuery("#shipping_postcode").val();
                } else {
                    var pincode = jQuery("#billing_postcode").val();
                }

                if (pincode != "") {
                    jQuery.ajax({
                        type: "POST",
                        url: wpcc_ajax_postajax.ajaxurl,
                        dataType: "json",
                        data: {
                            action: "wpc_check_checkout_page_pincode",
                            pincode: pincode,
                            nonce: pincode_check.wpc_nonce,
                        },
                        success: function(response) {
                            jQuery("body").trigger("update_checkout");
                        },
                    });
                }
            }
        );
        // Delivery message on grouped product
        var wpc_del_msg_div = $(".wc-delivery-time-response");
        if (wpc_del_msg_div.length > 1) {
          var wpc_lastDiv = wpc_del_msg_div.last();
          wpc_del_msg_div.not(wpc_lastDiv).hide();
        }        
    });
})(jQuery);