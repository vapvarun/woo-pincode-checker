# Customer Guide - Pincode Checker for WooCommerce

Complete guide for store owners and administrators to configure and use all features of the Pincode Checker plugin.

## Table of Contents

1. [Installation](#installation)
2. [General Settings](#general-settings)
3. [Managing Pincodes](#managing-pincodes)
4. [Category-Specific Delivery Rules](#category-specific-delivery-rules)
5. [Geocoding & Nearby Suggestions](#geocoding--nearby-suggestions)
6. [CSV Import/Export](#csv-importexport)
7. [Frontend Display](#frontend-display)
8. [Troubleshooting](#troubleshooting)

---

## Installation

### Automatic Installation

1. Go to **WordPress Admin → Plugins → Add New**
2. Search for "Pincode Checker for WooCommerce"
3. Click **Install Now**
4. Click **Activate** after installation

### Manual Installation

1. Download the plugin ZIP file
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate** after installation

### First-Time Setup

After activation, navigate to **WB Plugins → Pincode Checker** to access the plugin settings.

---

## General Settings

Access via: **WB Plugins → Pincode Checker → General**

### Basic Configuration

#### Pincode Check Text
- **Setting:** Text displayed above the pincode input field
- **Default:** "Check if we deliver to your pincode"
- **Usage:** Customize to match your store's language/tone
- **Example:** "Enter your ZIP code to check delivery"

#### Pincode Check Button Text
- **Setting:** Text on the check availability button
- **Default:** "Check"
- **Usage:** Keep it short and action-oriented
- **Example:** "Verify", "Check Now", "Submit"

#### Pincode Error Message
- **Setting:** Message shown when pincode format is invalid
- **Default:** "Please enter a valid pincode"
- **Usage:** Guide customers to correct format
- **Example:** "Please enter a 6-digit pincode"

### Delivery Settings

#### Default Delivery Days
- **Setting:** Number of days for delivery when not specified per pincode
- **Default:** 7 days
- **Range:** 1-365 days
- **Note:** This is overridden by:
  1. Category-specific rules (if product belongs to category with rule)
  2. Individual pincode delivery days

#### Delivery Date Format
- **Setting:** How the delivery date is displayed to customers
- **Options:**
  - `M jS` - Example: Jan 15th
  - `F j, Y` - Example: January 15, 2025
  - `d/m/Y` - Example: 15/01/2025
  - `Y-m-d` - Example: 2025-01-15

#### Message for Serviceable Pincode
- **Setting:** Success message when pincode is available
- **Default:** "Delivery available for [pincode]"
- **Variables:**
  - `[pincode]` - Customer's entered pincode
  - `[city]` - City name for the pincode
  - `[state]` - State name
  - `[delivery_date]` - Calculated delivery date
  - `[delivery_days]` - Number of delivery days
  - `[shipping_amount]` - Shipping cost
  - `[cod_availability]` - COD status
- **Example:** "Great! We deliver to [city] in [delivery_days] days. Estimated delivery: [delivery_date]"

#### Message for Non-Serviceable Pincode
- **Setting:** Error message when pincode is not available
- **Default:** "Sorry! We are currently not servicing your area"
- **Note:** If geocoding is enabled, nearby suggestions will automatically appear below this message

### Advanced Options

#### Hide/Disable Add to Cart Button
- **Setting:** Control cart button when pincode not checked or unavailable
- **Options:**
  - **None** - No restrictions
  - **Hide Button** - Completely remove add to cart button
  - **Disable Button** - Show but disable the button
- **Use Case:** Force customers to verify delivery before purchasing

#### Required Pincode Field
- **Setting:** Make pincode verification mandatory
- **Options:**
  - **Yes** - Customer must verify pincode before adding to cart
  - **No** - Pincode check is optional
- **Note:** Works best with "Hide/Disable Add to Cart Button" enabled

#### Categories for Shipping
- **Setting:** Select which WooCommerce product categories should display pincode checker
- **Options:** Multi-select all product categories
- **Leave Empty:** Show on all products
- **Use Case:** Limit pincode checking to specific product types

---

## Managing Pincodes

### View All Pincodes

Access via: **WB Plugins → Pincode Checker → All Pincodes**

#### Features:
- **Search:** Find pincodes by code, city, or state
- **Pagination:** Navigate through large pincode lists
- **Bulk Actions:** Delete multiple pincodes at once
- **Quick Edit:** Click on any field to edit inline
- **Sorting:** Click column headers to sort

#### Columns Displayed:
- Pincode
- City
- State
- Delivery Days
- Shipping Amount
- COD Enabled
- COD Amount Limit
- Actions (Edit/Delete)

### Add Single Pincode

Access via: **WB Plugins → Pincode Checker → Add New Pincode**

#### Required Fields:
- **Pincode:** 6-digit numeric code (or your country's format)
- **City:** City/district name
- **State:** State/province name

#### Optional Fields:
- **Delivery Days:** Override default delivery time (1-365 days)
- **Shipping Amount:** Shipping cost for this pincode (decimal, e.g., 50.00)
- **Cash on Delivery:** Enable/disable COD for this pincode
- **COD Amount:** Maximum order value allowed for COD (0 = unlimited)

#### Example:
```
Pincode: 110001
City: New Delhi
State: Delhi
Delivery Days: 3
Shipping Amount: 50.00
COD: Enabled
COD Amount: 5000.00
```

### Bulk Add by Pattern

Access via: **WB Plugins → Pincode Checker → Bulk Add by Pattern**

#### Pattern Syntax:

**Wildcards:**
- `*` - Matches any number of digits
- `?` - Matches exactly one digit

**Examples:**
- `1100*` - Generates 11000, 11001, 11002...11009
- `110?5` - Generates 11005, 11015, 11025...11095
- `11??1` - Generates 11001, 11011, 11021...11991

**Ranges:**
- `START-END` - Generates all pincodes from START to END

**Examples:**
- `110001-110010` - Generates 110001 through 110010
- `400001-400100` - Generates 400001 through 400100

#### Bulk Add Form:

1. **Pattern/Range:**
   - Enter your pattern or range
   - Click "Preview Count" to see how many pincodes will be created
   - Maximum recommended: 10,000 pincodes per batch

2. **Common Details:**
   - City: Applied to all generated pincodes
   - State: Applied to all generated pincodes
   - Delivery Days: Same for all (or override per pincode later)
   - Shipping Amount: Same for all
   - COD Settings: Same for all

3. **Auto-Geocode Option:**
   - ✅ Enable to automatically fetch city/state/coordinates from OpenStreetMap
   - ⚠️ This takes longer (1 second per pincode) but provides accurate location data
   - Useful when you don't know the city/state for each pincode

#### Use Cases:

**Scenario 1: Add entire city**
```
Pattern: 110***
City: New Delhi
State: Delhi
Delivery Days: 3
```
Result: 1,000 pincodes (110000-110999)

**Scenario 2: Add specific range**
```
Range: 400001-400050
City: Mumbai
State: Maharashtra
Delivery Days: 5
```
Result: 50 pincodes

**Scenario 3: Pattern with auto-geocoding**
```
Pattern: 1100**
Auto-Geocode: Yes
Delivery Days: 4
```
Result: 100 pincodes with auto-detected city/state

---

## Category-Specific Delivery Rules

Access via: **WB Plugins → Pincode Checker → Category Rules**

### Overview

Set different delivery times for different product categories. These rules apply site-wide to all pincodes unless overridden in individual pincode settings.

### How It Works

**Priority Order:**
1. ✅ **Category-specific rule** (highest priority)
2. Individual pincode delivery days
3. Default delivery days from General Settings

**Example Scenario:**
- Default delivery: 7 days
- Electronics category rule: 5 days
- Groceries category rule: 1 day
- Pincode 110001: 3 days

When customer checks delivery for:
- Electronics product at 110001 → **5 days** (category rule applies)
- Groceries product at 110001 → **1 day** (category rule applies)
- Clothing product at 110001 → **3 days** (pincode-specific setting)
- Clothing product at 110002 → **7 days** (default setting)

### Configuration

#### For Each Category:

1. **Enable Rule:** Toggle the switch to activate/deactivate
2. **Delivery Days:** Enter number of days (1-365)
3. **Auto-Apply:** Rules apply instantly to all products in that category

#### Category Table Shows:
- Category name
- Number of products in category
- Delivery days setting
- Enable/Disable toggle

#### Best Practices:

✅ **DO:**
- Use for perishable items (groceries, flowers) that need fast delivery
- Use for heavy/fragile items (electronics, furniture) that need extra time
- Use for custom/made-to-order products with longer lead times

❌ **DON'T:**
- Over-complicate with too many category rules
- Set unrealistic delivery times
- Forget to test on actual products

### Multi-Category Products

If a product belongs to multiple categories with different rules:
- **First matching rule applies**
- Order determined by WooCommerce category assignment order
- Consider using primary category for critical products

---

## Geocoding & Nearby Suggestions

Access via: **WB Plugins → Pincode Checker → Geocoding**

### Overview

When a customer's pincode is not serviceable, show them nearby pincodes where you DO deliver. This reduces cart abandonment and helps customers find alternative delivery locations.

### Setup Process

#### Step 1: Geocode Your Pincodes

1. Navigate to **Geocoding tab**
2. View current status:
   - Total Pincodes
   - Geocoded (with coordinates)
   - Remaining
   - Progress percentage

3. Click **Start Geocoding** button
4. Wait for process to complete (keep page open)
   - Estimated time: ~1 minute per 60 pincodes
   - Uses 100% FREE OpenStreetMap service
   - No API key required

#### Step 2: How It Works

**Backend Process:**
- Fetches latitude/longitude for each pincode
- Stores coordinates in database
- Rate limited to 1 request/second (OpenStreetMap policy)
- Runs in background via AJAX

**One-Time Setup:**
- Only needs to be done once
- New pincodes added later can be auto-geocoded
- No maintenance required

### Frontend Experience

When customer enters unavailable pincode:

**Before Geocoding:**
```
❌ Sorry! We are currently not servicing your area.
```

**After Geocoding:**
```
❌ Sorry! We are currently not servicing your area.

📍 But we deliver to nearby areas:
• 110002 - New Delhi (2.3 km away) (3 days delivery)
• 110003 - New Delhi (3.7 km away) (3 days delivery)
• 110005 - New Delhi (4.1 km away) (3 days delivery)
```

### Configuration Options

**Radius:** Fixed at 20km (can be modified via filter hook)
**Limit:** Shows up to 5 nearby pincodes
**Sorting:** Automatically sorted by distance (nearest first)

### Use Cases

#### Urban Areas
- High pincode density
- Many nearby alternatives available
- Customers can choose nearest service point

#### Suburban/Rural Areas
- Lower pincode density
- May show fewer or no alternatives
- Falls back to prefix-based matching if no geocoded results

### Privacy & Performance

✅ **Privacy-Friendly:**
- Uses customer's pincode only (no personal data)
- OpenStreetMap is GDPR-compliant
- No tracking or analytics

✅ **Performance-Optimized:**
- Calculations done server-side
- Results cached for repeat queries
- Minimal page load impact

---

## CSV Import/Export

Access via: **WB Plugins → Pincode Checker → Upload Pincodes**

### CSV Import

#### File Format

**Required CSV Structure:**
```csv
Pincode,City,State,Delivery Days,Shipping Amount,COD Enabled,COD Amount
110001,New Delhi,Delhi,3,50.00,1,5000.00
110002,New Delhi,Delhi,3,50.00,1,5000.00
400001,Mumbai,Maharashtra,5,75.00,0,0
```

**Column Details:**
1. **Pincode** - Required, numeric or pattern
2. **City** - Required, text
3. **State** - Required, text
4. **Delivery Days** - Optional, default 7
5. **Shipping Amount** - Optional, decimal, default 0.00
6. **COD Enabled** - Optional, 1=Yes, 0=No, default 1
7. **COD Amount** - Optional, decimal, default 0 (unlimited)

#### Pattern Support in CSV

You can use patterns directly in CSV:
```csv
Pincode,City,State,Delivery Days,Shipping Amount,COD Enabled,COD Amount
1100**,New Delhi,Delhi,3,50.00,1,5000.00
400001-400050,Mumbai,Maharashtra,5,75.00,1,3000.00
```

Each pattern row will be expanded into individual pincodes.

#### Import Process

1. **Prepare CSV file:**
   - Use Excel, Google Sheets, or text editor
   - Save as CSV (comma-separated)
   - Ensure UTF-8 encoding for special characters

2. **Upload:**
   - Click "Choose File"
   - Select your CSV
   - Click "Import Pincodes"

3. **Review Results:**
   - **Imported:** New pincodes added successfully
   - **Skipped:** Already existing pincodes (not updated)
   - **Errors:** Invalid format or data issues

#### Import Tips

✅ **Best Practices:**
- Test with small file first (10-20 rows)
- Remove duplicate pincodes before importing
- Validate pincode format for your country
- Use consistent city/state naming

⚠️ **Common Errors:**
- Wrong number of columns
- Missing required fields (pincode, city, state)
- Invalid pincode format
- File encoding issues (use UTF-8)

❌ **Limitations:**
- Existing pincodes are NOT updated (skipped)
- To update: delete old ones first, then import
- Maximum recommended: 50,000 rows per file

### CSV Export

Access via: **All Pincodes** page

#### Export Options:

1. **Export All:**
   - Exports entire pincode database
   - Includes all columns
   - Same format as import CSV

2. **Export Filtered:**
   - First use search/filter on the page
   - Then export to get only filtered results

3. **Export Selected:**
   - Select specific pincodes (checkboxes)
   - Use bulk action "Export Selected"

#### Export Use Cases:

- **Backup:** Regular database backups
- **Analysis:** Analyze delivery coverage in Excel
- **Transfer:** Move pincodes between sites
- **Audit:** Review and cleanup pincode data

---

## Frontend Display

### Product Page Integration

The pincode checker automatically appears on:
- Single product pages
- Variable product pages
- Product quick view (if theme supports)

**Default Position:** Below the product title/price, above add to cart button

### Checkout Page Integration

**Automatic Detection:**
- If customer enters shipping address with pincode
- Automatically validates during checkout
- Prevents order completion if pincode not serviceable

### Customization

#### CSS Styling

Add custom CSS in **Appearance → Customize → Additional CSS**:

```css
/* Pincode checker container */
.wpc-pincode-checker {
    margin: 20px 0;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 5px;
}

/* Input field */
.wpc-pincode-checker input[type="text"] {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
}

/* Check button */
.wpc-pincode-checker .wpc-check-button {
    background: #0073aa;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
}

/* Success message */
.wpc-success-msg {
    color: green;
    font-weight: bold;
}

/* Error message */
.wpc-error-msg {
    color: red;
}

/* Nearby suggestions */
.wpc-nearby-suggestions {
    margin-top: 10px;
    padding: 10px;
    background: #e7f5fe;
    border-left: 3px solid #0073aa;
}
```

#### JavaScript Events

Custom JavaScript for advanced integration:

```javascript
jQuery(document).on('wpc_pincode_checked', function(event, data) {
    if (data.success) {
        console.log('Pincode available:', data.pincode);
        console.log('Delivery days:', data.delivery_days);
        // Your custom code
    } else {
        console.log('Pincode not available:', data.error);
        // Your custom code
    }
});
```

---

## Troubleshooting

### Common Issues

#### 1. Pincode Checker Not Showing on Product Page

**Possible Causes:**
- Plugin not activated
- WooCommerce not installed
- Product excluded via category filter
- Theme compatibility issue

**Solutions:**
✅ Check plugin is active in Plugins page
✅ Verify WooCommerce is installed and active
✅ Check "Categories for Shipping" in General Settings
✅ Try different theme or contact theme developer

#### 2. Geocoding Not Working

**Possible Causes:**
- Database table missing latitude/longitude columns
- Migration not run
- Server blocking external requests

**Solutions:**
✅ Deactivate and reactivate plugin to trigger migration
✅ Check database: `wp_pincode_checker` table should have `latitude` and `longitude` columns
✅ Contact hosting provider about external API access
✅ Check error logs: `wp-content/debug.log`

#### 3. CSV Import Fails

**Possible Causes:**
- Wrong file format
- Encoding issues
- Too large file

**Solutions:**
✅ Ensure file is UTF-8 encoded CSV
✅ Check first row has correct headers
✅ Split large files into smaller batches (5,000 rows each)
✅ Remove special characters from city/state names

#### 4. Category Rules Not Applying

**Possible Causes:**
- Rule not enabled (toggle switch)
- Product not assigned to category
- Caching issue

**Solutions:**
✅ Check toggle switch is ON in Category Rules tab
✅ Verify product has category assigned in WooCommerce
✅ Clear site cache (if using caching plugin)
✅ Test in incognito browser window

#### 5. Nearby Suggestions Not Showing

**Possible Causes:**
- Geocoding not completed
- No nearby pincodes within 20km
- Database issue

**Solutions:**
✅ Complete geocoding process in Geocoding tab
✅ Check if other pincodes exist in same area
✅ Verify latitude/longitude data in database
✅ Check error logs for API failures

### Debug Mode

Enable WordPress debug mode to see detailed errors:

**Edit wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Check logs:** `wp-content/debug.log`

### Getting Help

1. **Check documentation** - This guide and developer docs
2. **Search FAQs** - WB Plugins → Pincode Checker → FAQ
3. **Check error logs** - Enable debug mode
4. **Contact support** - https://wbcomdesigns.com/support/

---

## Performance Optimization

### Database Optimization

**For Large Pincode Databases (50,000+):**

1. **Add Database Indexes:**
```sql
ALTER TABLE wp_pincode_checker ADD INDEX idx_pincode (pincode);
ALTER TABLE wp_pincode_checker ADD INDEX idx_city (city);
ALTER TABLE wp_pincode_checker ADD INDEX idx_state (state);
```

2. **Regular Cleanup:**
- Remove duplicate pincodes
- Delete unused pincodes
- Optimize database tables monthly

### Caching

**Compatible with:**
- WP Rocket
- W3 Total Cache
- WP Super Cache
- Object cache (Redis/Memcached)

**Cache Strategy:**
- AJAX requests bypass page cache
- Database queries use transients
- Geocoding results cached for 24 hours

### Server Requirements

**Minimum:**
- PHP 7.4+
- MySQL 5.6+
- Memory: 128MB
- Storage: 10MB + (100KB per 1,000 pincodes)

**Recommended:**
- PHP 8.0+
- MySQL 5.7+
- Memory: 256MB
- Storage: 50MB
- CDN for static assets

---

## Best Practices

### Pincode Management

1. **Start Small:** Add pincodes for your primary service area first
2. **Expand Gradually:** Add new areas as you grow
3. **Regular Updates:** Review and update delivery times quarterly
4. **Test Regularly:** Check pincode verification monthly
5. **Backup Data:** Export CSV monthly as backup

### Customer Experience

1. **Clear Messaging:** Use friendly, helpful error messages
2. **Accurate Delivery Times:** Be realistic with delivery estimates
3. **Alternative Options:** Enable nearby suggestions
4. **Mobile-Friendly:** Test on mobile devices regularly
5. **Fast Performance:** Keep pincode database optimized

### Delivery Planning

1. **Zone-Based Rules:** Use category rules for product types
2. **Realistic Timelines:** Account for weekends and holidays
3. **Buffer Time:** Add 1 day buffer to delivery estimates
4. **COD Limits:** Set sensible COD limits to reduce fraud
5. **Shipping Costs:** Update costs when courier rates change

---

## Frequently Asked Questions

### General

**Q: Does this work with any theme?**
A: Yes, it's designed to work with all WooCommerce-compatible themes. Some theme-specific adjustments may be needed.

**Q: Can I use this with other shipping plugins?**
A: Yes, this plugin focuses on availability checking and can work alongside shipping calculators.

**Q: Is it compatible with HPOS (High-Performance Order Storage)?**
A: Yes, fully compatible with WooCommerce HPOS.

### Features

**Q: Can I set different shipping costs per pincode?**
A: Yes, in the Add Pincode form, set the "Shipping Amount" field.

**Q: Can I disable COD for specific pincodes?**
A: Yes, uncheck "Cash on Delivery" when adding/editing a pincode.

**Q: How many pincodes can I add?**
A: No hard limit, but recommend under 100,000 for optimal performance.

### Technical

**Q: Where is pincode data stored?**
A: In `wp_pincode_checker` database table in your WordPress database.

**Q: Is geocoding data stored locally?**
A: Yes, latitude/longitude stored in database after one-time geocoding.

**Q: Does it work with caching plugins?**
A: Yes, AJAX requests bypass cache automatically.

---

**Next Steps:**
- Explore [Developer Guide](developer-guide.md) for custom integration
- Review [Hooks & Filters](hooks-filters.md) for advanced customization
- Check [Database Schema](database-schema.md) for technical details
