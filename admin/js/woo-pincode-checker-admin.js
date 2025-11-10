(function ($) {
  "use strict";

  /**
   * WooCommerce Pincode Checker Admin JavaScript
   * Enhanced with AJAX search, modern UI interactions, and improved UX
   */

  $(document).ready(function () {
    // Date display toggle
    $("#wpc_date_display").on("click", function () {
      if ($(this).prop("checked") == true) {
        $("#wbwss-wpc-deliver-date").slideDown(300);
        $("#wbwss-wpc-deliver-date-text").slideDown(300); 
      } else {
        $("#wbwss-wpc-deliver-date").slideUp(300);
        $("#wbwss-wpc-deliver-date-text").slideUp(300);
      }      
    });

    // FAQ tab accordion - Enhanced
    var wpc_elmt = document.getElementsByClassName("wbcom-faq-accordion");
    var k;
    var wpc_elmt_len = wpc_elmt.length;
    for (k = 0; k < wpc_elmt_len; k++) {
      wpc_elmt[k].onclick = function () {
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.maxHeight) {
          panel.style.maxHeight = null;
          panel.classList.remove("show");
        } else {
          panel.style.maxHeight = panel.scrollHeight + "px";
          panel.classList.add("show");
        }
      };
    }

    // Select2 initialization
    $("#wpc_delivery_date").select2({
      placeholder: "Select Delivery Date",
      allowClear: true,
      width: '100%'
    });
    
    $("#wpc-exclude-category").select2({
      placeholder: "Exclude Category",
      allowClear: true,
      width: '100%',
      multiple: true
    });
    
    $("#wpc_pincode_position").select2({
      placeholder: "Select Pincode Position",
      allowClear: true,
      width: '100%'
    });
    
    $("#wpc_add_to_cart_option").select2({
      placeholder: "Select Option",
      width: '100%'
    });

    // Bulk delete with improved confirmation
    $(".wpc-bulk-delete").on("click", function (event) {
      event.preventDefault();
      
      // Enhanced confirmation dialog
      var confirmDialog = $('<div class="wpc-confirm-dialog">' +
        '<p><strong>Warning!</strong> This will permanently delete all pincodes.</p>' +
        '<p>Are you sure you want to continue?</p>' +
        '</div>');
      
      $('body').append('<div class="wpc-overlay"></div>');
      $('body').append(confirmDialog);
      
      confirmDialog.append(
        '<div class="wpc-dialog-buttons">' +
        '<button class="button button-primary wpc-confirm-yes">Yes, Delete All</button>' +
        '<button class="button wpc-confirm-no">Cancel</button>' +
        '</div>'
      );
      
      $('.wpc-confirm-yes').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Deleting...');
        
        var data = {
          action: "wpc_bulk_delete_action",
          nonce: wpc_admin_ajax.nonce,
        };
        
        $.post(ajaxurl, data, function (response) {
          location.reload();
        }).fail(function() {
          alert('Error occurred while deleting. Please try again.');
          $button.prop('disabled', false).text('Yes, Delete All');
        });
      });
      
      $('.wpc-confirm-no, .wpc-overlay').on('click', function() {
        $('.wpc-confirm-dialog, .wpc-overlay').remove();
      });
    });

    // Position change handler
    $("#wpc_pincode_position")
      .on("change", function () {
        var optionValue = $(this).val();
        if (optionValue == "wpc_pincode_checker") {
          $(".wpc-display-shortcode-note").fadeIn(300);
        } else {
          $(".wpc-display-shortcode-note").fadeOut(300);
        }
      })
      .trigger("change");

    // AJAX Pincode Search Implementation
    if ($('.wp-list-table.pincodes').length > 0) {
      initializeAjaxSearch();
    }

    /**
     * Initialize AJAX search for pincode listing
     */
    function initializeAjaxSearch() {
      var searchTimer = null;
      var $searchBox = $('#search-pincode-input');
      var $searchSubmit = $('#search-submit');
      var $tableBody = $('.wp-list-table.pincodes tbody');
      var $pagination = $('.tablenav .tablenav-pages');
      
      // Add search spinner
      $searchBox.after('<span class="wpc-search-spinner" style="display:none;"></span>');
      
      // Real-time search as user types
      $searchBox.on('keyup', function(e) {
        clearTimeout(searchTimer);
        var searchTerm = $(this).val();
        
        // Don't search on navigation keys
        if (e.which >= 37 && e.which <= 40) {
          return;
        }
        
        // Start searching after 300ms delay
        searchTimer = setTimeout(function() {
          performAjaxSearch(searchTerm);
        }, 300);
        
        // Handle Enter key
        if (e.which === 13) {
          e.preventDefault();
          clearTimeout(searchTimer);
          performAjaxSearch(searchTerm);
        }
      });
      
      // Handle search button click
      $searchSubmit.on('click', function(e) {
        e.preventDefault();
        clearTimeout(searchTimer);
        performAjaxSearch($searchBox.val());
      });
      
      // Clear search
      $searchBox.parent().append('<button type="button" class="button wpc-clear-search" style="display:none;">Clear</button>');
      
      $('.wpc-clear-search').on('click', function() {
        $searchBox.val('');
        performAjaxSearch('');
        $(this).hide();
      });
      
      // Show/hide clear button based on input
      $searchBox.on('input', function() {
        if ($(this).val().length > 0) {
          $('.wpc-clear-search').show();
        } else {
          $('.wpc-clear-search').hide();
        }
      });
    }
    
    /**
     * Perform AJAX search
     */
    function performAjaxSearch(searchTerm) {
      var $spinner = $('.wpc-search-spinner');
      var $tableBody = $('.wp-list-table.pincodes tbody');
      var $pagination = $('.tablenav .tablenav-pages');
      
      // Show spinner
      $spinner.show();
      
      // Prepare data
      var data = {
        action: 'wpc_ajax_search_pincodes',
        search: searchTerm,
        nonce: wpc_admin_ajax.nonce
      };
      
      // Perform AJAX request
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        success: function(response) {
          if (response.success) {
            // Update table body
            $tableBody.html(response.data.html);
            
            // Update pagination
            if (response.data.pagination) {
              $pagination.html(response.data.pagination);
            } else {
              $pagination.empty();
            }
            
            // Add animation
            $tableBody.find('tr').each(function(index) {
              $(this).css('animation-delay', (index * 0.05) + 's');
              $(this).addClass('fade-in');
            });
            
            // Update results count
            if (response.data.count !== undefined) {
              updateResultsCount(response.data.count, searchTerm);
            }
          } else {
            $tableBody.html('<tr><td colspan="9" class="no-items">' + 
              (response.data.message || 'No pincodes found.') + '</td></tr>');
            $pagination.empty();
          }
        },
        error: function() {
          $tableBody.html('<tr><td colspan="9" class="no-items error">' + 
            'Error occurred while searching. Please try again.</td></tr>');
        },
        complete: function() {
          $spinner.hide();
        }
      });
    }
    
    /**
     * Update results count display
     */
    function updateResultsCount(count, searchTerm) {
      var $countDisplay = $('.displaying-num');
      
      if ($countDisplay.length === 0) {
        $('.tablenav.top .tablenav-pages').prepend('<span class="displaying-num"></span>');
        $countDisplay = $('.displaying-num');
      }
      
      var text = count + ' item' + (count !== 1 ? 's' : '');
      if (searchTerm) {
        text += ' found for "' + searchTerm + '"';
      }
      
      $countDisplay.text(text);
    }
    
    // Enhanced sorting for table headers
    $('.wp-list-table th.sortable a, .wp-list-table th.sorted a').on('click', function(e) {
      e.preventDefault();
      
      var $link = $(this);
      var orderby = $link.data('orderby') || $link.attr('href').match(/orderby=([^&]+)/)[1];
      var order = $link.hasClass('asc') ? 'desc' : 'asc';
      
      // Update URL without reloading
      var newUrl = updateQueryStringParameter(window.location.href, 'orderby', orderby);
      newUrl = updateQueryStringParameter(newUrl, 'order', order);
      window.history.pushState({path: newUrl}, '', newUrl);
      
      // Perform AJAX sort
      performAjaxSort(orderby, order);
    });
    
    /**
     * Perform AJAX sorting
     */
    function performAjaxSort(orderby, order) {
      var $tableBody = $('.wp-list-table.pincodes tbody');
      var searchTerm = $('#search-pincode-input').val();
      
      $tableBody.css('opacity', '0.5');
      
      var data = {
        action: 'wpc_ajax_sort_pincodes',
        orderby: orderby,
        order: order,
        search: searchTerm,
        nonce: wpc_admin_ajax.nonce
      };
      
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        success: function(response) {
          if (response.success) {
            $tableBody.html(response.data.html);
            
            // Update sort indicators
            $('.wp-list-table th').removeClass('sorted asc desc').addClass('sortable');
            var $sortedHeader = $('.wp-list-table th').filter(function() {
              return $(this).find('a').data('orderby') === orderby;
            });
            $sortedHeader.removeClass('sortable').addClass('sorted ' + order.toLowerCase());
          }
        },
        complete: function() {
          $tableBody.css('opacity', '1');
        }
      });
    }
    
    /**
     * Helper function to update URL parameters
     */
    function updateQueryStringParameter(uri, key, value) {
      var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
      var separator = uri.indexOf('?') !== -1 ? "&" : "?";
      if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
      } else {
        return uri + separator + key + "=" + value;
      }
    }
    
    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
      // Ctrl/Cmd + K to focus search
      if ((e.ctrlKey || e.metaKey) && e.which === 75) {
        e.preventDefault();
        $('#search-pincode-input').focus();
      }
      
      // ESC to clear search
      if (e.which === 27 && $('#search-pincode-input').is(':focus')) {
        $('#search-pincode-input').val('');
        $('.wpc-clear-search').click();
      }
    });
    
  });
  
  // Add CSS for new elements
  var style = '<style>' +
    '.wpc-search-spinner { display: inline-block; width: 16px; height: 16px; ' +
    'border: 2px solid #f3f3f3; border-top: 2px solid #2271b1; border-radius: 50%; ' +
    'animation: spin 1s linear infinite; margin-left: 10px; vertical-align: middle; }' +
    '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }' +
    '.wpc-clear-search { margin-left: 5px; }' +
    '.fade-in { animation: fadeIn 0.3s ease-out forwards; opacity: 0; }' +
    '@keyframes fadeIn { to { opacity: 1; } }' +
    '.wpc-confirm-dialog { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); ' +
    'background: white; padding: 30px; border-radius: 8px; z-index: 100001; ' +
    'box-shadow: 0 5px 20px rgba(0,0,0,0.3); min-width: 400px; text-align: center; }' +
    '.wpc-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; ' +
    'background: rgba(0,0,0,0.5); z-index: 100000; }' +
    '.wpc-dialog-buttons { margin-top: 20px; }' +
    '.wpc-dialog-buttons button { margin: 0 5px; }' +
    '.wp-list-table tbody tr { transition: background-color 0.2s; }' +
    '.wp-list-table tbody tr:hover { background-color: #f6f7f7; }' +
    '.wpc-status-available { color: #46b450; font-weight: 600; }' +
    '.wpc-status-unavailable { color: #dc3232; }' +
    '.wpc-free { color: #2271b1; font-weight: 600; }' +
    '</style>';
  
  $('head').append(style);
  
})(jQuery);