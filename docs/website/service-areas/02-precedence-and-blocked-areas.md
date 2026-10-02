---
title: Precedence and blocked areas
description: Which area wins when several match, how to carve out exceptions, and the "Postcodes with no area" setting.
---

# Precedence and blocked areas

Several areas can match one postcode. The most specific one wins.

## Order of precedence

1. Exact beats prefix, and prefix beats range.
2. A longer prefix beats a shorter one (`1100*` beats `110*`).
3. A narrower range beats a wider one.
4. An area for a specific country beats **Any country**.
5. At equal specificity, **blocked** wins over serviceable.

## Example: carve out an exception

| Area | Type | Status |
|------|------|--------|
| `110*` | Prefix | Serviceable |
| `110099` | Exact | Blocked |

Postcode `110005` matches only the prefix, so you deliver. Postcode `110099` matches both, the exact area wins, so you do not deliver.

## Postcodes with no area

Set this on **General > Postcode matching > Postcodes with no area**.

| Option | What it does |
|--------|--------------|
| Automatic (default) | Available until you add your first serviceable area, then only listed areas |
| Not available | You list every area you deliver to |
| Available on default terms | You list only exceptions. Everything else is delivered on default terms, COD included |

Use **Available on default terms** with blocked areas when you deliver almost everywhere.

## Nearby areas

When a postcode is not served, **Suggest nearby areas** (on by default) lists served postcodes that start the same way.

## Next step

[Testing a postcode](03-testing-a-postcode.md)
