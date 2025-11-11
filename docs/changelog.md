# Changelog

All notable changes to Pincode Checker for WooCommerce will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.5.0] - 2025-01-11

### Added

#### Pincode Range & Pattern Support
- **Pattern Wildcards**: Use `*` and `?` for flexible pincode matching
  - `*` matches any number of digits (e.g., `1100*` generates 11000-11009)
  - `?` matches exactly one digit (e.g., `110?5` generates 11005, 11015, etc.)
- **Range Expansion**: Create pincodes using `START-END` format
  - Example: `110001-110100` creates 100 pincodes automatically
- **Bulk Add by Pattern**: New admin page for pattern-based bulk pincode creation
  - Real-time pattern preview showing count before generation
  - Auto-geocoding option for generated pincodes
  - Support for up to 10,000 pincodes per pattern
- **CSV Pattern Support**: Import patterns directly in CSV files
  - Patterns expanded automatically during import
  - Mixed support for individual pincodes and patterns in same file

#### Category-Specific Delivery Rules
- **Global Category Rules**: Set different delivery times for product categories
  - Example: Electronics = 5 days, Groceries = 1 day
  - Rules apply site-wide to all pincodes
  - Override default pincode delivery times
- **Priority System**: Category rules > Pincode settings > Default settings
- **Visual UI**: Toggle switches and responsive table for category management
- **Admin Tab**: Dedicated "Category Rules" tab with modern interface
  - Product count per category
  - Enable/disable toggle for each category
  - 1-365 days range with validation

#### Nearby Pincode Suggestions
- **Smart Suggestions**: Show nearby serviceable pincodes when customer's location unavailable
  - Reduces cart abandonment
  - Provides alternative delivery locations
- **Distance-Based Matching**: Find pincodes within 20km radius
  - Uses geocoded coordinates
  - Shows distance to customer
  - Sorted by proximity
- **Prefix-Based Fallback**: When geocoding unavailable
  - 4-digit prefix matching
  - 3-digit prefix fallback
  - Always shows alternatives
- **Geocoding Integration**: Free OpenStreetMap Nominatim service
  - No API key required
  - Batch processing with progress bar
  - Rate-limited to respect service terms (1 req/sec)
  - One-time setup, no maintenance
- **Admin Tab**: "Geocoding" tab with setup wizard
  - Visual progress tracking
  - Statistics dashboard
  - Estimated time calculator

#### Database Enhancements
- **New Columns**:
  - `category_rules` (TEXT) - Reserved for pincode-level category overrides
  - `latitude` (DECIMAL 10,8) - Geocoded latitude coordinate
  - `longitude` (DECIMAL 11,8) - Geocoded longitude coordinate
- **Smart Migration**: Automatic column detection and creation
  - Checks column existence, not just version
  - Runs on plugin load if needed
  - Self-healing database updates

#### Technical Improvements
- **New Core Classes**:
  - `Woo_Pincode_Pattern_Handler` - Pattern parsing and expansion
  - `Woo_Pincode_Category_Rules` - Category rule logic
  - `Woo_Pincode_Nearby_Suggestions` - Geocoding and nearby search
- **Product ID Integration**: Pass product_id from frontend to backend
  - Enables category-specific rules to work correctly
  - Improves delivery time accuracy
- **AJAX Endpoints**:
  - `wpc_preview_pattern` - Preview pincode count for pattern
  - `wpc_geocode_batch` - Process geocoding in batches
- **Performance Optimization**:
  - Reduced geocoding API calls from 2 to 1 per pincode
  - Transient-based caching for API responses
  - Haversine formula for accurate distance calculation

### Changed
- **Admin UI**: Reorganized settings into logical tabs
  - Welcome, General, All Pincodes, Add Pincodes, Upload Pincodes, FAQ, Category Rules, Geocoding
  - Cleaner General Settings tab (moved advanced features to separate tabs)
  - Improved table styling with WordPress standards
- **CSV Import**: Now supports patterns and ranges
  - Pattern expansion during import
  - Auto-geocoding option for imports
  - Better error reporting
- **Migration Logic**: Enhanced database update detection
  - Column existence check
  - Version-independent updates
  - Automatic repair capability

### Fixed
- **Product ID Missing**: Fixed category rules not applying
  - Product ID now properly passed in AJAX requests
  - Category rules work correctly on product pages
- **Database Migration**: Fixed columns not being created
  - Migration now checks actual column existence
  - Works regardless of version number inconsistencies
  - Logs migration status for debugging
- **Double API Calls**: Optimized bulk add geocoding
  - Reduced from 2 calls to 1 per pincode
  - Faster bulk operations
  - Better rate limit compliance

### Developer Notes
- **New Hooks**:
  - `wpc_pincode_geocoded` - After successful geocoding
  - `wpc_bulk_pincodes_added` - After bulk addition
  - `wpc_nearby_radius` - Filter nearby search radius
  - `wpc_nearby_limit` - Filter suggestion count
  - `wpc_nearby_suggestions_html` - Customize suggestions display
- **New Filters**:
  - `wpc_pattern_max_count` - Set max pincodes per pattern
  - `wpc_category_rule_priority` - Control multi-category priority
- **Breaking Changes**: None - Fully backward compatible

---

## [1.4.0] - 2024-10-15

### Added
- **Shipping Amount Per Pincode**: Set custom shipping costs for each pincode
  - Decimal precision (10,2) for accurate pricing
  - Free shipping option (0.00)
  - Display in success messages
- **COD Amount Limit**: Maximum order value for Cash on Delivery
  - Prevent fraud on high-value orders
  - 0 = unlimited COD
  - Per-pincode control

### Changed
- **Admin Interface**: Complete overhaul
  - Modern wbcom design system
  - Responsive table layouts
  - Better mobile experience
- **CSS & JS Loading**: Fixed duplicate loading issues
  - Proper enqueue with dependencies
  - Minified assets for production
  - Conditional loading

### Fixed
- **Text Domain**: Corrected from 'woo-pincode-checker' to 'pincode-checker-for-woocommerce'
  - All translations now work correctly
  - Consistent naming throughout
- **Sanitization**: Added proper callback for settings
  - Security hardening
  - XSS prevention
  - SQL injection protection

---

## [1.3.0] - 2024-05-20

### Added
- **Timestamp Tracking**: `created_at` and `updated_at` columns
  - Track when pincodes are added
  - Monitor last update time
  - Useful for analytics and auditing
- **Bulk Actions**: Select and delete multiple pincodes
  - Checkboxes in admin table
  - Bulk delete confirmation
- **Search & Filter**: Enhanced admin table
  - Search by pincode, city, state
  - Sort by any column
  - Pagination for large datasets

### Changed
- **Database Structure**: Added timestamp columns
  - Auto-updated on changes
  - Migration script included
- **Admin Table**: Improved performance
  - Ajax-based operations
  - Faster pagination
  - Better sorting

---

## [1.2.0] - 2024-02-10

### Added
- **CSV Import/Export**: Bulk pincode management
  - Import thousands of pincodes at once
  - Export for backup or analysis
  - Error reporting for failed imports
  - Skip duplicate pincodes
- **Delivery Days Per Pincode**: Custom delivery time
  - Override default delivery days
  - Per-pincode granularity
  - Calculated delivery dates

### Changed
- **Settings Page**: Improved organization
  - Tab-based navigation
  - Upload Pincodes tab added
  - FAQ section added
- **Message Customization**: More variables available
  - `[pincode]`, `[city]`, `[state]`
  - `[delivery_date]`, `[delivery_days]`
  - `[shipping_amount]`, `[cod_availability]`

### Fixed
- **Duplicate Prevention**: Better handling during import
  - Skip existing pincodes
  - Report skipped count
  - Maintain data integrity

---

## [1.1.0] - 2023-11-05

### Added
- **COD Settings**: Per-pincode Cash on Delivery control
  - Enable/disable COD for specific areas
  - Manage COD availability
- **Admin Menu**: Reorganized menu structure
  - All Pincodes page
  - Add New Pincode page
  - Settings page
- **HPOS Compatibility**: WooCommerce High-Performance Order Storage
  - Declared compatibility
  - Tested with HPOS enabled

### Changed
- **Database Schema**: Added `case_on_delivery` column
  - Boolean field for COD
  - Default enabled (1)
- **Frontend Display**: Show COD availability
  - Display in success message
  - Conditional display

---

## [1.0.0] - 2023-08-15

### Added
- **Initial Release**: Core pincode checking functionality
  - Real-time pincode availability verification
  - Product page integration
  - Checkout page integration
- **Admin Panel**: Basic management interface
  - Add single pincode
  - View all pincodes
  - Edit/delete pincodes
- **Settings**: Configuration options
  - Customize check text
  - Customize button text
  - Error messages
  - Success messages
- **Database Table**: `wp_pincode_checker`
  - Core schema with pincode, city, state
  - Delivery days configuration
  - Unique pincode constraint
- **Security**: WordPress standards
  - Nonce verification
  - Capability checks
  - Input sanitization
  - SQL injection prevention

---

## Upgrade Notes

### Upgrading to 1.5.0

**Automatic Changes:**
- Database columns added automatically
- Settings preserved
- Pincodes remain intact

**New Features to Configure:**
1. **Category Rules** (Optional):
   - Visit: WB Plugins → Pincode Checker → Category Rules
   - Enable rules for categories needing different delivery times
   - Set delivery days (1-365)

2. **Geocoding** (Optional but Recommended):
   - Visit: WB Plugins → Pincode Checker → Geocoding
   - Click "Start Geocoding" button
   - Wait for completion (keep page open)
   - Enables distance-based nearby suggestions

3. **Pattern Features** (Optional):
   - Use wildcards in CSV imports: `1100*`, `110?5`
   - Use ranges in CSV imports: `110001-110100`
   - Visit "Bulk Add by Pattern" for UI-based pattern creation

**No Breaking Changes:**
- All existing functionality works as before
- Existing pincodes unaffected
- Settings preserved
- Frontend display unchanged (unless geocoding enabled)

---

### Upgrading from 1.3.0 or earlier

If upgrading from versions before 1.4.0, note:
- New columns: `shipping_amount`, `cod_amount` (will be added automatically)
- Text domain changed (re-save translated strings)
- Admin UI updated (may need to clear browser cache)

---

## Future Roadmap

### Planned for 1.6.0
- **Rate Limiting**: Prevent abuse of pincode checking
- **Analytics Dashboard**: Track most-searched pincodes
- **REST API**: External integrations
- **Multi-Currency**: Support different currencies per pincode
- **Shipping Zones**: Integration with WooCommerce shipping zones

### Under Consideration
- **Mobile App**: Companion app for pincode management
- **Advanced Geocoding**: Multiple geocoding service support
- **Delivery Slots**: Time slot selection for customers
- **SMS Notifications**: Delivery updates via SMS
- **Webhook Support**: Real-time notifications for integrations

---

## Support

**Documentation:**
- [Customer Guide](customer-guide.md) - Complete user guide
- [Developer Guide](developer-guide.md) - Technical documentation
- [Hooks & Filters](hooks-filters.md) - API reference
- [Database Schema](database-schema.md) - Database documentation

**Contact:**
- Plugin URI: https://wbcomdesigns.com/downloads/pincode-checker-for-woocommerce/
- Support: https://wbcomdesigns.com/support/
- Author: Wbcom Designs (https://wbcomdesigns.com)

---

**Legend:**
- `Added` - New features
- `Changed` - Changes in existing functionality
- `Deprecated` - Features marked for removal
- `Removed` - Deleted features
- `Fixed` - Bug fixes
- `Security` - Vulnerability fixes
