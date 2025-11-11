# Pincode Checker for WooCommerce - Documentation

**Version:** 1.5.0
**Author:** Wbcom Designs
**Requires WordPress:** 5.0+
**Requires WooCommerce:** 5.0+
**Requires PHP:** 7.4+

## Overview

Pincode Checker for WooCommerce enables location-based delivery verification with pincode/ZIP code checking. Show product availability, delivery timelines, shipping costs, and COD options based on customer location.

## Documentation Navigation

### For Store Owners & Administrators

- **[Customer Guide](customer-guide.md)** - Complete guide for using all features
  - Installation & Setup
  - General Settings Configuration
  - Managing Pincodes (Add, Upload, Import)
  - Category-Specific Delivery Rules
  - Geocoding & Nearby Suggestions
  - Customization Options

### For Developers

- **[Developer Guide](developer-guide.md)** - Technical documentation
  - Architecture Overview
  - Core Classes & Methods
  - Custom Integration Examples
  - Best Practices

- **[Hooks & Filters Reference](hooks-filters.md)** - Complete API reference
  - Actions
  - Filters
  - Usage Examples

- **[Database Schema](database-schema.md)** - Database structure
  - Tables
  - Columns
  - Indexes
  - Migration History

## Key Features

### 🎯 Core Features
- Real-time pincode availability checking on product pages
- Delivery timeline calculation based on pincode
- Shipping cost display per pincode
- Cash on Delivery (COD) availability and limits
- Product page and checkout integration

### 🚀 Advanced Features (v1.5.0+)
- **Pattern & Range Support**: Bulk add pincodes using wildcards (`*`, `?`) and ranges (`START-END`)
- **Category-Specific Rules**: Set different delivery times for different product categories
- **Nearby Suggestions**: Show nearby serviceable pincodes when customer's location is unavailable (Free OpenStreetMap integration)

### 📊 Management Features
- Single pincode addition with detailed settings
- Bulk pattern-based pincode creation
- CSV import/export for mass pincode management
- Search, filter, and sort pincode database
- Bulk actions (delete, update)

### 🎨 Customization
- Customizable messages and labels
- Multiple date format options
- Button text customization
- Cart button disable options
- Required field validation

## Quick Start

1. **Install the plugin** through WordPress admin or upload manually
2. **Activate the plugin** in WordPress plugins page
3. **Add your serviceable pincodes** via WB Plugins → Pincode Checker → Add Pincodes
4. **Configure settings** in General tab for messages and delivery options
5. **Test on a product page** by entering a pincode

## Support & Contributing

- **Plugin URI:** https://wbcomdesigns.com/downloads/pincode-checker-for-woocommerce/
- **Author Website:** https://wbcomdesigns.com
- **License:** GPL-2.0+

## Version History

See [CHANGELOG.md](changelog.md) for detailed version history.

---

**Need Help?** Start with the [Customer Guide](customer-guide.md) for step-by-step instructions or [Developer Guide](developer-guide.md) for technical integration.
