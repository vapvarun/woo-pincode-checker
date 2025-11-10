=== Pincode Checker for WooCommerce ===
Contributors: wbcomdesigns, vapvarun
Donate link: https://wbcomdesigns.com/donate
Tags: woocommerce, pincode, zip code, delivery, shipping
Requires at least: 5.0
Tested up to: 6.8.2
Stable tag: 1.4.0
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 10.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enable location-based delivery verification for your WooCommerce store. Check product availability, delivery timelines, and shipping options based on customer's pincode/ZIP code.

== Description ==

**Pincode Checker for WooCommerce** is an essential plugin for stores that need to verify delivery availability to specific locations before customers place orders. Perfect for businesses with limited delivery areas or varying shipping times by location.

This plugin helps reduce cart abandonment by providing upfront delivery information, including:
* Product availability by location
* Estimated delivery dates
* Shipping costs per pincode
* Cash on Delivery (COD) availability
* Custom messages per location

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Download the zip file and extract it.
2. Upload `woo-pincode-checker` directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the \'Plugins\' menu.
4. Enjoy
If you need additional help you can contact us for [Custom Development](https://wbcomdesigns.com/hire-us/).

== Changelog ==

= 1.4.0 - 2025-01-10 =
* **Major Update:** Plugin renamed to "Pincode Checker for WooCommerce" for WordPress.org compliance
* **Enhancement:** Improved plugin description and documentation
* **Enhancement:** Better error handling for database operations
* **Enhancement:** Added comprehensive audit documentation
* **Fix:** Synchronized version numbers across all files
* **Fix:** Updated text domain to match new plugin slug
* **Security:** Identified and documented AJAX security improvements needed
* **Compatibility:** Documented PHP 8.3+ compatibility requirements

= 1.3.6 =
* Enhancement: Updated for WooCommerce 10.1.2 compatibility
* Enhancement: Updated for WordPress 6.8.2 compatibility
* Enhancement: Improved database table creation with better error handling
* Enhancement: Added comprehensive system requirement checks
* Enhancement: Enhanced security with proper input validation and sanitization
* Enhancement: Added rate limiting for AJAX requests
* Enhancement: Implemented caching for better performance
* Enhancement: Added database permission checks during activation
* Enhancement: Improved error logging and debugging
* Enhancement: Added sample data for testing
* Enhancement: Better handling of table structure updates
* Fix: Resolved database table creation issues
* Fix: Fixed PHP 8.x compatibility issues (strpos and str_replace deprecation warnings)
* Fix: Fixed null value handling in filter_input and get_current_screen calls
* Fix: Improved cookie handling and validation
* Fix: Enhanced XSS protection
* Fix: Fixed admin notices display conditions

= 1.3.4 =
* Fix: PHPCS fixes
* Fix: (#88) Issue with php 8.2
* Fix: (#85) Issue with multiple pincode on group product
* Fix: (#87) Disable add to cart button option missing
* Fix: (#80) Check box is repeating
* Fix: (#75) Shortcode not working
* Fix: (#76) Pincode checker is required field
* Fix: Plugin redirect issue when multiple plugins activate at the same time
* Fix: (#74) Issue with search Pincode
* Fix: (#55) BB theme pincode button UI managed
* Fix: (#67) On adding new pincode shipping and COD amount compulsory

= 1.3.3 =
* Fixed: update wrapper html

= 1.3.2 =
* Fixed: Added Database version

= 1.3.1 =
* Fixed: Fixed bulk delete issue
* Fixed: Removed Extra closed div
* Enhancement: Added Default option for color setting
* Fixed: Fixed shortcode page UI issue
* Fixed: fixed shortcode UI and button dynamic color option
* Enhancement: Added class according to selected position
* Fixed: Fix cash on delivery icon display issue
* Fixed: Fix notice display issue on selection on use pincode
* Fixed: Managed Mobile view
* Fixed: (#54) manage the pincode UI
* Enhancement: Added shortcode note when select shortcode
* Enhancement: Added Admin setting for display COD option
* Enhancement: Added Change Label Functionality
* Enhancement: Added Shortcode Functionality
* Enhancement: Added pincode availability position
* Enhancement: Added Exclude Category Functionality for pincode checker
* Enhancement: Added Sample CSV download option in FAQ

= 1.3.0 =
* Fixed: Added Escaping Function
* Fixed: (#51)Fixed Database error when upload bulk pincodes
* Fixed: Update plugin backend UI and error fixes
* Fixed: (#45) Fixed grammatically error
* Fixed: (#43) Fixed Not showing on Pincode Checker option
* Fixed: (#38) Fixed Change the 'Add to cart' button color in storefront theme
* Fixed: (#37) Fixed Label color issue with reign theme
* Fixed: (#36) Fixed Space is displaying before the Delivery Date
* Fixed: (#35) Fixed Pincode Checker Form Display Issue

= 1.2.0 =
* Fixed: (#31) Fixed hide pincode checker issue
* Fixed: (#31) Fixed pincode checker for specific product
* Fixed: Undefined variable: class notice
* Fixed: (#25) Fixed delivery date format settings display issue
* Fixed: (#28) Remove cart button if pincode is not deliverable
* Fixed: (#12) Fixed Error if woocommerce plugin is not activated
* Fixed: (#27) Fixed pincode checker issue
* Fixed: (#12) Add admin notice if woocommerce plugin is not activated
* Fixed: (#26) change admin color setting text
* Fixed: PHPCS error

= 1.1.0 =
* Fixed: #11 Notices and warnings in backend dashboard settings

= 1.0.0 =
* Initial release.
