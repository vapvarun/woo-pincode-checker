---
title: Common issues
description: Fix a missing checker, a missing Pincode rate, COD not offered or a missing delivery estimate.
---

# Common issues

Start with the tools in [Diagnose with the Overview tester and Tools](02-diagnose.md).

## The checker does not show

**Cause 1: the product is excluded.** The checker is off, and checks are skipped, for a product that:

- Does not need shipping (virtual or downloadable products).
- Is an external or affiliate product.
- Has **Pincode checker** ticked on its **Shipping** tab ("Hide the pincode checker and skip delivery-area checks for this product").
- Is in a category listed under **Skip these categories** on the General tab.

**Cause 2: placement is manual.** If **Show the checker** is "Only where I add the block or [wbpc_pincode_checker] shortcode", add the **Pincode Checker** block or the shortcode.

**Cause 3: the product already has one.** Only one checker per product shows on a page.

## The Pincode rate is not offered

1. Check that **Pincode rate** is added to the shipping zone that covers the customer's address (**Overview** shows "Enabled" when it is in a zone).
2. Check that the shopper has entered a postcode or checked a pincode.
3. Test the postcode on the **Overview** tab. The rate is offered only when the result is deliverable.

## Cash on delivery is not offered

1. Check that WooCommerce's Cash on delivery method is enabled.
2. Open the matching area and check its **Cash on delivery** switch.
3. Check **Offer COD only where allowed** on **Checkout and COD**.
4. Test the postcode on **Overview**. Blocked and unserved postcodes never get COD.

## The delivery estimate is missing

1. Check **Show delivery dates** and **Show the estimate on** on **Delivery Dates**.
2. Estimates show only for deliverable postcodes.
3. On the product page, the estimate line needs **Product page** under **Show the estimate on**.
4. Orders placed before the plugin was set up have no saved estimate.

## Everything is deliverable, or nothing is

With **Automatic**, every postcode is deliverable until you add your first serviceable area. If you only added blocked areas, everything else is still delivered. To deliver only listed areas, add at least one serviceable area or set **Not available**.
