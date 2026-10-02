=== Pincode Checker for WooCommerce ===
Contributors: wbcomdesigns
Tags: pincode, postcode, delivery date, cash on delivery, shipping
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.6.0
WC requires at least: 8.0
WC tested up to: 11.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tell shoppers whether you deliver to their pincode or postcode, when it will arrive, what shipping costs and whether cash on delivery is available.

== Description ==

Pincode Checker for WooCommerce adds a delivery check to your product pages and enforces it at checkout.

* Works with any postal format: exact postcodes (110001), prefixes (SW1*, 110*) and numeric ranges (110001 to 110099), per country, with blocked exceptions.
* Delivery dates: processing days, a same-day cut-off, working days, holidays and extra days per product category. Shown on the product page, next to each shipping option, on the order and in order emails.
* Per-area shipping fees through the "Pincode rate" shipping method, and cash on delivery offered only where you allow it, with an optional COD fee.
* Checkout Block and classic checkout supported. Orders to areas you do not serve are blocked on the server.
* Safe with page caching: product pages are identical for every visitor.
* Plug and play: until you add your first area, every postcode is deliverable on your default delivery days.
* Import and export areas as CSV (tens of thousands of rows, in the background), REST API and WP-CLI.

== Installation ==

1. Install and activate WooCommerce, then this plugin.
2. Go to WB Plugins > Pincode Checker. The checker already shows on product pages.
3. Add your delivery areas under Service Areas, or import a CSV under Import / Export.
4. Optional: add the "Pincode rate" method to a shipping zone (WooCommerce > Settings > Shipping) to charge each area's shipping fee.

== Frequently Asked Questions ==

= Do I have to add areas before the store works? =

No. With the default "Automatic" setting every postcode is deliverable until you add your first area. After that, only the areas you list are deliverable.

= Does it work with the Cart and Checkout blocks? =

Yes, and with the classic checkout shortcode.

= Where is the checker shown? =

Choose a position on the General tab, or use the Pincode Checker block or the [wbpc_pincode_checker] shortcode.

== Changelog ==

= 1.6.0 - October 2026 =

Rebuilt from the ground up: faster, works with any postal format, the Checkout block and page caching, and shows delivery dates.

* New      - Delivery areas as exact postcodes, prefixes and numeric ranges, per country, with blocked exceptions.
* New      - Delivery date engine with processing days, same-day cut-off, working days, holidays and per-category extra days.
* New      - Delivery estimate on the product page, next to each delivery option (not local pickup), on orders and in order emails.
* New      - "Pincode rate" shipping method that charges each area's shipping fee.
* New      - Cash on delivery offered only where an area allows it, with an optional per-area COD fee.
* New      - Checkout Block support, including server-side blocking of orders to areas you do not serve.
* New      - Pincode Checker block and [wbpc_pincode_checker] shortcode.
* New      - Background CSV import with preview, progress and an error report, plus CSV export.
* New      - Test a postcode tool on the Overview to see which area decides the answer.
* New      - REST API and WP-CLI commands (wp wbpc).
* Improve  - Plug and play defaults: nothing is blocked until you add your first delivery area.
* Improve  - Product pages work with full-page caching and CDNs.
* Improve  - Add to cart control works with variable products.
* Improve  - New admin screen under WB Plugins, usable by Shop Managers.
* Improve  - Lookups stay fast with over 100,000 delivery areas.
* Improve  - Translation ready: dates, prices and counts follow the site language and WooCommerce price format, and saved order estimates show in the reader's language.
* Fix      - Saving one settings tab no longer resets another.
* Fix      - Bulk add and import no longer time out on large lists.
* Fix      - Bulk delete works.
* Fix      - Removed the PHP 8.4 fgetcsv() deprecation notice.
* Dev      - Requires PHP 8.1, WordPress 6.5 and WooCommerce 8.0.
* Dev      - New hooks for extensions, including wbpc_shipping_estimate per shipping method; see docs/HOOKS.md.
* Compat   - WPML and Polylang: display texts, the COD fee name and the Pincode rate method name are registered for translation.
* Compat   - Declared compatible with WooCommerce HPOS and Cart/Checkout blocks.
