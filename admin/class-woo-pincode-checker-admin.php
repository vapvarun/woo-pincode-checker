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
	}

	/**
	 * Validate and sanitize pincode input
	 *
	 * @param string $pincode The pincode to validate
	 * @return string|false Sanitized pincode or false if invalid
	 */
	private function validate_pincode( $pincode ) {
		// Remove extra whitespace and normalize
		$pincode = trim( $pincode );
		$pincode = preg_replace('/\s+/', ' ', $pincode);
		
		// Check if empty after cleaning
		if ( empty( $pincode ) ) {
			return false;
		}
		
		// Check length (3-10 characters)
		if ( strlen( $pincode ) < 3 || strlen( $pincode ) > 10 ) {
			return false;
		}
		
		// Check format - alphanumeric with optional single spaces, but not at start/end
		if ( ! preg_match( '/^[A-Za-z0-9](?:[A-Za-z0-9\s]*[A-Za-z0-9])?$/', $pincode ) ) {
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
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();
		$allowed_pages = array(
			'wb-plugins_page_woo-pincode-checker',
			'toplevel_page_pincode_lists',
			'pincodes_page_add_wpc_pincode',
			'toplevel_page_wbcomplugins',
		);
		
		if ( in_array( $screen->id, array( 'pincodes_page_woo-pincode-checker', 'wb-plugins_page_woo-pincode-checker' ) ) ) {
			wp_enqueue_style( 
				'wpc-select2', 
				plugin_dir_url( __FILE__ ) . 'css/select2.min.css', 
				array(), 
				$this->version, 
				'all' 
			);
		}
		
		if ( $screen && in_array( $screen->id, $allowed_pages ) ) {
			wp_enqueue_style( 
				$this->plugin_name, 
				plugin_dir_url( __FILE__ ) . 'css/woo-pincode-checker-admin.css', 
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
		$allowed_pages = array(
			'pincodes_page_woo-pincode-checker',
			'toplevel_page_pincode_lists',
			'pincodes_page_add_wpc_pincode',
			'wb-plugins_page_woo-pincode-checker'
		);
		
		if ( $screen && in_array( $screen->id, $allowed_pages ) ) {
			wp_enqueue_script( 
				'wpc-select2', 
				plugin_dir_url( __FILE__ ) . 'js/select2.min.js', 
				array( 'jquery' ), 
				$this->version, 
				true 
			);
			
			wp_enqueue_script( 
				$this->plugin_name, 
				plugin_dir_url( __FILE__ ) . 'js/woo-pincode-checker-admin.js', 
				array( 'jquery'), 
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
		$wbcom_pages_array  = array( 'wbcomplugins', 'wbcom-plugins-page', 'wbcom-support-page', 'woo-pincode-checker' );
		$wbcom_setting_page = filter_input( INPUT_GET, 'page' ) ? filter_input( INPUT_GET, 'page' ) : '';

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
			esc_html__( 'Pincodes', 'woo-pincode-checker' ), 
			esc_html__( 'Pincodes', 'woo-pincode-checker' ), 
			'manage_options', 
			'pincode_lists', 
			array( $this, 'wpc_pincode_lists_func' ), 
			'dashicons-location-alt', 
			'50' 
		);

		$page_hook = add_submenu_page( 
			'pincode_lists', 
			esc_html__( 'All Pincodes', 'woo-pincode-checker' ), 
			esc_html__( 'All Pincodes', 'woo-pincode-checker' ), 
			'manage_options', 
			'pincode_lists', 
			array( $this, 'wpc_pincode_lists_func' ) 
		);

		add_submenu_page( 
			'pincode_lists', 
			esc_html__( 'Add New Pincode', 'woo-pincode-checker' ), 
			esc_html__( 'Add New Pincode', 'woo-pincode-checker' ), 
			'manage_options', 
			'add_wpc_pincode', 
			array( $this, 'wpc_add_pincode_func' ) 
		);

		if ( class_exists( 'WooCommerce' ) ) {
			if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
				add_menu_page( 
					esc_html__( 'WB Plugins', 'woo-pincode-checker' ), 
					esc_html__( 'WB Plugins', 'woo-pincode-checker' ), 
					'manage_options', 
					'wbcomplugins', 
					array( $this, 'wpc_admin_settings_page' ), 
					'dashicons-lightbulb', 
					59 
				);
				add_submenu_page( 
					'wbcomplugins', 
					esc_html__( 'General', 'woo-pincode-checker' ), 
					esc_html__( 'General', 'woo-pincode-checker' ), 
					'manage_options', 
					'wbcomplugins' 
				);
			}
			add_submenu_page( 
				'wbcomplugins', 
				esc_html__( 'Woo Pincode Checker', 'woo-pincode-checker' ), 
				esc_html__( 'Woo Pincode Checker', 'woo-pincode-checker' ), 
				'manage_options', 
				'woo-pincode-checker', 
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
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-pincode-checker' ) );
		}

		$current = ( filter_input( INPUT_GET, 'tab' ) !== null ) ? sanitize_text_field( filter_input( INPUT_GET, 'tab' ) ) : 'wpc-welcome';

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
							<?php esc_html_e( 'Woo Pincode Checker', 'woo-pincode-checker' ); ?>
							<span>
								<?php
								printf( 
									esc_html__( 'Version %s', 'woo-pincode-checker' ), 
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
		$current = ( filter_input( INPUT_GET, 'tab' ) !== null ) ? sanitize_text_field( filter_input( INPUT_GET, 'tab' ) ) : 'wpc-welcome';

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
				$page      = 'woo-pincode-checker';
				$tab_html .= '<li class="' . esc_attr( $edd_tab ) . '"><a id="' . esc_attr( $edd_tab ) . '" class="nav-tab ' . esc_attr( $class ) . '" href="admin.php?page=' . esc_attr( $page ) . '&tab=' . esc_attr( $edd_tab ) . '">' . esc_html( $tab_name ) . '</a></li>';
			}
		}
		$tab_html .= '</div></ul></div>';
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
	 * Get faq html.
	 */
	public function wpc_faq_settings_content() {
		include_once 'partials/woo-pincode-checker-faq-display.php';
	}

	/**
	 * Register all settings.
	 */
	public function wpc_add_admin_register_setting() {
		$this->plugin_settings_tabs['wpc-welcome'] = esc_html__( 'Welcome', 'woo-pincode-checker' );
		add_settings_section( 'wpc-welcome', ' ', array( $this, 'wpc_welcome_content' ), 'wpc-welcome' );

		$this->plugin_settings_tabs['wpc-general'] = esc_html__( 'General', 'woo-pincode-checker' );
		register_setting( 'wpc_general_settings', 'wpc_general_settings' );
		add_settings_section( 'wpc-general', ' ', array( $this, 'wpc_general_settings_content' ), 'wpc-general' );

		$this->plugin_settings_tabs['wpc-pincodes'] = esc_html__( 'All Pincodes', 'woo-pincode-checker' );
		register_setting( 'wpc_pincodes_settings', 'wpc_pincodes_settings' );

		$this->plugin_settings_tabs['wpc-add-pincodes'] = esc_html__( 'Add Pincodes', 'woo-pincode-checker' );
		register_setting( 'wpc_add_pincodes_settings', 'wpc_add_pincodes_settings' );

		$this->plugin_settings_tabs['wpc-upload-pincodes'] = esc_html__( 'Upload Pincodes', 'woo-pincode-checker' );
		register_setting( 'wpc_upload_pincodes_settings', 'wpc_upload_pincodes_settings' );
		add_settings_section( 'wpc-upload-pincodes', ' ', array( $this, 'wpc_upload_pincodes_func' ), 'wpc-upload-pincodes' );

		$this->plugin_settings_tabs['wpc-faq'] = esc_html__( 'FAQ', 'woo-pincode-checker' );
		register_setting( 'wpc_faq_settings', 'wpc_faq_settings' );
		add_settings_section( 'wpc-faq', ' ', array( $this, 'wpc_faq_settings_content' ), 'wpc-faq' );
	}

	/**
	 * Add screen option.
	 */
	public function load_user_list_table_screen_options() {
		$arguments = array(
			'label'   => __( 'Pincode Per Page', 'woo-pincode-checker' ),
			'default' => 20,
			'option'  => 'pincode_checker_per_page',
		);
		add_screen_option( 'per_page', $arguments );
		$this->Woo_Pincode_Checker_Listing = new Woo_Pincode_Checker_Listing( 'woo-pincode-checker' );
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
	 * Display Pincode Lists table in admin.
	 *
	 * @since 1.0.0
	 */
	public function wpc_pincode_lists_func() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-pincode-checker' ) );
		}

		?>
		<div class="wpc-actions wrap">
		<h2>
		<?php esc_html_e( 'Pincode Lists', 'woo-pincode-checker' ); ?>
				<a class="add-new-h2" href="<?php echo esc_url( admin_url( 'admin.php?page=add_wpc_pincode' ) ); ?>">
					<?php esc_html_e( 'Add New', 'woo-pincode-checker' ); ?>
				</a>
				<a class="add-new-h2" href="<?php echo esc_url( admin_url( 'admin.php?page=woo-pincode-checker&tab=wpc-upload-pincodes' ) ); ?>">
					<?php esc_html_e( 'Import Bulk Post/Zip codes', 'woo-pincode-checker' ); ?>
				</a>
				<a class="add-new-h2 wpc-bulk-delete">
					<?php esc_html_e( 'Bulk Delete', 'woo-pincode-checker' ); ?>
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
		$pincode_list->search_box( 'Search Pincode', 'woo-pincode-checker' );
		$pincode_list->display();
		?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Pincode - SECURE VERSION.
	 *
	 * @since 1.0.0
	 */
	public function wpc_add_pincode_func() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-pincode-checker' ) );
		}

		global $wpdb;

		$wpc_message = $message_type = '';
		
		if ( isset( $_POST['wpc-pincode-submit'] ) && $_POST['wpc-pincode-submit'] != '' ) {
			// Verify nonce
			if ( ! $this->verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpc-pincode-submit'] ) ), 'wpc-pincode-submit' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'woo-pincode-checker' ) );
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
				$wpc_message = __( 'Please enter a valid pincode (3-10 alphanumeric characters only).', 'woo-pincode-checker' );
			} elseif ( empty( $wpc_city ) || empty( $wpc_state ) ) {
				$message_type = 'error';
				$wpc_message = __( 'City and State are required fields.', 'woo-pincode-checker' );
			} elseif ( $wpc_delivery_days < 1 || $wpc_delivery_days > 365 ) {
				$message_type = 'error';
				$wpc_message = __( 'Delivery days must be between 1 and 365.', 'woo-pincode-checker' );
			} elseif ( $wpc_shipping_amount < 0 || $wpc_cod_amount < 0 ) {
				$message_type = 'error';
				$wpc_message = __( 'Amounts cannot be negative.', 'woo-pincode-checker' );
			} else {
				$table_name = $wpdb->prefix . 'pincode_checker';

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
							$wpc_message = __( 'Pincode updated successfully.', 'woo-pincode-checker' );
							// Clear cache for this pincode
							wp_cache_delete( 'wpc_pincode_' . md5( $wpc_pincode ), 'woo_pincode_checker' );
						} else {
							$message_type = 'error';
							$wpc_message = __( 'Error updating pincode. Please try again.', 'woo-pincode-checker' );
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
							$wpc_message = __( 'Pincode added successfully.', 'woo-pincode-checker' );
						} else {
							$message_type = 'error';
							$wpc_message = __( 'Error adding pincode. Please try again.', 'woo-pincode-checker' );
						}
					}
				} else {
					$message_type = 'error';
					$wpc_message = esc_html__( 'This pincode already exists.', 'woo-pincode-checker' );
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
					"SELECT * FROM {$wpdb->prefix}pincode_checker WHERE id = %d", 
					$id
				), ARRAY_A );
			}
		}
		?>
		<div class="wrap wpc-add-pincode-wrap">
		<h2>
		<?php
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) {
			esc_html_e( 'Edit Pincode', 'woo-pincode-checker' );
		} else {
			esc_html_e( 'Add Pincode', 'woo-pincode-checker' );
		}
		?>
			</h2>
			<div class="wpc-add-pincode-section">
				<form action="" method="post" name="wpc-picode-form" id="wpc-picode-form">
					<table class="form-table">
						<tbody>
							<tr>
								<th>
									<label for="wpc-pincode"><?php esc_html_e( 'Pincode', 'woo-pincode-checker' ); ?> <span class="required">*</span></label>
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
										   placeholder="<?php esc_attr_e( 'Enter pincode', 'woo-pincode-checker' ); ?>">
									<p class="description"><?php esc_html_e( 'Enter alphanumeric pincode (3-10 characters)', 'woo-pincode-checker' ); ?></p>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-city"><?php esc_html_e( 'City', 'woo-pincode-checker' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" 
										   required="required" 
										   class="regular-text" 
										   id="wpc-city" 
										   value="<?php echo ( isset( $query_results[0]['city'] ) ) ? esc_attr( $query_results[0]['city'] ) : ''; ?>" 
										   name="wpc-city"
										   maxlength="100"
										   placeholder="<?php esc_attr_e( 'Enter city name', 'woo-pincode-checker' ); ?>">
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-state"><?php esc_html_e( 'State', 'woo-pincode-checker' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" 
										   required="required" 
										   class="regular-text" 
										   id="wpc-state" 
										   name="wpc-state" 
										   value="<?php echo ( isset( $query_results[0]['state'] ) ) ? esc_attr( $query_results[0]['state'] ) : ''; ?>"
										   maxlength="100"
										   placeholder="<?php esc_attr_e( 'Enter state name', 'woo-pincode-checker' ); ?>">
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-shipping-amount"><?php esc_html_e( 'Shipping Amount', 'woo-pincode-checker' ); ?></label>
								</th>
								<td>
									<input type="number" 
										   step="0.01" 
										   min="0"
										   class="regular-text" 
										   id="wpc-shipping-amount" 
										   name="wpc_shipping_amount" 
										   value="<?php echo isset( $query_results[0]['shipping_amount'] ) ? esc_attr( $query_results[0]['shipping_amount'] ) : '0'; ?>">
									<p class="description"><?php esc_html_e( 'Enable shipping cost in settings to calculate the shipping amount.', 'woo-pincode-checker' ); ?></p>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-delivery-days"><?php esc_html_e( 'Delivery within days', 'woo-pincode-checker' ); ?></label>
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
									<p class="description"><?php esc_html_e( 'Number of days for delivery (1-365)', 'woo-pincode-checker' ); ?></p>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-case-on-delivery"><?php esc_html_e( 'Cash on Delivery', 'woo-pincode-checker' ); ?></label>
								</th>
								<td>
									<input type="checkbox" 
										   value="1" 
										   class="regular-text" 
										   id="wpc-case-on-delivery" 
										   name="wpc-case-on-delivery" 
										   <?php checked( '1', ( isset( $query_results[0]['case_on_delivery'] ) ) ? $query_results[0]['case_on_delivery'] : '' ); ?>>
									<label for="wpc-case-on-delivery"><?php esc_html_e( 'Enable Cash on Delivery for this pincode', 'woo-pincode-checker' ); ?></label>
								</td>
							</tr>
							<tr>
								<th>
									<label for="wpc-case-on-delivery-amount"><?php esc_html_e( 'Cash on Delivery Amount', 'woo-pincode-checker' ); ?></label>
								</th>
								<td>
									<input type="number" 
										   step="0.01" 
										   min="0"
										   class="regular-text" 
										   id="wpc-case-on-delivery-amount" 
										   name="wpc_case_on_delivery_amount" 
										   value="<?php echo ( isset( $query_results[0]['cod_amount'] ) ) ? esc_attr( $query_results[0]['cod_amount'] ) : '0'; ?>">
									<p class="description"><?php esc_html_e( 'If COD option is enabled, then COD amount will be counted on cart and checkout page.', 'woo-pincode-checker' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
		<?php
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) {
			submit_button( __( 'Update Pincode', 'woo-pincode-checker' ) );
		} else {
			submit_button( __( 'Add Pincode', 'woo-pincode-checker' ) );
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
			return __( 'File does not exist.', 'woo-pincode-checker' );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return __( 'Cannot read uploaded file.', 'woo-pincode-checker' );
		}

		// Check first line (header)
		$first_line = fgetcsv( $handle );
		if ( ! $first_line || count( $first_line ) < 3 ) {
			fclose( $handle );
			return __( 'Invalid CSV format. Expected columns: pincode, city, state, delivery_days, shipping_amount, cash_on_delivery, cod_amount', 'woo-pincode-checker' );
		}

		// Check a few data rows
		$row_count = 0;
		while ( ( $data = fgetcsv( $handle ) ) !== false && $row_count < 5 ) {
			if ( count( $data ) < 3 ) {
				fclose( $handle );
				return sprintf( __( 'Invalid CSV format at row %d. Minimum 3 columns required.', 'woo-pincode-checker' ), $row_count + 2 );
			}
			
			// Validate pincode format
			if ( ! empty( $data[0] ) && false === $this->validate_pincode( $data[0] ) ) {
				fclose( $handle );
				return sprintf( __( 'Invalid pincode format at row %d: %s', 'woo-pincode-checker' ), $row_count + 2, $data[0] );
			}
			
			$row_count++;
		}

		fclose( $handle );
		return true;
	}

	/**
	 * Upload Pincode with enhanced security.
	 *
	 * @since 1.0.0
	 */
	public function wpc_upload_pincodes_func() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woo-pincode-checker' ) );
		}

		global $wpdb;
		$wpc_message = '';
		$message_type = '';
		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );

		if ( isset( $_POST['upload_pincodes'] ) && isset( $_POST['wpc-pincode-submit'] ) ) {
			// Verify nonce
			if ( ! $this->verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpc-pincode-submit'] ) ), 'wpc-pincode-submit' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'woo-pincode-checker' ) );
			}

			$should_continue = true;
			
			// Validate file upload
			if ( ! isset( $_FILES['import'] ) || $_FILES['import']['error'] !== UPLOAD_ERR_OK ) {
				$message_type = 'error';
				$wpc_message = __( 'File upload error. Please try again.', 'woo-pincode-checker' );
				$should_continue = false;
			}

			if ( $should_continue ) {
				// Check file size (limit to 5MB)
				$max_size = 5 * 1024 * 1024; // 5MB
				if ( ! empty( $_FILES['import']['size'] ) && $_FILES['import']['size'] > $max_size ) {
					$message_type = 'error';
					$wpc_message = __( 'File too large. Maximum size is 5MB.', 'woo-pincode-checker' );
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
					$wpc_message = __( 'Invalid file type. Please upload a CSV file only.', 'woo-pincode-checker' );
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
						$wpc_message = __( 'File content does not match CSV format.', 'woo-pincode-checker' );
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
					fgetcsv( $handle );
					
					$table_name = $wpdb->prefix . 'pincode_checker';

					while ( ( $data = fgetcsv( $handle, 100000, ',' ) ) !== false ) {
						// Validate row data
						if ( count( $data ) < 3 ) {
							$skipped_count++;
							continue;
						}

						// Validate and sanitize pincode
						$pincode = $this->validate_pincode( trim( $data[0] ) );
						if ( false === $pincode ) {
							$error_count++;
							continue;
						}

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
							}
						} else {
							$skipped_count++;
						}
					}
					fclose( $handle );

					$message_type = 'updated';
					$wpc_message = sprintf(
						__( 'Import completed. %d pincodes imported, %d skipped (already exist), %d errors.', 'woo-pincode-checker' ),
						$imported_count,
						$skipped_count,
						$error_count
					);
				} else {
					$message_type = 'error';
					$wpc_message = __( 'Could not read the uploaded file.', 'woo-pincode-checker' );
				}
			}
		}

		?>
		<div class="wbcom-tab-content wpc-upload-pincode-wrap">
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
					<h3><?php esc_html_e( 'Upload Your CSV File', 'woo-pincode-checker' ); ?></h3>
					<p><?php esc_html_e( 'Upload a CSV file to import multiple pincodes at once. Make sure your file follows the correct format.', 'woo-pincode-checker' ); ?></p>
				</div>
				<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view wpc-upload-pincode-section">
					<form enctype="multipart/form-data" method="post">
						<section>
							<div class="form-table wpc-pincode-submit-importer-options">
								<div class="wbcom-settings-section-wrap">
									<div class="wbcom-settings-section-options-heading">
										<label for="upload">
											<?php esc_html_e( 'Select a CSV file to upload from your device.', 'woo-pincode-checker' ); ?>
										</label>
									</div>
									<div class="wbcom-settings-section-options">
										<input type="file" 
											   id="upload" 
											   name="import" 
											   accept=".csv,text/csv" 
											   required />
										<input type="hidden" name="action" value="save" />
										<input type="hidden" name="max_file_size" value="<?php echo esc_attr( $bytes ); ?>" />
										<br>
										<small>
											<?php
											printf(
												esc_html__( 'Note: Maximum file size allowed is %s. Only CSV files are accepted.', 'woo-pincode-checker' ),
												esc_html( $size )
											);
											?>
										</small>
									</div>
								</div>
								<div class="wbcom-settings-section-wrap">
									<div class="wbcom-settings-section-options-heading">
										<label for="upload">
											<?php esc_html_e( 'Download Sample CSV File:', 'woo-pincode-checker' ); ?>
										</label>
									</div>
									<div class="wbcom-settings-section-options">
										<a href="<?php echo esc_url( WPCP_PLUGIN_URL . 'sample-data/sample-pincodes.csv' ); ?>" class="button">
											<?php esc_html_e( 'Download Sample', 'woo-pincode-checker' ); ?>
										</a>
										<p class="description">
											<?php esc_html_e( 'Download the sample file to see the correct format. Your CSV should have columns: pincode, city, state, delivery_days, shipping_amount, cash_on_delivery, cod_amount', 'woo-pincode-checker' ); ?>
										</p>
									</div>
								</div>
							</div>
						</section>
						<div class="wc-actions submit">
							<button type="submit" class="button button-primary button-next" name="upload_pincodes">
								<?php esc_html_e( 'Import CSV File', 'woo-pincode-checker' ); ?>
							</button>
						</div>
						<?php wp_nonce_field( 'wpc-pincode-submit', 'wpc-pincode-submit' ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds a meta box to the post editing screen
	 */
	public function wpc_featured_meta() {
		add_meta_box( 
			'wpc-hide-pincode-checker', 
			__( 'Pincode/Zipcode for Shipping Availability', 'woo-pincode-checker' ), 
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
		wp_nonce_field( basename( __FILE__ ), 'wpc_hide_pincode_nonce' );
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
					<?php esc_html_e( 'Hide pincode checker for this product', 'woo-pincode-checker' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Check this option to hide the pincode checker form on this product page.', 'woo-pincode-checker' ); ?>
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
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpc_hide_pincode_nonce'] ) ), basename( __FILE__ ) ) ) {
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
	 * This Function is handle the Bulk delete ajax callback.
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

		// Use prepared statement for deletion
		$result = $wpdb->query( "TRUNCATE TABLE `{$table_name}`" );

		if ( false !== $result ) {
			// Clear all pincode cache
			wp_cache_flush_group( 'woo_pincode_checker' );
			wp_send_json_success( 'Pincodes deleted successfully' );
		} else {
			wp_send_json_error( 'Failed to delete pincodes' );
		}
	}
}