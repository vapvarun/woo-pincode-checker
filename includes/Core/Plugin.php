<?php
/**
 * Plugin bootstrap: wires each layer's hooks.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Core;

use Wbcom\PincodeChecker\Admin\DownloadHandler;
use Wbcom\PincodeChecker\Admin\SettingsPage;
use Wbcom\PincodeChecker\Admin\SettingsRegistrar;
use Wbcom\PincodeChecker\CLI\Commands;
use Wbcom\PincodeChecker\Frontend\CheckerRenderer;
use Wbcom\PincodeChecker\Frontend\Messages;
use Wbcom\PincodeChecker\Frontend\Storefront;
use Wbcom\PincodeChecker\Integrations\WooCommerce\CodGateway;
use Wbcom\PincodeChecker\Integrations\WooCommerce\CustomerPostcode;
use Wbcom\PincodeChecker\Integrations\WooCommerce\DeliveryEstimates;
use Wbcom\PincodeChecker\Integrations\WooCommerce\Enforcement;
use Wbcom\PincodeChecker\Integrations\WooCommerce\ShippingMethod;
use Wbcom\PincodeChecker\Integrations\WooCommerce\ProductSettings;
use Wbcom\PincodeChecker\Repository\AreaRepository;
use Wbcom\PincodeChecker\REST\Controller\AreasController;
use Wbcom\PincodeChecker\REST\Controller\CheckController;
use Wbcom\PincodeChecker\REST\Controller\ImportController;
use Wbcom\PincodeChecker\REST\Controller\ToolsController;
use Wbcom\PincodeChecker\Services\AreaService;
use Wbcom\PincodeChecker\Services\CheckService;
use Wbcom\PincodeChecker\Services\CsvService;
use Wbcom\PincodeChecker\Services\DeliveryDateService;
use Wbcom\PincodeChecker\Services\ImportService;
use Wbcom\PincodeChecker\Services\ProductService;
use Wbcom\PincodeChecker\Services\RateLimiter;
use Wbcom\PincodeChecker\Services\SettingsService;

defined( 'ABSPATH' ) || exit;

/**
 * Entry point. Services get lazy getters here as they are built; this class is the container.
 */
final class Plugin {

	/**
	 * Lazily built services.
	 *
	 * @var array<string, object>
	 */
	private static array $services = array();

	/**
	 * Boot on plugins_loaded.
	 */
	public static function boot(): void {
		// `Requires Plugins: woocommerce` normally guarantees this; a forced deactivation must not fatal.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		Installer::maybe_upgrade();

		add_action( 'init', array( self::class, 'load_textdomain' ), 0 );

		add_action(
			'rest_api_init',
			static function (): void {
				( new AreasController() )->register_routes();
				( new ToolsController() )->register_routes();
				( new ImportController() )->register_routes();
				( new CheckController() )->register_routes();
			}
		);

		// Action Scheduler runs this from cron/async requests, so it is registered in every context.
		add_action(
			ImportService::HOOK,
			static function (): void {
				self::importer()->run_chunk();
			}
		);

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'wbpc', Commands::class );
		}

		// Storefront hooks, shortcode and block are needed everywhere (the editor renders the block too).
		( new Storefront() )->register();
		( new DeliveryEstimates() )->register();
		( new Enforcement() )->register();
		( new CodGateway() )->register();
		( new CustomerPostcode() )->register();

		add_filter(
			'woocommerce_shipping_methods',
			static function ( array $methods ): array {
				$methods[ ShippingMethod::ID ] = ShippingMethod::class;
				return $methods;
			}
		);

		if ( is_admin() ) {
			( new ProductSettings() )->register();
			( new SettingsPage() )->register();
			( new DownloadHandler() )->register();
			( new SettingsRegistrar() )->register();
		}
	}

	/**
	 * Load translations before the settings shell boots at init priority 1.
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'woo-pincode-checker', false, dirname( plugin_basename( WBPC_FILE ) ) . '/languages' );
	}

	/**
	 * Capability that manages the plugin: screen, REST and admin-post handlers all check this one.
	 */
	public static function cap(): string {
		/**
		 * Filter the capability required to manage Pincode Checker.
		 *
		 * @param string $cap Default manage_woocommerce, so Shop Managers can manage delivery areas.
		 */
		return (string) apply_filters( 'wbpc_manage_capability', 'manage_woocommerce' );
	}

	/**
	 * Activation: create tables, flag a one-time redirect to the Overview.
	 */
	public static function activate(): void {
		Installer::install();
		set_transient( 'wbpc_activation_redirect', 1, 30 );
	}

	/**
	 * Area storage.
	 */
	public static function areas(): AreaRepository {
		return self::$services['areas'] ??= new AreaRepository();
	}

	/**
	 * Area create/update/delete with validation and hooks.
	 */
	public static function area_service(): AreaService {
		return self::$services['area_service'] ??= new AreaService( self::areas() );
	}

	/**
	 * Settings schema + reader.
	 */
	public static function settings(): SettingsService {
		return self::$services['settings'] ??= new SettingsService();
	}

	/**
	 * CSV format helpers.
	 */
	public static function csv(): CsvService {
		return self::$services['csv'] ??= new CsvService();
	}

	/**
	 * Background CSV import.
	 */
	public static function importer(): ImportService {
		return self::$services['importer'] ??= new ImportService( self::area_service(), self::areas(), self::csv() );
	}

	/**
	 * Delivery estimate engine.
	 */
	public static function dates(): DeliveryDateService {
		return self::$services['dates'] ??= new DeliveryDateService( self::settings() );
	}

	/**
	 * Product-level rules.
	 */
	public static function products(): ProductService {
		return self::$services['products'] ??= new ProductService( self::settings() );
	}

	/**
	 * Public endpoint rate limiter.
	 */
	public static function rate_limiter(): RateLimiter {
		return self::$services['rate_limiter'] ??= new RateLimiter();
	}

	/**
	 * Shopper-facing text.
	 */
	public static function messages(): Messages {
		return self::$services['messages'] ??= new Messages( self::settings() );
	}

	/**
	 * Checker markup.
	 */
	public static function renderer(): CheckerRenderer {
		return self::$services['renderer'] ??= new CheckerRenderer( self::settings(), self::products() );
	}

	/**
	 * Postcode checker.
	 */
	public static function checker(): CheckService {
		return self::$services['checker'] ??= new CheckService( self::areas(), self::settings(), self::dates() );
	}
}
