<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 * @author     wbcomdesigns <admin@wbcomdesigns.com>
 */
class Woo_Pincode_Checker {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woo_Pincode_Checker_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WOO_PINCODE_CHECKER_VERSION' ) ) {
			$this->version = WOO_PINCODE_CHECKER_VERSION;
		} else {
			$this->version = '1.3.4';
		}
		$this->plugin_name = 'woo-pincode-checker';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woo_Pincode_Checker_Loader. Orchestrates the hooks of the plugin.
	 * - Woo_Pincode_Checker_i18n. Defines internationalization functionality.
	 * - Woo_Pincode_Checker_Admin. Defines all hooks for the admin area.
	 * - Woo_Pincode_Checker_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woo-pincode-checker-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woo-pincode-checker-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woo-pincode-checker-functions.php';
		
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woo-pincode-checker-admin.php';

		/* Enqueue wbcom plugin settings file if it exists. */
		$wbcom_settings_file = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wbcom/wbcom-admin-settings.php';
		if ( file_exists( $wbcom_settings_file ) ) {
			require_once $wbcom_settings_file;
		}

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woo-pincode-checker-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woo-pincode-checker-form.php';
		
		/**
		 * Include plugin Update Checker file if it exists.
		 */
		$update_checker_file = plugin_dir_path( dirname( __FILE__ ) ) . 'wpc-update-checker/plugin-update-checker.php';
		if ( file_exists( $update_checker_file ) ) {
			require_once $update_checker_file;
		}
		
		/**
		 * Include plugin General Functions file.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/woo-pincode-checker-general-functions.php';

		$this->loader = new Woo_Pincode_Checker_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woo_Pincode_Checker_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Woo_Pincode_Checker_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woo_Pincode_Checker_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'wpc_admin_menu', 100 );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'wpc_add_admin_register_setting' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'wpc_featured_meta' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'wpc_meta_save' );
		
		/* screen option */
		$this->loader->add_filter( 'set-screen-option', $plugin_admin, 'wpc_pincode_per_page_set_option', 10, 3 );
		$this->loader->add_action( 'wp_ajax_wpc_bulk_delete_action', $plugin_admin, 'wpc_bulk_delete_action_ajax_callback', 10, 3 );
		$this->loader->add_action( 'in_admin_header', $plugin_admin, 'wpc_hide_all_admin_notices_from_setting_page' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Woo_Pincode_Checker_Public( $this->get_plugin_name(), $this->get_version() );
		$pincode_form  = new Woo_Pincode_Checker_Form( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_before_main_content', $plugin_public, 'wpc_hide_shop_page_cart_button' );
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $plugin_public, 'wpc_added_wc_shipping_and_cod_amount' );
		$this->loader->add_action( 'woocommerce_checkout_init', $plugin_public, 'wpc_refresh_checkout_form_on_payment_method_switched' );
		$this->loader->add_action( 'wp_ajax_wpc_check_checkout_page_pincode', $plugin_public, 'wpc_check_checkout_page_pincode' );
		$this->loader->add_action( 'wp_ajax_nopriv_wpc_check_checkout_page_pincode', $plugin_public, 'wpc_check_checkout_page_pincode' );
		$this->loader->add_action( 'woocommerce_after_checkout_validation', $plugin_public, 'wpc_add_pincode_checker_validation_on_checkout_page', 10, 2 );
		
		/* add ajax for pincode checker */
		$this->loader->add_action( 'wp_ajax_nopriv_wpc_picode_check_ajax_submit', $pincode_form, 'wpc_picode_check_ajax_submit' );
		$this->loader->add_action( 'wp_ajax_wpc_picode_check_ajax_submit', $pincode_form, 'wpc_picode_check_ajax_submit' );
		
		$wpc_pincode_btn_position = wpc_single_product_button_position();
		if ( 'woocommerce_before_add_to_cart_button' === $wpc_pincode_btn_position ) {
			/* add pincode checker form single product page */
			$this->loader->add_action( $wpc_pincode_btn_position, $pincode_form, 'wpc_display_pincode_field' );
		} elseif ( 'woocommerce_after_add_to_cart_button' === $wpc_pincode_btn_position ) {
			/* add pincode checker form single product page */
			$this->loader->add_action( $wpc_pincode_btn_position, $pincode_form, 'wpc_display_pincode_field' );
		} elseif ( 'woocommerce_after_add_to_cart_quantity' === $wpc_pincode_btn_position ) {
			/* add pincode checker form single product page */
			$this->loader->add_action( $wpc_pincode_btn_position, $pincode_form, 'wpc_display_pincode_field' );
		} elseif ( 'wpc_pincode_checker' === $wpc_pincode_btn_position ) {
			/* add pincode checker form single product page */
			$this->loader->add_shortcode( $wpc_pincode_btn_position, $pincode_form, 'wpc_display_shortcode_pincode_form' );
		}

		/* admin setting css */
		$this->loader->add_action( 'wp_head', $pincode_form, 'wpc_add_custom_css' );
		if( 'wpc_pincode_checker' !== $wpc_pincode_btn_position ){
			$this->loader->add_action( 'init', $pincode_form, 'wpc_set_wc_billing_and_shipping_zipcode' );
		}

	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woo_Pincode_Checker_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}