---
title: Category extra days
description: Add days to the estimate for products that take longer to deliver.
---

# Category extra days

Use this for products that take longer, such as made-to-order furniture.

## Add a rule

1. Go to **WB Plugins > Pincode Checker > Category Rules**.
2. Under **Add a category**, choose a category and enter the extra days (1 to 60).
3. Click **Save changes**.

To change a rule, edit its **Extra days** value. Set it to 0 to remove the rule.

## How the days are applied

- Extra days are added to both the earliest and latest delivery days.
- A product in several categories gets the largest number.
- A variation uses its parent product's categories.
- A cart gets the largest number among its products, so the slowest item decides.

Without rules, every product uses the delivery days of its area.
