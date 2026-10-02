---
title: Area types
description: Exact postcodes, prefixes and numeric ranges, and how postcodes are compared.
---

# Area types

An area is a rule that matches postcodes. Each area has a status: **serviceable** (you deliver) or **blocked** (you do not).

| Type | You enter | Matches | Admin label |
|------|-----------|---------|-------------|
| Exact | `110001` | Only that postcode | Exact postcode |
| Prefix | `SW1*` or `110*` | Every postcode that starts that way | Starts with |
| Range | `110001` to `110099` | Every number from start to end | Numeric range |

## Rules for each type

- **Exact:** 2 to 20 letters or digits.
- **Prefix:** end the code with `*`. The `*` must be last. `1*0` is rejected.
- **Range:** both ends must be numbers of the same length, and the start cannot be higher than the end. Letter postcodes cannot use ranges.
- A range only matches postcodes of its own length. The range `01000` to `01999` does not match `1500`.

## How postcodes are compared

Postcodes are compared in upper case, without spaces or hyphens. `sw1a 1aa` and `SW1A-1AA` are the same postcode as `SW1A1AA`. The postcode must also be valid for its country's format in WooCommerce.

Use prefixes for letter postcodes (`SW1*`, `M1*`) and ranges for numeric ones.

## Country

An area can be tied to one country, or to **Any country**. A postcode is read in the shopper's country. When the shopper has not chosen one, the plugin uses the **Default country** setting on the **General** tab, or your store country.

## Next step

[Precedence and blocked areas](02-precedence-and-blocked-areas.md)
