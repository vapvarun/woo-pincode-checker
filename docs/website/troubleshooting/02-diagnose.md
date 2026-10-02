---
title: Diagnose with the Overview tester and Tools
description: Use Test a postcode and System status to find out what is happening.
---

# Diagnose with the Overview tester and Tools

## Test a postcode

1. Go to **WB Plugins > Pincode Checker > Overview**.
2. Enter the postcode and country, then click **Test**.
3. Read the result and the area that decided it.

If the area shown is not the one you expected, see [Precedence](../service-areas/02-precedence-and-blocked-areas.md).

## Store status

The **Store status** card on Overview shows:

- WooCommerce version (8.0 or newer is required).
- The number of delivery areas.
- Whether **Pincode rate shipping** is enabled.
- Whether your checkout page uses the Checkout block or the classic checkout.
- The plugin version.

## System status

Go to **Tools > System status** and share it with support. It lists:

- Areas table and its row count
- Database version (installed and expected)
- Background jobs (pending and failed)
- Object cache (persistent or per request)
- Plugin, WooCommerce and PHP versions

## Fixes

| Problem | Action on Tools |
|---------|-----------------|
| Areas table is missing | Click **Update database** |
| A database change is not showing | Click **Clear lookup cache** |

## Debug logging

Enable WordPress debug logging and check `wp-content/debug.log`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

When you contact support, include your WordPress, WooCommerce, PHP and plugin versions, any error message, and the steps to reproduce.
