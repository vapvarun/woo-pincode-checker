---
title: Uninstall and data
description: What the plugin stores, and what happens to it when you delete the plugin.
---

# Uninstall and data

## What the plugin stores

- A custom table `{prefix}wbpc_areas` with your areas.
- Options: `wbpc_general`, `wbpc_delivery`, `wbpc_category_days`, `wbpc_checkout`, `wbpc_display`, plus internal options for database version, import jobs and the keep-data choice.
- Product meta `_wbpc_hide_checker`.
- Order meta `_wbpc_postcode` and `_wbpc_estimate`.
- Import files and error reports in `uploads/wbpc-private/`, with directory listing blocked.
- Short-lived rate-limit transients.

Settings are saved per tab. Defaults are never written to the database, so default labels follow the site language.

## Deleting the plugin

By default, deleting the plugin (not just deactivating it) removes its table, settings, product meta, transients, scheduled import jobs and private files. On multisite it cleans every site.

Order meta (`_wbpc_postcode`, `_wbpc_estimate`) is always kept. It is your order history.

## Keep your data

1. Go to **WB Plugins > Pincode Checker > Tools**.
2. Under **When the plugin is deleted**, tick **Keep my delivery areas and settings when the plugin is deleted**.
3. Click **Save**.

Do this before you delete the plugin if you plan to reinstall it.

## Other Tools actions

| Action | What it does |
|--------|--------------|
| Update database | Creates or repairs the plugin's table. Safe to run any time. |
| Clear lookup cache | Needed only when a change made directly in the database is not showing. |
