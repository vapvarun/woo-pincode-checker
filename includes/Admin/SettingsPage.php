<?php
/**
 * The admin screen on the shared Wbcom settings shell.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the shell, owns the nav and routes each tab to its renderer class.
 */
final class SettingsPage {

	public const SLUG = 'woo-pincode-checker';

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'boot_shell' ), 1 );
		add_action( 'admin_menu', array( $this, 'parent_menu' ), 5 );
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WBPC_FILE ), array( $this, 'action_links' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		Tabs\ToolsTab::register(); // After the shell registers 'wbcom-settings'.
	}

	/**
	 * Our admin CSS/JS, on our screen only.
	 */
	public function enqueue(): void {
		$screen = get_current_screen();

		if ( ! $screen || ! str_ends_with( $screen->id, '_page_' . self::SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'wbpc-admin', WBPC_URL . 'assets/admin/css/admin.css', array( 'wbcom-settings' ), WBPC_VERSION );
		wp_enqueue_script( 'wbpc-admin', WBPC_URL . 'assets/admin/js/admin.js', array( 'wp-api-fetch', 'wp-i18n', 'wp-url', 'wbcom-settings' ), WBPC_VERSION, true );
		wp_set_script_translations( 'wbpc-admin', 'woo-pincode-checker', WBPC_DIR . 'languages' );
		wp_localize_script(
			'wbpc-admin',
			'wbpcAdmin',
			array(
				'currency'  => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'decimals'  => wc_get_price_decimals(),
				'errorsUrl' => DownloadHandler::url( 'wbpc_import_errors' ),
			)
		);
	}

	/**
	 * Register this screen with the shell.
	 */
	public function boot_shell(): void {
		if ( ! class_exists( 'Wbcom_Settings_Page' ) ) {
			return;
		}

		Wbcom_Settings_Page::boot(
			array(
				'prefix'     => 'wbpc',
				'slug'       => self::SLUG,
				'capability' => Plugin::cap(),
				'assets_url' => WBPC_URL,
				'version'    => WBPC_VERSION,
				'icon'       => 'map-pin',
				'labels'     => array(
					'menu_title' => __( 'Pincode Checker', 'woo-pincode-checker' ),
					'brand'      => __( 'Pincode Checker', 'woo-pincode-checker' ),
					'subtitle'   => __( 'Delivery areas and dates', 'woo-pincode-checker' ),
					'nav_label'  => __( 'Pincode Checker sections', 'woo-pincode-checker' ),
				),
			)
		);

		add_filter( 'wbpc_settings_nav_groups', array( $this, 'nav_groups' ) );
		add_action( 'wbpc_settings_tab_content', array( $this, 'render_tab' ) );
	}

	/**
	 * The shared "WB Plugins" parent, only when no other Wbcom plugin has added it.
	 *
	 * Uses our capability so a Shop Manager can reach the child page even when this plugin owns the parent.
	 */
	public function parent_menu(): void {
		if ( ! empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) || ! class_exists( 'Wbcom_Settings_Page' ) ) {
			return;
		}

		add_menu_page(
			__( 'WB Plugins', 'woo-pincode-checker' ),
			__( 'WB Plugins', 'woo-pincode-checker' ),
			Plugin::cap(),
			'wbcomplugins',
			array( Wbcom_Settings_Page::class, 'render_welcome' ),
			'dashicons-lightbulb',
			59
		);
	}

	/**
	 * Tabs: id => [ title, Lucide icon, renderer class ]. Grouped for the sidebar.
	 *
	 * Tabs are added here as each one is built; nothing is listed before it works.
	 *
	 * @return array<string, array{label: string, items: array<string, array{title: string, icon: string, class: class-string}>}>
	 */
	private function tabs(): array {
		return array(
			'main'     => array(
				'label' => __( 'Pincode Checker', 'woo-pincode-checker' ),
				'items' => array(
					'wbpc-overview' => array(
						'title' => __( 'Overview', 'woo-pincode-checker' ),
						'icon'  => 'gauge',
						'class' => Tabs\OverviewTab::class,
					),
					'wbpc-areas'    => array(
						'title' => __( 'Service Areas', 'woo-pincode-checker' ),
						'icon'  => 'map-pin',
						'class' => Tabs\AreasTab::class,
					),
					'wbpc-import'   => array(
						'title' => __( 'Import / Export', 'woo-pincode-checker' ),
						'icon'  => 'file-up',
						'class' => Tabs\ImportTab::class,
					),
				),
			),
			'settings' => array(
				'label' => __( 'Settings', 'woo-pincode-checker' ),
				'items' => array(
					'wbpc-general'    => array(
						'title' => __( 'General', 'woo-pincode-checker' ),
						'icon'  => 'settings-2',
						'class' => Tabs\GeneralTab::class,
					),
					'wbpc-delivery'   => array(
						'title' => __( 'Delivery Dates', 'woo-pincode-checker' ),
						'icon'  => 'calendar-clock',
						'class' => Tabs\DeliveryTab::class,
					),
					'wbpc-categories' => array(
						'title' => __( 'Category Rules', 'woo-pincode-checker' ),
						'icon'  => 'folder-tree',
						'class' => Tabs\CategoriesTab::class,
					),
					'wbpc-checkout'   => array(
						'title' => __( 'Checkout and COD', 'woo-pincode-checker' ),
						'icon'  => 'credit-card',
						'class' => Tabs\CheckoutTab::class,
					),
					'wbpc-display'    => array(
						'title' => __( 'Display and Messages', 'woo-pincode-checker' ),
						'icon'  => 'palette',
						'class' => Tabs\DisplayTab::class,
					),
				),
			),
			'help'     => array(
				'label' => __( 'Help', 'woo-pincode-checker' ),
				'items' => array(
					'wbpc-tools' => array(
						'title' => __( 'Tools', 'woo-pincode-checker' ),
						'icon'  => 'wrench',
						'class' => Tabs\ToolsTab::class,
					),
					'wbpc-faq'   => array(
						'title' => __( 'FAQ', 'woo-pincode-checker' ),
						'icon'  => 'help-circle',
						'class' => Tabs\FaqTab::class,
					),
				),
			),
		);
	}

	/**
	 * Shell filter: declare the nav.
	 *
	 * @param array $groups Groups declared so far (Pro may add its own).
	 */
	public function nav_groups( array $groups ): array {
		return array_merge( $this->tabs(), $groups );
	}

	/**
	 * Shell action: render one tab body.
	 *
	 * @param string $tab_id Tab id.
	 */
	public function render_tab( string $tab_id ): void {
		foreach ( $this->tabs() as $group ) {
			if ( isset( $group['items'][ $tab_id ] ) ) {
				( new $group['items'][ $tab_id ]['class']() )->render();
				return;
			}
		}
	}

	/**
	 * One-time redirect to the Overview after a single, interactive activation.
	 */
	public function activation_redirect(): void {
		if ( ! get_transient( 'wbpc_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'wbpc_activation_redirect' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check of core's bulk-activation flag.
		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( Plugin::cap() ) ) {
			return;
		}

		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Plugins screen link.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html__( 'Settings', 'woo-pincode-checker' ) )
		);

		return $links;
	}

	/**
	 * URL of this screen, optionally on a tab.
	 *
	 * @param string $tab Tab id.
	 */
	public static function url( string $tab = '' ): string {
		$url = admin_url( 'admin.php?page=' . self::SLUG );

		return '' === $tab ? $url : add_query_arg( 'tab', $tab, $url );
	}
}
