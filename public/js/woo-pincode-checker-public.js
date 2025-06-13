(function($) {
    "use strict";

    // Initialize i18n if available
    const { __ } = wp.i18n || { __: (text) => text };

    class WooPincodeChecker {
        constructor() {
            this.cache = new Map();
            this.debounceTimer = null;
            this.retryCount = 0;
            this.maxRetries = 3;
            this.isChecking = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupAutocomplete();
            this.restoreLastPincode();
            this.preloadCurrentLocationPincode();
        }

        bindEvents() {
            // Main check button
            $(document).on('click', '#wpc-check-btn, #checkpin', (e) => {
                e.preventDefault();
                this.handlePincodeCheck();
            });

            // Change pincode button
            $(document).on('click', '#change_pin', (e) => {
                e.preventDefault();
                this.showPincodeForm();
            });

            // Real-time validation
            $(document).on('input', '#wpc-pincode-input, #pincode_field_id', (e) => {
                this.handleInputChange(e.target);
            });

            // Enter key support
            $(document).on('keypress', '#wpc-pincode-input, #pincode_field_id', (e) => {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    this.handlePincodeCheck();
                }
            });

            // Checkout page events
            this.bindCheckoutEvents();

            // Auto-detect events
            $(document).on('click', '.wpc-use-detected', (e) => {
                const pincode = $(e.target).data('pincode');
                this.setPincodeValue(pincode);
                this.handlePincodeCheck();
                $('.wpc-auto-detect-banner').fadeOut();
            });

            $(document).on('click', '.wpc-dismiss-banner', () => {
                $('.wpc-auto-detect-banner').fadeOut();
            });

            // Error retry
            $(document).on('click', '.wpc-error-retry', () => {
                this.handlePincodeCheck();
            });

            // Add to cart behavior modification
            this.handleAddToCartBehavior();
        }

        bindCheckoutEvents() {
            $("body").on("blur", "#billing_postcode", () => {
                const pincode = $("#ship-to-different-address-checkbox").prop("checked") 
                    ? $("#shipping_postcode").val() 
                    : $("#billing_postcode").val();
                    
                if (pincode) {
                    this.checkCheckoutPincode(pincode);
                }
            });

            $("body").on("blur", "#shipping_postcode, #shipping-postcode", function() {
                const pincode = $(this).val();
                if (pincode) {
                    this.checkCheckoutPincode(pincode);
                }
            }.bind(this));

            $("body").on("click", "#ship-to-different-address-checkbox", () => {
                const pincode = $("#ship-to-different-address-checkbox").prop("checked")
                    ? $("#shipping_postcode").val()
                    : $("#billing_postcode").val();

                if (pincode) {
                    this.checkCheckoutPincode(pincode);
                }
            });
        }

        handleAddToCartBehavior() {
            const requiredField = pincode_check.required_pincode_field_btn === 'on';
            
            if (requiredField) {
                $(document).on('input', '#wpc-pincode-input, #pincode_field_id', () => {
                    const value = this.getPincodeValue();
                    const addToCartBtn = $('.single_add_to_cart_button');
                    
                    if (value.length > 0) {
                        addToCartBtn.hide();
                    } else {
                        addToCartBtn.show();
                    }
                });
            }
        }

        handlePincodeCheck() {
            if (this.isChecking) return;

            const pincode = this.getPincodeValue().trim();
            
            if (!pincode) {
                this.showError(pincode_check.messages?.enter_pincode || __('Please enter a pincode.', 'woo-pincode-checker'));
                this.focusPincodeInput();
                return;
            }

            if (!this.validatePincodeFormat(pincode)) {
                this.showError(pincode_check.messages?.invalid_format || __('Please enter a valid pincode.', 'woo-pincode-checker'));
                this.addErrorClass();
                return;
            }

            this.checkPincodeAvailability(pincode);
        }

        async checkPincodeAvailability(pincode) {
            this.isChecking = true;
            this.showLoading(true);
            this.hideError();
            this.removeErrorClass();

            try {
                const response = await this.makeAjaxRequest('wpc_picode_check_ajax_submit', {
                    pin_code: pincode,
                    nonce: pincode_check.wpc_nonce
                });

                if (response.success) {
                    this.handleSuccessResponse(response.data, pincode);
                } else {
                    this.handleErrorResponse(response.data, pincode);
                }
            } catch (error) {
                console.error('Pincode check error:', error);
                this.handleNetworkError();
            } finally {
                this.isChecking = false;
                this.showLoading(false);
            }
        }

        async makeAjaxRequest(action, data, retryCount = 0) {
            const requestData = {
                action: action,
                ...data
            };

            try {
                const response = await $.ajax({
                    url: pincode_check.ajaxurl,
                    type: 'POST',
                    data: requestData,
                    timeout: 15000
                });

                this.retryCount = 0; // Reset on success
                return response;
            } catch (error) {
                if (retryCount < this.maxRetries && (error.status === 0 || error.status >= 500)) {
                    // Exponential backoff
                    const delay = Math.pow(2, retryCount) * 1000;
                    await this.sleep(delay);
                    return this.makeAjaxRequest(action, data, retryCount + 1);
                }
                throw error;
            }
        }

        handleSuccessResponse(data, pincode) {
            this.saveToLocalStorage(pincode);
            $('.wc-delivery-time-response').html(data.html).show();
            $('#my_custom_checkout_field2').hide();
            $('.single_add_to_cart_button').show();
            
            // Hide duplicate delivery messages on grouped products
            this.hideDuplicateMessages();
            
            // Trigger custom event
            $(document).trigger('wpc_pincode_checked', {
                pincode: pincode,
                success: true,
                data: data
            });
        }

        handleErrorResponse(data, pincode) {
            const message = data?.message || pincode_check.messages?.not_serviceable || 
                          __('Sorry! We are currently not servicing your area.', 'woo-pincode-checker');
            
            this.showError(message, true);
            $('.delivery_msg').hide();
            
            const hideDisableOption = pincode_check.hide_disable_product_page_cart_btn;
            if (hideDisableOption === 'add_to_cart_disable') {
                $('.single_add_to_cart_button').prop('disabled', true);
            } else if (hideDisableOption === 'add_to_cart_hide') {
                $('.single_add_to_cart_button').hide();
            }

            // Trigger custom event
            $(document).trigger('wpc_pincode_checked', {
                pincode: pincode,
                success: false,
                error: message
            });
        }

        handleNetworkError() {
            const message = pincode_check.messages?.network_error || 
                          __('Connection issue. Please check your internet and try again.', 'woo-pincode-checker');
            this.showError(message, true);
        }

        async checkCheckoutPincode(pincode) {
            if (!pincode || !this.validatePincodeFormat(pincode)) return;

            try {
                await $.ajax({
                    type: "POST",
                    url: pincode_check.ajaxurl,
                    data: {
                        action: "wpc_check_checkout_page_pincode",
                        pincode: pincode,
                        nonce: pincode_check.wpc_nonce,
                    }
                });
                
                $("body").trigger("update_checkout");
            } catch (error) {
                console.warn('Checkout pincode check failed:', error);
            }
        }

        showPincodeForm() {
            $('#my_custom_checkout_field2').show();
            $('#avlpin').hide();
            $('.wpc_delivery-info-wrap').hide();
            this.hideError();
            
            const requiredField = pincode_check.required_pincode_field_btn === 'on';
            if (requiredField) {
                $('.single_add_to_cart_button').show();
            } else {
                $('.single_add_to_cart_button').hide();
            }
            
            this.focusPincodeInput();
        }

        showLoading(show) {
            const loader = $('.wpc-loader, .pincode_loader');
            const checkBtn = $('#wpc-check-btn, #checkpin');
            
            if (show) {
                loader.show();
                checkBtn.addClass('loading').prop('disabled', true);
                checkBtn.find('.wpc-btn-text').hide();
                checkBtn.find('.wpc-btn-spinner').show();
            } else {
                loader.hide();
                checkBtn.removeClass('loading').prop('disabled', false);
                checkBtn.find('.wpc-btn-text').show();
                checkBtn.find('.wpc-btn-spinner').hide();
            }
        }

        showError(message, showRetry = false) {
            const errorContainer = $('#wpc-error, #error_pin');
            const errorMessage = errorContainer.find('.wpc-error-message');
            const retryBtn = errorContainer.find('.wpc-error-retry');
            
            if (errorMessage.length) {
                errorMessage.text(message);
            } else {
                errorContainer.html(message);
            }
            
            errorContainer.show();
            
            if (showRetry && retryBtn.length) {
                retryBtn.show();
            }

            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (!showRetry) {
                    errorContainer.fadeOut();
                }
            }, 5000);
        }

        hideError() {
            $('#wpc-error, #error_pin').hide();
        }

        addErrorClass() {
            this.getPincodeInput().addClass('error');
            setTimeout(() => {
                this.removeErrorClass();
            }, 2000);
        }

        removeErrorClass() {
            this.getPincodeInput().removeClass('error');
        }

        validatePincodeFormat(pincode) {
            return /^[A-Za-z0-9\s]{3,10}$/.test(pincode.trim());
        }

        getPincodeValue() {
            return this.getPincodeInput().val() || '';
        }

        setPincodeValue(value) {
            this.getPincodeInput().val(value);
        }

        getPincodeInput() {
            return $('#wpc-pincode-input, #pincode_field_id').first();
        }

        focusPincodeInput() {
            setTimeout(() => {
                this.getPincodeInput().focus();
            }, 100);
        }

        handleInputChange(input) {
            clearTimeout(this.debounceTimer);
            const value = $(input).val();
            
            // Real-time validation
            if (value.length > 0 && !this.validatePincodeFormat(value)) {
                $(input).addClass('error');
            } else {
                $(input).removeClass('error');
            }

            // Debounced suggestions
            this.debounceTimer = setTimeout(() => {
                if (value.length >= 2) {
                    this.showSuggestions(value);
                } else {
                    this.hideSuggestions();
                }
            }, 300);
        }

        setupAutocomplete() {
            const input = this.getPincodeInput();
            if (!input.length) return;

            // Create suggestions container if it doesn't exist
            if (!$('#wpc-suggestions').length) {
                input.after('<div id="wpc-suggestions" class="wpc-suggestions" style="display:none;"><div class="wpc-suggestions-list"></div></div>');
            }

            // Handle suggestion clicks
            $(document).on('click', '.wpc-suggestion-item', (e) => {
                const suggestion = $(e.target).data('pincode');
                this.setPincodeValue(suggestion);
                this.hideSuggestions();
                this.handlePincodeCheck();
            });

            // Hide suggestions when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.wpc-input-wrapper, .wpc-suggestions').length) {
                    this.hideSuggestions();
                }
            });
        }

        async showSuggestions(query) {
            try {
                const suggestions = await this.getSuggestions(query);
                this.displaySuggestions(suggestions);
            } catch (error) {
                console.warn('Failed to load suggestions:', error);
            }
        }

        async getSuggestions(query) {
            const cacheKey = `suggestions_${query}`;
            
            if (this.cache.has(cacheKey)) {
                return this.cache.get(cacheKey);
            }

            try {
                const response = await $.ajax({
                    url: pincode_check.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpc_get_pincode_suggestions',
                        query: query,
                        nonce: pincode_check.wpc_nonce
                    },
                    timeout: 5000
                });

                const suggestions = response.success ? response.data : [];
                this.cache.set(cacheKey, suggestions);
                return suggestions;
            } catch (error) {
                return [];
            }
        }

        displaySuggestions(suggestions) {
            const container = $('#wpc-suggestions');
            const list = container.find('.wpc-suggestions-list');

            if (!suggestions || suggestions.length === 0) {
                this.hideSuggestions();
                return;
            }

            list.empty();
            suggestions.slice(0, 5).forEach(suggestion => {
                const item = $(`
                    <div class="wpc-suggestion-item" data-pincode="${suggestion.pincode}">
                        <strong>${suggestion.pincode}</strong> - ${suggestion.city}, ${suggestion.state}
                    </div>
                `);
                list.append(item);
            });

            container.show();
        }

        hideSuggestions() {
            $('#wpc-suggestions').hide();
        }

        saveToLocalStorage(pincode) {
            try {
                localStorage.setItem('wpc_last_pincode', pincode);
                localStorage.setItem('wpc_last_check_time', Date.now().toString());
            } catch (error) {
                // LocalStorage not available
            }
        }

        restoreLastPincode() {
            try {
                const lastPincode = localStorage.getItem('wpc_last_pincode');
                const lastCheckTime = localStorage.getItem('wpc_last_check_time');
                
                // Only restore if checked within last 7 days
                if (lastPincode && lastCheckTime) {
                    const daysSinceCheck = (Date.now() - parseInt(lastCheckTime)) / (1000 * 60 * 60 * 24);
                    if (daysSinceCheck < 7 && this.getPincodeValue() === '') {
                        this.setPincodeValue(lastPincode);
                    }
                }
            } catch (error) {
                // LocalStorage not available
            }
        }

        preloadCurrentLocationPincode() {
            // Try to detect from browser if geolocation is available
            if ('geolocation' in navigator) {
                const detectBtn = $('#wpc-detect-location');
                if (detectBtn.length) {
                    detectBtn.on('click', () => {
                        this.detectLocation();
                    });
                }
            }
        }

        detectLocation() {
            const detectBtn = $('#wpc-detect-location');
            detectBtn.prop('disabled', true).text(__('Detecting location...', 'woo-pincode-checker'));

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    try {
                        const { latitude, longitude } = position.coords;
                        const pincode = await this.reverseGeocode(latitude, longitude);
                        
                        if (pincode) {
                            this.setPincodeValue(pincode);
                            detectBtn.text(__('Location detected!', 'woo-pincode-checker'));
                            // Auto-check the detected pincode
                            setTimeout(() => {
                                this.handlePincodeCheck();
                            }, 1000);
                        } else {
                            detectBtn.text(__('Unable to determine pincode', 'woo-pincode-checker'));
                        }
                    } catch (error) {
                        detectBtn.text(__('Location detection failed', 'woo-pincode-checker'));
                    }
                    
                    setTimeout(() => {
                        detectBtn.prop('disabled', false).text(__('📍 Detect my location', 'woo-pincode-checker'));
                    }, 3000);
                },
                (error) => {
                    let message;
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            message = __('Location access denied', 'woo-pincode-checker');
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = __('Location unavailable', 'woo-pincode-checker');
                            break;
                        case error.TIMEOUT:
                            message = __('Location request timeout', 'woo-pincode-checker');
                            break;
                        default:
                            message = __('Location detection failed', 'woo-pincode-checker');
                    }
                    
                    detectBtn.text(message).prop('disabled', false);
                    setTimeout(() => {
                        detectBtn.text(__('📍 Detect my location', 'woo-pincode-checker'));
                    }, 3000);
                }
            );
        }

        async reverseGeocode(latitude, longitude) {
            try {
                // Using a free geocoding service
                const response = await fetch(
                    `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`
                );
                
                if (response.ok) {
                    const data = await response.json();
                    return data.postcode || null;
                }
            } catch (error) {
                console.warn('Reverse geocoding failed:', error);
            }
            return null;
        }

        hideDuplicateMessages() {
            const deliveryMessages = $('.wc-delivery-time-response');
            if (deliveryMessages.length > 1) {
                deliveryMessages.not(':last').hide();
            }
        }

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        // Public methods for external integration
        checkPincode(pincode) {
            this.setPincodeValue(pincode);
            return this.handlePincodeCheck();
        }

        getCurrentPincode() {
            return this.getPincodeValue();
        }

        clearPincode() {
            this.setPincodeValue('');
            $('.wc-delivery-time-response').hide();
            $('#my_custom_checkout_field2').show();
            this.hideError();
        }
    }

    // Enhanced error handling and monitoring
    class WPCErrorMonitor {
        constructor() {
            this.errors = [];
            this.maxErrors = 10;
            this.init();
        }

        init() {
            // Monitor AJAX errors
            $(document).ajaxError((event, xhr, settings) => {
                if (settings.data && settings.data.includes('wpc_')) {
                    this.logError('AJAX Error', {
                        url: settings.url,
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText
                    });
                }
            });

            // Monitor JavaScript errors
            window.addEventListener('error', (event) => {
                if (event.filename && event.filename.includes('woo-pincode-checker')) {
                    this.logError('JavaScript Error', {
                        message: event.message,
                        filename: event.filename,
                        lineno: event.lineno,
                        colno: event.colno
                    });
                }
            });
        }

        logError(type, details) {
            const error = {
                type: type,
                details: details,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href
            };

            this.errors.push(error);
            
            // Keep only recent errors
            if (this.errors.length > this.maxErrors) {
                this.errors.shift();
            }

            // Log to console in debug mode
            if (window.WPC_DEBUG) {
                console.warn('WPC Error:', error);
            }

            // Could send to server for monitoring
            // this.sendErrorToServer(error);
        }

        getErrors() {
            return this.errors;
        }

        clearErrors() {
            this.errors = [];
        }
    }

    // Performance monitoring
    class WPCPerformanceMonitor {
        constructor() {
            this.metrics = {};
            this.init();
        }

        init() {
            // Monitor API response times
            $(document).on('wpc_pincode_checked', (event, data) => {
                this.recordMetric('pincode_check_duration', data.duration || 0);
            });
        }

        recordMetric(name, value) {
            if (!this.metrics[name]) {
                this.metrics[name] = [];
            }
            
            this.metrics[name].push({
                value: value,
                timestamp: Date.now()
            });

            // Keep only last 100 measurements
            if (this.metrics[name].length > 100) {
                this.metrics[name].shift();
            }
        }

        getMetrics() {
            return this.metrics;
        }

        getAverageMetric(name) {
            if (!this.metrics[name] || this.metrics[name].length === 0) {
                return 0;
            }

            const sum = this.metrics[name].reduce((acc, metric) => acc + metric.value, 0);
            return sum / this.metrics[name].length;
        }
    }

    // Initialize everything when DOM is ready
    $(document).ready(function() {
        // Initialize main functionality
        window.WooPincodeChecker = new WooPincodeChecker();
        
        // Initialize monitoring (only in debug mode)
        if (window.WPC_DEBUG) {
            window.WPCErrorMonitor = new WPCErrorMonitor();
            window.WPCPerformanceMonitor = new WPCPerformanceMonitor();
        }

        // Global event for theme/plugin integration
        $(document).trigger('wpc_initialized', {
            checker: window.WooPincodeChecker,
            version: '1.3.4'
        });

        // Legacy support for existing themes
        window.checkPincode = function(pincode) {
            return window.WooPincodeChecker.checkPincode(pincode);
        };

        // Enhanced accessibility
        $('.wpc-pincode-field').attr({
            'aria-label': __('Enter your pincode for delivery information', 'woo-pincode-checker'),
            'aria-describedby': 'wpc-field-help'
        });

        // Add screen reader help text
        if (!$('#wpc-field-help').length) {
            $('.wpc-pincode-field').after(
                '<div id="wpc-field-help" class="screen-reader-text">' +
                __('Enter your postal code to check delivery availability and estimated delivery time', 'woo-pincode-checker') +
                '</div>'
            );
        }

        // Progressive enhancement for mobile
        if (window.innerWidth <= 768) {
            // Add mobile-specific enhancements
            $('.wpc-pincode-checker-container').addClass('mobile-optimized');
            
            // Prevent zoom on iOS
            $('.wpc-pincode-field').attr('font-size', '16px');
        }

        // Add keyboard navigation
        $(document).on('keydown', '.wpc-suggestion-item', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });

        // Enhanced form validation
        $('.wpc-pincode-field').on('blur', function() {
            const value = $(this).val();
            if (value && !window.WooPincodeChecker.validatePincodeFormat(value)) {
                $(this).addClass('error');
                window.WooPincodeChecker.showError(
                    pincode_check.messages?.invalid_format || 
                    __('Please enter a valid pincode format', 'woo-pincode-checker')
                );
            }
        });
    });

})(jQuery);