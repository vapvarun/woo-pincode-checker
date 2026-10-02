# Pincode Checker for WooCommerce - Developer Guide

Guideline doc: how to build in this plugin. Change history lives in git, not here.

## Identity
| | |
|---|---|
| Folder / text domain | `woo-pincode-checker` |
| Main file | `woo-pincode-checker.php` |
| Prefix | `wbpc` (options, hooks, CSS, JS, REST `wbpc/v1`, CLI `wp wbpc`, table `{prefix}wbpc_areas`) |
| Namespace | `Wbcom\PincodeChecker` (PSR-4 from `includes/`) |
| Requires | PHP 8.1, WP 6.5, WooCommerce 8.0 (declared HPOS + Cart/Checkout blocks compatible) |
| Admin | WB Plugins > Pincode Checker on the shared shell `lib/wbcom-settings` (vendored, 1.0.5) |

## Layers (where code goes)
- `Core/` - bootstrap (`Plugin` = lazy service getters, the container), `Installer` (dbDelta, `DB_VERSION`), `Uninstaller`.
- `Domain/Postcode` - pure postcode logic, no WordPress calls.
- `Repository/AreaRepository` - the ONLY class that runs SQL on `wbpc_areas`. Every write bumps `wbpc_areas_version` (cache key part).
- `Services/` - business logic returning arrays (never HTML, never HTTP): `CheckService` (match + precedence + result), `AreaService` (validation + hooks for every write surface), `DeliveryDateService` (pure `calculate()`), `SettingsService` (one schema: defaults, sanitize, get), `ImportService` (Action Scheduler chunks), `CsvService`, `ProductService`, `RateLimiter`.
- `Integrations/WooCommerce/` - WooCommerce hooks only: `Enforcement` (add to cart + classic + Blocks checkout share one decision), `ShippingMethod`, `CodGateway`, `DeliveryEstimates`, `CustomerPostcode`, `ProductSettings`.
- `REST/Controller/`, `CLI/Commands`, `Frontend/`, `Admin/` - thin adapters that call services.
- `templates/checker.php` (theme override `woocommerce/wbpc/checker.php`), `src/blocks/pincode-checker` (SSR, no build).

## Rules
- Storefront output must be identical for every visitor (page-cache safe). Shopper data comes only from `GET wbpc/v1/check`.
- The server enforces (add to cart, checkout, COD, shipping); storefront JS only improves the experience.
- One option per settings tab; read settings only through `Plugin::settings()->get()`. Read them after `init` (translated defaults).
- Capability: always `Plugin::cap()` (filter `wbpc_manage_capability`, default `manage_woocommerce`).
- No admin-ajax, no inline `<style>`/`<script>`, no `alert()`/`confirm()`. Admin JS uses `wp.apiFetch`.
- Big-site: indexed queries only, cached counts, background work for bulk operations.
- New hook = document it in `docs/HOOKS.md`.

## Checks before a release
- `wp wbpc self-test` (postcode parsing, precedence, delivery dates).
- `phpcs` (phpcs.xml.dist, WordPress-Extra + PHPCompatibility 8.1+) and `phpstan` level 6 (see `.github/workflows/ci.yml` for stubs).
- `bin/build-release.sh`.
- Browser: product check, Checkout block + classic checkout orders, admin tabs at 1440 and 390.
