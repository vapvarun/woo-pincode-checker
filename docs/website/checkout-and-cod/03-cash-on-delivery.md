---
title: Cash on delivery
description: Offer cash on delivery only where an area allows it, and charge a COD fee.
---

# Cash on delivery

Set COD per area with the **Cash on delivery** switch and the **COD fee** field. Then turn on the store-wide options on **Checkout and COD > Cash on delivery**.

| Setting | Default | What it does |
|---------|---------|--------------|
| Offer COD only where allowed | On | Hides the Cash on delivery payment method for areas that do not allow it |
| Charge the area's COD fee | On | Adds the area's fee when the shopper pays by cash on delivery |
| Fee name shown in the cart | Cash on delivery fee | The fee's label |

## How it works

- COD is hidden when the shopper's area is blocked or has COD turned off. This works in the classic checkout and the Checkout block, and is checked again when the order is placed.
- Postcodes delivered on default terms (no matching area) allow COD.
- The fee is added only when COD is the chosen payment method and the fee is above 0. In the classic checkout, totals refresh when the payment method changes.
- COD is left alone when no postcode is known yet, or the cart ships nothing.

The checker on product pages can show a line such as "Cash on delivery available". Turn it on or off with **Show cash on delivery availability** on **Display and Messages**.

This feature needs WooCommerce's own Cash on delivery payment method to be enabled.

## Guest prefill

See [Checkout validation](01-checkout-validation.md).
