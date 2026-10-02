---
title: How the estimate is calculated
description: The steps behind a delivery estimate, with a worked example.
---

# How the estimate is calculated

## The steps

1. **Start day.** Today, if today is a working day and the time is before the **Same-day cut-off**. Otherwise the next working day.
2. **Dispatch day.** The start day plus **Processing days**, counted in working days.
3. **Earliest and latest dates.** The dispatch day plus the delivery days, plus any [category extra days](03-category-extra-days.md).

All times use your site timezone (Settings > General).

## Which delivery days are used

- The area's **Delivery days (min)** and **(max)**, if set.
- An area with only a minimum uses it for both.
- Otherwise the **Default delivery days (min)** and **(max)** from the **Delivery Dates** tab.

## Worked example

Settings: working days Monday to Friday, cut-off 14:00, processing days 0, delivery days counted as working days. The area has 2 to 3 delivery days.

A customer orders on **Monday at 15:00**:

1. Past the cut-off, so the start day is Tuesday, Oct 6.
2. No processing days, so dispatch is Tuesday, Oct 6.
3. Two working days later is Thursday, Oct 8. Three is Friday, Oct 9.

The shopper sees **Arrives Thu, Oct 8 to Fri, Oct 9** (with the default date format).

Variations on the same order:

- **Processing days 1:** dispatch is Wednesday, Oct 7, so the estimate is Fri, Oct 9 to Mon, Oct 12.
- **Ordered at 10:00 instead:** the start day is Monday, Oct 5, so the estimate is Wed, Oct 7 to Thu, Oct 8.
- **Category extra days 2 (still ordered Monday at 15:00):** the delivery days become 4 to 5, so the estimate is Mon, Oct 12 to Tue, Oct 13.

## When there is no estimate

No estimate shows for postcodes you do not deliver to, or when **Show delivery dates** is off.

## Next step

[Cut-off, working days and holidays](02-cutoff-working-days-holidays.md)
