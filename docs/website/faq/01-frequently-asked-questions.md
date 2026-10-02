---
title: Frequently asked questions
description: Short answers to what store owners ask first.
---

# Frequently asked questions

## Do I have to add areas before the store works?

No. With the default "Automatic" setting every postcode is deliverable on your default delivery days until you add your first area. After that, only the areas you list are deliverable.

## What can an area be?

An exact postcode (110001), a prefix ending in * that covers every postcode starting that way (SW1* or 110*), or a numeric range (110001 to 110099). Mark an area "Blocked" to carve exceptions out of a prefix or range.

## Which area wins when several match?

The most specific one: an exact postcode beats a prefix, a longer prefix beats a shorter one, a narrower range beats a wider one, and an area for a specific country beats "Any country". At equal specificity, blocked wins. Use the "Test a postcode" tool on the Overview to see the winner.

## Does it work with UK, US and other postcodes?

Yes. Postcodes are compared without spaces or hyphens and checked against the country's format. Use prefixes for letter postcodes (SW1*, M1*) and ranges for numeric ones.

## How do I charge shipping per area?

Add the "Pincode rate" method to a shipping zone (WooCommerce > Settings > Shipping). It charges the area's shipping fee plus an optional base cost, and is only offered where you deliver.

## Does it work with the Cart and Checkout blocks?

Yes, and with the classic shortcodes. Orders to areas you do not serve are blocked in both, cash on delivery follows each area, and delivery dates appear next to each shipping option.

## Will page caching break it?

No. The product page is the same for every visitor; the answer is fetched separately, so full-page caches and CDNs can cache your pages safely.

## Where do I place the checker?

Choose a position on the General tab, or add the Pincode Checker block or the [wbpc_pincode_checker] shortcode anywhere. On a product it also controls Add to cart; elsewhere it is a general delivery check.

## Can I hide the checker on one product?

Yes. Edit the product, open the **Shipping** tab, and tick the **Pincode checker** box ("Hide the pincode checker and skip delivery-area checks for this product"). You can also skip whole categories on the General tab.

## Does a Shop Manager have access?

Yes. The admin screens and REST routes use the `manage_woocommerce` capability.

## What happens to my data if I delete the plugin?

Everything is removed unless you tick **Keep my delivery areas and settings when the plugin is deleted** on the Tools tab. See [Uninstall and data](../developer-guide/04-uninstall-and-data.md).
