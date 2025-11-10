# Pincode Checker for WooCommerce

**Version:** 1.4.0
**Requires:** WordPress 5.0+, WooCommerce 5.0+, PHP 7.4+
**License:** GPL v2 or later

Enable location-based delivery verification for your WooCommerce store. Check product availability, delivery timelines, and shipping options based on customer's pincode/ZIP code.

## ✅ What's Fixed in Version 1.4.0

### Critical Fixes Completed:
1. **Database Table Creation** - Fixed compatibility issues with various MySQL versions
2. **Version Synchronization** - All components now use version 1.4.0
3. **Plugin Naming** - Renamed to "Pincode Checker for WooCommerce" for WordPress.org compliance
4. **Text Domain** - Updated to `pincode-checker-for-woocommerce`

### Database Fix Details:
- Removed problematic BOOLEAN type (using TINYINT instead)
- Fixed TIMESTAMP issues (using DATETIME for better compatibility)
- Improved table creation with multiple fallback methods
- Added automatic retry on activation
- Sample data automatically inserted on fresh install

## 🚀 Features

### Core Functionality
- ✅ **Pincode Verification** - Check delivery availability by pincode/ZIP code
- ✅ **Delivery Date Display** - Show estimated delivery dates
- ✅ **Shipping Costs** - Display location-specific shipping charges
- ✅ **COD Availability** - Show Cash on Delivery options by location
- ✅ **Session Storage** - Remember customer's pincode across pages
- ✅ **Bulk Management** - Import/Export pincodes via CSV
- ✅ **Sample Data** - 5 major cities pre-loaded for testing

### Admin Features
- **Pincode Management** - Add, edit, delete individual pincodes
- **Bulk Operations** - Delete multiple pincodes at once
- **CSV Import/Export** - Manage large pincode lists
- **Search & Filter** - Find pincodes quickly
- **Settings Panel** - Customize all aspects of the checker

### Display Options
- **Multiple Positions** - Choose where to show the checker:
  - Before Add to Cart button (default)
  - After Add to Cart button
  - After product summary
  - Via shortcode anywhere
- **Custom Styling** - Color pickers for buttons and text
- **Label Customization** - Change all display text

## 📋 Installation

1. Upload the `pincode-checker-for-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Go to **WooCommerce → Pincode Checker** to configure settings
4. The database table will be created automatically with sample data

### Manual Database Creation (if needed)
If the automatic table creation fails, you can create it manually:

```sql
CREATE TABLE wp_pincode_checker (
  id int(11) NOT NULL AUTO_INCREMENT,
  pincode varchar(20) NOT NULL,
  city varchar(100) NOT NULL,
  state varchar(100) NOT NULL,
  delivery_days int(11) DEFAULT 1,
  shipping_amount decimal(10,2) DEFAULT 0.00,
  case_on_delivery tinyint(1) DEFAULT 0,
  cod_amount decimal(10,2) DEFAULT 0.00,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_pincode (pincode),
  KEY idx_city (city),
  KEY idx_state (state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🎯 Usage

### Basic Setup
1. Navigate to **WooCommerce → Pincode Checker**
2. Add your delivery pincodes with:
   - Pincode/ZIP code
   - City and State
   - Delivery days (1-30)
   - Shipping amount (optional)
   - COD availability and charges

### Using the Shortcode
Display the pincode checker anywhere using:
```
[wpc_pincode_checker]
```

### Settings Configuration

#### General Settings
- **Date Display** - Enable/disable delivery date
- **Date Format** - Choose date format (e.g., "M jS", "d/m/Y")
- **COD Display** - Show/hide Cash on Delivery option
- **Shipping Cost** - Display shipping charges
- **Button Text** - Customize "Check" and "Change" button labels

#### Display Position
Choose from 4 positions:
1. Before Add to Cart button
2. After Add to Cart button
3. After Product Summary
4. Use shortcode (for custom placement)

#### Styling Options
- **Text Color** - Main text color
- **Button Color** - Check button background
- **Button Text Color** - Check button text

#### Advanced Options
- **Hide Add to Cart** - Disable cart button until pincode verified
- **Category Exclusions** - Skip checker for specific categories
- **Required Field** - Make pincode check mandatory

## 📊 CSV Import Format

Your CSV should have these columns (in order):
```
pincode,city,state,delivery_days,shipping_amount,case_on_delivery,cod_amount
110001,New Delhi,Delhi,2,0,1,25
400001,Mumbai,Maharashtra,3,50,1,30
560001,Bangalore,Karnataka,2,0,1,20
```

**Column Details:**
- `pincode` - The postal code (required)
- `city` - City name (required)
- `state` - State/Province name (required)
- `delivery_days` - Number of days for delivery (1-30)
- `shipping_amount` - Shipping cost (0.00 for free)
- `case_on_delivery` - COD available (1) or not (0)
- `cod_amount` - COD charges if applicable

## 🔧 WooCommerce Hooks Used

The plugin integrates with these WooCommerce hooks:

### Product Page Hooks
- `woocommerce_before_add_to_cart_button`
- `woocommerce_after_add_to_cart_button`
- `woocommerce_after_add_to_cart_quantity`
- `woocommerce_single_product_summary`

### Cart/Checkout Hooks
- `woocommerce_before_cart`
- `woocommerce_checkout_before_customer_details`
- `woocommerce_after_checkout_validation`

### Admin Hooks
- `woocommerce_admin_order_data_after_billing_address`
- `woocommerce_process_shop_order_meta`

## 🎨 Developer Information

### Available Filters

```php
// Modify pincode check result
apply_filters('wpc_pincode_check_result', $result, $pincode);

// Change delivery date format
apply_filters('wpc_delivery_date_format', $format);

// Modify shipping amount display
apply_filters('wpc_shipping_amount', $amount, $pincode);

// Customize COD message
apply_filters('wpc_cod_message', $message, $is_available);
```

### Available Actions

```php
// After pincode check
do_action('wpc_after_pincode_check', $pincode, $result);

// Before displaying form
do_action('wpc_before_pincode_form');

// After form submission
do_action('wpc_after_form_submit', $pincode, $product_id);
```

### JavaScript Events

```javascript
// Listen for pincode check
jQuery(document).on('wpc_pincode_checked', function(e, data) {
    console.log('Pincode:', data.pincode);
    console.log('Available:', data.available);
});

// Listen for form display
jQuery(document).on('wpc_form_displayed', function(e) {
    // Custom logic here
});
```

## 🐛 Troubleshooting

### Database Table Not Created
1. Check PHP error logs for specific errors
2. Verify database user has CREATE TABLE permission
3. Try deactivating and reactivating the plugin
4. Use the manual SQL query provided above

### Pincode Checker Not Showing
1. Check position setting in admin
2. Verify theme compatibility
3. Try using shortcode instead
4. Check for JavaScript errors in console

### Import Not Working
1. Verify CSV format matches requirements
2. Check file permissions
3. Ensure no duplicate pincodes
4. Try smaller batches (< 1000 rows)

### Styling Issues
1. Clear browser and site cache
2. Check theme CSS conflicts
3. Use browser inspector to debug
4. Try different position settings

## 📝 Changelog

### Version 1.4.0 (2025-01-10)
- **Major Update:** Plugin renamed for WordPress.org compliance
- **Fixed:** Database table creation issues
- **Fixed:** Version synchronization across all files
- **Fixed:** Text domain updated to match plugin slug
- **Improved:** Better MySQL compatibility
- **Added:** Comprehensive documentation
- **Added:** Automatic sample data insertion
- **Enhanced:** Error handling and logging

### Version 1.3.6
- WooCommerce 10.1.2 compatibility
- WordPress 6.8.2 compatibility
- Improved error handling
- Security enhancements

## 🤝 Support

For issues or feature requests, please visit:
- [Plugin Homepage](https://wbcomdesigns.com/downloads/pincode-checker-for-woocommerce/)
- [Support Forum](https://wordpress.org/support/plugin/pincode-checker-for-woocommerce/)
- [GitHub Issues](https://github.com/wbcomdesigns/pincode-checker-for-woocommerce)

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

**Created by:** [Wbcom Designs](https://wbcomdesigns.com)
**Version:** 1.4.0
**Last Updated:** January 10, 2025