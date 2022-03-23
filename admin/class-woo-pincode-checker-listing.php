<?php
/**
 * The admin pincode cheker listing.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * The admin pincode cheker listing.
 *
 * Defines the constructor, columns, fetching data from database and display listing and actions.
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin
 * @author     wbcomdesigns <admin@wbcomdesigns.com>
 */
class Woo_Pincode_Checker_Listing extends WP_List_Table {

	/**
	 * Class constructor
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Pincode', 'woo-pincode-checker' ),
				'plural'   => __( 'Pincodes', 'woo-pincode-checker' ),
				'ajax'     => false,

			)
		);

	}

	/**
	 * Display column.
	 */
	public function get_columns() {

		$table_columns = array(
			'cb'               => '<input type="checkbox" />',
			'pincode'          => __( 'Pincode', 'woo-pincode-checker' ),
			'city'             => __( 'City', 'woo-pincode-checker' ),
			'state'            => __( 'State', 'woo-pincode-checker' ),
			'delivery_days'    => __( 'Delivery Days', 'woo-pincode-checker' ),
			'shipping_amount'  => __( 'Shipping Amount', 'woo-pincode-checker' ),
			'case_on_delivery' => __( 'Cash on Delivery', 'woo-pincode-checker' ),
			'cod_amount'       => __( 'COD Amount', 'woo-pincode-checker' ),
		);
		return $table_columns;
	}

	/**
	 * Code for fetch data and display listing.
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/*Process bulk action */
		$this->get_bulk_action();
		$this->handle_table_actions();

		/* pagination */
		$user   = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );

		/* pagination */
		$pincode_per_page = get_user_meta( $user, $option, true );

		if ( empty( $pincode_per_page ) || $pincode_per_page < 1 ) {
			$pincode_per_page = $screen->get_option( 'per_page', 'default' );
		}

		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $pincode_per_page,
			)
		);

		$this->items = $this->fetch_table_data( $pincode_per_page, $current_page );
	}

	/**
	 * Count a pincode from database.
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}pincode_checker";
		if ( isset( $_REQUEST['s'] ) && '' !== $_REQUEST['s'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$sql .= " WHERE `pincode` LIKE '%" . sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) . "%' "; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return $wpdb->get_var( $sql );
	}

	/**
	 * Fetch data from database.
	 *
	 * @param string $pincode_per_page Display per page pincode.
	 * @param string $page_number Pagination.
	 */
	public function fetch_table_data( $pincode_per_page, $page_number = 1 ) {
		global $wpdb;

		/* -- Preparing your query -- */
		$pincode_query = "SELECT * FROM {$wpdb->prefix}pincode_checker";

		if ( isset( $_REQUEST['s'] ) && '' !== $_REQUEST['s'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$pincode_query .= " WHERE `pincode` LIKE '%" . sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) . "%' "; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$pincode_query .= ' ORDER BY ' . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$pincode_query .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : ' ASC'; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( ! isset( $_REQUEST['s'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$pincode_query .= " LIMIT $pincode_per_page";
			$pincode_query .= ' OFFSET ' . ( $page_number - 1 ) * $pincode_per_page;
		}

		$query_results = $wpdb->get_results( $pincode_query, ARRAY_A );
		return $query_results;
	}

	/**
	 *  Display default column.
	 *
	 * @param string $item Display pincode.
	 * @param string $column_name Display the column name.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'pincode':
			case 'city':
			case 'state':
			case 'delivery_days':
			case 'shipping_amount':
			case 'case_on_delivery':
			case 'cod_amount':
				return '<strong>' . $item[ $column_name ] . '</strong>';
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Display bulk checkbox.
	 *
	 * @param array $item Get a Posts.
	 */
	public function column_cb( $item ) {

		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Display edit/delete option.
	 *
	 * @param string $item Display the pincodes.
	 */
	public function column_pincode( $item ) {

		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&action=%s&id=%s">Edit</a>', 'add_wpc_pincode', 'edit', $item['id'] ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s">Delete</a>', sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'delete', $item['id'] ), //phpcs:ignore
		);
		return sprintf( '%1$s %2$s', $item['pincode'], $this->row_actions( $actions ) );
	}

	/**
	 * Sortable column.
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'pincode' => array( 'pincode', true ),
			'city'    => array( 'city', true ),
			'state'   => array( 'state', true ),
		);
		return $sortable_columns;
	}

	/**
	 * Delete action.
	 */
	public function handle_table_actions() {
		global $wpdb;

		/* delete action */
		if ( ( isset( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::delete_pincode( absint( $_GET['id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/* bulk delete */
		if ( ( isset( $_REQUEST['action'] ) && 'bulk-delete' === $_REQUEST['action'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$delete_ids = isset( $_REQUEST['bulk-delete'] ) ? map_deep( wp_unslash( $_REQUEST['bulk-delete'] ), 'sanitize_text_field' ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $delete_ids ) {
				// loop over the array of record IDs and delete them.
				foreach ( $delete_ids as $id ) {
					self::delete_pincode( $id );
				}
			}

			wp_redirect( esc_url( add_query_arg() ) );
			wp_die();
		}
	}

	/**
	 *  Delete Query.
	 */
	public static function delete_pincode( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}pincode_checker",
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * Delete Action.
	 */
	public function get_bulk_actions() {
		$actions = array( 'bulk-delete' => 'Delete' );
		return $actions;
	}
}
