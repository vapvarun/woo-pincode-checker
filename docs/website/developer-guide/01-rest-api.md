---
title: REST API
description: The public check endpoint and the admin endpoints for areas, imports and testing.
---

# REST API

Base URL: `/wp-json/wbpc/v1/`

## Public: GET /check

Looks up a postcode. It needs no nonce, so cached pages can call it. It changes nothing.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `postcode` | string | Yes | Max 32 characters |
| `country` | string | No | Two-letter code. Empty uses the default country. |
| `product_id` | integer | No | Adds the product's category extra days and returns `can_add` for it |

The response is the check result plus `messages` and `can_add`:

| Field | Description |
|-------|-------------|
| `status` | `available`, `unavailable` or `invalid` |
| `postcode`, `country` | Normalized values |
| `area_id`, `city`, `state` | The matching area, if any |
| `days_min`, `days_max`, `shipping_fee` | Area values, or null |
| `cod` | `{ allowed, fee }` |
| `nearby` | Served areas that start the same way (unavailable results only) |
| `estimate` | `{ min, max, label }`, or null |
| `messages` | `headline`, `estimate`, `shipping`, `cod` text lines |
| `can_add` | Whether Add to cart should stay usable |

**Rate limit:** 60 requests per minute per client IP. Over the limit returns HTTP 429 with a `Retry-After: 60` header. The response header is `Cache-Control: private, max-age=60`.

Change the limit with the `wbpc_rate_limit` filter. Behind a proxy or CDN, restore the real visitor IP with `wbpc_client_ip`.

```bash
curl "https://example.com/wp-json/wbpc/v1/check?postcode=110001&country=IN"
```

## Admin routes

All admin routes require the `manage_woocommerce` capability (changeable with the `wbpc_manage_capability` filter). Use cookie authentication with an `X-WP-Nonce` header (`wp_rest` nonce), or application passwords.

### Areas

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/areas` | List. Filters: `search`, `type`, `status`, `cod`, `country`. Also `orderby` (`newest`, `code`, `city`, `days`, `updated`), `order`, `page`, `per_page` (max 100, default 25). Headers `X-WP-Total` and `X-WP-TotalPages`. |
| POST | `/areas` | Create. JSON body. |
| GET | `/areas/{id}` | Read one |
| PATCH | `/areas/{id}` | Update (also POST or PUT) |
| DELETE | `/areas/{id}` | Delete one |
| POST | `/areas/batch-delete` | Body `{ "ids": [1,2] }`, or `{ "all": true }` plus the list filters |

Area fields: `code`, `code_to`, `country`, `status` (`serviceable` or `blocked`), `city`, `state`, `days_min`, `days_max`, `shipping_fee`, `cod_allowed`, `cod_fee`, `note`. Delivery days are 0 to 365. `type` is returned as `exact`, `prefix` or `range`.

### Import

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/import` | Current job and history |
| POST | `/import` | Multipart upload, field `file`, plus `mode` (`skip`, `update`, `replace`) and `country`. Returns a 20-row preview. |
| POST | `/import/start` | Start the staged job |
| POST | `/import/cancel` | Cancel the job |

### Tools

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/tools/test` | Body `postcode`, optional `country`. Returns `result` and the deciding `area`. |
