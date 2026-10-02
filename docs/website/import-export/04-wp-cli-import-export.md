---
title: WP-CLI import and export
description: Import and export areas from the command line.
---

# WP-CLI import and export

Use WP-CLI for very large files or scripted deployments. The import follows the same rules as the admin importer and runs to the end in one process.

## Import

```bash
wp wbpc import areas.csv --mode=update --country=IN
```

| Option | Default | Description |
|--------|---------|-------------|
| `<file>` | - | CSV file with a header row |
| `--mode` | `skip` | `skip`, `update` or `replace` |
| `--country` | any country | Country for rows without one |

The command prints the totals: rows, added, updated, skipped and failed. If rows failed, download the error report from **Import / Export** in the admin.

## Export

```bash
wp wbpc export areas.csv --country=IN --type=prefix --status=serviceable
```

| Option | Description |
|--------|-------------|
| `[<file>]` | Output file. Without it, the CSV prints to standard output. |
| `--country` | Only this country. `any` means areas without a country. |
| `--type` | `exact`, `prefix` or `range` |
| `--status` | `serviceable` or `blocked` |

See [CSV format](01-csv-format.md) for the columns.
