---
title: CSV format
description: Columns, accepted header names and values for importing delivery areas.
---

# CSV format

The first row must name the columns. Only `code` is required. Leave other cells empty to use store defaults. You can download a sample file from **Import / Export > File format**.

## Columns

| Column | Description |
|--------|-------------|
| `code` | Postcode. End with `*` for a prefix (`110*`, `SW1*`). |
| `code_to` | End of a numeric range (`code` 110001, `code_to` 110099). |
| `country` | Two-letter country code such as `IN`, `GB` or `US`. |
| `status` | `serviceable` or `blocked`. |
| `city` | City name. |
| `state` | State or region. |
| `days_min` | Minimum delivery days. |
| `days_max` | Maximum delivery days. |
| `shipping_fee` | Area shipping fee, charged by the Pincode rate method. |
| `cod_allowed` | `yes` or `no`. |
| `cod_fee` | Cash on delivery fee. |
| `note` | Internal note. |

## Accepted header names

Header matching ignores case. Spaces and hyphens count as underscores. A UTF-8 byte order mark is ignored. Unknown columns are ignored.

| Your header | Read as |
|-------------|---------|
| `pincode`, `pin_code`, `postcode`, `post_code`, `postal_code`, `zip`, `zipcode`, `zip_code` | `code` |
| `to`, `code_end`, `range_end`, `end`, `zip_end`, `zip_to`, `pincode_to`, `pincode_end`, `postcode_to` | `code_to` |
| `cod` | `cod_allowed` |
| `shipping` | `shipping_fee` |
| `delivery_days` | `days_min` |

## Accepted values

- **status:** `blocked`, `block`, `no`, `0` or `false` mean blocked. Anything else means serviceable.
- **cod_allowed:** `yes`, `y`, `1`, `true` or `allowed` mean allowed, and any other value means not allowed. An empty cell means allowed.
- **country:** rows with an empty country use the country you choose on the import screen (or "Any country").

## Example

```csv
country,code,code_to,status,city,state,days_min,days_max,shipping_fee,cod_allowed,cod_fee,note
IN,110001,,serviceable,New Delhi,Delhi,1,2,40,yes,25,
IN,110*,,serviceable,Delhi NCR,Delhi,2,4,60,yes,30,All Delhi PINs
IN,110099,,blocked,,,,,,no,0,No courier service
GB,SW1*,,serviceable,Westminster,London,1,1,4.99,no,0,
US,10001,10099,serviceable,New York,NY,2,3,,no,0,Manhattan ZIPs
```

## Limits

- File size: 10 MB. Split larger files.
- Rows that fail validation are skipped and listed in an error report. See [Importing](02-importing.md).
