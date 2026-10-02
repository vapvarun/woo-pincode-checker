---
title: Exporting areas
description: Download your delivery areas as CSV for backup or editing.
---

# Exporting areas

1. Go to **Import / Export**.
2. Under **Export areas**, optionally filter by country, type (Exact, Prefix, Range) or status (Serviceable, Blocked).
3. Click **Download CSV**.

The file uses the [CSV format](01-csv-format.md), so you can edit it in a spreadsheet and import it again in update mode. Text that starts with `=`, `+`, `-` or `@` gets a leading apostrophe so spreadsheet apps do not run it as a formula.

Choose **Any country** in the country filter to export only areas that have no country.

Export from the command line with `wp wbpc export`. See [WP-CLI](../developer-guide/03-wp-cli.md).
