<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://wbcomdesigns.com/plugins
 * @since 1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin
 */

require_once 'class-woo-pincode-checker-listing.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin
 * @author     wbcomdesigns <admin@wbcomdesigns.com>
 */
class Woo_Pincode_Checker_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The Settings tabs of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_settings_tabs    Plugin Setting Tab.
	 */
	private $plugin_settings_tabs;
		
	/**
	 * Woo_Pincode_Checker_Listing
	 * 
	 * @since  1.0.0
	 * @access private
	 * @var mixed          $Woo_Pincode_Checker_Listing   Dynamic property creation.
	 */
	private $Woo_Pincode_Checker_Listing;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		
		// Add table existence check
		add_action( 'admin_init', array( $this, 'check_table_existence' ), 1 );
	}

	/**
	 * Check if database table exists and show notice if missing
	 */
	public function check_table_existence() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'pincode_checker';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		if ( ! $table_exists ) {
			// Check if we're on a plugin page to show appropriate notice
			$current_screen = get_current_screen();
			if ( $current_screen && ! empty( $current_screen->id ) && is_string( $current_screen->id ) && strpos( $current_screen->id, 'pincode' ) !== false ) {
				add_action( 'admin_notices', array( $this, 'show_table_missing_notice' ) );
			}
		}
	}

	/**
	 * Show admin notice when table is missing
	 */
	public function show_table_missing_notice() {
		echo '<div class="notice notice-error is-dismissible">';
		echo '<p><strong>Woo Pincode Checker:</strong> Database table is missing!</p>';
		echo '<p>The plugin cannot function without its database table.</p>';
		echo '<p>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ) . '" class="button button-primary">Fix Database Issue</a> ';
		echo '<a href="#" onclick="location.reload();" class="button">Retry</a>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Validate and sanitize pincode input
	 *
	 * @param string $pincode The pincode to validate
	 * @return string|false Sanitized pincode or false if invalid
	 */
	private function validate_pincode( $pincode ) {
		// PHP 8.3+ compatibility: ensure we have a string
		if ( ! is_string( $pincode ) ) {
			$pincode = is_null( $pincode ) ? '' : (string) $pincode;
		}
		
		// Remove extra whitespace and normalize
		$pincode = trim( $pincode );
		
		// Check if empty after trimming
		if ( empty( $pincode ) ) {
			return false;
		}
		
		// Safely use preg_replace
		$pincode = preg_replace('/\s+/', ' ', $pincode);
		if ( $pincode === null ) {
			return false; // preg_replace error
		}
		
		// Check length (3-10 characters)
		$length = mb_strlen( $pincode, 'UTF-8' );
		if ( $length < 3 || $length > 10 ) {
			return false;
		}
		
		// Check format - alphanumeric with optional single spaces, but not at start/end
		if ( ! preg_match( '/^[A-Za-z0-9](?:[A-Za-z0-9\s]*[A-Za-z0-9])?$/u', $pincode ) ) {
			return false;
		}
		
		// Additional security - prevent potential XSS
		$pincode = sanitize_text_field( $pincode );
		
		// Final check - ensure no malicious patterns
		if ( preg_match('/[<>"\']/', $pincode) ) {
			return false;
		}
		
		return $pincode;
	}

	/**
	 * Check user capabilities for admin actions
	 *
	 * @return bool
	 */
	private function check_admin_capabilities() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Verify nonce for security
	 *
	 * @param string $nonce_value The nonce value
	 * @param string $nonce_action The nonce action
	 * @return bool
	 */
	private function verify_nonce( $nonce_value, $nonce_action ) {
		return wp_verify_nonce( $nonce_value, $nonce_action );
	}

	/**
	 * Safe database operation wrapper
	 *
	 * @param string $query The SQL query
	 * @param array $params Query parameters
	 * @return mixed Query result or false on failure
	 */
	private function safe_db_operation( $query, $params = array() ) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'pincode_checker';
		
		// Check if table exists before operation
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;
		
		if ( ! $table_exists ) {
			error_log( 'WPC: Attempted database operation on non-existent table' );
			
			// Try to create table
			if ( function_exists( 'activate_woo_pincode_checker' ) ) {
				activate_woo_pincode_checker();
				
				// Check again
				$table_exists = $wpdb->get_var( $wpdb->prepare( 
					"SHOW TABLES LIKE %s", 
					$table_name 
				) ) == $table_name;
			}
			
			if ( ! $table_exists ) {
				return false;
			}
		}
		
		// Execute the query
		if ( ! empty( $params ) ) {
			return $wpdb->query( $wpdb->prepare( $query, $params ) );
		} else {
			return $wpdb->query( $query );
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();

		// Get current page from request
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Get plugin URL safely - use the constant which is guaranteed to be set
		$plugin_url = defined( 'WPCP_PLUGIN_URL' ) ? WPCP_PLUGIN_URL : plugins_url( '/woo-pincode-checker/' );

		// ONLY load custom CSS on the main settings page and wbcom plugins page
		// Do NOT load on pincode_lists or add_wpc_pincode pages (they use WordPress defaults)
		$is_settings_page = false;
		if ( $screen && ! empty( $screen->id ) ) {
			// Check if screen ID is the main settings page
			$is_settings_page = (
				strpos( $screen->id, 'pincode-checker-for-woocommerce' ) !== false ||
				strpos( $screen->id, 'wbcomplugins' ) !== false
			);
		}

		// Also check GET parameter as fallback
		if ( ! $is_settings_page && ! empty( $page ) ) {
			$is_settings_page = (
				$page === 'pincode-checker-for-woocommerce' ||
				$page === 'woo-pincode-checker' // Alternative page slug
			);
		}

		// Enqueue styles ONLY on settings pages
		if ( $is_settings_page ) {
			wp_enqueue_style(
				'wpc-select2',
				$plugin_url . 'admin/css/select2.min.css',
				array(),
				$this->version,
				'all'
			);

			wp_enqueue_style(
				$this->plugin_name,
				$plugin_url . 'admin/css/woo-pincode-checker-admin.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		// Get current page from request
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Get plugin URL safely - use the constant which is guaranteed to be set
		$plugin_url = defined( 'WPCP_PLUGIN_URL' ) ? WPCP_PLUGIN_URL : plugins_url( '/woo-pincode-checker/' );

		// ONLY load scripts on the main settings page and wbcom plugins page
		// Do NOT load on pincode_lists or add_wpc_pincode pages (they use WordPress defaults)
		$is_settings_page = false;
		if ( $screen && ! empty( $screen->id ) ) {
			// Check if screen ID is the main settings page
			$is_settings_page = (
				strpos( $screen->id, 'pincode-checker-for-woocommerce' ) !== false ||
				strpos( $screen->id, 'wbcomplugins' ) !== false
			);
		}

		// Also check GET parameter as fallback
		if ( ! $is_settings_page && ! empty( $page ) ) {
			$is_settings_page = (
				$page === 'pincode-checker-for-woocommerce' ||
				$page === 'woo-pincode-checker' // Alternative page slug
			);
		}

		// Enqueue scripts ONLY on settings pages
		if ( $is_settings_page ) {
			wp_enqueue_script(
				'wpc-select2',
				$plugin_url . 'admin/js/select2.min.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			wp_enqueue_script(
				$this->plugin_name,
				$plugin_url . 'admin/js/woo-pincode-checker-admin.js',
				array( 'jquery', 'wp-color-picker'),
				$this->version,
				true
			);

			// Localize script with nonce
			wp_localize_script(
				$this->plugin_name,
				'wpc_admin_ajax',
				array(
					'url'   => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wpc-admin-nonce' ),
				)
			);
		}
	}

	/**
	 * Hide all notices from the setting page.
	 *
	 * @return void
	 */
	public function wpc_hide_all_admin_notices_from_setting_page() {
		$wbcom_pages_array  = array( 'wbcomplugins', 'wbcom-plugins-page', 'wbcom-support-page', 'pincode-checker-for-woocommerce' );
		$page_input = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$wbcom_setting_page = ! empty( $page_input ) ? $page_input : '';

		if ( in_array( $wbcom_setting_page, $wbcom_pages_array, true ) ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	}

	/**
	 * Add Woo Pincode Checker Menu in admin.
	 *
	 * @since 1.0.0
	 */
	public function wpc_admin_menu() {
		add_menu_page( 
			esc_html__( 'Pincodes', 'pincode-checker-for-woocommerce' ), 
			esc_html__( 'Pincodes', 'pincode-checker-for-woocommerce' ), 
			'manage_options', 
			'pincode_lists', 
			array( $this, 'wpc_pincode_lists_func' ), 
			'dashicons-location-alt', 
			'50' 
		);

		$page_hook = add_submenu_page( 
			'pincode_lists', 
			esc_html__( 'All Pincodes', 'pincode-checker-for-woocommerce' ), 
			esc_html__( 'All Pincodes', 'pincode-checker-for-woocommerce' ), 
			'manage_options', 
			'pincode_lists', 
			array( $this, 'wpc_pincode_lists_func' ) 
		);

		add_submenu_page(
			'pincode_lists',
			esc_html__( 'Add New Pincode', 'pincode-checker-for-woocommerce' ),
			esc_html__( 'Add New Pincode', 'pincode-checker-for-woocommerce' ),
			'manage_options',
			'add_wpc_pincode',
			array( $this, 'wpc_add_pincode_func' )
		);

		add_submenu_page(
			'pincode_lists',
			esc_html__( 'Bulk Add by Pattern', 'pincode-checker-for-woocommerce' ),
			esc_html__( 'Bulk Add by Pattern', 'pincode-checker-for-woocommerce' ),
			'manage_options',
			'bulk_add_wpc_pincode',
			array( $this, 'wpc_bulk_add_pincode_func' )
		);

		if ( class_exists( 'WooCommerce' ) ) {
			if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
				add_menu_page(
					esc_html__( 'WB Plugins', 'pincode-checker-for-woocommerce' ),
					esc_html__( 'WB Plugins', 'pincode-checker-for-woocommerce' ),
					'manage_options',
					'wbcomplugins',
					array( $this, 'wpc_admin_settings_page' ),
					'dashicons-lightbulb',
					59
				);
				add_submenu_page(
					'wbcomplugins',
					esc_html__( 'General', 'pincode-checker-for-woocommerce' ),
					esc_html__( 'General', 'pincode-checker-for-woocommerce' ),
					'manage_options',
					'wbcomplugins'
				);
			}
			add_submenu_page(
				'wbcomplugins',
				esc_html__( 'Pincode Checker', 'pincode-checker-for-woocommerce' ),
				esc_html__( 'Pincode Checker', 'pincode-checker-for-woocommerce' ),
				'manage_options',
				'pincode-checker-for-woocommerce',
				array( $this, 'wpc_admin_settings_page' )
			);
		}
		
		/* screen Option */
		add_action( 'load-' . $page_hook, array( $this, 'load_user_list_table_screen_options' ) );
	}

	/**
	 * Actions performed to create a submenu page content.
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public function wpc_admin_settings_page() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pincode-checker-for-woocommerce' ) );
		}

		$tab_input = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		$current = ! empty( $tab_input ) ? sanitize_text_field( $tab_input ) : 'wpc-welcome';

		?>
		<div class="wrap">
			<div class="wbcom-bb-plugins-offer-wrapper">
				<div id="wb_admin_logo">
				</div>
			</div>
			<div class="wbcom-wrap">
				<div class="blpro-header">
					<div class="wbcom_admin_header-wrapper">
						<div id="wb_admin_plugin_name">
							<?php esc_html_e( 'Woo Pincode Checker', 'pincode-checker-for-woocommerce' ); ?>
							<span>
								<?php
								printf( 
									esc_html__( 'Version %s', 'pincode-checker-for-woocommerce' ), 
									esc_html( WOO_PINCODE_CHECKER_VERSION ) 
								);
								?>
							</span>
						</div>
						<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
					</div>
				</div>
				<div class="wbcom-admin-settings-page">
					<?php
					$this->wpc_plugin_settings_tabs();
					settings_fields( $current );
					do_settings_sections( $current );
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Actions performed to create tabs on the sub menu page.
	 */
	public function wpc_plugin_settings_tabs() {
		$tab_input = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		$current = ! empty( $tab_input ) ? sanitize_text_field( $tab_input ) : 'wpc-welcome';

		$tab_html = '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html( 'Menu' ) . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';

		foreach ( $this->plugin_settings_tabs as $edd_tab => $tab_name ) {
			if ( 'wpc-pincodes' === $edd_tab ) {
				$class         = ( $edd_tab === $current ) ? 'nav-tab-active' : '';
				$pincode_lists = 'pincode_lists';
				$tab_html     .= '<li class="' . esc_attr( $edd_tab ) . '"><a id="' . esc_attr( $edd_tab ) . '" class="nav-tab ' . esc_attr( $class ) . '" href="admin.php?page=' . esc_attr( $pincode_lists ) . '">' . esc_html( $tab_name ) . '</a></li>';
			} elseif ( 'wpc-add-pincodes' === $edd_tab ) {
				$class        = ( $edd_tab === $current ) ? 'nav-tab-active' : '';
				$add_pincodes = 'add_wpc_pincode';
				$tab_html    .= '<li class="' . esc_attr( $edd_tab ) . '"><a id="' . esc_attr( $edd_tab ) . '" class="nav-tab ' . esc_attr( $class ) . '" href="admin.php?page=' . esc_attr( $add_pincodes ) . '">' . esc_html( $tab_name ) . '</a></li>';
			} else {
				$class     = ( $edd_tab === $current ) ? 'nav-tab-active' : '';
				$page      = 'pincode-checker-for-woocommerce';
				$tab_html .= '<li class="' . esc_attr( $edd_tab ) . '"><a id="' . esc_attr( $edd_tab ) . '" class="nav-tab ' . esc_attr( $class ) . '" href="admin.php?page=' . esc_attr( $page ) . '&tab=' . esc_attr( $edd_tab ) . '">' . esc_html( $tab_name ) . '</a></li>';
			}
		}
		$tab_html .= '</ul></div></div>';
		echo wp_kses_post( $tab_html );
	}

	/**
	 * Get welcome settings html.
	 */
	public function wpc_welcome_content() {
		include_once 'partials/woo-welcome-page.php';
	}

	/**
	 * Get general settings html.
	 */
	public function wpc_general_settings_content() {
		include_once 'partials/woo-pincode-checker-admin-display.php';
	}

	/**
	 * Sanitize general settings before saving to database.
	 *
	 * @param array $input The input array from the settings form.
	 * @return array Sanitized settings array.
	 */
	public function wpc_general_settings_sanitize( $input ) {
		// Get existing settings to preserve values from other tabs
		$existing_settings = get_option( 'wpc_general_settings', array() );

		// Start with existing settings instead of empty array
		$sanitized = is_array( $existing_settings ) ? $existing_settings : array();

		// Detect which tab is being saved
		// Category Rules tab has 'global_category_rules' field
		// General tab has other configuration fields
		$is_category_rules_tab = isset( $input['global_category_rules'] );
		$is_general_tab = isset( $input['pincode_position'] ) || isset( $input['delivery_date'] ) || isset( $input['add_to_cart_option'] ) || isset( $input['textcolor'] );

		// Only process general tab fields if saving from general tab
		if ( $is_general_tab || ! $is_category_rules_tab ) {
			// Checkbox fields - sanitize as on/off
			$checkbox_fields = array( 'date_display', 'pincode_field', 'cod_display' );
			foreach ( $checkbox_fields as $field ) {
				$sanitized[ $field ] = isset( $input[ $field ] ) && 'on' === $input[ $field ] ? 'on' : '';
			}

			// Select fields - sanitize as text and validate against allowed values
			if ( isset( $input['delivery_date'] ) ) {
				$allowed_formats = array( 'M jS', 'D, jS M', 'D, M d', 'M d' );
				$sanitized['delivery_date'] = in_array( $input['delivery_date'], $allowed_formats, true )
					? sanitize_text_field( $input['delivery_date'] )
					: 'M jS';
			}

			if ( isset( $input['add_to_cart_option'] ) ) {
				$allowed_options = array( 'add_to_cart_hide', 'add_to_cart_disable' );
				$sanitized['add_to_cart_option'] = in_array( $input['add_to_cart_option'], $allowed_options, true )
					? sanitize_text_field( $input['add_to_cart_option'] )
					: 'add_to_cart_hide';
			}

			if ( isset( $input['pincode_position'] ) ) {
				$allowed_positions = array(
					'woocommerce_before_add_to_cart_button',
					'woocommerce_after_add_to_cart_button',
					'woocommerce_after_add_to_cart_quantity',
					'wpc_pincode_checker'
				);
				$sanitized['pincode_position'] = in_array( $input['pincode_position'], $allowed_positions, true )
					? sanitize_text_field( $input['pincode_position'] )
					: 'woocommerce_before_add_to_cart_button';
			}

			// Text fields - sanitize as text
			$text_fields = array(
				'delivery_date_label_text',
				'check_btn_text',
				'change_btn_text',
				'cod_label_text',
				'availability_label_text'
			);
			foreach ( $text_fields as $field ) {
				if ( isset( $input[ $field ] ) ) {
					$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
				}
			}

			// Color fields - sanitize as hex color
			$color_fields = array( 'textcolor', 'buttoncolor', 'buttontcolor' );
			foreach ( $color_fields as $field ) {
				if ( isset( $input[ $field ] ) ) {
					$sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
				}
			}

			// Array field - categories for shipping
			if ( isset( $input['categories_for_shipping'] ) && is_array( $input['categories_for_shipping'] ) ) {
				$sanitized['categories_for_shipping'] = array_map( 'absint', $input['categories_for_shipping'] );
			} elseif ( $is_general_tab ) {
				// Only reset to empty if we're saving from general tab
				$sanitized['categories_for_shipping'] = array();
			}
		}

		// Category-specific delivery rules
		// Only process if saving from category rules tab
		if ( $is_category_rules_tab ) {
			if ( isset( $input['global_category_rules'] ) && is_array( $input['global_category_rules'] ) ) {
				$sanitized_rules = array();

				foreach ( $input['global_category_rules'] as $category_id => $rule_data ) {
					// Only include if enabled checkbox is checked
					if ( isset( $rule_data['enabled'] ) && '1' === $rule_data['enabled'] ) {
						$cat_id = absint( $category_id );
						$days = isset( $rule_data['days'] ) ? absint( $rule_data['days'] ) : 1;

						// Validate delivery days range
						if ( $days < 1 ) {
							$days = 1;
						} elseif ( $days > 365 ) {
							$days = 365;
						}

						// Verify category exists
						$category = get_term( $cat_id, 'product_cat' );
						if ( $category && ! is_wp_error( $category ) ) {
							$sanitized_rules[ $cat_id ] = $days;
						}
					}
				}

				$sanitized['global_category_rules'] = $sanitized_rules;
			} else {
				// Only reset to empty if we're saving from category rules tab
				$sanitized['global_category_rules'] = array();
			}
		}

		return $sanitized;
	}

	/**
	 * Get faq html.
	 */
	public function wpc_faq_settings_content() {
		include_once 'partials/woo-pincode-checker-faq-display.php';
	}

	/**
	 * Get category rules settings html.
	 *
	 * @since 1.5.0
	 */
	public function wpc_category_rules_content() {
		include_once 'partials/woo-pincode-category-rules-display.php';
	}

	/**
	 * Get geocoding settings html.
	 *
	 * @since 1.5.0
	 */
	public function wpc_geocoding_content() {
		include_once 'partials/woo-pincode-geocoding-display.php';
	}

	/**
	 * Register all settings.
	 */
	public function wpc_add_admin_register_setting() {
		$this->plugin_settings_tabs['wpc-welcome'] = esc_html__( 'Welcome', 'pincode-checker-for-woocommerce' );
		add_settings_section( 'wpc-welcome', ' ', array( $this, 'wpc_welcome_content' ), 'wpc-welcome' );

		$this->plugin_settings_tabs['wpc-general'] = esc_html__( 'General', 'pincode-checker-for-woocommerce' );
		register_setting( 'wpc_general_settings', 'wpc_general_settings', array( $this, 'wpc_general_settings_sanitize' ) );
		add_settings_section( 'wpc-general', ' ', array( $this, 'wpc_general_settings_content' ), 'wpc-general' );

		$this->plugin_settings_tabs['wpc-pincodes'] = esc_html__( 'All Pincodes', 'pincode-checker-for-woocommerce' );
		register_setting( 'wpc_pincodes_settings', 'wpc_pincodes_settings' );

		$this->plugin_settings_tabs['wpc-add-pincodes'] = esc_html__( 'Add Pincodes', 'pincode-checker-for-woocommerce' );
		register_setting( 'wpc_add_pincodes_settings', 'wpc_add_pincodes_settings' );

		$this->plugin_settings_tabs['wpc-upload-pincodes'] = esc_html__( 'Upload Pincodes', 'pincode-checker-for-woocommerce' );
		register_setting( 'wpc_upload_pincodes_settings', 'wpc_upload_pincodes_settings' );
		add_settings_section( 'wpc-upload-pincodes', ' ', array( $this, 'wpc_upload_pincodes_func' ), 'wpc-upload-pincodes' );

		$this->plugin_settings_tabs['wpc-category-rules'] = esc_html__( 'Category Rules', 'pincode-checker-for-woocommerce' );
		register_setting( 'wpc_general_settings', 'wpc_general_settings', array( $this, 'wpc_general_settings_sanitize' ) );
		add_settings_section( 'wpc-category-rules', ' ', array( $this, 'wpc_category_rules_content' ), 'wpc-category-rules' );

		$this->plugin_settings_tabs['wpc-geocoding'] = esc_html__( 'Geocoding', 'pincode-checker-for-woocommerce' );
		add_settings_section( 'wpc-geocoding', ' ', array( $this, 'wpc_geocoding_content' ), 'wpc-geocoding' );

		$this->plugin_settings_tabs['wpc-faq'] = esc_html__( 'FAQ', 'pincode-checker-for-woocommerce' );
		register_setting( 'wpc_faq_settings', 'wpc_faq_settings' );
		add_settings_section( 'wpc-faq', ' ', array( $this, 'wpc_faq_settings_content' ), 'wpc-faq' );
	}

	/**
	 * Add screen option.
	 */
	public function load_user_list_table_screen_options() {
		$arguments = array(
			'label'   => __( 'Pincode Per Page', 'pincode-checker-for-woocommerce' ),
			'default' => 20,
			'option'  => 'pincode_checker_per_page',
		);
		add_screen_option( 'per_page', $arguments );
		$this->Woo_Pincode_Checker_Listing = new Woo_Pincode_Checker_Listing( 'pincode-checker-for-woocommerce' );
	}

	/**
	 * Save screen option.
	 *
	 * @param string $status  Get a Screen Status.
	 * @param string $option  Get a Screen option.
	 * @param string $value Get a Screen value.
	 */
	public function wpc_pincode_per_page_set_option( $status, $option, $value ) {
		if ( 'pincode_checker_per_page' == $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Display Pincode Lists table in admin - Enhanced with table checks.
	 *
	 * @since 1.0.0
	 */
	public function wpc_pincode_lists_func() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pincode-checker-for-woocommerce' ) );
		}

		// Check if table exists
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;

		if ( ! $table_exists ) {
			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'Pincode Lists', 'pincode-checker-for-woocommerce' ); ?></h2>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'Database Error:', 'pincode-checker-for-woocommerce' ); ?></strong></p>
					<p><?php esc_html_e( 'The plugin database table is missing. This usually happens when the plugin activation failed.', 'pincode-checker-for-woocommerce' ); ?></p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Fix Database Issue', 'pincode-checker-for-woocommerce' ); ?>
						</a>
						<a href="#" onclick="location.reload();" class="button">
							<?php esc_html_e( 'Retry', 'pincode-checker-for-woocommerce' ); ?>
						</a>
					</p>
				</div>
			</div>
			<?php
			return;
		}

		?>
		<div class="wpc-actions wrap">
		<h2>
		<?php esc_html_e( 'Pincode Lists', 'pincode-checker-for-woocommerce' ); ?>
				<a class="add-new-h2" href="<?php echo esc_url( admin_url( 'admin.php?page=add_wpc_pincode' ) ); ?>">
					<?php esc_html_e( 'Add New', 'pincode-checker-for-woocommerce' ); ?>
				</a>
				<a class="add-new-h2" href="<?php echo esc_url( admin_url( 'admin.php?page=pincode-checker-for-woocommerce&tab=wpc-upload-pincodes' ) ); ?>">
					<?php esc_html_e( 'Import Bulk Post/Zip codes', 'pincode-checker-for-woocommerce' ); ?>
				</a>
				<a class="add-new-h2 wpc-bulk-delete">
					<?php esc_html_e( 'Bulk Delete', 'pincode-checker-for-woocommerce' ); ?>
				</a>
			</h2>
			<div class="pincode-listing">
				<form id="nds-user-list-form" method="get">				
					<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '' ); ?>" />
		<?php
		$pincode_list = new Woo_Pincode_Checker_Listing();

		if ( isset( $_GET['s'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			$pincode_list->prepare_items( $search_term );
		} else {
			$pincode_list->prepare_items();
		}
		$pincode_list->search_box( 'Search Pincode', 'pincode-checker-for-woocommerce' );
		$pincode_list->display();
		?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Pincode - Enhanced with better error handling and table checks.
	 *
	 * @since 1.0.0
	 */
	public function wpc_add_pincode_func() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pincode-checker-for-woocommerce' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;

		if ( ! $table_exists ) {
			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'Add Pincode', 'pincode-checker-for-woocommerce' ); ?></h2>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'Database Error:', 'pincode-checker-for-woocommerce' ); ?></strong></p>
					<p><?php esc_html_e( 'The plugin database table is missing. Cannot add pincodes without the database table.', 'pincode-checker-for-woocommerce' ); ?></p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Fix Database Issue', 'pincode-checker-for-woocommerce' ); ?>
						</a>
					</p>
				</div>
			</div>
			<?php
			return;
		}

		$wpc_message = $message_type = '';
		
		if ( isset( $_POST['wpc-pincode-submit'] ) && $_POST['wpc-pincode-submit'] != '' ) {
			// Verify nonce
			if ( ! $this->verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpc-pincode-submit'] ) ), 'wpc-pincode-submit' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'pincode-checker-for-woocommerce' ) );
			}

			$wpc_pincode = isset( $_POST['wpc-pincode'] ) ? $this->validate_pincode( wp_unslash( $_POST['wpc-pincode'] ) ) : '';
			$wpc_city = isset( $_POST['wpc-city'] ) ? sanitize_text_field( wp_unslash( $_POST['wpc-city'] ) ) : '';
			$wpc_state = isset( $_POST['wpc-state'] ) ? sanitize_text_field( wp_unslash( $_POST['wpc-state'] ) ) : '';
			$wpc_shipping_amount = isset( $_POST['wpc_shipping_amount'] ) ? floatval( wp_unslash( $_POST['wpc_shipping_amount'] ) ) : 0;
			$wpc_delivery_days = isset( $_POST['wpc-delivery-days'] ) ? intval( wp_unslash( $_POST['wpc-delivery-days'] ) ) : 1;
			$wpc_case_on_delivery = isset( $_POST['wpc-case-on-delivery'] ) ? 1 : 0;
			$wpc_cod_amount = isset( $_POST['wpc_case_on_delivery_amount'] ) ? floatval( wp_unslash( $_POST['wpc_case_on_delivery_amount'] ) ) : 0;

			// Validate pincode format
			if ( false === $wpc_pincode ) {
				$message_type = 'error';
				$wpc_message = __( 'Please enter a valid pincode (3-10 alphanumeric characters only).', 'pincode-checker-for-woocommerce' );
			} elseif ( empty( $wpc_city ) || empty( $wpc_state ) ) {
				$message_type = 'error';
				$wpc_message = __( 'City and State are required fields.', 'pincode-checker-for-woocommerce' );
			} elseif ( $wpc_delivery_days < 1 || $wpc_delivery_days > 365 ) {
				$message_type = 'error';
				$wpc_message = __( 'Delivery days must be between 1 and 365.', 'pincode-checker-for-woocommerce' );
			} elseif ( $wpc_shipping_amount < 0 || $wpc_cod_amount < 0 ) {
				$message_type = 'error';
				$wpc_message = __( 'Amounts cannot be negative.', 'pincode-checker-for-woocommerce' );
			} else {
				// Check if this is an edit action
				$is_edit = isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit';
				$edit_id = $is_edit ? intval( $_REQUEST['id'] ?? 0 ) : 0;

				// Check if pincode already exists (exclude current record if editing)
				$existing_query = "SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s";
				$existing_params = array( $wpc_pincode );
				
				if ( $is_edit && $edit_id > 0 ) {
					$existing_query .= " AND id != %d";
					$existing_params[] = $edit_id;
				}
				
				$existing_pincode = $wpdb->get_var( $wpdb->prepare( $existing_query, $existing_params ) );

				if ( 0 == $existing_pincode ) {
					if ( $is_edit && $edit_id > 0 ) {
						// Update existing record
						$result = $wpdb->update(
							$table_name,
							array(
								'pincode'          => $wpc_pincode,
								'city'             => $wpc_city,
								'state'            => $wpc_state,
								'delivery_days'    => $wpc_delivery_days,
								'shipping_amount'  => $wpc_shipping_amount,
								'case_on_delivery' => $wpc_case_on_delivery,
								'cod_amount'       => $wpc_cod_amount,
							),
							array( 'id' => $edit_id ),
							array( '%s', '%s', '%s', '%d', '%f', '%d', '%f' ),
							array( '%d' )
						);

						if ( false !== $result ) {
							$message_type = 'updated';
							$wpc_message = __( 'Pincode updated successfully.', 'pincode-checker-for-woocommerce' );
							// Clear cache for this pincode
							wp_cache_delete( 'wpc_pincode_' . md5( $wpc_pincode ), 'woo_pincode_checker' );
						} else {
							$message_type = 'error';
							$wpc_message = __( 'Error updating pincode. Please try again.', 'pincode-checker-for-woocommerce' ) . ' ' . $wpdb->last_error;
						}
					} else {
						// Insert new record
						$result = $wpdb->insert(
							$table_name,
							array(
								'pincode'          => $wpc_pincode,
								'city'             => $wpc_city,
								'state'            => $wpc_state,
								'delivery_days'    => $wpc_delivery_days,
								'shipping_amount'  => $wpc_shipping_amount,
								'case_on_delivery' => $wpc_case_on_delivery,
								'cod_amount'       => $wpc_cod_amount,
							),
							array( '%s', '%s', '%s', '%d', '%f', '%d', '%f' )
						);

						if ( false !== $result ) {
							$message_type = 'updated';
							$wpc_message = __( 'Pincode added successfully.', 'pincode-checker-for-woocommerce' );
						} else {
							$message_type = 'error';
							$wpc_message = __( 'Error adding pincode. Please try again.', 'pincode-checker-for-woocommerce' ) . ' ' . $wpdb->last_error;
						}
					}
				} else {
					$message_type = 'error';
					$wpc_message = esc_html__( 'This pincode already exists.', 'pincode-checker-for-woocommerce' );
				}
			}
		}

		if ( $wpc_message != '' ) {
			?>
			<div class="<?php echo esc_attr( $message_type ); ?> below-h2 notice is-dismissible" id="message">
				<p><?php echo wp_kses_post( $wpc_message ); ?></p>
			</div>
			<?php
		}

		/* if edit action then display record */
		$query_results = array();
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) {
			$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
			
			if ( $id > 0 ) {
				$query_results = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM {$table_name} WHERE id = %d", 
					$id
				), ARRAY_A );
			}
		}
		?>
		<div class="wrap wpc-add-pincode-wrap">
		<h2>
		<?php
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) {
			esc_html_e( 'Edit Pincode', 'pincode-checker-for-woocommerce' );
		} else {
			esc_html_e( 'Add Pincode', 'pincode-checker-for-woocommerce' );
		}
		?>
			</h2>
			<div class="wpc-add-pincode-section">
				<form action="" method="post" name="wpc-picode-form" id="wpc-picode-form">
					<table class="form-table">
						<tbody>
							<tr>
								<th>
									<label for="wpc-pincode"><?php esc_html_e( 'Pincode', 'pincode-checker-for-woocommerce' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" 
										   pattern="[a-zA-Z0-9\s]+" 
										   required="required" 
										   class="regular-text" 
										   id="wpc-pincode" 
										   value="<?php echo ( isset( $query_results[0]['pincode'] ) ) ? esc_attr( $query_results[0]['pincode'] ) : ''; ?>" 
										   name="wpc-pincode"
										   maxlength="10"
										   placeholder="<?php esc_attr_e( 'Enter pincode', 'pincode-checker-for-woocommerce' ); ?>">
									<p class="description"><?php esc_html_e( 'Enter alphanumeric pincode (3-10 characters)', 'pincode-checker-for-woocommerce' ); ?></p>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-city"><?php esc_html_e( 'City', 'pincode-checker-for-woocommerce' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" 
										   required="required" 
										   class="regular-text" 
										   id="wpc-city" 
										   value="<?php echo ( isset( $query_results[0]['city'] ) ) ? esc_attr( $query_results[0]['city'] ) : ''; ?>" 
										   name="wpc-city"
										   maxlength="100"
										   placeholder="<?php esc_attr_e( 'Enter city name', 'pincode-checker-for-woocommerce' ); ?>">
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-state"><?php esc_html_e( 'State', 'pincode-checker-for-woocommerce' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" 
										   required="required" 
										   class="regular-text" 
										   id="wpc-state" 
										   name="wpc-state" 
										   value="<?php echo ( isset( $query_results[0]['state'] ) ) ? esc_attr( $query_results[0]['state'] ) : ''; ?>"
										   maxlength="100"
										   placeholder="<?php esc_attr_e( 'Enter state name', 'pincode-checker-for-woocommerce' ); ?>">
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-shipping-amount"><?php esc_html_e( 'Shipping Amount', 'pincode-checker-for-woocommerce' ); ?></label>
								</th>
								<td>
									<input type="number" 
										   step="0.01" 
										   min="0"
										   class="regular-text" 
										   id="wpc-shipping-amount" 
										   name="wpc_shipping_amount" 
										   value="<?php echo isset( $query_results[0]['shipping_amount'] ) ? esc_attr( $query_results[0]['shipping_amount'] ) : '0'; ?>">
									<p class="description"><?php esc_html_e( 'Enable shipping cost in settings to calculate the shipping amount.', 'pincode-checker-for-woocommerce' ); ?></p>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-delivery-days"><?php esc_html_e( 'Delivery within days', 'pincode-checker-for-woocommerce' ); ?></label>
								</th>
								<td>
									<input type="number" 
										   min="1" 
										   max="365" 
										   step="1" 
										   class="regular-text" 
										   id="wpc-delivery-days" 
										   name="wpc-delivery-days" 
										   value="<?php echo ( isset( $query_results[0]['delivery_days'] ) ) ? esc_attr( $query_results[0]['delivery_days'] ) : '1'; ?>">
									<p class="description"><?php esc_html_e( 'Number of days for delivery (1-365)', 'pincode-checker-for-woocommerce' ); ?></p>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-case-on-delivery"><?php esc_html_e( 'Cash on Delivery', 'pincode-checker-for-woocommerce' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										   value="1" 
										   class="regular-text" 
										   id="wpc-case-on-delivery" 
										   name="wpc-case-on-delivery" 
										   <?php checked( '1', ( isset( $query_results[0]['case_on_delivery'] ) ) ? $query_results[0]['case_on_delivery'] : '' ); ?>>
									<label for="wpc-case-on-delivery"><?php esc_html_e( 'Enable Cash on Delivery for this pincode', 'pincode-checker-for-woocommerce' ); ?></label>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-case-on-delivery-amount"><?php esc_html_e( 'Cash on Delivery Amount', 'pincode-checker-for-woocommerce' ); ?></label>
								</th>
								<td>
									<input type="number" 
										   step="0.01" 
										   min="0"
										   class="regular-text" 
										   id="wpc-case-on-delivery-amount" 
										   name="wpc_case_on_delivery_amount" 
										   value="<?php echo ( isset( $query_results[0]['cod_amount'] ) ) ? esc_attr( $query_results[0]['cod_amount'] ) : '0'; ?>">
									<p class="description"><?php esc_html_e( 'If COD option is enabled, then COD amount will be counted on cart and checkout page.', 'pincode-checker-for-woocommerce' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
		<?php
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) {
			submit_button( __( 'Update Pincode', 'pincode-checker-for-woocommerce' ) );
		} else {
			submit_button( __( 'Add Pincode', 'pincode-checker-for-woocommerce' ) );
		}
		?>
		<?php wp_nonce_field( 'wpc-pincode-submit', 'wpc-pincode-submit' ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Validate CSV file content
	 *
	 * @param string $file_path Path to the uploaded file
	 * @return bool|string True if valid, error message if invalid
	 */
	private function validate_csv_file( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return __( 'File does not exist.', 'pincode-checker-for-woocommerce' );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return __( 'Cannot read uploaded file.', 'pincode-checker-for-woocommerce' );
		}

		// Check first line (header)
		$first_line = fgetcsv( $handle, 0, ',', '"', '\\' );
		if ( ! $first_line || count( $first_line ) < 3 ) {
			fclose( $handle );
			return __( 'Invalid CSV format. Expected columns: pincode, city, state, delivery_days, shipping_amount, cash_on_delivery, cod_amount', 'pincode-checker-for-woocommerce' );
		}

		// Check a few data rows
		$row_count = 0;
		while ( ( $data = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false && $row_count < 5 ) {
			if ( count( $data ) < 3 ) {
				fclose( $handle );
				return sprintf( __( 'Invalid CSV format at row %d. Minimum 3 columns required.', 'pincode-checker-for-woocommerce' ), $row_count + 2 );
			}
			
			// Validate pincode format
			if ( ! empty( $data[0] ) && false === $this->validate_pincode( $data[0] ) ) {
				fclose( $handle );
				return sprintf( __( 'Invalid pincode format at row %d: %s', 'pincode-checker-for-woocommerce' ), $row_count + 2, $data[0] );
			}
			
			$row_count++;
		}

		fclose( $handle );
		return true;
	}

	/**
	 * Upload Pincode with enhanced security and table checks.
	 *
	 * @since 1.0.0
	 */
	public function wpc_upload_pincodes_func() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pincode-checker-for-woocommerce' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;

		if ( ! $table_exists ) {
			?>
			<div class="wbcom-tab-content wpc-upload-pincode-wrap">
				<div class="wbcom-wrapper-admin">
					<div class="notice notice-error">
						<p><strong><?php esc_html_e( 'Database Error:', 'pincode-checker-for-woocommerce' ); ?></strong></p>
						<p><?php esc_html_e( 'The plugin database table is missing. Cannot upload pincodes without the database table.', 'pincode-checker-for-woocommerce' ); ?></p>
						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ); ?>" class="button button-primary">
								<?php esc_html_e( 'Fix Database Issue', 'pincode-checker-for-woocommerce' ); ?>
							</a>
						</p>
					</div>
				</div>
			</div>
			<?php
			return;
		}

		$wpc_message = '';
		$message_type = '';
		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );

		if ( isset( $_POST['upload_pincodes'] ) && isset( $_POST['wpc-pincode-submit'] ) ) {
			// Verify nonce
			if ( ! $this->verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpc-pincode-submit'] ) ), 'wpc-pincode-submit' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'pincode-checker-for-woocommerce' ) );
			}

			$should_continue = true;
			
			// Validate file upload
			if ( ! isset( $_FILES['import'] ) || $_FILES['import']['error'] !== UPLOAD_ERR_OK ) {
				$message_type = 'error';
				$wpc_message = __( 'File upload error. Please try again.', 'pincode-checker-for-woocommerce' );
				$should_continue = false;
			}

			if ( $should_continue ) {
				// Check file size (limit to 5MB)
				$max_size = 5 * 1024 * 1024; // 5MB
				if ( ! empty( $_FILES['import']['size'] ) && $_FILES['import']['size'] > $max_size ) {
					$message_type = 'error';
					$wpc_message = __( 'File too large. Maximum size is 5MB.', 'pincode-checker-for-woocommerce' );
					$should_continue = false;
				}
			}

			if ( $should_continue ) {
				// Enhanced file validation using WordPress functions
				$uploaded_file = $_FILES['import']['tmp_name'];
				$original_filename = $_FILES['import']['name'];
				
				// Check file extension
				$file_info = wp_check_filetype_and_ext( $uploaded_file, $original_filename );
				$allowed_types = array( 'csv' );
				
				if ( ! in_array( $file_info['ext'], $allowed_types ) || 
					 ! in_array( $file_info['type'], array( 'text/csv', 'application/csv', 'text/plain' ) ) ) {
					$message_type = 'error';
					$wpc_message = __( 'Invalid file type. Please upload a CSV file only.', 'pincode-checker-for-woocommerce' );
					$should_continue = false;
				}
				
				// Additional MIME type check
				if ( $should_continue && function_exists( 'finfo_open' ) ) {
					$finfo = finfo_open( FILEINFO_MIME_TYPE );
					$detected_type = finfo_file( $finfo, $uploaded_file );
					finfo_close( $finfo );
					
					$allowed_mime_types = array( 'text/csv', 'text/plain', 'application/csv' );
					if ( ! in_array( $detected_type, $allowed_mime_types ) ) {
						$message_type = 'error';
						$wpc_message = __( 'File content does not match CSV format.', 'pincode-checker-for-woocommerce' );
						$should_continue = false;
					}
				}
			}

			if ( $should_continue ) {
				// Validate CSV content
				$validation_result = $this->validate_csv_file( $_FILES['import']['tmp_name'] );
				if ( true !== $validation_result ) {
					$message_type = 'error';
					$wpc_message = $validation_result;
					$should_continue = false;
				}
			}

			$imported_count = 0;
			$skipped_count = 0;
			$error_count = 0;

			if ( $should_continue ) {
				set_time_limit( 300 );

				$handle = fopen( $_FILES['import']['tmp_name'], 'r' );
				if ( $handle ) {
					// Skip header row
					fgetcsv( $handle, 0, ',', '"', '\\' );

					// Initialize pattern handler for pattern expansion
					$pattern_handler = new Woo_Pincode_Pattern_Handler();

					while ( ( $data = fgetcsv( $handle, 100000, ',', '"', '\\' ) ) !== false ) {
						// Validate row data
						if ( count( $data ) < 3 ) {
							$skipped_count++;
							continue;
						}

						// Get pincode/pattern from CSV
						$pincode_input = trim( $data[0] );

						// Get other fields
						$city = sanitize_text_field( trim( $data[1] ) );
						$state = sanitize_text_field( trim( $data[2] ) );
						$delivery_days = isset( $data[3] ) ? intval( $data[3] ) : 1;
						$shipping_amount = isset( $data[4] ) ? floatval( $data[4] ) : 0;
						$case_on_delivery = isset( $data[5] ) ? intval( $data[5] ) : 0;
						$cod_amount = isset( $data[6] ) ? floatval( $data[6] ) : 0;

						// Validate ranges
						if ( $delivery_days < 1 || $delivery_days > 365 ) {
							$delivery_days = 1;
						}
						if ( $shipping_amount < 0 ) {
							$shipping_amount = 0;
						}
						if ( $cod_amount < 0 ) {
							$cod_amount = 0;
						}

						// Check if it's a pattern (contains *, ?, or -)
						$is_pattern = ( strpos( $pincode_input, '*' ) !== false ||
									   strpos( $pincode_input, '?' ) !== false ||
									   strpos( $pincode_input, '-' ) !== false );

						if ( $is_pattern ) {
							// It's a pattern - expand it
							$pincodes = $pattern_handler->parse_pattern( $pincode_input );

							if ( is_wp_error( $pincodes ) ) {
								// Pattern parsing error
								$error_count++;
								error_log( 'WPC CSV Import Pattern Error: ' . $pincodes->get_error_message() );
								continue;
							}

							// Insert all pincodes from pattern
							foreach ( $pincodes as $pincode ) {
								// Check if pincode already exists
								$existing = $wpdb->get_var( $wpdb->prepare(
									"SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s",
									$pincode
								) );

								if ( ! $existing ) {
									$result = $wpdb->insert(
										$table_name,
										array(
											'pincode'          => $pincode,
											'city'             => $city,
											'state'            => $state,
											'delivery_days'    => $delivery_days,
											'shipping_amount'  => $shipping_amount,
											'case_on_delivery' => $case_on_delivery,
											'cod_amount'       => $cod_amount,
										),
										array( '%s', '%s', '%s', '%d', '%f', '%d', '%f' )
									);

									if ( false !== $result ) {
										$imported_count++;
									} else {
										$error_count++;
										error_log( 'WPC CSV Import Error: ' . $wpdb->last_error );
									}
								} else {
									$skipped_count++;
								}
							}
						} else {
							// Single pincode - validate and process normally
							$pincode = $this->validate_pincode( $pincode_input );
							if ( false === $pincode ) {
								$error_count++;
								continue;
							}

							// Check if pincode already exists
							$existing = $wpdb->get_var( $wpdb->prepare(
								"SELECT COUNT(*) FROM {$table_name} WHERE pincode = %s",
								$pincode
							) );

							if ( ! $existing ) {
								$result = $wpdb->insert(
									$table_name,
									array(
										'pincode'          => $pincode,
										'city'             => $city,
										'state'            => $state,
										'delivery_days'    => $delivery_days,
										'shipping_amount'  => $shipping_amount,
										'case_on_delivery' => $case_on_delivery,
										'cod_amount'       => $cod_amount,
									),
									array( '%s', '%s', '%s', '%d', '%f', '%d', '%f' )
								);

								if ( false !== $result ) {
									$imported_count++;
								} else {
									$error_count++;
									error_log( 'WPC CSV Import Error: ' . $wpdb->last_error );
								}
							} else {
								$skipped_count++;
							}
						}
					}
					fclose( $handle );

					$message_type = 'updated';
					$wpc_message = sprintf(
						__( 'Import completed. %d pincodes imported, %d skipped (already exist), %d errors.', 'pincode-checker-for-woocommerce' ),
						$imported_count,
						$skipped_count,
						$error_count
					);
				} else {
					$message_type = 'error';
					$wpc_message = __( 'Could not read the uploaded file.', 'pincode-checker-for-woocommerce' );
				}
			}
		}

		?>
		<div class="wbcom-tab-content">
			<div class="wbcom-wrapper-admin">
				<?php
				if ( $wpc_message != '' ) {
					?>
				<div class="<?php echo esc_attr( $message_type ); ?> below-h2 error notice is-dismissible" id="message">
					<p><?php echo wp_kses_post( $wpc_message ); ?></p>
				</div>
					<?php
				}
				?>
				<div class="wbcom-admin-title-section">
					<h3><?php esc_html_e( 'Upload Your CSV File', 'pincode-checker-for-woocommerce' ); ?></h3>
					<p><?php esc_html_e( 'Upload a CSV file to import multiple pincodes at once. Make sure your file follows the correct format.', 'pincode-checker-for-woocommerce' ); ?></p>
				</div>
				<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
					<form enctype="multipart/form-data" method="post">
						<div class="form-table">
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="upload">
										<?php esc_html_e( 'Select CSV File', 'pincode-checker-for-woocommerce' ); ?>
									</label>
									<p class="description">
										<?php
										printf(
											esc_html__( 'Choose a CSV file from your device to upload. Maximum file size allowed is %s. Only CSV files are accepted.', 'pincode-checker-for-woocommerce' ),
											esc_html( $size )
										);
										?>
									</p>
								</div>
								<div class="wbcom-settings-section-options">
									<input type="file"
										   id="upload"
										   name="import"
										   accept=".csv,text/csv"
										   required />
									<input type="hidden" name="action" value="save" />
									<input type="hidden" name="max_file_size" value="<?php echo esc_attr( $bytes ); ?>" />
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="download-sample">
										<?php esc_html_e( 'CSV File Format', 'pincode-checker-for-woocommerce' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Download the sample CSV file to see the correct format. Your CSV should have columns: pincode, city, state, delivery_days, shipping_amount, cash_on_delivery, cod_amount', 'pincode-checker-for-woocommerce' ); ?>
									</p>
								</div>
								<div class="wbcom-settings-section-options">
									<a href="<?php echo esc_url( WPCP_PLUGIN_URL . 'sample-data/sample-pincodes.csv' ); ?>" class="button" id="download-sample">
										<?php esc_html_e( 'Download Sample CSV', 'pincode-checker-for-woocommerce' ); ?>
									</a>
								</div>
							</div>
						</div>
						<?php submit_button( esc_html__( 'Import CSV File', 'pincode-checker-for-woocommerce' ), 'primary', 'upload_pincodes' ); ?>
						<?php wp_nonce_field( 'wpc-pincode-submit', 'wpc-pincode-submit' ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Bulk Add Pincode by Pattern or Range.
	 *
	 * @since 1.6.0
	 */
	public function wpc_bulk_add_pincode_func() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'pincode-checker-for-woocommerce' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table_name
		) ) == $table_name;

		if ( ! $table_exists ) {
			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'Bulk Add by Pattern', 'pincode-checker-for-woocommerce' ); ?></h2>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'Database Error:', 'pincode-checker-for-woocommerce' ); ?></strong></p>
					<p><?php esc_html_e( 'The plugin database table is missing. Cannot add pincodes without the database table.', 'pincode-checker-for-woocommerce' ); ?></p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpc-manual-fix' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Fix Database Issue', 'pincode-checker-for-woocommerce' ); ?>
						</a>
					</p>
				</div>
			</div>
			<?php
			return;
		}

		// Include the bulk add template
		$plugin_base_path = defined( 'WPCP_PLUGIN_PATH' ) ? WPCP_PLUGIN_PATH : '';
		if ( ! empty( $plugin_base_path ) ) {
			$template_file = $plugin_base_path . 'admin/partials/woo-pincode-bulk-add.php';
			if ( file_exists( $template_file ) ) {
				include $template_file;
			} else {
				?>
				<div class="wrap">
					<h2><?php esc_html_e( 'Bulk Add by Pattern', 'pincode-checker-for-woocommerce' ); ?></h2>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'Template file not found.', 'pincode-checker-for-woocommerce' ); ?></p>
					</div>
				</div>
				<?php
			}
		}
	}

	/**
	 * Adds a meta box to the post editing screen
	 */
	public function wpc_featured_meta() {
		add_meta_box( 
			'wpc-hide-pincode-checker', 
			__( 'Pincode/Zipcode for Shipping Availability', 'pincode-checker-for-woocommerce' ), 
			array( $this, 'wcpc_meta_callback' ), 
			'product', 
			'side', 
			'default' 
		);
	}

	/**
	 * Outputs the content of the meta box.
	 *
	 * @param WP_Post $post Get a Post Object.
	 */
	public function wcpc_meta_callback( $post ) {
		wp_nonce_field( 'wpc_hide_pincode_meta_box', 'wpc_hide_pincode_nonce' );
		$wpc_hide_pincode_checker = get_post_meta( $post->ID, 'wpc_hide_pincode_checker', true );
		?>
		<p>
			<div class="prfx-row-content">
				<label for="featured-checkbox">
					<input type="checkbox" 
						   name="wpc_hide_pincode_checker" 
						   id="featured-checkboxs" 
						   value="yes"
						   <?php checked( $wpc_hide_pincode_checker, 'yes' ); ?> />
					<?php esc_html_e( 'Hide pincode checker for this product', 'pincode-checker-for-woocommerce' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Check this option to hide the pincode checker form on this product page.', 'pincode-checker-for-woocommerce' ); ?>
				</p>
			</div>
		</p>
		<?php
	}

	/**
	 * Saves the custom meta input
	 *
	 * @param int $post_id Post ID.
	 */
	public function wpc_meta_save( $post_id ) {
		// Check for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['wpc_hide_pincode_nonce'] ) || 
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpc_hide_pincode_nonce'] ) ), 'wpc_hide_pincode_meta_box' ) ) {
			return;
		}

		// Save meta value
		if ( isset( $_POST['wpc_hide_pincode_checker'] ) ) {
			update_post_meta( $post_id, 'wpc_hide_pincode_checker', 'yes' );
		} else {
			update_post_meta( $post_id, 'wpc_hide_pincode_checker', 'no' );
		}
	}

	/**
	 * Enhanced bulk delete with table checks.
	 */
	public function wpc_bulk_delete_action_ajax_callback() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_send_json_error( 'Unauthorized access' );
			exit;
		}

		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc-admin-nonce' ) ) {
			wp_send_json_error( 'Invalid security token' );
			exit;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) ) == $table_name;

		if ( ! $table_exists ) {
			wp_send_json_error( 'Database table does not exist' );
			exit;
		}

		// Use prepared statement for deletion
		$result = $wpdb->query( "TRUNCATE TABLE `{$table_name}`" );

		if ( false !== $result ) {
			// Clear all pincode cache
			wp_cache_flush_group( 'woo_pincode_checker' );
			wp_send_json_success( 'Pincodes deleted successfully' );
		} else {
			wp_send_json_error( 'Failed to delete pincodes: ' . $wpdb->last_error );
		}
	}

	/**
	 * AJAX handler for searching pincodes with real-time results
	 * 
	 * @since 1.3.0
	 */
	public function wpc_ajax_search_pincodes() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ) );
			exit;
		}

		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc-admin-nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token' ) );
			exit;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';
		
		// Get search term
		$search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		
		// Validate search term
		if ( ! empty( $search_term ) ) {
			$search_term = $this->validate_pincode( $search_term );
			if ( false === $search_term ) {
				wp_send_json_error( array( 'message' => 'Invalid search term' ) );
				exit;
			}
		}
		
		// Build query
		$query = "SELECT * FROM {$table_name}";
		$params = array();
		
		if ( ! empty( $search_term ) ) {
			$query .= " WHERE (pincode LIKE %s OR city LIKE %s OR state LIKE %s)";
			$search_pattern = '%' . $wpdb->esc_like( $search_term ) . '%';
			$params[] = $search_pattern;
			$params[] = $search_pattern;
			$params[] = $search_pattern;
		}
		
		$query .= " ORDER BY id DESC LIMIT 50";
		
		// Execute query
		if ( ! empty( $params ) ) {
			$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );
		} else {
			$results = $wpdb->get_results( $query, ARRAY_A );
		}
		
		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$table_name}";
		if ( ! empty( $search_term ) ) {
			$count_query .= " WHERE (pincode LIKE %s OR city LIKE %s OR state LIKE %s)";
			$count = $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );
		} else {
			$count = $wpdb->get_var( $count_query );
		}
		
		// Generate table HTML
		$html = '';
		if ( ! empty( $results ) ) {
			foreach ( $results as $pincode ) {
				$html .= '<tr>';
				$html .= '<th scope="row" class="check-column">';
				$html .= '<input type="checkbox" name="wpc_pincode[]" value="' . esc_attr( $pincode['id'] ) . '" />';
				$html .= '</th>';
				$html .= '<td>' . esc_html( $pincode['pincode'] ) . '</td>';
				$html .= '<td>' . esc_html( $pincode['city'] ) . '</td>';
				$html .= '<td>' . esc_html( $pincode['state'] ) . '</td>';
				$html .= '<td>' . intval( $pincode['delivery_days'] ) . ' ' . ( intval( $pincode['delivery_days'] ) === 1 ? 'day' : 'days' ) . '</td>';
				
				// Shipping amount
				if ( $pincode['shipping_amount'] > 0 ) {
					$html .= '<td>' . wc_price( $pincode['shipping_amount'] ) . '</td>';
				} else {
					$html .= '<td><span class="wpc-free">' . __( 'Free', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				}
				
				// COD status
				if ( $pincode['case_on_delivery'] == 1 ) {
					$html .= '<td><span class="wpc-status-available">' . __( 'Available', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				} else {
					$html .= '<td><span class="wpc-status-unavailable">' . __( 'Unavailable', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				}
				
				// COD amount
				if ( $pincode['cod_amount'] > 0 ) {
					$html .= '<td>' . wc_price( $pincode['cod_amount'] ) . '</td>';
				} else {
					$html .= '<td><span class="wpc-free">' . __( 'Free', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				}
				
				// Created date
				if ( ! empty( $pincode['created_at'] ) ) {
					$html .= '<td>' . wp_date( get_option( 'date_format' ), strtotime( $pincode['created_at'] ) ) . '</td>';
				} else {
					$html .= '<td>-</td>';
				}
				
				$html .= '</tr>';
			}
		} else {
			$html = '<tr><td colspan="9" class="no-items">' . __( 'No pincodes found.', 'pincode-checker-for-woocommerce' ) . '</td></tr>';
		}
		
		// Send response
		wp_send_json_success( array(
			'html' => $html,
			'count' => intval( $count ),
			'pagination' => $count > 50 ? $this->generate_pagination_html( $count, 1, 50 ) : ''
		) );
	}
	
	/**
	 * AJAX handler for sorting pincodes
	 * 
	 * @since 1.3.0
	 */
	public function wpc_ajax_sort_pincodes() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ) );
			exit;
		}

		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc-admin-nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token' ) );
			exit;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';
		
		// Get sort parameters
		$orderby = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'id';
		$order = isset( $_POST['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['order'] ) ) ) : 'DESC';
		$search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		
		// Validate orderby and order
		$allowed_orderby = array( 'pincode', 'city', 'state', 'delivery_days', 'shipping_amount', 'created_at', 'id' );
		$allowed_order = array( 'ASC', 'DESC' );
		
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'id';
		}
		
		if ( ! in_array( $order, $allowed_order, true ) ) {
			$order = 'DESC';
		}
		
		// Build query
		$query = "SELECT * FROM {$table_name}";
		$params = array();
		
		if ( ! empty( $search_term ) ) {
			$query .= " WHERE (pincode LIKE %s OR city LIKE %s OR state LIKE %s)";
			$search_pattern = '%' . $wpdb->esc_like( $search_term ) . '%';
			$params[] = $search_pattern;
			$params[] = $search_pattern;
			$params[] = $search_pattern;
		}
		
		$query .= " ORDER BY `{$orderby}` {$order} LIMIT 50";
		
		// Execute query
		if ( ! empty( $params ) ) {
			$results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );
		} else {
			$results = $wpdb->get_results( $query, ARRAY_A );
		}
		
		// Generate table HTML
		$html = '';
		if ( ! empty( $results ) ) {
			foreach ( $results as $pincode ) {
				$html .= '<tr>';
				$html .= '<th scope="row" class="check-column">';
				$html .= '<input type="checkbox" name="wpc_pincode[]" value="' . esc_attr( $pincode['id'] ) . '" />';
				$html .= '</th>';
				$html .= '<td>' . esc_html( $pincode['pincode'] ) . '</td>';
				$html .= '<td>' . esc_html( $pincode['city'] ) . '</td>';
				$html .= '<td>' . esc_html( $pincode['state'] ) . '</td>';
				$html .= '<td>' . intval( $pincode['delivery_days'] ) . ' ' . ( intval( $pincode['delivery_days'] ) === 1 ? 'day' : 'days' ) . '</td>';
				
				// Shipping amount
				if ( $pincode['shipping_amount'] > 0 ) {
					$html .= '<td>' . wc_price( $pincode['shipping_amount'] ) . '</td>';
				} else {
					$html .= '<td><span class="wpc-free">' . __( 'Free', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				}
				
				// COD status
				if ( $pincode['case_on_delivery'] == 1 ) {
					$html .= '<td><span class="wpc-status-available">' . __( 'Available', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				} else {
					$html .= '<td><span class="wpc-status-unavailable">' . __( 'Unavailable', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				}
				
				// COD amount
				if ( $pincode['cod_amount'] > 0 ) {
					$html .= '<td>' . wc_price( $pincode['cod_amount'] ) . '</td>';
				} else {
					$html .= '<td><span class="wpc-free">' . __( 'Free', 'pincode-checker-for-woocommerce' ) . '</span></td>';
				}
				
				// Created date
				if ( ! empty( $pincode['created_at'] ) ) {
					$html .= '<td>' . wp_date( get_option( 'date_format' ), strtotime( $pincode['created_at'] ) ) . '</td>';
				} else {
					$html .= '<td>-</td>';
				}
				
				$html .= '</tr>';
			}
		} else {
			$html = '<tr><td colspan="9" class="no-items">' . __( 'No pincodes found.', 'pincode-checker-for-woocommerce' ) . '</td></tr>';
		}
		
		// Send response
		wp_send_json_success( array(
			'html' => $html
		) );
	}
	
	/**
	 * Generate pagination HTML
	 * 
	 * @param int $total_items Total number of items
	 * @param int $current_page Current page number
	 * @param int $per_page Items per page
	 * @return string Pagination HTML
	 */
	private function generate_pagination_html( $total_items, $current_page = 1, $per_page = 50 ) {
		$total_pages = ceil( $total_items / $per_page );
		
		if ( $total_pages <= 1 ) {
			return '';
		}
		
		$html = '<span class="displaying-num">' . sprintf( 
			_n( '%s item', '%s items', $total_items, 'pincode-checker-for-woocommerce' ), 
			number_format_i18n( $total_items ) 
		) . '</span>';
		
		$html .= '<span class="pagination-links">';
		
		// First page link
		if ( $current_page > 1 ) {
			$html .= '<a class="first-page button" href="#" data-page="1">';
			$html .= '<span class="screen-reader-text">' . __( 'First page', 'pincode-checker-for-woocommerce' ) . '</span>';
			$html .= '<span aria-hidden="true">«</span></a>';
			
			$html .= '<a class="prev-page button" href="#" data-page="' . ( $current_page - 1 ) . '">';
			$html .= '<span class="screen-reader-text">' . __( 'Previous page', 'pincode-checker-for-woocommerce' ) . '</span>';
			$html .= '<span aria-hidden="true">‹</span></a>';
		} else {
			$html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
			$html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
		}
		
		$html .= '<span class="paging-input">';
		$html .= '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page', 'pincode-checker-for-woocommerce' ) . '</label>';
		$html .= '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . $current_page . '" size="1" aria-describedby="table-paging">';
		$html .= '<span class="tablenav-paging-text"> of <span class="total-pages">' . $total_pages . '</span></span>';
		$html .= '</span>';
		
		// Next page link
		if ( $current_page < $total_pages ) {
			$html .= '<a class="next-page button" href="#" data-page="' . ( $current_page + 1 ) . '">';
			$html .= '<span class="screen-reader-text">' . __( 'Next page', 'pincode-checker-for-woocommerce' ) . '</span>';
			$html .= '<span aria-hidden="true">›</span></a>';
			
			$html .= '<a class="last-page button" href="#" data-page="' . $total_pages . '">';
			$html .= '<span class="screen-reader-text">' . __( 'Last page', 'pincode-checker-for-woocommerce' ) . '</span>';
			$html .= '<span aria-hidden="true">»</span></a>';
		} else {
			$html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
			$html .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
		}
		
		$html .= '</span>';

		return $html;
	}

	/**
	 * AJAX handler for pattern preview
	 *
	 * @since 1.6.0
	 */
	public function wpc_preview_pattern() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access', 'pincode-checker-for-woocommerce' ) ) );
			exit;
		}

		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc-preview-pattern' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token', 'pincode-checker-for-woocommerce' ) ) );
			exit;
		}

		// Get pattern
		$pattern = isset( $_POST['pattern'] ) ? sanitize_text_field( wp_unslash( $_POST['pattern'] ) ) : '';

		if ( empty( $pattern ) ) {
			wp_send_json_error( array( 'message' => __( 'Pattern is required', 'pincode-checker-for-woocommerce' ) ) );
			exit;
		}

		// Initialize pattern handler
		$pattern_handler = new Woo_Pincode_Pattern_Handler();

		// Get preview count
		$count = $pattern_handler->preview_count( $pattern );

		if ( is_wp_error( $count ) ) {
			wp_send_json_error( array( 'message' => $count->get_error_message() ) );
			exit;
		}

		wp_send_json_success( array( 'count' => $count ) );
		exit;
	}

	/**
	 * AJAX handler for geocoding pincodes in batches
	 *
	 * @since 1.5.0
	 */
	public function wpc_geocode_batch() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access', 'pincode-checker-for-woocommerce' ) ) );
			exit;
		}

		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc-geocode-batch' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token', 'pincode-checker-for-woocommerce' ) ) );
			exit;
		}

		// Initialize nearby suggestions handler
		$nearby_handler = new Woo_Pincode_Nearby_Suggestions();

		// Process a batch (50 pincodes at a time)
		$result = $nearby_handler->geocode_batch( 50 );

		wp_send_json_success( $result );
		exit;
	}
}