---
title: Template override
description: Copy the checker template into your theme to change its markup.
---

# Template override

1. Copy `templates/checker.php` from the plugin folder.
2. Paste it to `yourtheme/woocommerce/wbpc/checker.php` (use a child theme).
3. Edit the copy.

## Rules

- Keep every `data-wbpc-*` attribute. The script uses them.
- Never print shopper-specific data in the template. The page may be cached.

## Available variables

| Variable | Description |
|----------|-------------|
| `$product_id` | Product ID, 0 for a general check |
| `$labels` | The Display and Messages settings |
| `$wrapper` | Extra wrapper attributes, already escaped (block supports) |
| `$numeric` | True when postcodes in the default country are numeric |

The template ends with the `wbpc_checker_form_after` action, which receives the product ID.

After a plugin update, compare your copy with the plugin's template. The current template version is 1.6.0.
