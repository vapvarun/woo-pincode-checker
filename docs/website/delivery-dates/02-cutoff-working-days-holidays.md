---
title: Cut-off, working days and holidays
description: Delivery Dates settings, defaults and allowed values.
---

# Cut-off, working days and holidays

Go to **WB Plugins > Pincode Checker > Delivery Dates**.

## Delivery estimate

| Setting | Default | Allowed |
|---------|---------|---------|
| Show delivery dates | On | On or off |
| Default delivery days (min) | 3 | 0 to 90 |
| Default delivery days (max) | 5 | 0 to 90 |
| Processing days | 0 | 0 to 30 (working days before an order ships) |
| Same-day cut-off | 14:00 | A time. Orders after it start the next working day. |

If the maximum is lower than the minimum when you save, it is raised to match the minimum.

## Working days and holidays

| Setting | Default | Notes |
|---------|---------|-------|
| Working days | Monday to Saturday | Choose at least one. An empty choice reverts to Monday to Saturday. |
| Count delivery days as | Working days only | Or **Calendar days (couriers deliver every day)** |
| Holidays | None | One date per line as `YYYY-MM-DD`. Past dates are removed. Maximum 366. |

### Working days vs calendar days

- **Working days only:** delivery days skip days off and holidays.
- **Calendar days:** delivery days count every day, including weekends and holidays.

Calendar days apply to transit only. The start day and **Processing days** always follow your working days and holidays.

## How dates look

| Setting | Default | Options |
|---------|---------|---------|
| Show the estimate as | A date range | A date range ("Arrives Oct 8 to Oct 10"), the latest date ("Arrives by Oct 10"), days ("Arrives in 3-5 days") |
| Date format | `D, M j` | Six formats, shown with a live example |
| Show the estimate on | All four | Product page, Cart and checkout, Order details, Order emails |

If the earliest and latest dates are the same, the estimate shows a single date ("by ...").

See [where estimates show](04-where-estimates-show.md).
