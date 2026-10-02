---
title: Messages and tokens
description: Edit every label and message shoppers see, and use tokens in them.
---

# Messages and tokens

Go to **Display and Messages**. Leave a field empty to restore its default.

## Checker labels

| Field | Default |
|-------|---------|
| Heading | Check delivery |
| Input placeholder | Enter pincode |
| Check button | Check |
| Change button | Change |

## Messages

| Field | Default | Tokens |
|-------|---------|--------|
| Delivery available | Delivery available to {city} | {postcode}, {city}, {state} |
| Delivery estimate | Arrives {estimate} | {estimate} |
| Not delivered | Sorry, we don't deliver to {postcode} yet. | {postcode}, {city}, {state} |
| Invalid pincode | Please enter a valid pincode. | none |
| Check required | Check your pincode to add this product to the cart. | none |
| Shipping fee | Shipping: {amount} | {amount} |
| COD available | Cash on delivery available | none |
| COD not available | Cash on delivery not available | none |

Also on this tab: **Show cash on delivery availability** (on by default) shows or hides the COD line.

## What the tokens become

- `{postcode}`: the normalized postcode.
- `{city}` and `{state}`: from the matching area. If the area has no city, `{city}` shows the postcode.
- `{estimate}`: the date, range or days set on the **Delivery Dates** tab.
- `{amount}`: the area's shipping fee, in your store currency.

The estimate line shows only when **Product page** is selected under **Show the estimate on**. The shipping line shows only when the area has a fee and the Pincode rate method is enabled.

Messages are translatable. Default text follows the site language until you save your own.
