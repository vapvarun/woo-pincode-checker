---
title: Placement
description: Choose where the checker appears on product pages, or place it yourself with a block or shortcode.
---

# Placement

Go to **General > Product page > Show the checker**.

| Option | Where it appears |
|--------|------------------|
| Above the Add to cart button (default) | Before the button |
| Below the Add to cart button | After the button |
| Right after the quantity box | Next to quantity |
| In the product summary, below the price | Before the add to cart form |
| Only where I add the block or [wbpc_pincode_checker] shortcode | Nowhere automatic |

## Block

In the editor, add the **Pincode Checker** block anywhere. It uses the same settings and design as the automatic placement.

## Shortcode

```text
[wbpc_pincode_checker]
[wbpc_pincode_checker product_id="123"]
```

- On a product page, the shortcode binds to that product.
- With `product_id`, it binds to that product.
- Elsewhere, with no product, it is a general delivery check. It does not control Add to cart.

On a product, the checker controls Add to cart according to your [checkout settings](../checkout-and-cod/01-checkout-validation.md).

## One checker per product

If a block, a shortcode and the automatic placement all target the same product on one page, only the first one is shown.

## Page caching

The checker works with page caching and CDNs. The page is the same for every visitor, and the answer is fetched separately. The shopper's last pincode is saved in a cookie named `wbpc_postcode`, for **Remember the pincode for** days (default 30, range 1 to 365), on that device.
