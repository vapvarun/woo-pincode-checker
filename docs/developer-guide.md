# Developer Guide - Pincode Checker for WooCommerce

Technical documentation for developers integrating with or extending the Pincode Checker plugin.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Core Classes](#core-classes)
3. [Database Structure](#database-structure)
4. [Hooks & Filters](#hooks--filters)
5. [AJAX Endpoints](#ajax-endpoints)
6. [Frontend Integration](#frontend-integration)
7. [Custom Extensions](#custom-extensions)
8. [Testing](#testing)

---

## Architecture Overview

### Plugin Structure

```
pincode-checker-for-woocommerce/
├── admin/                          # Admin-specific functionality
│   ├── class-woo-pincode-checker-admin.php
│   ├── partials/                   # Admin view templates
│   │   ├── woo-pincode-checker-admin-display.php
│   │   ├── woo-pincode-category-rules-display.php
│   │   ├── woo-pincode-geocoding-display.php
│   │   ├── woo-pincode-bulk-add.php
│   │   └── ...
│   ├── css/                        # Admin styles
│   └── js/                         # Admin scripts
├── includes/                       # Core plugin classes
│   ├── class-woo-pincode-checker.php
│   ├── class-woo-pincode-checker-loader.php
│   ├── class-woo-pincode-checker-activator.php
│   ├── class-woo-pincode-checker-functions.php
│   ├── class-woo-pincode-pattern-handler.php
│   ├── class-woo-pincode-category-rules.php
│   └── class-woo-pincode-nearby-suggestions.php
├── public/                         # Frontend functionality
│   ├── class-woo-pincode-checker-public.php
│   ├── class-woo-pincode-checker-form.php
│   ├── css/                        # Frontend styles
│   └── js/                         # Frontend scripts
├── languages/                      # Translation files
└── pincode-checker-for-woocommerce.php  # Main plugin file
```

### Design Patterns

1. **MVC Pattern:**
   - **Model:** Database interactions via `$wpdb`
   - **View:** Template files in `admin/partials/` and public output
   - **Controller:** Admin and Public classes

2. **Hook-Based Architecture:**
   - WordPress actions and filters throughout
   - Extensibility via custom hooks
   - Event-driven AJAX handling

3. **Object-Oriented Design:**
   - Class-based structure
   - Single responsibility principle
   - Dependency injection where applicable

### Plugin Initialization Flow

```
1. pincode-checker-for-woocommerce.php (main file)
   ↓
2. Check dependencies (WordPress, WooCommerce)
   ↓
3. Define constants (paths, version)
   ↓
4. Activation/Deactivation hooks
   ↓
5. Load Woo_Pincode_Checker class
   ↓
6. Initialize loader (Woo_Pincode_Checker_Loader)
   ↓
7. Load core classes (includes/)
   ↓
8. Load admin classes (if is_admin())
   ↓
9. Load public classes (frontend)
   ↓
10. Register all hooks via loader
    ↓
11. Run the plugin
```

---

## Core Classes

### 1. Woo_Pincode_Checker

**Location:** `includes/class-woo-pincode-checker.php`

**Purpose:** Main plugin class that orchestrates all components.

**Key Methods:**

```php
public function __construct()
// Initialize plugin, load dependencies

private function load_dependencies()
// Require all class files

private function set_locale()
// Initialize internationalization

private function define_admin_hooks()
// Register admin-specific hooks

private function define_public_hooks()
// Register frontend hooks

public function run()
// Execute the loader
```

**Usage Example:**

```php
// Access global plugin instance
global $woo_pincode_checker;

// Plugin version
$version = $woo_pincode_checker->get_version();

// Plugin name
$name = $woo_pincode_checker->get_plugin_name();
```

---

### 2. Woo_Pincode_Checker_Admin

**Location:** `admin/class-woo-pincode-checker-admin.php`

**Purpose:** Handles all admin functionality, settings, menus, and bulk operations.

**Key Methods:**

```php
public function enqueue_styles()
// Enqueue admin CSS

public function enqueue_scripts()
// Enqueue admin JavaScript

public function wpc_admin_menu()
// Register admin menu pages

public function wpc_general_settings_sanitize($input)
// Sanitize and validate settings before saving

public function wpc_csv_upload_process()
// Handle CSV file import with pattern support

public function wpc_preview_pattern()
// AJAX: Preview pincode count for pattern

public function wpc_geocode_batch()
// AJAX: Process geocoding in batches
```

**Usage Example:**

```php
// Get admin instance
$admin = new Woo_Pincode_Checker_Admin('pincode-checker', '1.5.0');

// Validate pincode format
$is_valid = $admin->validate_pincode('110001');

// Add pincode programmatically
global $wpdb;
$table_name = $wpdb->prefix . 'pincode_checker';

$wpdb->insert($table_name, array(
    'pincode'          => '110001',
    'city'             => 'New Delhi',
    'state'            => 'Delhi',
    'delivery_days'    => 3,
    'shipping_amount'  => 50.00,
    'case_on_delivery' => 1,
    'cod_amount'       => 5000.00
), array('%s', '%s', '%s', '%d', '%f', '%d', '%f'));
```

---

### 3. Woo_Pincode_Checker_Form

**Location:** `public/class-woo-pincode-checker-form.php`

**Purpose:** Handles frontend pincode checking functionality and AJAX requests.

**Key Methods:**

```php
public function wpc_picode_check_ajax_submit()
// AJAX: Process pincode check request

private function validate_pincode($pincode)
// Validate pincode format

private function check_rate_limit()
// Prevent abuse with rate limiting

public function display_pincode_form()
// Output frontend pincode checker HTML
```

**Usage Example:**

```php
// Manually trigger pincode check
$form = new Woo_Pincode_Checker_Form('pincode-checker', '1.5.0');

// Check if pincode is serviceable
global $wpdb;
$table_name = $wpdb->prefix . 'pincode_checker';
$result = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE pincode = %s",
    '110001'
));

if ($result) {
    echo "Available - Delivery in {$result->delivery_days} days";
} else {
    echo "Not serviceable";
}
```

---

### 4. Woo_Pincode_Pattern_Handler

**Location:** `includes/class-woo-pincode-pattern-handler.php`

**Purpose:** Parse and expand pincode patterns with wildcards and ranges.

**Key Methods:**

```php
public function parse_pattern($pattern)
// Parse pattern and return array of pincodes

public function preview_count($pattern)
// Get count without generating all pincodes

public function validate_pattern($pattern)
// Validate pattern syntax

private function expand_wildcards($pattern)
// Expand * and ? wildcards

private function expand_range($range)
// Expand START-END range
```

**Usage Example:**

```php
$pattern_handler = new Woo_Pincode_Pattern_Handler();

// Expand pattern
$pincodes = $pattern_handler->parse_pattern('1100*');
// Returns: ['11000', '11001', '11002', ..., '11009']

// Expand range
$pincodes = $pattern_handler->parse_pattern('110001-110005');
// Returns: ['110001', '110002', '110003', '110004', '110005']

// Preview count before generating
$count = $pattern_handler->preview_count('11***');
// Returns: 1000 (without generating all pincodes)

// Validate pattern
$is_valid = $pattern_handler->validate_pattern('1100*');
// Returns: true

$is_valid = $pattern_handler->validate_pattern('ABC*');
// Returns: WP_Error object
```

**Pattern Syntax:**

- `*` - Matches any number of digits (0-9)
- `?` - Matches exactly one digit (0-9)
- `START-END` - Range from START to END (inclusive)

**Examples:**

```php
'110*'      // 1100, 1101, 1102, ..., 1109 (10 pincodes)
'11**'      // 1100 through 1199 (100 pincodes)
'110?5'     // 11005, 11015, 11025, ..., 11095 (10 pincodes)
'11??1'     // 11001, 11011, 11021, ..., 11991 (100 pincodes)
'110001-110010'  // 110001 through 110010 (10 pincodes)
```

---

### 5. Woo_Pincode_Category_Rules

**Location:** `includes/class-woo-pincode-category-rules.php`

**Purpose:** Handle category-specific delivery time rules.

**Key Methods:**

```php
public function get_product_delivery_info($product_id, $pincode)
// Get delivery info for product considering category rules

public function get_product_categories($product_id)
// Get all categories for a product

public function get_global_category_rules()
// Get all active category rules from settings

public function get_all_product_categories()
// Get all WooCommerce product categories with product counts
```

**Usage Example:**

```php
$category_rules = new Woo_Pincode_Category_Rules();

// Get delivery info for product
$delivery_info = $category_rules->get_product_delivery_info(123, '110001');
/*
Returns:
array(
    'delivery_days' => 5,
    'source' => 'category_rule', // or 'pincode' or 'default'
    'category_id' => 15,
    'category_name' => 'Electronics'
)
*/

// Get product categories
$categories = $category_rules->get_product_categories(123);
// Returns: [15, 22, 8] (category IDs)

// Get all category rules
$rules = $category_rules->get_global_category_rules();
/*
Returns:
array(
    15 => 5,  // Electronics: 5 days
    22 => 1,  // Groceries: 1 day
)
*/
```

**Priority Logic:**

1. **Category Rule** (highest priority) - If product belongs to category with rule
2. **Pincode Setting** - Delivery days set for specific pincode
3. **Default Setting** (lowest priority) - From General Settings

---

### 6. Woo_Pincode_Nearby_Suggestions

**Location:** `includes/class-woo-pincode-nearby-suggestions.php`

**Purpose:** Geocoding and finding nearby serviceable pincodes.

**Key Methods:**

```php
public function geocode_pincode_nominatim($pincode)
// Geocode pincode using OpenStreetMap Nominatim

public function geocode_pincode_with_details($pincode)
// Geocode and extract city/state information

public function find_nearby($pincode, $options = array())
// Find nearby serviceable pincodes

public function find_nearby_by_distance($pincode, $radius_km = 20, $limit = 5)
// Find pincodes within radius using coordinates

public function find_nearby_by_prefix($pincode, $limit = 5)
// Find pincodes with similar prefix (fallback)

public function geocode_batch($batch_size = 50)
// Geocode multiple pincodes (AJAX batch processing)

public function get_geocoding_stats()
// Get statistics on geocoding progress

private function calculate_distance($lat1, $lon1, $lat2, $lon2)
// Calculate distance between two coordinates (Haversine formula)
```

**Usage Example:**

```php
$nearby_handler = new Woo_Pincode_Nearby_Suggestions();

// Geocode single pincode
$coords = $nearby_handler->geocode_pincode_nominatim('110001');
/*
Returns:
array(
    'latitude' => 28.6139298,
    'longitude' => 77.2089908
)
*/

// Geocode with full details
$details = $nearby_handler->geocode_pincode_with_details('110001');
/*
Returns:
array(
    'latitude' => 28.6139298,
    'longitude' => 77.2089908,
    'city' => 'New Delhi',
    'state' => 'Delhi',
    'country' => 'India'
)
*/

// Find nearby pincodes
$nearby = $nearby_handler->find_nearby('110001', array(
    'radius_km' => 20,
    'limit' => 5,
    'method' => 'auto' // 'auto', 'distance', or 'prefix'
));
/*
Returns:
array(
    array(
        'pincode' => '110002',
        'city' => 'New Delhi',
        'state' => 'Delhi',
        'delivery_days' => 3,
        'distance_km' => 2.3
    ),
    array(
        'pincode' => '110003',
        'city' => 'New Delhi',
        'state' => 'Delhi',
        'delivery_days' => 3,
        'distance_km' => 3.7
    ),
    // ... up to 5 results
)
*/

// Get geocoding stats
$stats = $nearby_handler->get_geocoding_stats();
/*
Returns:
array(
    'total' => 1000,
    'geocoded' => 750,
    'remaining' => 250,
    'percentage' => 75.0
)
*/

// Batch geocoding (for AJAX)
$result = $nearby_handler->geocode_batch(50);
/*
Returns:
array(
    'processed' => 50,
    'remaining' => 200,
    'completed' => false
)
*/
```

**Geocoding Rate Limits:**

- OpenStreetMap Nominatim: 1 request per second
- Automatically handled with sleep()
- Transient-based rate limiting

**Distance Calculation:**

Uses Haversine formula for accurate distance:

```php
$distance_km = $nearby_handler->calculate_distance(
    28.6139298,  // lat1
    77.2089908,  // lon1
    28.6304203,  // lat2
    77.2177326   // lon2
);
// Returns: 2.34 (km)
```

---

## Database Structure

### Table: `wp_pincode_checker`

**Schema:**

```sql
CREATE TABLE wp_pincode_checker (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    pincode varchar(10) NOT NULL,
    city varchar(100) NOT NULL,
    state varchar(100) NOT NULL,
    delivery_days int(11) NOT NULL DEFAULT 7,
    case_on_delivery tinyint(1) NOT NULL DEFAULT 1,
    shipping_amount decimal(10,2) NOT NULL DEFAULT 0.00,
    cod_amount decimal(10,2) NOT NULL DEFAULT 0.00,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    category_rules TEXT NULL DEFAULT NULL,
    latitude DECIMAL(10,8) NULL DEFAULT NULL,
    longitude DECIMAL(11,8) NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_pincode (pincode),
    KEY idx_city (city),
    KEY idx_state (state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns:**

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `pincode` | varchar(10) | Unique pincode/ZIP code |
| `city` | varchar(100) | City/district name |
| `state` | varchar(100) | State/province name |
| `delivery_days` | int | Number of days for delivery |
| `case_on_delivery` | tinyint | COD enabled (1=Yes, 0=No) |
| `shipping_amount` | decimal | Shipping cost |
| `cod_amount` | decimal | Max COD order value (0=unlimited) |
| `created_at` | datetime | Record creation timestamp |
| `updated_at` | datetime | Last update timestamp |
| `category_rules` | text | JSON for pincode-specific category overrides (future use) |
| `latitude` | decimal | Geocoded latitude |
| `longitude` | decimal | Geocoded longitude |

**Indexes:**

- `PRIMARY KEY (id)` - Fast lookups by ID
- `UNIQUE KEY (pincode)` - Prevent duplicates, fast pincode checks
- `KEY idx_city (city)` - Fast city-based searches
- `KEY idx_state (state)` - Fast state-based searches

**Query Examples:**

```php
global $wpdb;
$table_name = $wpdb->prefix . 'pincode_checker';

// Check if pincode exists
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s",
    '110001'
));

// Get pincode details
$pincode_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE pincode = %s",
    '110001'
));

// Get all pincodes in a city
$city_pincodes = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE city = %s ORDER BY pincode",
    'New Delhi'
));

// Get pincodes with coordinates (geocoded)
$geocoded = $wpdb->get_results(
    "SELECT * FROM {$table_name} WHERE latitude IS NOT NULL AND longitude IS NOT NULL"
);

// Insert new pincode
$wpdb->insert($table_name, array(
    'pincode' => '110001',
    'city' => 'New Delhi',
    'state' => 'Delhi',
    'delivery_days' => 3,
    'shipping_amount' => 50.00,
    'case_on_delivery' => 1,
    'cod_amount' => 5000.00
), array('%s', '%s', '%s', '%d', '%f', '%d', '%f'));

// Update pincode
$wpdb->update($table_name,
    array('delivery_days' => 5),
    array('pincode' => '110001'),
    array('%d'),
    array('%s')
);

// Delete pincode
$wpdb->delete($table_name,
    array('pincode' => '110001'),
    array('%s')
);
```

---

## Hooks & Filters

### Actions

#### `wpc_pincode_checked`

**Triggered:** After successful pincode check

**Parameters:**
- `$pincode` (string) - The checked pincode
- `$result` (array) - Check result data

**Usage:**

```php
add_action('wpc_pincode_checked', function($pincode, $result) {
    if ($result['success']) {
        // Log successful checks
        error_log("Pincode {$pincode} checked successfully");

        // Send to analytics
        // ga_track_event('pincode_check', 'success', $pincode);
    }
}, 10, 2);
```

#### `wpc_pincode_added`

**Triggered:** After new pincode is added

**Parameters:**
- `$pincode_id` (int) - Database ID of new pincode
- `$pincode_data` (array) - Pincode details

**Usage:**

```php
add_action('wpc_pincode_added', function($pincode_id, $pincode_data) {
    // Notify admin of new service area
    $message = "New pincode added: {$pincode_data['pincode']} - {$pincode_data['city']}";
    wp_mail(get_option('admin_email'), 'New Service Area', $message);
}, 10, 2);
```

#### `wpc_before_csv_import`

**Triggered:** Before CSV import starts

**Parameters:**
- `$file_path` (string) - Path to CSV file

**Usage:**

```php
add_action('wpc_before_csv_import', function($file_path) {
    // Backup current database
    // backup_pincode_database();

    error_log("Starting CSV import from: {$file_path}");
});
```

#### `wpc_after_csv_import`

**Triggered:** After CSV import completes

**Parameters:**
- `$stats` (array) - Import statistics

**Usage:**

```php
add_action('wpc_after_csv_import', function($stats) {
    $message = sprintf(
        "CSV Import Complete:\nImported: %d\nSkipped: %d\nErrors: %d",
        $stats['imported'],
        $stats['skipped'],
        $stats['errors']
    );

    wp_mail(get_option('admin_email'), 'CSV Import Complete', $message);
}, 10, 1);
```

### Filters

#### `wpc_pincode_check_result`

**Purpose:** Modify pincode check result before sending to frontend

**Parameters:**
- `$result` (array) - Original result
- `$pincode` (string) - Checked pincode

**Returns:** Modified result array

**Usage:**

```php
add_filter('wpc_pincode_check_result', function($result, $pincode) {
    if ($result['success']) {
        // Add custom data
        $result['custom_message'] = "Free shipping for orders over $50!";
        $result['show_promo'] = true;
    }

    return $result;
}, 10, 2);
```

#### `wpc_delivery_days`

**Purpose:** Modify delivery days calculation

**Parameters:**
- `$delivery_days` (int) - Calculated days
- `$pincode` (string) - Pincode being checked
- `$product_id` (int) - Product ID (if applicable)

**Returns:** Modified delivery days

**Usage:**

```php
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    // Add 2 days for pre-order products
    $product = wc_get_product($product_id);
    if ($product && $product->is_on_backorder()) {
        $delivery_days += 2;
    }

    // Weekend adjustment - add extra day if delivery falls on weekend
    $delivery_date = date('N', strtotime("+{$delivery_days} days"));
    if ($delivery_date >= 6) { // Saturday or Sunday
        $delivery_days += 2;
    }

    return $delivery_days;
}, 10, 3);
```

#### `wpc_nearby_radius`

**Purpose:** Customize nearby pincode search radius

**Parameters:**
- `$radius_km` (float) - Default radius (20km)
- `$pincode` (string) - Search center pincode

**Returns:** Modified radius in kilometers

**Usage:**

```php
add_filter('wpc_nearby_radius', function($radius_km, $pincode) {
    // Larger radius for rural areas (starting with 1-5)
    if (preg_match('/^[1-5]/', $pincode)) {
        return 50; // 50km for rural
    }

    return 10; // 10km for urban
}, 10, 2);
```

#### `wpc_nearby_limit`

**Purpose:** Customize number of nearby suggestions

**Parameters:**
- `$limit` (int) - Default limit (5)
- `$pincode` (string) - Search center pincode

**Returns:** Modified limit

**Usage:**

```php
add_filter('wpc_nearby_limit', function($limit, $pincode) {
    // Show more suggestions for metro cities
    $metro_prefixes = ['110', '400', '560', '700'];
    $prefix = substr($pincode, 0, 3);

    if (in_array($prefix, $metro_prefixes)) {
        return 10; // Show 10 for metros
    }

    return 5; // Default
}, 10, 2);
```

#### `wpc_success_message`

**Purpose:** Customize success message

**Parameters:**
- `$message` (string) - Default message
- `$pincode_data` (array) - Pincode details
- `$product_id` (int) - Product ID

**Returns:** Modified message

**Usage:**

```php
add_filter('wpc_success_message', function($message, $pincode_data, $product_id) {
    // Add tracking info
    $message .= sprintf(
        '<br><small>Track your order at: <a href="%s">Track Now</a></small>',
        home_url('/track-order')
    );

    // Add COD info if enabled
    if ($pincode_data['case_on_delivery']) {
        $cod_limit = $pincode_data['cod_amount'] > 0
            ? 'up to ₹' . number_format($pincode_data['cod_amount'])
            : 'available';
        $message .= "<br><strong>Cash on Delivery {$cod_limit}</strong>";
    }

    return $message;
}, 10, 3);
```

#### `wpc_error_message`

**Purpose:** Customize error message for non-serviceable pincode

**Parameters:**
- `$message` (string) - Default error message
- `$pincode` (string) - Checked pincode

**Returns:** Modified message

**Usage:**

```php
add_filter('wpc_error_message', function($message, $pincode) {
    // Add contact info
    $message .= sprintf(
        '<br><small>Want delivery here? <a href="%s">Request Service Area</a></small>',
        home_url('/request-service?pincode=' . $pincode)
    );

    return $message;
}, 10, 2);
```

#### `wpc_validate_pincode_format`

**Purpose:** Custom pincode format validation

**Parameters:**
- `$is_valid` (bool) - Default validation result
- `$pincode` (string) - Pincode to validate

**Returns:** bool

**Usage:**

```php
add_filter('wpc_validate_pincode_format', function($is_valid, $pincode) {
    // Custom format: 6 digits, starts with 1-9
    if (preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
        return true;
    }

    // US ZIP code format
    if (preg_match('/^\d{5}(-\d{4})?$/', $pincode)) {
        return true;
    }

    return false;
}, 10, 2);
```

---

## AJAX Endpoints

### Frontend Endpoints

#### `wpc_picode_check_ajax_submit`

**Purpose:** Check pincode availability

**Method:** POST

**Parameters:**
- `pin_code` (string) - Pincode to check
- `product_id` (int) - Product ID
- `nonce` (string) - Security nonce

**Response:**

```json
{
    "success": true,
    "data": {
        "message": "Delivery available...",
        "pincode": "110001",
        "city": "New Delhi",
        "state": "Delhi",
        "delivery_date": "Jan 15th",
        "delivery_days": 3,
        "shipping_amount": 50.00,
        "cod_available": true
    }
}
```

**Error Response:**

```json
{
    "success": false,
    "data": {
        "message": "Sorry! We are currently not servicing your area.",
        "pincode": "999999",
        "nearby_html": "<div>...</div>",
        "has_nearby": true,
        "nearby_count": 3
    }
}
```

**JavaScript Usage:**

```javascript
jQuery.ajax({
    url: pincode_check.ajaxurl,
    type: 'POST',
    data: {
        action: 'wpc_picode_check_ajax_submit',
        pin_code: '110001',
        product_id: 123,
        nonce: pincode_check.wpc_nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Available:', response.data);
        } else {
            console.log('Not available:', response.data);
        }
    }
});
```

### Admin Endpoints

#### `wpc_preview_pattern`

**Purpose:** Preview count for pincode pattern

**Method:** POST

**Parameters:**
- `pattern` (string) - Pattern to preview
- `nonce` (string) - Security nonce

**Response:**

```json
{
    "success": true,
    "data": {
        "count": 1000
    }
}
```

**JavaScript Usage:**

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'wpc_preview_pattern',
        pattern: '11***',
        nonce: wpc_nonces.preview
    },
    success: function(response) {
        if (response.success) {
            alert('Will create ' + response.data.count + ' pincodes');
        }
    }
});
```

#### `wpc_geocode_batch`

**Purpose:** Geocode pincodes in batches

**Method:** POST

**Parameters:**
- `nonce` (string) - Security nonce

**Response:**

```json
{
    "success": true,
    "data": {
        "processed": 50,
        "remaining": 200,
        "completed": false
    }
}
```

**JavaScript Usage:**

```javascript
function geocodeBatch() {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'wpc_geocode_batch',
            nonce: wpc_nonces.geocode
        },
        success: function(response) {
            if (response.success) {
                var data = response.data;
                var percentage = Math.round((data.processed / (data.processed + data.remaining)) * 100);

                updateProgressBar(percentage);

                if (!data.completed) {
                    // Continue geocoding
                    setTimeout(geocodeBatch, 1000);
                } else {
                    alert('Geocoding completed!');
                }
            }
        }
    });
}
```

---

## Frontend Integration

### JavaScript API

#### Events

**`wpc_pincode_checked`**

Triggered after pincode check (success or failure)

```javascript
jQuery(document).on('wpc_pincode_checked', function(event, data) {
    console.log('Pincode:', data.pincode);
    console.log('Success:', data.success);

    if (data.success) {
        console.log('Delivery days:', data.delivery_days);
        console.log('City:', data.city);

        // Your custom code
        // e.g., show custom popup, update cart, etc.
    } else {
        console.log('Error:', data.error);
        console.log('Nearby suggestions:', data.nearby_suggestions);
    }
});
```

#### Methods

**Check Pincode Programmatically:**

```javascript
var pincodeChecker = {
    check: function(pincode, productId) {
        return jQuery.ajax({
            url: pincode_check.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpc_picode_check_ajax_submit',
                pin_code: pincode,
                product_id: productId || 0,
                nonce: pincode_check.wpc_nonce
            }
        });
    }
};

// Usage
pincodeChecker.check('110001', 123).done(function(response) {
    if (response.success) {
        alert('Delivery available in ' + response.data.delivery_days + ' days');
    } else {
        alert('Not serviceable');
    }
});
```

### Custom Form Integration

Add pincode checker to custom location:

```php
<?php
// In your theme template
if (function_exists('wpc_display_pincode_checker')) {
    wpc_display_pincode_checker();
}
?>
```

Or manually:

```html
<div class="custom-pincode-checker">
    <label>Check Delivery:</label>
    <input type="text" id="custom_pincode" maxlength="10" placeholder="Enter pincode">
    <button id="custom_check_btn">Check</button>
    <div id="custom_result"></div>
</div>

<script>
jQuery('#custom_check_btn').on('click', function() {
    var pincode = jQuery('#custom_pincode').val();
    var productId = <?php echo get_the_ID(); ?>;

    jQuery.ajax({
        url: pincode_check.ajaxurl,
        type: 'POST',
        data: {
            action: 'wpc_picode_check_ajax_submit',
            pin_code: pincode,
            product_id: productId,
            nonce: pincode_check.wpc_nonce
        },
        success: function(response) {
            if (response.success) {
                jQuery('#custom_result')
                    .html(response.data.message)
                    .css('color', 'green');
            } else {
                jQuery('#custom_result')
                    .html(response.data.message + (response.data.nearby_html || ''))
                    .css('color', 'red');
            }
        }
    });
});
</script>
```

---

## Custom Extensions

### Example 1: SMS Notification for New Service Areas

```php
/**
 * Send SMS when new pincode is added
 */
add_action('wpc_pincode_added', function($pincode_id, $pincode_data) {
    $message = "New delivery area: {$pincode_data['city']} - {$pincode_data['pincode']}";

    // Your SMS API integration
    send_sms_notification($message);
}, 10, 2);
```

### Example 2: Dynamic Delivery Dates (Exclude Weekends)

```php
/**
 * Exclude weekends from delivery calculation
 */
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    $current_date = time();
    $business_days = 0;
    $days_added = 0;

    while ($business_days < $delivery_days) {
        $days_added++;
        $check_date = strtotime("+{$days_added} days", $current_date);
        $day_of_week = date('N', $check_date); // 1=Mon, 7=Sun

        // Skip weekends
        if ($day_of_week < 6) {
            $business_days++;
        }
    }

    return $days_added;
}, 10, 3);
```

### Example 3: Integration with External Shipping API

```php
/**
 * Fetch shipping cost from external API
 */
add_filter('wpc_pincode_check_result', function($result, $pincode) {
    if ($result['success']) {
        // Call external shipping API
        $api_response = wp_remote_get("https://shipping-api.com/rate?pincode={$pincode}");

        if (!is_wp_error($api_response)) {
            $body = json_decode(wp_remote_retrieve_body($api_response), true);

            if (isset($body['rate'])) {
                $result['shipping_amount'] = $body['rate'];
                $result['message'] = str_replace(
                    '[shipping_amount]',
                    wc_price($body['rate']),
                    $result['message']
                );
            }
        }
    }

    return $result;
}, 10, 2);
```

### Example 4: Holiday Adjustments

```php
/**
 * Add extra days during holiday season
 */
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    $current_month = date('n');

    // Add 3 days during December (holiday rush)
    if ($current_month == 12) {
        $delivery_days += 3;
    }

    // Check for specific holidays
    $holidays = ['2025-01-26', '2025-08-15', '2025-10-02']; // Indian holidays
    $delivery_date = date('Y-m-d', strtotime("+{$delivery_days} days"));

    if (in_array($delivery_date, $holidays)) {
        $delivery_days += 1; // Add one day if delivery falls on holiday
    }

    return $delivery_days;
}, 10, 3);
```

### Example 5: Product Weight-Based Delivery

```php
/**
 * Adjust delivery based on product weight
 */
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    $product = wc_get_product($product_id);

    if ($product) {
        $weight = floatval($product->get_weight());

        // Heavy items take longer
        if ($weight > 50) { // >50 kg
            $delivery_days += 5;
        } elseif ($weight > 20) { // >20 kg
            $delivery_days += 2;
        }
    }

    return $delivery_days;
}, 10, 3);
```

---

## Testing

### Unit Testing Setup

```php
/**
 * Test Pattern Handler
 */
class Test_Pattern_Handler extends WP_UnitTestCase {

    public function test_wildcard_expansion() {
        $handler = new Woo_Pincode_Pattern_Handler();
        $result = $handler->parse_pattern('1100*');

        $this->assertCount(10, $result);
        $this->assertContains('11000', $result);
        $this->assertContains('11009', $result);
    }

    public function test_range_expansion() {
        $handler = new Woo_Pincode_Pattern_Handler();
        $result = $handler->parse_pattern('110001-110005');

        $this->assertCount(5, $result);
        $this->assertEquals(['110001', '110002', '110003', '110004', '110005'], $result);
    }

    public function test_preview_count() {
        $handler = new Woo_Pincode_Pattern_Handler();
        $count = $handler->preview_count('11***');

        $this->assertEquals(1000, $count);
    }
}
```

### Manual Testing Checklist

**Frontend Testing:**
- [ ] Pincode checker displays on product page
- [ ] Valid pincode shows success message
- [ ] Invalid pincode shows error message
- [ ] Nearby suggestions appear when available
- [ ] COD information displays correctly
- [ ] Delivery date calculates correctly
- [ ] Mobile responsive design works

**Admin Testing:**
- [ ] Add single pincode works
- [ ] Bulk add by pattern works
- [ ] CSV import processes correctly
- [ ] Category rules apply properly
- [ ] Geocoding completes successfully
- [ ] Search and filter work in All Pincodes
- [ ] Settings save correctly

**Edge Cases:**
- [ ] Very large pincode database (100,000+)
- [ ] Invalid CSV format handling
- [ ] Pattern with no results
- [ ] Product in multiple categories
- [ ] Missing WooCommerce scenario
- [ ] Rate limit handling

---

## Performance Considerations

### Database Optimization

```sql
-- Add indexes for better performance
ALTER TABLE wp_pincode_checker ADD INDEX idx_lat_lng (latitude, longitude);
ALTER TABLE wp_pincode_checker ADD INDEX idx_delivery (delivery_days);
```

### Caching Strategy

```php
/**
 * Cache pincode check results
 */
add_filter('wpc_pincode_check_result', function($result, $pincode) {
    if ($result['success']) {
        // Cache for 1 hour
        set_transient('wpc_check_' . $pincode, $result, HOUR_IN_SECONDS);
    }
    return $result;
}, 10, 2);

/**
 * Use cached result if available
 */
add_action('wpc_before_pincode_check', function($pincode) {
    $cached = get_transient('wpc_check_' . $pincode);
    if ($cached !== false) {
        wp_send_json_success($cached);
        exit;
    }
});
```

### Load Optimization

```php
/**
 * Only load on product pages
 */
add_filter('wpc_load_scripts', function($load) {
    if (!is_product() && !is_checkout()) {
        return false;
    }
    return $load;
});
```

---

## Security Best Practices

### Input Sanitization

Always sanitize user input:

```php
// Good
$pincode = sanitize_text_field(wp_unslash($_POST['pincode']));

// Bad
$pincode = $_POST['pincode'];
```

### Nonce Verification

Always verify nonces in AJAX:

```php
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpc-action')) {
    wp_send_json_error('Invalid nonce');
    exit;
}
```

### Capability Checks

Verify user capabilities for admin actions:

```php
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}
```

### SQL Injection Prevention

Always use prepared statements:

```php
// Good
$wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE pincode = %s",
    $pincode
));

// Bad
$wpdb->get_row("SELECT * FROM {$table_name} WHERE pincode = '{$pincode}'");
```

---

## Contributing

### Code Standards

- Follow WordPress Coding Standards
- Use PHP 7.4+ features
- Add PHPDoc blocks for all functions
- Use meaningful variable names
- Comment complex logic

### Submitting Changes

1. Fork the repository
2. Create feature branch
3. Make your changes
4. Test thoroughly
5. Submit pull request

---

**Additional Resources:**

- [Hooks & Filters Reference](hooks-filters.md) - Complete API reference
- [Database Schema](database-schema.md) - Detailed database documentation
- [Customer Guide](customer-guide.md) - User documentation

**Support:**
- Plugin URI: https://wbcomdesigns.com/downloads/pincode-checker-for-woocommerce/
- Documentation: https://wbcomdesigns.com/docs/pincode-checker/
