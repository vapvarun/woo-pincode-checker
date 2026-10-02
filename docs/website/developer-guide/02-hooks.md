---
title: Hooks
description: Actions and filters for extending Pincode Checker.
---

# Hooks

The full reference with parameters and examples is in the plugin repository: [docs/HOOKS.md](../../HOOKS.md).

## Quick list

| Hook | Type | Purpose |
|------|------|---------|
| `wbpc_check_result` | Filter | Change a check result before any surface uses it |
| `wbpc_check_matched` | Action | A check matched an area |
| `wbpc_check_not_matched` | Action | A check matched no area |
| `wbpc_estimate` | Filter | Change a delivery estimate (`min`, `max`, `label`) |
| `wbpc_shipping_estimate` | Filter | Change or hide the estimate for one shipping method (none for local pickup by default) |
| `wbpc_is_product_excluded` | Filter | Turn the checker off or on for a product |
| `wbpc_before_area_save` | Filter | Change or reject (return `WP_Error`) an area before it is written |
| `wbpc_area_saved` | Action | An area was created or updated |
| `wbpc_area_deleted` | Action | Areas were deleted |
| `wbpc_areas_imported` | Action | An import finished |
| `wbpc_manage_capability` | Filter | Capability for admin screens and routes (default `manage_woocommerce`) |
| `wbpc_rate_limit` | Filter | Requests per minute on the public check (default 60) |
| `wbpc_client_ip` | Filter | IP used for rate limiting |
| `wbpc_load_public_assets` | Filter | Load the checker CSS and JS on this request |
| `wbpc_checker_form_after` | Action | After the checker markup |
| `wbpc_settings_nav_groups` | Filter | Add tabs to the settings screen (shared Wbcom settings shell) |
| `wbpc_settings_tab_content` | Action | Print the body of a tab you added (receives the tab id) |

## Example

```php
// Treat a product tag as "no pincode checks".
add_filter( 'wbpc_is_product_excluded', function ( $excluded, $product ) {
	return $excluded || has_term( 'pickup-only', 'product_tag', $product->get_id() );
}, 10, 2 );
```
