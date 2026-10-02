---
title: Pincode rate shipping method
description: Charge each area's shipping fee with the Pincode rate method in a WooCommerce shipping zone.
---

# Pincode rate shipping method

**Pincode rate** charges the shipping fee you set on each area, plus an optional base cost. It is only offered where you deliver.

## Add it to a shipping zone

1. Go to **WooCommerce > Settings > Shipping** and open a shipping zone.
2. Click **Add shipping method** and choose **Pincode rate**.
3. Click the method to set its options.
4. Save.

## Options

| Option | Default | Description |
|--------|---------|-------------|
| Name shown to shoppers | Delivery | The label at checkout |
| Tax status | Taxable | Taxable or None |
| Base cost | 0 | Added to every order, before the area fee |
| Fee when the area has none | 0 | The fee for areas without their own shipping fee, and for postcodes delivered on default terms |

The rate is: base cost plus the area's **Shipping fee**, or the fallback fee when the area has none.

## When the rate is offered

- The shopper has given a postcode (in the address or a saved pincode check).
- The postcode is valid and deliverable.

Other shipping methods in the zone are not affected. The method works in the Checkout block and the classic checkout.

## Shipping fee on the product page

The checker shows "Shipping: {amount}" only when the Pincode rate method is enabled in a zone, because area fees are charged only by this method. The **Overview** tab shows whether it is enabled.

Next: [Cash on delivery](03-cash-on-delivery.md)
