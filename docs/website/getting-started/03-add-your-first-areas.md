---
title: Add your first areas
description: Add a delivery area by hand and understand what changes when you do.
---

# Add your first areas

## What changes when you add an area

With the default **Automatic** setting, adding your first serviceable area switches the store from "deliver everywhere" to "deliver only to listed areas". Add all the areas you serve in one session, or [import them from CSV](../import-export/01-csv-format.md), so shoppers are not turned away in between.

If you prefer to list only exceptions, set **Postcodes with no area** to **Available on default terms** on the **General** tab. See [Precedence and blocked areas](../service-areas/02-precedence-and-blocked-areas.md).

## Add an area

1. Go to **WB Plugins > Pincode Checker > Service Areas**.
2. Click **Add area**.
3. Under **Match**, choose **Exact postcode**, **Starts with** or **Numeric range**.
4. Enter the **Postcode**. For a range, also enter **Range end**.
5. Choose a **Country**, or leave **Any country**.
6. Set **Status** to **We deliver here** or **Blocked (no delivery)**.
7. Optional: fill in **City**, **State / region**, **Delivery days (min)**, **Delivery days (max)**, **Shipping fee**, **Cash on delivery**, **COD fee** and **Internal note**.
8. Click **Save area**.

Empty delivery days use the store default. Empty max days use the min. Empty **Shipping fee** means the area has no fee of its own.

## Manage the list

The list is paged (25, 50 or 100 per page). Use the search box and the Type, Status, Cash on delivery and Country filters, and sort by newest, code, city or delivery days. Select rows and click **Delete selected** to remove several. Deleting asks you to confirm. Deleting more than 100 areas at once also asks you to type DELETE.

## Next step

[Area types](../service-areas/01-area-types.md)
