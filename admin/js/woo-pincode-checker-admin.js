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
    $("#wpc_date_display").on("click", function () {
      if ($(this).prop("checked") == true) {
        $("#wbwss-wpc-deliver-date").show(500);
        $("#wbwss-wpc-deliver-date-text").show(500); 
      } else {
        $("#wbwss-wpc-deliver-date").hide(500);
        $("#wbwss-wpc-deliver-date-text").hide(500);
      }      
    });

    /*faq tab accordion*/
    var wpc_elmt = document.getElementsByClassName("wbcom-faq-accordion");
    var k;
    var wpc_elmt_len = wpc_elmt.length;
    for (k = 0; k < wpc_elmt_len; k++) {
      wpc_elmt[k].onclick = function () {
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.maxHeight) {
          panel.style.maxHeight = null;
        } else {
          panel.style.maxHeight = panel.scrollHeight + "px";
        }
      };
    }

    $("#wpc_delivery_date").select2({
      placeholder: "Select Delivery Date",
      plugins: ["remove_button"],
    });
    $("#wpc-exclude-category").select2({
      placeholder: "Exclude Category",
      plugins: ["remove_button"],
    });
    $("#wpc_pincode_position").select2({
      placeholder: "Select Pincode Position",
      plugins: ["remove_button"],
    });
    $("#wpc_add_to_cart_option").select2({
      placeholder: "",
    });

    // console.log( $(".wpc-bulk-delete"));
    $(".wpc-bulk-delete").on("click", function (event) {
      event.preventDefault();
      let user_cnfrm = confirm("Are you sure you want to delete all pincodes?");
      if(!user_cnfrm ){
        return;
      }
      var data = {
        action: "wpc_bulk_delete_action",
        nonce: wpc_admin_ajax.nonce,
      };
      $.post(ajaxurl, data, function (response) {
        location.reload();
      });
    });

    $("#wpc_pincode_position")
      .on("change", function () {
        $(this)
          .find("option:selected")
          .each(function () {
            var optionValue = $(this).attr("value");
            if (optionValue == "wpc_pincode_checker") {
              $(".wpc-display-shortcode-note").show();
            } else {
              $(".wpc-display-shortcode-note").hide();
            }
          });
      })
      .trigger("change"); // replaces `.change()` call to trigger manually
  });
})(jQuery);
