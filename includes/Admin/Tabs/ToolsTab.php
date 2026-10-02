<?php
/**
 * Tools tab: status and maintenance actions.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Admin\SettingsPage;
use Wbcom\PincodeChecker\Core\Installer;
use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Actions post to admin-post.php, so they work without JavaScript.
 */
final class ToolsTab {

	public const ACTIONS = array( 'wbpc_tool_upgrade', 'wbpc_tool_cache', 'wbpc_tool_keep_data' );

	/**
	 * Hook the action handlers.
	 */
	public static function register(): void {
		foreach ( self::ACTIONS as $action ) {
			add_action( 'admin_post_' . $action, array( self::class, 'handle' ) );
		}
	}

	/**
	 * Run one tool, then return to the tab with a notice.
	 */
	public static function handle(): void {
		$action = current_action() ? substr( current_action(), strlen( 'admin_post_' ) ) : '';

		if ( ! current_user_can( Plugin::cap() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'woo-pincode-checker' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action );

		switch ( $action ) {
			case 'wbpc_tool_upgrade':
				Installer::install();
				$done = __( 'Database tables checked and updated.', 'woo-pincode-checker' );
				break;
			case 'wbpc_tool_cache':
				Plugin::areas()->bump_version();
				$done = __( 'Lookup cache cleared.', 'woo-pincode-checker' );
				break;
			default:
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
				$keep = ! empty( $_POST['keep'] );
				update_option( 'wbpc_keep_data', $keep ? 'yes' : 'no', false );
				$done = $keep ? __( 'Your areas and settings will be kept if the plugin is deleted.', 'woo-pincode-checker' ) : __( 'All plugin data will be removed if the plugin is deleted.', 'woo-pincode-checker' );
		}

		add_settings_error( 'wbpc_tools', 'wbpc_tools', $done, 'success' );
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', SettingsPage::url( 'wbpc-tools' ) ) );
		exit;
	}

	/**
	 * Render.
	 */
	public function render(): void {
		global $wpdb;

		$pending = as_get_scheduled_actions(
			array(
				'group'    => 'wbpc',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 100,
			),
			'ids'
		);
		$failed  = as_get_scheduled_actions(
			array(
				'group'    => 'wbpc',
				'status'   => \ActionScheduler_Store::STATUS_FAILED,
				'per_page' => 100,
			),
			'ids'
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Status check of our own table.
		$exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', Plugin::areas()->table() ) );

		Wbcom_Settings_Page::card_open( __( 'System status', 'woo-pincode-checker' ), __( 'Share this with support if something does not look right.', 'woo-pincode-checker' ) );
		echo '<dl class="wbpc-tester__list">';
		$this->item( __( 'Areas table', 'woo-pincode-checker' ), $exists ? sprintf( '%s (%s)', Plugin::areas()->table(), number_format_i18n( Plugin::areas()->count() ) ) : __( 'Missing - run "Update database" below', 'woo-pincode-checker' ) );
		/* translators: 1: installed schema version, 2: expected schema version. */
		$this->item( __( 'Database version', 'woo-pincode-checker' ), sprintf( __( '%1$s (expected %2$s)', 'woo-pincode-checker' ), (string) get_option( 'wbpc_db_version', '-' ), Installer::DB_VERSION ) );
		$this->item( __( 'Background jobs', 'woo-pincode-checker' ), sprintf( /* translators: 1: pending, 2: failed. */ __( '%1$d pending, %2$d failed', 'woo-pincode-checker' ), count( $pending ), count( $failed ) ) );
		$this->item( __( 'Object cache', 'woo-pincode-checker' ), wp_using_ext_object_cache() ? __( 'Persistent', 'woo-pincode-checker' ) : __( 'Per request (no persistent cache)', 'woo-pincode-checker' ) );
		$this->item( __( 'Plugin / WooCommerce / PHP', 'woo-pincode-checker' ), WBPC_VERSION . ' / ' . WC()->version . ' / ' . PHP_VERSION );
		echo '</dl>';
		Wbcom_Settings_Page::card_close();

		Wbcom_Settings_Page::card_open( __( 'Maintenance', 'woo-pincode-checker' ) );
		$this->button( 'wbpc_tool_upgrade', __( 'Update database', 'woo-pincode-checker' ), __( 'Creates or repairs the plugin\'s table. Safe to run any time.', 'woo-pincode-checker' ) );
		$this->button( 'wbpc_tool_cache', __( 'Clear lookup cache', 'woo-pincode-checker' ), __( 'Only needed if a change you made directly in the database is not showing.', 'woo-pincode-checker' ) );
		Wbcom_Settings_Page::card_close();

		$keep = 'yes' === get_option( 'wbpc_keep_data', 'no' );
		Wbcom_Settings_Page::card_open( __( 'When the plugin is deleted', 'woo-pincode-checker' ) );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wbpc-tool">
			<input type="hidden" name="action" value="wbpc_tool_keep_data">
			<?php wp_nonce_field( 'wbpc_tool_keep_data' ); ?>
			<label><input type="checkbox" name="keep" value="1" <?php checked( $keep ); ?>> <?php esc_html_e( 'Keep my delivery areas and settings when the plugin is deleted', 'woo-pincode-checker' ); ?></label>
			<p class="description"><?php esc_html_e( 'Off: deleting the plugin removes its table, settings and files. Delivery dates already saved on orders are always kept.', 'woo-pincode-checker' ); ?></p>
			<p><button type="submit" class="button wbcom-btn"><?php esc_html_e( 'Save', 'woo-pincode-checker' ); ?></button></p>
		</form>
		<?php
		Wbcom_Settings_Page::card_close();
	}

	/**
	 * Status line.
	 *
	 * @param string $term  Label.
	 * @param string $value Value.
	 */
	private function item( string $term, string $value ): void {
		printf( '<dt>%s</dt><dd>%s</dd>', esc_html( $term ), esc_html( $value ) );
	}

	/**
	 * A one-button admin-post form.
	 *
	 * @param string $action Action.
	 * @param string $label  Button.
	 * @param string $desc   Help.
	 */
	private function button( string $action, string $label, string $desc ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wbpc-tool">
			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
			<?php wp_nonce_field( $action ); ?>
			<button type="submit" class="button wbcom-btn"><?php echo esc_html( $label ); ?></button>
			<span class="description"><?php echo esc_html( $desc ); ?></span>
		</form>
		<?php
	}
}
