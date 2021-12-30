(function ($) {
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

	$(document).ready(function () {

		$('#wpc_date_display').on('click', function () {

			if ($(this).prop("checked") == true) {
				$('#wbwss-wpc-deliver-date').show(500);
			} else {
				$('#wbwss-wpc-deliver-date').hide(500);
			}
		});

		/*faq tab accordion*/
		var wpc_elmt = document.getElementsByClassName("wpc-accordion");
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
			}
		}


		$('#products-to-quote').selectize({
			placeholder: "Select Category",
			plugins: ['remove_button'],
		});

	});

})(jQuery);