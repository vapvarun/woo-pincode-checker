(function ($) {
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
  $(document).ready(function () {
    /* default pincode form button */
    $("#checkpin").click(function () {
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
          success: function (response) {
            if (response == 1) {
              location.reload();
            } else {
              $("#error_pin").show();
              $(".delivery_msg").hide();
              if (pincode_check.hide_product_page_cart_btn) {
                jQuery(".single_add_to_cart_button").prop("disabled", true);
              }
            }
          },
        });
      }
    });
    /* already pincode checking form */
    $("#change_pin").click(function () {
      $("#my_custom_checkout_field2").show();
      $("#avlpin").hide();
      $(".single_add_to_cart_button").hide();
      $(".wpc_delivery-info-wrap").hide();
    });
    jQuery("body").on("blur", "#billing_postcode", function () {
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
          success: function (response) {
            jQuery("body").trigger("update_checkout");
          },
        });
      }
    });
    jQuery("body").on("blur", "#shipping_postcode", function () {
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
          success: function (response) {
            jQuery("body").trigger("update_checkout");
          },
        });
      }
    });
    jQuery("body").on(
      "click",
      "#ship-to-different-address-checkbox",
      function () {
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
            success: function (response) {
              jQuery("body").trigger("update_checkout");
            },
          });
        }
      }
    );
  });
})(jQuery);
