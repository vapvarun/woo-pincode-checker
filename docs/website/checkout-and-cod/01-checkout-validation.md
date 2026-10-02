---
title: Checkout validation
description: How the plugin blocks orders to postcodes you do not serve, at Add to cart and at checkout.
---

# Checkout validation

## Add to cart

Settings are on **General > Product page**.

| Setting | Default | What it does |
|---------|---------|--------------|
| Require a pincode check | Off | Shoppers must check a pincode you deliver to before adding the product to the cart |
| When you do not deliver there | Disable the Add to cart button | Or **Hide the Add to cart button**, or **Keep Add to cart, show a warning** |

The rules also run on the server, so shop pages, quick-add buttons and the Store API follow them too. With "Keep Add to cart, show a warning", shoppers can add the product, and checkout still blocks the order.

## Checkout

Go to **Checkout and COD > Checkout**.

| Setting | Default | What it does |
|---------|---------|--------------|
| Block orders to areas you do not serve | On | Checks the shipping postcode when the order is placed, on both the Checkout block and the classic checkout |
| Fill in the checked pincode at checkout | On | Guests do not type it twice |

The shipping address is checked. If there is none, the billing address is used.

Validation is skipped when:

- The setting is off.
- The cart has nothing to ship.
- Every item is excluded (see [Troubleshooting](../troubleshooting/01-common-issues.md)).
- The postcode is invalid. WooCommerce's own address validation handles that.

## Guest prefill

When a guest has checked a pincode on a product page, it is copied into the cart session. Shipping rates, COD and the checkout form start from it. A logged-in customer's saved address is never changed, and an address the guest already entered is not overwritten.

Next: [Pincode rate shipping method](02-pincode-rate-shipping-method.md)
