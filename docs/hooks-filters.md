# Hooks & Filters Reference

Complete API reference for all actions and filters available in Pincode Checker for WooCommerce.

## Table of Contents

- [Actions](#actions)
- [Filters](#filters)
- [JavaScript Events](#javascript-events)

---

## Actions

### Plugin Lifecycle

#### `wpc_plugin_activated`

Fired when plugin is activated.

**Parameters:** None

**Usage:**
```php
add_action('wpc_plugin_activated', function() {
    // Setup custom tables
    // Initialize default settings
    // Send activation notification
});
```

---

#### `wpc_plugin_deactivated`

Fired when plugin is deactivated.

**Parameters:** None

**Usage:**
```php
add_action('wpc_plugin_deactivated', function() {
    // Cleanup temporary data
    // Send deactivation notification
});
```

---

### Pincode Management

#### `wpc_pincode_added`

Fired after a new pincode is successfully added to the database.

**Parameters:**
- `$pincode_id` (int) - Database ID of the new pincode
- `$pincode_data` (array) - Array containing pincode details

**Pincode Data Structure:**
```php
array(
    'pincode'          => '110001',
    'city'             => 'New Delhi',
    'state'            => 'Delhi',
    'delivery_days'    => 3,
    'shipping_amount'  => 50.00,
    'case_on_delivery' => 1,
    'cod_amount'       => 5000.00
)
```

**Usage:**
```php
add_action('wpc_pincode_added', function($pincode_id, $pincode_data) {
    // Log new pincode
    error_log("New pincode added: {$pincode_data['pincode']}");

    // Send notification
    $admin_email = get_option('admin_email');
    wp_mail(
        $admin_email,
        'New Service Area Added',
        "Pincode {$pincode_data['pincode']} - {$pincode_data['city']} has been added."
    );

    // Update analytics
    // track_service_area_expansion($pincode_data);
}, 10, 2);
```

---

#### `wpc_pincode_updated`

Fired after a pincode is updated in the database.

**Parameters:**
- `$pincode_id` (int) - Database ID
- `$pincode_data` (array) - New pincode details
- `$old_data` (array) - Previous pincode details

**Usage:**
```php
add_action('wpc_pincode_updated', function($pincode_id, $pincode_data, $old_data) {
    // Compare changes
    if ($pincode_data['delivery_days'] != $old_data['delivery_days']) {
        error_log("Delivery time changed for {$pincode_data['pincode']}: " .
                  "{$old_data['delivery_days']} → {$pincode_data['delivery_days']} days");
    }

    // Notify customers in that pincode
    // notify_customers_in_pincode($pincode_data['pincode'], $pincode_data);
}, 10, 3);
```

---

#### `wpc_pincode_deleted`

Fired after a pincode is deleted from the database.

**Parameters:**
- `$pincode` (string) - The deleted pincode
- `$pincode_data` (array) - Pincode data before deletion

**Usage:**
```php
add_action('wpc_pincode_deleted', function($pincode, $pincode_data) {
    // Log deletion
    error_log("Pincode deleted: {$pincode} - {$pincode_data['city']}");

    // Archive data
    // archive_pincode_data($pincode, $pincode_data);

    // Notify admin
    $message = "Service area removed: {$pincode} - {$pincode_data['city']}";
    wp_mail(get_option('admin_email'), 'Service Area Removed', $message);
}, 10, 2);
```

---

#### `wpc_bulk_pincodes_added`

Fired after bulk pincodes are added (pattern/range expansion or CSV import).

**Parameters:**
- `$count` (int) - Number of pincodes added
- `$method` (string) - 'pattern', 'range', or 'csv'
- `$details` (array) - Additional details about the operation

**Usage:**
```php
add_action('wpc_bulk_pincodes_added', function($count, $method, $details) {
    error_log("Bulk add completed: {$count} pincodes via {$method}");

    // Send summary email
    $message = "Bulk operation completed:\n";
    $message .= "Method: {$method}\n";
    $message .= "Pincodes added: {$count}\n";

    if ($method === 'pattern') {
        $message .= "Pattern used: {$details['pattern']}\n";
    }

    wp_mail(get_option('admin_email'), 'Bulk Pincode Addition Complete', $message);
}, 10, 3);
```

---

### Pincode Checking

#### `wpc_before_pincode_check`

Fired before pincode availability check is performed.

**Parameters:**
- `$pincode` (string) - Pincode being checked
- `$product_id` (int) - Product ID (0 if not product-specific)

**Usage:**
```php
add_action('wpc_before_pincode_check', function($pincode, $product_id) {
    // Check cache first
    $cached = get_transient("wpc_check_{$pincode}_{$product_id}");
    if ($cached !== false) {
        wp_send_json_success($cached);
        exit;
    }

    // Track pincode searches
    // analytics_track('pincode_search', $pincode, $product_id);
}, 10, 2);
```

---

#### `wpc_after_pincode_check`

Fired after pincode availability check is performed.

**Parameters:**
- `$result` (array) - Check result (success/failure)
- `$pincode` (string) - Pincode that was checked
- `$product_id` (int) - Product ID

**Usage:**
```php
add_action('wpc_after_pincode_check', function($result, $pincode, $product_id) {
    // Log check result
    $status = $result['success'] ? 'available' : 'not_available';
    error_log("Pincode check: {$pincode} - {$status}");

    // Cache result
    if ($result['success']) {
        set_transient("wpc_check_{$pincode}_{$product_id}", $result, HOUR_IN_SECONDS);
    }

    // Track conversion funnel
    // track_pincode_check($pincode, $status, $product_id);
}, 10, 3);
```

---

#### `wpc_pincode_checked`

Fired after successful or failed pincode check (convenience action).

**Parameters:**
- `$pincode` (string) - Checked pincode
- `$result` (array) - Complete result array

**Usage:**
```php
add_action('wpc_pincode_checked', function($pincode, $result) {
    if ($result['success']) {
        // Successful check
        $delivery_days = $result['delivery_days'];
        // track_available_pincode($pincode, $delivery_days);
    } else {
        // Failed check
        // track_unavailable_pincode($pincode);

        // Store for future expansion planning
        global $wpdb;
        $table = $wpdb->prefix . 'wpc_requested_pincodes';
        $wpdb->insert($table, array(
            'pincode' => $pincode,
            'request_count' => 1,
            'last_request' => current_time('mysql')
        ), array('%s', '%d', '%s'));
    }
}, 10, 2);
```

---

### CSV Operations

#### `wpc_before_csv_import`

Fired before CSV import starts.

**Parameters:**
- `$file_path` (string) - Full path to uploaded CSV file
- `$file_data` (array) - Array with file info (name, size, type)

**Usage:**
```php
add_action('wpc_before_csv_import', function($file_path, $file_data) {
    // Backup current database
    global $wpdb;
    $table = $wpdb->prefix . 'pincode_checker';
    $backup_file = WP_CONTENT_DIR . '/uploads/wpc-backup-' . date('Y-m-d-His') . '.sql';

    $query = "SELECT * FROM {$table}";
    $results = $wpdb->get_results($query, ARRAY_A);
    file_put_contents($backup_file, json_encode($results));

    error_log("Database backed up before CSV import: {$backup_file}");
}, 10, 2);
```

---

#### `wpc_after_csv_import`

Fired after CSV import completes.

**Parameters:**
- `$stats` (array) - Import statistics

**Stats Structure:**
```php
array(
    'imported' => 150,  // Successfully imported
    'skipped'  => 20,   // Already existed
    'errors'   => 5,    // Failed to import
    'total'    => 175   // Total rows processed
)
```

**Usage:**
```php
add_action('wpc_after_csv_import', function($stats) {
    // Send detailed report
    $message = "CSV Import Completed\n\n";
    $message .= "Imported: {$stats['imported']}\n";
    $message .= "Skipped (duplicates): {$stats['skipped']}\n";
    $message .= "Errors: {$stats['errors']}\n";
    $message .= "Total: {$stats['total']}\n";

    $success_rate = round(($stats['imported'] / $stats['total']) * 100, 2);
    $message .= "\nSuccess Rate: {$success_rate}%";

    wp_mail(get_option('admin_email'), 'CSV Import Report', $message);

    // Clear cache after import
    wp_cache_flush_group('woo_pincode_checker');
}, 10, 1);
```

---

### Geocoding

#### `wpc_before_geocoding_batch`

Fired before geocoding batch starts.

**Parameters:**
- `$batch_size` (int) - Number of pincodes in this batch

**Usage:**
```php
add_action('wpc_before_geocoding_batch', function($batch_size) {
    error_log("Starting geocoding batch: {$batch_size} pincodes");

    // Check API rate limits
    $last_request = get_transient('wpc_nominatim_rate_limit');
    if ($last_request && (time() - $last_request) < 1) {
        sleep(1); // Respect 1 req/sec limit
    }
});
```

---

#### `wpc_after_geocoding_batch`

Fired after geocoding batch completes.

**Parameters:**
- `$processed` (int) - Number of pincodes processed in this batch
- `$successful` (int) - Number successfully geocoded
- `$failed` (int) - Number that failed

**Usage:**
```php
add_action('wpc_after_geocoding_batch', function($processed, $successful, $failed) {
    error_log("Geocoding batch complete: {$successful}/{$processed} successful, {$failed} failed");

    // Update progress in option
    $stats = get_option('wpc_geocoding_progress', array());
    $stats['total_processed'] = ($stats['total_processed'] ?? 0) + $processed;
    $stats['total_successful'] = ($stats['total_successful'] ?? 0) + $successful;
    update_option('wpc_geocoding_progress', $stats);
}, 10, 3);
```

---

#### `wpc_pincode_geocoded`

Fired after a single pincode is successfully geocoded.

**Parameters:**
- `$pincode` (string) - Geocoded pincode
- `$coordinates` (array) - Latitude and longitude

**Coordinates Structure:**
```php
array(
    'latitude'  => 28.6139298,
    'longitude' => 77.2089908,
    'city'      => 'New Delhi',  // Optional
    'state'     => 'Delhi'       // Optional
)
```

**Usage:**
```php
add_action('wpc_pincode_geocoded', function($pincode, $coordinates) {
    error_log("Geocoded {$pincode}: {$coordinates['latitude']}, {$coordinates['longitude']}");

    // Store in custom location index
    // add_to_location_index($pincode, $coordinates);
}, 10, 2);
```

---

## Filters

### Pincode Validation

#### `wpc_validate_pincode_format`

Customize pincode format validation logic.

**Parameters:**
- `$is_valid` (bool) - Default validation result
- `$pincode` (string) - Pincode to validate

**Returns:** bool

**Usage:**
```php
add_filter('wpc_validate_pincode_format', function($is_valid, $pincode) {
    // Indian format: 6 digits, starts with 1-9
    if (preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
        return true;
    }

    // US ZIP code: 5 digits or 5+4
    if (preg_match('/^\d{5}(-\d{4})?$/', $pincode)) {
        return true;
    }

    // UK postcode: Various formats
    if (preg_match('/^[A-Z]{1,2}\d{1,2}[A-Z]?\s?\d[A-Z]{2}$/i', $pincode)) {
        return true;
    }

    return false;
}, 10, 2);
```

---

### Delivery Calculation

#### `wpc_delivery_days`

Modify calculated delivery days before displaying to customer.

**Parameters:**
- `$delivery_days` (int) - Calculated delivery days
- `$pincode` (string) - Customer's pincode
- `$product_id` (int) - Product ID (0 if not product-specific)

**Returns:** int

**Usage:**
```php
// Exclude weekends from delivery calculation
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    $current_date = time();
    $business_days = 0;
    $total_days = 0;

    while ($business_days < $delivery_days) {
        $total_days++;
        $check_date = strtotime("+{$total_days} days", $current_date);
        $day_of_week = date('N', $check_date); // 1=Mon, 7=Sun

        // Count only weekdays
        if ($day_of_week < 6) {
            $business_days++;
        }
    }

    return $total_days;
}, 10, 3);

// Adjust for product weight
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    if ($product_id > 0) {
        $product = wc_get_product($product_id);
        $weight = floatval($product->get_weight());

        if ($weight > 50) {
            $delivery_days += 5; // Heavy items
        } elseif ($weight > 20) {
            $delivery_days += 2;
        }
    }

    return $delivery_days;
}, 10, 3);

// Add days for remote areas
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    // Remote areas (e.g., starting with 7, 8, 9)
    if (preg_match('/^[789]/', $pincode)) {
        $delivery_days += 3;
    }

    return $delivery_days;
}, 10, 3);
```

---

#### `wpc_delivery_date_format`

Customize delivery date format.

**Parameters:**
- `$format` (string) - PHP date format string
- `$pincode` (string) - Customer's pincode

**Returns:** string (PHP date format)

**Usage:**
```php
add_filter('wpc_delivery_date_format', function($format, $pincode) {
    // Use different format for international
    if (strlen($pincode) !== 6) {
        return 'Y-m-d'; // ISO format for international
    }

    return 'M jS'; // Jan 15th for domestic
}, 10, 2);
```

---

#### `wpc_exclude_holidays`

Provide list of holidays to exclude from delivery calculation.

**Parameters:**
- `$holidays` (array) - Array of holiday dates (Y-m-d format)

**Returns:** array

**Usage:**
```php
add_filter('wpc_exclude_holidays', function($holidays) {
    // Indian national holidays 2025
    $holidays = array_merge($holidays, [
        '2025-01-26',  // Republic Day
        '2025-03-14',  // Holi
        '2025-08-15',  // Independence Day
        '2025-10-02',  // Gandhi Jayanti
        '2025-10-24',  // Diwali
        '2025-12-25',  // Christmas
    ]);

    return $holidays;
});
```

---

### Messages

#### `wpc_success_message`

Customize success message for available pincode.

**Parameters:**
- `$message` (string) - Default success message
- `$pincode_data` (array) - Pincode details
- `$product_id` (int) - Product ID

**Returns:** string (HTML allowed)

**Usage:**
```php
add_filter('wpc_success_message', function($message, $pincode_data, $product_id) {
    $delivery_date = date('M jS', strtotime("+{$pincode_data['delivery_days']} days"));

    $custom_message = '<div class="wpc-success">';
    $custom_message .= '<strong>✓ Available for delivery!</strong><br>';
    $custom_message .= "Location: {$pincode_data['city']}, {$pincode_data['state']}<br>";
    $custom_message .= "Estimated delivery: <strong>{$delivery_date}</strong><br>";

    if ($pincode_data['shipping_amount'] > 0) {
        $custom_message .= "Shipping: " . wc_price($pincode_data['shipping_amount']) . "<br>";
    } else {
        $custom_message .= "<span class='free-shipping'>FREE Shipping!</span><br>";
    }

    if ($pincode_data['case_on_delivery']) {
        $custom_message .= "💵 Cash on Delivery available";
        if ($pincode_data['cod_amount'] > 0) {
            $custom_message .= " (up to " . wc_price($pincode_data['cod_amount']) . ")";
        }
    }

    $custom_message .= '</div>';

    return $custom_message;
}, 10, 3);
```

---

#### `wpc_error_message`

Customize error message for non-serviceable pincode.

**Parameters:**
- `$message` (string) - Default error message
- `$pincode` (string) - Entered pincode

**Returns:** string (HTML allowed)

**Usage:**
```php
add_filter('wpc_error_message', function($message, $pincode) {
    $custom_message = '<div class="wpc-error">';
    $custom_message .= '<strong>Sorry!</strong> We don\'t deliver to ' . esc_html($pincode) . ' yet.<br>';
    $custom_message .= '<a href="' . home_url('/request-service?pincode=' . $pincode) . '">';
    $custom_message .= 'Request this location</a> and we\'ll notify you when available.';
    $custom_message .= '</div>';

    return $custom_message;
}, 10, 2);
```

---

### Pincode Check Results

#### `wpc_pincode_check_result`

Modify complete pincode check result before sending to frontend.

**Parameters:**
- `$result` (array) - Result array
- `$pincode` (string) - Checked pincode

**Returns:** array

**Result Structure:**
```php
array(
    'success' => true,
    'message' => 'Delivery available...',
    'pincode' => '110001',
    'city' => 'New Delhi',
    'state' => 'Delhi',
    'delivery_date' => 'Jan 15th',
    'delivery_days' => 3,
    'shipping_amount' => 50.00,
    'cod_available' => true
)
```

**Usage:**
```php
add_filter('wpc_pincode_check_result', function($result, $pincode) {
    if ($result['success']) {
        // Add promotional message
        if ($result['shipping_amount'] > 0) {
            $result['promo_message'] = 'Order above ₹500 for FREE shipping!';
        }

        // Add estimated slots
        $result['delivery_slots'] = [
            '09:00 AM - 12:00 PM',
            '12:00 PM - 03:00 PM',
            '03:00 PM - 06:00 PM',
        ];

        // Add courier partner info
        $result['courier'] = 'Express Delivery Inc.';

        // Add carbon footprint
        $distance_km = calculate_distance_from_warehouse($pincode);
        $result['carbon_footprint'] = round($distance_km * 0.21, 2) . ' kg CO₂';
    }

    return $result;
}, 10, 2);
```

---

### Nearby Suggestions

#### `wpc_nearby_radius`

Customize search radius for nearby pincodes.

**Parameters:**
- `$radius_km` (float) - Default radius in kilometers (20)
- `$pincode` (string) - Search center pincode

**Returns:** float

**Usage:**
```php
add_filter('wpc_nearby_radius', function($radius_km, $pincode) {
    // Larger radius for rural areas
    $rural_prefixes = ['1', '2', '3', '4', '5'];
    $prefix = substr($pincode, 0, 1);

    if (in_array($prefix, $rural_prefixes)) {
        return 50; // 50km for rural
    }

    // Metro cities - smaller radius
    $metro_prefixes = ['110', '400', '560', '700', '600'];
    $prefix3 = substr($pincode, 0, 3);

    if (in_array($prefix3, $metro_prefixes)) {
        return 10; // 10km for metros
    }

    return 20; // Default
}, 10, 2);
```

---

#### `wpc_nearby_limit`

Customize number of nearby pincode suggestions to show.

**Parameters:**
- `$limit` (int) - Default limit (5)
- `$pincode` (string) - Search center pincode

**Returns:** int

**Usage:**
```php
add_filter('wpc_nearby_limit', function($limit, $pincode) {
    // Show more for premium customers
    if (is_user_logged_in() && wc_get_customer_total_spent(get_current_user_id()) > 5000) {
        return 10; // Show 10 for premium
    }

    return 5; // Default
}, 10, 2);
```

---

#### `wpc_nearby_suggestions_html`

Customize nearby suggestions HTML output.

**Parameters:**
- `$html` (string) - Default HTML
- `$nearby_pincodes` (array) - Array of nearby pincode data
- `$pincode` (string) - Original searched pincode

**Returns:** string (HTML)

**Usage:**
```php
add_filter('wpc_nearby_suggestions_html', function($html, $nearby_pincodes, $pincode) {
    if (empty($nearby_pincodes)) {
        return $html;
    }

    $custom_html = '<div class="wpc-nearby-custom">';
    $custom_html .= '<h4>Nearby Delivery Locations:</h4>';
    $custom_html .= '<div class="nearby-grid">';

    foreach ($nearby_pincodes as $nearby) {
        $custom_html .= '<div class="nearby-card">';
        $custom_html .= '<div class="pincode">' . esc_html($nearby['pincode']) . '</div>';
        $custom_html .= '<div class="city">' . esc_html($nearby['city']) . '</div>';

        if (isset($nearby['distance_km'])) {
            $custom_html .= '<div class="distance">' . $nearby['distance_km'] . ' km away</div>';
        }

        $custom_html .= '<div class="delivery">' . $nearby['delivery_days'] . ' days</div>';
        $custom_html .= '<button class="use-pincode" data-pincode="' . esc_attr($nearby['pincode']) . '">';
        $custom_html .= 'Use this pincode</button>';
        $custom_html .= '</div>';
    }

    $custom_html .= '</div></div>';

    return $custom_html;
}, 10, 3);
```

---

### Category Rules

#### `wpc_category_rule_priority`

Determine which category rule applies when product is in multiple categories.

**Parameters:**
- `$category_id` (int) - Selected category ID
- `$all_categories` (array) - All category IDs product belongs to
- `$product_id` (int) - Product ID

**Returns:** int (category ID to use)

**Usage:**
```php
add_filter('wpc_category_rule_priority', function($category_id, $all_categories, $product_id) {
    // Priority list
    $priority_categories = [
        15 => 1,  // Electronics - highest priority
        22 => 2,  // Groceries
        8 => 3,   // Clothing
        // etc.
    ];

    $highest_priority = 999;
    $selected_cat = $category_id;

    foreach ($all_categories as $cat_id) {
        if (isset($priority_categories[$cat_id])) {
            if ($priority_categories[$cat_id] < $highest_priority) {
                $highest_priority = $priority_categories[$cat_id];
                $selected_cat = $cat_id;
            }
        }
    }

    return $selected_cat;
}, 10, 3);
```

---

### Admin UI

#### `wpc_admin_pincode_columns`

Customize columns in All Pincodes admin table.

**Parameters:**
- `$columns` (array) - Default columns

**Returns:** array

**Usage:**
```php
add_filter('wpc_admin_pincode_columns', function($columns) {
    // Add custom column
    $columns['coordinates'] = 'Coordinates';
    $columns['last_checked'] = 'Last Checked';

    // Remove a column
    unset($columns['cod_amount']);

    return $columns;
});
```

---

#### `wpc_csv_export_data`

Modify data before CSV export.

**Parameters:**
- `$data` (array) - Array of pincode rows to export

**Returns:** array

**Usage:**
```php
add_filter('wpc_csv_export_data', function($data) {
    // Add calculated column
    foreach ($data as &$row) {
        $row['express_available'] = ($row['delivery_days'] <= 2) ? 'Yes' : 'No';

        // Format currency
        $row['shipping_amount'] = '₹' . number_format($row['shipping_amount'], 2);
    }

    return $data;
});
```

---

### Pattern Expansion

#### `wpc_pattern_max_count`

Set maximum pincodes allowed for a single pattern expansion.

**Parameters:**
- `$max_count` (int) - Default maximum (10000)

**Returns:** int

**Usage:**
```php
add_filter('wpc_pattern_max_count', function($max_count) {
    // Increase for admins
    if (current_user_can('manage_options')) {
        return 50000;
    }

    return 10000; // Default
});
```

---

## JavaScript Events

### `wpc_pincode_checked`

Triggered on document after AJAX pincode check completes.

**Event Data:**
```javascript
{
    pincode: '110001',
    success: true,
    delivery_days: 3,
    city: 'New Delhi',
    error: null,  // or error message if success=false
    nearby_suggestions: '<html>' // if available
}
```

**Usage:**
```javascript
jQuery(document).on('wpc_pincode_checked', function(event, data) {
    console.log('Pincode checked:', data.pincode);

    if (data.success) {
        console.log('Available! Delivery in', data.delivery_days, 'days');

        // Show custom popup
        showDeliveryModal(data);

        // Update cart
        updateCartWithPincode(data.pincode);

        // Track analytics
        ga('send', 'event', 'Pincode', 'Available', data.pincode);
    } else {
        console.log('Not available:', data.error);

        // Track failed checks
        ga('send', 'event', 'Pincode', 'Not Available', data.pincode);

        // Show nearby suggestions modal
        if (data.nearby_suggestions) {
            showNearbyModal(data.nearby_suggestions);
        }
    }
});
```

---

### Custom Events

You can trigger custom events for your integrations:

```javascript
// Trigger custom event before check
jQuery(document).trigger('wpc_before_check', {
    pincode: pincode,
    product_id: product_id
});

// Listen to custom event
jQuery(document).on('wpc_before_check', function(event, data) {
    console.log('About to check:', data.pincode);
    // Your custom code
});
```

---

## Complete Integration Example

Here's a complete example combining multiple hooks and filters:

```php
/**
 * Complete Pincode Checker Customization
 */

// 1. Custom validation (support international codes)
add_filter('wpc_validate_pincode_format', function($is_valid, $pincode) {
    // Indian: 6 digits
    if (preg_match('/^[1-9][0-9]{5}$/', $pincode)) return true;

    // US: 5 or 5+4 digits
    if (preg_match('/^\d{5}(-\d{4})?$/', $pincode)) return true;

    return false;
}, 10, 2);

// 2. Exclude weekends and holidays
add_filter('wpc_delivery_days', function($delivery_days, $pincode, $product_id) {
    $holidays = ['2025-01-26', '2025-08-15', '2025-10-02', '2025-12-25'];
    $current_date = time();
    $business_days = 0;
    $total_days = 0;

    while ($business_days < $delivery_days) {
        $total_days++;
        $check_date = strtotime("+{$total_days} days", $current_date);
        $date_str = date('Y-m-d', $check_date);
        $day_of_week = date('N', $check_date);

        // Skip weekends and holidays
        if ($day_of_week < 6 && !in_array($date_str, $holidays)) {
            $business_days++;
        }
    }

    return $total_days;
}, 10, 3);

// 3. Custom success message with promotions
add_filter('wpc_success_message', function($message, $pincode_data, $product_id) {
    $delivery_date = date('M jS', strtotime("+{$pincode_data['delivery_days']} days"));

    $msg = '<div class="wpc-custom-success">';
    $msg .= '<strong>✓ Great news!</strong><br>';
    $msg .= "Delivering to {$pincode_data['city']} by <strong>{$delivery_date}</strong><br>";

    if ($pincode_data['shipping_amount'] == 0) {
        $msg .= '<span class="free-ship">🎉 FREE Shipping!</span><br>';
    } else {
        $msg .= "Shipping: " . wc_price($pincode_data['shipping_amount']);
        $msg .= ' <small>(Free on orders ₹500+)</small><br>';
    }

    if ($pincode_data['case_on_delivery']) {
        $msg .= '💵 Cash on Delivery available<br>';
    }

    $msg .= '</div>';

    return $msg;
}, 10, 3);

// 4. Add custom data to result
add_filter('wpc_pincode_check_result', function($result, $pincode) {
    if ($result['success']) {
        // Add delivery slots
        $result['slots'] = [
            '9 AM - 12 PM',
            '12 PM - 3 PM',
            '3 PM - 6 PM'
        ];

        // Add tracking info
        $result['track_url'] = home_url('/track-order');
    }

    return $result;
}, 10, 2);

// 5. Log all checks
add_action('wpc_pincode_checked', function($pincode, $result) {
    global $wpdb;
    $table = $wpdb->prefix . 'wpc_search_log';

    $wpdb->insert($table, [
        'pincode' => $pincode,
        'result' => $result['success'] ? 'available' : 'not_available',
        'product_id' => $_POST['product_id'] ?? 0,
        'user_id' => get_current_user_id(),
        'timestamp' => current_time('mysql')
    ]);
}, 10, 2);

// 6. Send notifications for new areas
add_action('wpc_pincode_added', function($pincode_id, $pincode_data) {
    // Notify admin
    wp_mail(
        get_option('admin_email'),
        'New Service Area',
        "New area added: {$pincode_data['pincode']} - {$pincode_data['city']}"
    );

    // Notify users who requested this pincode
    notify_waiting_customers($pincode_data['pincode']);
}, 10, 2);

// 7. Custom nearby suggestions
add_filter('wpc_nearby_suggestions_html', function($html, $nearby_pincodes, $pincode) {
    if (empty($nearby_pincodes)) return $html;

    $html = '<div class="nearby-custom">';
    $html .= '<h4>🚚 We deliver nearby:</h4>';
    $html .= '<ul class="nearby-list">';

    foreach ($nearby_pincodes as $nearby) {
        $html .= '<li>';
        $html .= '<strong>' . $nearby['pincode'] . '</strong> ';
        $html .= $nearby['city'];
        if (isset($nearby['distance_km'])) {
            $html .= ' <small>(' . $nearby['distance_km'] . ' km)</small>';
        }
        $html .= ' - ' . $nearby['delivery_days'] . ' days';
        $html .= '</li>';
    }

    $html .= '</ul></div>';

    return $html;
}, 10, 3);
```

---

**Related Documentation:**
- [Developer Guide](developer-guide.md) - Technical overview
- [Customer Guide](customer-guide.md) - User documentation
- [Database Schema](database-schema.md) - Database structure
