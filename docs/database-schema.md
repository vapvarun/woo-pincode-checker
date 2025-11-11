# Database Schema Documentation

Complete database structure documentation for Pincode Checker for WooCommerce.

## Table of Contents

1. [Overview](#overview)
2. [Tables](#tables)
3. [Columns](#columns)
4. [Indexes](#indexes)
5. [Migration History](#migration-history)
6. [Query Examples](#query-examples)
7. [Performance Optimization](#performance-optimization)

---

## Overview

The plugin uses a single custom table to store all pincode data. The table name follows WordPress conventions with the site's table prefix.

**Table Name:** `{$wpdb->prefix}pincode_checker`

**Example:** `wp_pincode_checker`

**Storage Engine:** InnoDB
**Character Set:** utf8mb4
**Collation:** utf8mb4_unicode_ci

---

## Tables

### wp_pincode_checker

Primary table storing all pincode/ZIP code information for delivery verification.

**Purpose:**
- Store serviceable pincodes with delivery settings
- Track shipping costs per pincode
- Manage COD availability and limits
- Store geocoded coordinates for nearby suggestions

**Creation SQL:**

```sql
CREATE TABLE IF NOT EXISTS `wp_pincode_checker` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `pincode` varchar(10) NOT NULL,
    `city` varchar(100) NOT NULL,
    `state` varchar(100) NOT NULL,
    `delivery_days` int(11) NOT NULL DEFAULT 7,
    `case_on_delivery` tinyint(1) NOT NULL DEFAULT 1,
    `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `cod_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `category_rules` text DEFAULT NULL,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_pincode` (`pincode`),
    KEY `idx_city` (`city`),
    KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Columns

### Core Columns

#### `id`
- **Type:** `bigint(20) UNSIGNED`
- **Null:** NOT NULL
- **Default:** AUTO_INCREMENT
- **Description:** Primary key, unique identifier for each pincode record

**Usage:**
```php
// Get pincode by ID
$pincode_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE id = %d",
    $pincode_id
));
```

---

#### `pincode`
- **Type:** `varchar(10)`
- **Null:** NOT NULL
- **Unique:** Yes
- **Description:** Pincode/ZIP code value (unique constraint ensures no duplicates)

**Notes:**
- Length 10 supports various formats (Indian 6-digit, US 5+4, UK postcodes)
- Unique key prevents duplicate pincodes
- Case-sensitive by default (use UPPER/LOWER for case-insensitive searches)

**Usage:**
```php
// Check if pincode exists
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s",
    '110001'
));

// Get pincode details
$pincode = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE pincode = %s",
    '110001'
));
```

---

#### `city`
- **Type:** `varchar(100)`
- **Null:** NOT NULL
- **Indexed:** Yes (idx_city)
- **Description:** City/district/locality name

**Notes:**
- Indexed for fast city-based searches
- Stored as provided (no automatic capitalization)
- Used in success messages and nearby suggestions

**Usage:**
```php
// Get all pincodes in a city
$city_pincodes = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE city = %s ORDER BY pincode",
    'New Delhi'
));

// Count pincodes per city
$city_counts = $wpdb->get_results(
    "SELECT city, COUNT(*) as count
     FROM {$table_name}
     GROUP BY city
     ORDER BY count DESC
     LIMIT 10"
);
```

---

#### `state`
- **Type:** `varchar(100)`
- **Null:** NOT NULL
- **Indexed:** Yes (idx_state)
- **Description:** State/province/region name

**Notes:**
- Indexed for fast state-based searches
- Used for geographic grouping and reporting
- Displayed in success messages

**Usage:**
```php
// Get all pincodes in a state
$state_pincodes = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE state = %s ORDER BY city, pincode",
    'Delhi'
));

// Get states with pincode count
$states = $wpdb->get_results(
    "SELECT state, COUNT(*) as pincode_count
     FROM {$table_name}
     GROUP BY state
     ORDER BY state"
);
```

---

### Delivery Settings Columns

#### `delivery_days`
- **Type:** `int(11)`
- **Null:** NOT NULL
- **Default:** 7
- **Range:** 1-365
- **Description:** Number of days required for delivery to this pincode

**Notes:**
- Can be overridden by category-specific rules
- Used to calculate delivery date
- 0 = same day (if implemented)

**Usage:**
```php
// Get average delivery time
$avg_days = $wpdb->get_var(
    "SELECT AVG(delivery_days) FROM {$table_name}"
);

// Get pincodes with express delivery (<=2 days)
$express = $wpdb->get_results(
    "SELECT * FROM {$table_name} WHERE delivery_days <= 2"
);

// Update delivery days for a city
$wpdb->update(
    $table_name,
    array('delivery_days' => 3),
    array('city' => 'New Delhi'),
    array('%d'),
    array('%s')
);
```

---

#### `shipping_amount`
- **Type:** `decimal(10,2)`
- **Null:** NOT NULL
- **Default:** 0.00
- **Description:** Shipping cost for this pincode in base currency

**Notes:**
- Stores numeric value without currency symbol
- 0.00 = Free shipping
- Displayed using WooCommerce price formatting
- Supports up to 99,999,999.99

**Usage:**
```php
// Get pincodes with free shipping
$free_shipping = $wpdb->get_results(
    "SELECT * FROM {$table_name} WHERE shipping_amount = 0"
);

// Get pincodes by shipping cost range
$expensive = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE shipping_amount BETWEEN %f AND %f",
    100.00,
    200.00
));

// Calculate total revenue from shipping (example)
$total_shipping = $wpdb->get_var(
    "SELECT SUM(shipping_amount) FROM {$table_name}"
);
```

---

### COD (Cash on Delivery) Columns

#### `case_on_delivery`
- **Type:** `tinyint(1)`
- **Null:** NOT NULL
- **Default:** 1
- **Values:** 0 = Disabled, 1 = Enabled
- **Description:** Whether COD is available for this pincode

**Usage:**
```php
// Get COD-enabled pincodes
$cod_pincodes = $wpdb->get_results(
    "SELECT * FROM {$table_name} WHERE case_on_delivery = 1"
);

// Count COD vs non-COD
$stats = $wpdb->get_row(
    "SELECT
        SUM(case_on_delivery = 1) as cod_enabled,
        SUM(case_on_delivery = 0) as cod_disabled
     FROM {$table_name}"
);

// Enable COD for entire state
$wpdb->update(
    $table_name,
    array('case_on_delivery' => 1),
    array('state' => 'Delhi'),
    array('%d'),
    array('%s')
);
```

---

#### `cod_amount`
- **Type:** `decimal(10,2)`
- **Null:** NOT NULL
- **Default:** 0.00
- **Description:** Maximum order value allowed for COD (0 = unlimited)

**Notes:**
- Only applies when `case_on_delivery = 1`
- 0.00 means no limit on COD orders
- Used to prevent fraud on high-value orders
- Currency in base WooCommerce currency

**Usage:**
```php
// Get pincodes with COD limits
$limited_cod = $wpdb->get_results(
    "SELECT * FROM {$table_name}
     WHERE case_on_delivery = 1 AND cod_amount > 0"
);

// Get pincodes with unlimited COD
$unlimited_cod = $wpdb->get_results(
    "SELECT * FROM {$table_name}
     WHERE case_on_delivery = 1 AND cod_amount = 0"
);

// Set COD limit for high-risk areas
$wpdb->update(
    $table_name,
    array('cod_amount' => 3000.00),
    array('city' => 'Remote City'),
    array('%f'),
    array('%s')
);
```

---

### Timestamp Columns

#### `created_at`
- **Type:** `datetime`
- **Null:** NULL
- **Default:** CURRENT_TIMESTAMP
- **Description:** Timestamp when pincode record was created

**Usage:**
```php
// Get recently added pincodes (last 7 days)
$recent = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name}
     WHERE created_at >= %s
     ORDER BY created_at DESC",
    date('Y-m-d H:i:s', strtotime('-7 days'))
));

// Get pincodes added in specific month
$monthly = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name}
     WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d",
    2025,
    1
));
```

---

#### `updated_at`
- **Type:** `datetime`
- **Null:** NULL
- **Default:** CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
- **Description:** Timestamp of last update (auto-updated on any change)

**Usage:**
```php
// Get recently updated pincodes
$updated = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name}
     WHERE updated_at >= %s AND updated_at != created_at
     ORDER BY updated_at DESC",
    date('Y-m-d H:i:s', strtotime('-24 hours'))
));

// Find stale records (not updated in 6 months)
$stale = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name}
     WHERE updated_at < %s",
    date('Y-m-d H:i:s', strtotime('-6 months'))
));
```

---

### Advanced Columns (v1.5.0+)

#### `category_rules`
- **Type:** `text`
- **Null:** NULL
- **Default:** NULL
- **Description:** JSON-encoded pincode-specific category rule overrides (future use)

**Notes:**
- Currently reserved for future functionality
- Intended for pincode-level category rule overrides
- Would allow: "For pincode 110001, Electronics = 7 days instead of 5"

**Planned Usage:**
```php
// Future implementation
$category_override = array(
    15 => 7,  // Category ID 15 (Electronics) = 7 days for this pincode
    22 => 2   // Category ID 22 (Groceries) = 2 days for this pincode
);

$wpdb->update(
    $table_name,
    array('category_rules' => json_encode($category_override)),
    array('pincode' => '110001'),
    array('%s'),
    array('%s')
);
```

---

#### `latitude`
- **Type:** `decimal(10,8)`
- **Null:** NULL
- **Default:** NULL
- **Description:** Geocoded latitude coordinate

**Notes:**
- Decimal degrees format (e.g., 28.6139298)
- Precision: 8 decimal places (~1mm accuracy)
- Used for distance-based nearby suggestions
- NULL = not yet geocoded

**Range:**
- Valid range: -90.00000000 to 90.00000000
- Northern hemisphere: positive values
- Southern hemisphere: negative values

**Usage:**
```php
// Get geocoded pincodes
$geocoded = $wpdb->get_results(
    "SELECT * FROM {$table_name} WHERE latitude IS NOT NULL"
);

// Find pincodes within bounding box
$pincodes_in_area = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name}
     WHERE latitude BETWEEN %f AND %f
     AND longitude BETWEEN %f AND %f",
    28.5, 28.7,  // Lat range
    77.1, 77.3   // Lon range
));

// Store geocoded coordinates
$wpdb->update(
    $table_name,
    array(
        'latitude' => 28.6139298,
        'longitude' => 77.2089908
    ),
    array('pincode' => '110001'),
    array('%f', '%f'),
    array('%s')
);
```

---

#### `longitude`
- **Type:** `decimal(11,8)`
- **Null:** NULL
- **Default:** NULL
- **Description:** Geocoded longitude coordinate

**Notes:**
- Decimal degrees format (e.g., 77.2089908)
- Precision: 8 decimal places (~1mm accuracy)
- Used with latitude for distance calculations
- NULL = not yet geocoded

**Range:**
- Valid range: -180.00000000 to 180.00000000
- Eastern hemisphere: positive values
- Western hemisphere: negative values

**Usage:**
```php
// Find pincodes near a coordinate
$nearby = $wpdb->get_results($wpdb->prepare(
    "SELECT *,
     (6371 * acos(cos(radians(%f)) * cos(radians(latitude)) *
      cos(radians(longitude) - radians(%f)) +
      sin(radians(%f)) * sin(radians(latitude)))) AS distance
     FROM {$table_name}
     WHERE latitude IS NOT NULL AND longitude IS NOT NULL
     HAVING distance < %f
     ORDER BY distance
     LIMIT 10",
    28.6139298,  // Center latitude
    77.2089908,  // Center longitude
    28.6139298,  // Center latitude (again for formula)
    20           // Within 20km
));
```

---

## Indexes

### PRIMARY KEY (`id`)

**Type:** Primary Key
**Columns:** `id`
**Purpose:** Unique row identifier

**Performance:**
- O(log n) lookup by ID
- Auto-indexed by MySQL
- Used for joins and foreign keys

---

### UNIQUE KEY `unique_pincode` (`pincode`)

**Type:** Unique Index
**Columns:** `pincode`
**Purpose:** Ensure pincode uniqueness, fast pincode lookups

**Performance:**
- O(log n) lookup
- Prevents duplicate pincodes
- Used by primary search functionality

**Impact:**
- Fastest column for searches (after PRIMARY KEY)
- INSERT/UPDATE checks uniqueness automatically
- Essential for plugin functionality

---

### KEY `idx_city` (`city`)

**Type:** Non-unique Index
**Columns:** `city`
**Purpose:** Fast city-based filtering and grouping

**Performance:**
- O(log n) lookup
- Speeds up GROUP BY city
- Optimizes admin filters

**Queries Optimized:**
```sql
SELECT * FROM wp_pincode_checker WHERE city = 'New Delhi';
SELECT city, COUNT(*) FROM wp_pincode_checker GROUP BY city;
SELECT * FROM wp_pincode_checker WHERE city LIKE 'New%';
```

---

### KEY `idx_state` (`state`)

**Type:** Non-unique Index
**Columns:** `state`
**Purpose:** Fast state-based filtering and reporting

**Performance:**
- O(log n) lookup
- Speeds up state statistics
- Optimizes regional reports

**Queries Optimized:**
```sql
SELECT * FROM wp_pincode_checker WHERE state = 'Delhi';
SELECT state, COUNT(*) FROM wp_pincode_checker GROUP BY state;
SELECT DISTINCT state FROM wp_pincode_checker ORDER BY state;
```

---

### Additional Recommended Indexes

For large databases (100,000+ pincodes), consider adding:

```sql
-- Optimize geocoded pincode queries
ALTER TABLE wp_pincode_checker
ADD INDEX idx_coordinates (latitude, longitude);

-- Optimize delivery time filtering
ALTER TABLE wp_pincode_checker
ADD INDEX idx_delivery_days (delivery_days);

-- Optimize COD filtering
ALTER TABLE wp_pincode_checker
ADD INDEX idx_cod (case_on_delivery, cod_amount);

-- Composite index for geographic queries
ALTER TABLE wp_pincode_checker
ADD INDEX idx_location (state, city, pincode);

-- Optimize date range queries
ALTER TABLE wp_pincode_checker
ADD INDEX idx_created (created_at);

ALTER TABLE wp_pincode_checker
ADD INDEX idx_updated (updated_at);
```

---

## Migration History

### Version 1.0.0 (Initial)

**Original Schema:**
```sql
CREATE TABLE wp_pincode_checker (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    pincode varchar(10) NOT NULL,
    city varchar(100) NOT NULL,
    state varchar(100) NOT NULL,
    delivery_days int(11) NOT NULL DEFAULT 7,
    case_on_delivery tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY unique_pincode (pincode)
);
```

---

### Version 1.2.0

**Added Columns:**
- `shipping_amount` decimal(10,2) - Shipping cost per pincode
- `cod_amount` decimal(10,2) - COD limit per pincode

**Migration:**
```sql
ALTER TABLE wp_pincode_checker
ADD COLUMN shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER delivery_days;

ALTER TABLE wp_pincode_checker
ADD COLUMN cod_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER case_on_delivery;
```

---

### Version 1.3.0

**Added Columns:**
- `created_at` datetime - Record creation timestamp
- `updated_at` datetime - Last update timestamp

**Migration:**
```sql
ALTER TABLE wp_pincode_checker
ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER cod_amount;

ALTER TABLE wp_pincode_checker
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
```

---

### Version 1.5.0 (Current)

**Added Columns:**
- `category_rules` text - JSON category overrides (reserved)
- `latitude` decimal(10,8) - Geocoded latitude
- `longitude` decimal(11,8) - Geocoded longitude

**Migration:**
```sql
ALTER TABLE wp_pincode_checker
ADD COLUMN category_rules TEXT NULL DEFAULT NULL AFTER updated_at;

ALTER TABLE wp_pincode_checker
ADD COLUMN latitude DECIMAL(10,8) NULL DEFAULT NULL AFTER category_rules;

ALTER TABLE wp_pincode_checker
ADD COLUMN longitude DECIMAL(11,8) NULL DEFAULT NULL AFTER latitude;
```

**Migration Logic:**
- Check if columns exist before adding
- Run automatically on plugin activation
- Run on version mismatch detection
- Manual trigger: deactivate/reactivate plugin

---

## Query Examples

### Basic Operations

#### Insert Pincode
```php
global $wpdb;
$table_name = $wpdb->prefix . 'pincode_checker';

$result = $wpdb->insert($table_name, array(
    'pincode'          => '110001',
    'city'             => 'New Delhi',
    'state'            => 'Delhi',
    'delivery_days'    => 3,
    'shipping_amount'  => 50.00,
    'case_on_delivery' => 1,
    'cod_amount'       => 5000.00
), array('%s', '%s', '%s', '%d', '%f', '%d', '%f'));

if ($result) {
    $insert_id = $wpdb->insert_id;
}
```

---

#### Update Pincode
```php
$wpdb->update($table_name,
    array(
        'delivery_days' => 5,
        'shipping_amount' => 75.00
    ),
    array('pincode' => '110001'),
    array('%d', '%f'),
    array('%s')
);
```

---

#### Delete Pincode
```php
$wpdb->delete($table_name,
    array('pincode' => '110001'),
    array('%s')
);
```

---

#### Check if Pincode Exists
```php
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s",
    '110001'
));

if ($exists) {
    echo "Pincode exists";
}
```

---

### Advanced Queries

#### Search with LIKE
```php
// Pincodes starting with 110
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE pincode LIKE %s",
    '110%'
));
```

---

#### Multiple Conditions
```php
// COD-enabled pincodes in Delhi with delivery <= 3 days
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name}
     WHERE state = %s
     AND case_on_delivery = 1
     AND delivery_days <= %d
     ORDER BY city, pincode",
    'Delhi',
    3
));
```

---

#### Aggregation
```php
// Count pincodes per state
$stats = $wpdb->get_results(
    "SELECT state,
            COUNT(*) as total,
            AVG(delivery_days) as avg_delivery,
            SUM(case_on_delivery = 1) as cod_enabled
     FROM {$table_name}
     GROUP BY state
     ORDER BY total DESC"
);
```

---

#### Distance Calculation (Haversine Formula)
```php
$user_lat = 28.6139298;
$user_lon = 77.2089908;

$nearby = $wpdb->get_results($wpdb->prepare(
    "SELECT *,
     (6371 * acos(
         cos(radians(%f)) * cos(radians(latitude)) *
         cos(radians(longitude) - radians(%f)) +
         sin(radians(%f)) * sin(radians(latitude))
     )) AS distance_km
     FROM {$table_name}
     WHERE latitude IS NOT NULL
     AND longitude IS NOT NULL
     HAVING distance_km < %f
     ORDER BY distance_km
     LIMIT %d",
    $user_lat,
    $user_lon,
    $user_lat,
    20,  // Within 20km
    5    // Top 5 results
));
```

---

#### Bulk Insert
```php
$pincodes = ['110001', '110002', '110003'];
$values = array();
$placeholders = array();

foreach ($pincodes as $pincode) {
    $placeholders[] = "(%s, %s, %s, %d, %f, %d, %f)";
    array_push($values, $pincode, 'New Delhi', 'Delhi', 3, 50.00, 1, 5000.00);
}

$query = "INSERT INTO {$table_name}
          (pincode, city, state, delivery_days, shipping_amount, case_on_delivery, cod_amount)
          VALUES " . implode(', ', $placeholders);

$wpdb->query($wpdb->prepare($query, $values));
```

---

### Performance Queries

#### Get Database Statistics
```php
// Table size and row count
$stats = $wpdb->get_row("
    SELECT
        COUNT(*) as row_count,
        ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    AND table_name = '{$table_name}'
");

echo "Rows: {$stats->row_count}, Size: {$stats->size_mb} MB";
```

---

#### Index Usage Analysis
```php
// Check index statistics
$indexes = $wpdb->get_results("
    SHOW INDEX FROM {$table_name}
");

foreach ($indexes as $index) {
    echo "Index: {$index->Key_name}, Column: {$index->Column_name}, Cardinality: {$index->Cardinality}\n";
}
```

---

## Performance Optimization

### Query Optimization

#### Use Prepared Statements
```php
// Good
$pincode = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE pincode = %s",
    '110001'
));

// Bad (SQL injection risk + no query cache benefit)
$pincode = $wpdb->get_row("SELECT * FROM {$table_name} WHERE pincode = '110001'");
```

---

#### Use Appropriate Data Types
```php
// Good
$count = absint($_GET['limit']);
$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} LIMIT %d", $count));

// Bad
$count = $_GET['limit']; // Not sanitized, could be non-numeric
```

---

#### Avoid SELECT *
```php
// Good - Only select needed columns
$wpdb->get_results("SELECT pincode, city, delivery_days FROM {$table_name}");

// Bad - Fetches all columns including large TEXT fields
$wpdb->get_results("SELECT * FROM {$table_name}");
```

---

### Caching Strategies

#### Object Cache
```php
function get_pincode_cached($pincode) {
    $cache_key = 'wpc_pincode_' . $pincode;
    $cached = wp_cache_get($cache_key, 'woo_pincode_checker');

    if ($cached !== false) {
        return $cached;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pincode_checker';
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE pincode = %s",
        $pincode
    ));

    wp_cache_set($cache_key, $result, 'woo_pincode_checker', HOUR_IN_SECONDS);

    return $result;
}
```

---

#### Transients
```php
function get_all_pincodes_cached() {
    $transient_key = 'wpc_all_pincodes';
    $cached = get_transient($transient_key);

    if ($cached !== false) {
        return $cached;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pincode_checker';
    $results = $wpdb->get_results("SELECT * FROM {$table_name}");

    set_transient($transient_key, $results, 12 * HOUR_IN_SECONDS);

    return $results;
}
```

---

### Database Maintenance

#### Optimize Table
```sql
OPTIMIZE TABLE wp_pincode_checker;
```

#### Analyze Table (Update Index Statistics)
```sql
ANALYZE TABLE wp_pincode_checker;
```

#### Check Table Integrity
```sql
CHECK TABLE wp_pincode_checker;
```

#### Repair Table (if needed)
```sql
REPAIR TABLE wp_pincode_checker;
```

---

### Backup & Restore

#### Export to SQL
```bash
mysqldump -u username -p database_name wp_pincode_checker > pincode_backup.sql
```

#### Export to CSV
```sql
SELECT * FROM wp_pincode_checker
INTO OUTFILE '/tmp/pincodes.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

#### Import from SQL
```bash
mysql -u username -p database_name < pincode_backup.sql
```

---

## Size Estimates

### Storage Requirements

**Per Row:** ~200-300 bytes (varies with data)

**Estimates:**
- 1,000 pincodes: ~300 KB
- 10,000 pincodes: ~3 MB
- 100,000 pincodes: ~30 MB
- 1,000,000 pincodes: ~300 MB

**Indexes add approximately 30-40% overhead**

---

## Related Documentation

- [Developer Guide](developer-guide.md) - Technical overview
- [Hooks & Filters](hooks-filters.md) - API reference
- [Customer Guide](customer-guide.md) - User documentation
