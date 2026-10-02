# Pincode Checker for WooCommerce - Hooks

Prefix: `wbpc_`. Every hook below exists in 1.6.0 (file:line in `includes/` unless noted).

## Filters

| Hook | Args | Purpose | Where |
|---|---|---|---|
| `wbpc_check_result` | `array $result` | Change a check result before any surface uses it (product page, cart, checkout, REST, CLI). | Services/CheckService.php |
| `wbpc_estimate` | `array $estimate` {min, max, label}, `int $extra_days` | Adjust a delivery estimate (Pro warehouses). | Services/DeliveryDateService.php |
| `wbpc_shipping_estimate` | `array\|null $estimate`, `string $method_id`, `int $instance_id` | Estimate for one shipping method, shown on its rate and saved on the order. Null for local pickup by default; return null to hide it, or shift dates for express. | Integrations/WooCommerce/DeliveryEstimates.php |
| `wbpc_is_product_excluded` | `bool $excluded`, `WC_Product $product` | Turn the checker and its enforcement off or on for a product. | Services/ProductService.php |
| `wbpc_before_area_save` | `array $row`, `int $area_id` (0 on create) | Change or reject (return `WP_Error`) an area before it is written. Runs for admin, REST, CLI and imports. | Services/AreaService.php |
| `wbpc_load_public_assets` | `bool $load` | Load the checker CSS/JS on extra pages (custom templates, page builders). | Frontend/Storefront.php |
| `wbpc_manage_capability` | `string $cap` (default `manage_woocommerce`) | Capability for the admin screen, REST admin routes, downloads and settings. | Core/Plugin.php |
| `wbpc_rate_limit` | `int $per_minute` (default 60), `string $bucket` | Public lookup rate limit per client. | Services/RateLimiter.php |
| `wbpc_client_ip` | `string $ip` (REMOTE_ADDR) | Restore the real client IP behind a proxy/CDN for rate limiting. | Services/RateLimiter.php |
| `wbpc_settings_nav_groups` | `array $groups` | Add admin tabs (shared Wbcom settings shell). | Admin/SettingsPage.php |

## Actions

| Hook | Args | When |
|---|---|---|
| `wbpc_check_matched` | `array $result` | A check matched an area. |
| `wbpc_check_not_matched` | `array $result` | A check matched no area (Pro waitlist / demand analytics). |
| `wbpc_area_saved` | `array $area` (formatted) | An area was created or updated. |
| `wbpc_area_deleted` | `int[] $ids` (empty when deleted by filter) | Areas were deleted. |
| `wbpc_areas_imported` | `array $job` (counts) | A CSV import finished. |
| `wbpc_checker_form_after` | `int $product_id` | After the checker markup (templates/checker.php). |
| `wbpc_settings_tab_content` | `string $tab_id` | Render a tab body (shared settings shell). |

## JavaScript events

Dispatched on the checker root element (`[data-wbpc-checker]`), bubbling:

| Event | `event.detail` |
|---|---|
| `wbpc:checked` | The check result (same shape as `GET /wp-json/wbpc/v1/check`). |

## Templates

Override path: `yourtheme/woocommerce/wbpc/checker.php`. Keep the `data-wbpc-*` attributes; never print shopper-specific data in it (pages may be cached).
