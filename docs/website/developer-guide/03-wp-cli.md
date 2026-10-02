---
title: WP-CLI commands
description: The wp wbpc commands.
---

# WP-CLI commands

| Command | Description |
|---------|-------------|
| `wp wbpc check <postcode> [--country=<code>]` | Check a postcode the way the storefront does. Prints the result as JSON. |
| `wp wbpc count` | Print the number of delivery areas |
| `wp wbpc import <file> [--mode=<mode>] [--country=<code>]` | Import a CSV. Modes: `skip` (default), `update`, `replace`. |
| `wp wbpc export [<file>] [--country=] [--type=] [--status=]` | Export areas as CSV, to a file or standard output |
| `wp wbpc self-test` | Run the built-in checks for postcode parsing, match precedence and date calculation |
| `wp wbpc seed <count>` | Insert random areas. For testing on development sites only. |

## Examples

```bash
wp wbpc check "SW1A 1AA" --country=GB
wp wbpc import areas.csv --mode=update --country=IN
wp wbpc export backup.csv --status=blocked
```

Import and export details: [WP-CLI import and export](../import-export/04-wp-cli-import-export.md).

Never run `seed` on a live store. It adds fake areas, which changes which postcodes you deliver to.
