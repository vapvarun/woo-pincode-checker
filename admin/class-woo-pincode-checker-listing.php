<?php
/**
 * The admin pincode checker listing.
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
 * The admin pincode checker listing.
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
	 * Check user capabilities for admin actions
	 *
	 * @return bool
	 */
	private function check_admin_capabilities() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Sanitize and validate search term
	 *
	 * @param string $search_term The search term to sanitize
	 * @return string|false Sanitized search term or false if invalid
	 */
	private function sanitize_search_term( $search_term ) {
		$search_term = trim( $search_term );
		
		// Limit search term length
		if ( strlen( $search_term ) > 50 ) {
			return false;
		}
		
		// Allow alphanumeric, spaces, and basic punctuation
		if ( ! preg_match( '/^[A-Za-z0-9\s\-_.,]+$/', $search_term ) ) {
			return false;
		}
		
		return sanitize_text_field( $search_term );
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
			'created_at'       => __( 'Created', 'woo-pincode-checker' ),
		);
		return $table_columns;
	}

	/**
	 * Get hidden columns
	 */
	protected function get_hidden_columns() {
		return array( 'created_at' );
	}

	/**
	 * Show success/error messages
	 */
	private function show_admin_notices() {
		if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == '1' ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>' . 
					 esc_html__( 'Pincode deleted successfully.', 'woo-pincode-checker' ) . 
					 '</p></div>';
			});
		}
		
		if ( isset( $_GET['bulk-deleted'] ) && intval( $_GET['bulk-deleted'] ) > 0 ) {
			$count = intval( $_GET['bulk-deleted'] );
			add_action( 'admin_notices', function() use ( $count ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . 
					 sprintf( 
						 esc_html( _n( '%d pincode deleted successfully.', '%d pincodes deleted successfully.', $count, 'woo-pincode-checker' ) ), 
						 $count 
					 ) . 
					 '</p></div>';
			});
		}
	}

	/**
	 * Code for fetch data and display listing.
	 */
	public function prepare_items() {
		// Show admin notices
		$this->show_admin_notices();
		
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/* Process bulk action */
		$this->get_bulk_action();
		$this->handle_table_actions();

		/* pagination */
		$user = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );

		/* pagination */
		$pincode_per_page = get_user_meta( $user, $option, true );

		if ( empty( $pincode_per_page ) || $pincode_per_page < 1 ) {
			$pincode_per_page = $screen->get_option( 'per_page', 'default' );
		}

		$current_page = $this->get_pagenum();
		$total_items = self::record_count();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $pincode_per_page,
			)
		);

		$this->items = $this->fetch_table_data( $pincode_per_page, $current_page );
	}

	/**
	 * Count a pincode from database with improved security.
	 */
	public static function record_count() {
		global $wpdb;

		$base_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}pincode_checker";
		$where_clause = '';
		$params = array();

		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$search_term = self::sanitize_search_term_static( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
			
			if ( false !== $search_term ) {
				$where_clause = " WHERE (pincode LIKE %s OR city LIKE %s OR state LIKE %s)";
				$search_pattern = '%' . $wpdb->esc_like( $search_term ) . '%';
				$params = array( $search_pattern, $search_pattern, $search_pattern );
			}
		}

		$sql = $base_sql . $where_clause;
		
		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return intval( $wpdb->get_var( $sql ) );
	}

	/**
	 * Static method to sanitize search term
	 */
	private static function sanitize_search_term_static( $search_term ) {
		$search_term = trim( $search_term );
		
		if ( strlen( $search_term ) > 50 ) {
			return false;
		}
		
		if ( ! preg_match( '/^[A-Za-z0-9\s\-_.,]+$/', $search_term ) ) {
			return false;
		}
		
		return $search_term;
	}

	/**
	 * Fetch data from database with enhanced security.
	 *
	 * @param int $pincode_per_page Display per page pincode.
	 * @param int $page_number Pagination.
	 */
	public function fetch_table_data( $pincode_per_page, $page_number = 1 ) {
		global $wpdb;

		$base_query = "SELECT * FROM {$wpdb->prefix}pincode_checker";
		$where_clause = '';
		$order_clause = ' ORDER BY id DESC';
		$limit_clause = '';
		$params = array();

		// Handle search
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$search_term = $this->sanitize_search_term( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
			
			if ( false !== $search_term ) {
				$where_clause = " WHERE (pincode LIKE %s OR city LIKE %s OR state LIKE %s)";
				$search_pattern = '%' . $wpdb->esc_like( $search_term ) . '%';
				$params[] = $search_pattern;
				$params[] = $search_pattern;
				$params[] = $search_pattern;
			}
		}

		// Handle ordering
		$allowed_orderby = array( 'pincode', 'city', 'state', 'delivery_days', 'shipping_amount', 'created_at' );
		$allowed_order = array( 'ASC', 'DESC' );
		
		if ( ! empty( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], $allowed_orderby, true ) ) {
			$orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
			$order = ( ! empty( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), $allowed_order, true ) ) 
				? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) 
				: 'ASC';
			$order_clause = " ORDER BY `{$orderby}` {$order}";
		}

		// Handle pagination
		if ( ! isset( $_REQUEST['s'] ) || empty( $_REQUEST['s'] ) ) {
			$offset = ( $page_number - 1 ) * $pincode_per_page;
			$limit_clause = " LIMIT %d OFFSET %d";
			$params[] = $pincode_per_page;
			$params[] = $offset;
		}

		$final_query = $base_query . $where_clause . $order_clause . $limit_clause;
		
		if ( ! empty( $params ) ) {
			$final_query = $wpdb->prepare( $final_query, $params );
		}

		$query_results = $wpdb->get_results( $final_query, ARRAY_A );

		if ( ! $query_results ) {
			return array();
		}

		// Process results for display
		foreach ( $query_results as &$val ) {
			// Format COD display
			if ( isset( $val['case_on_delivery'] ) && $val['case_on_delivery'] == 1 ) {
				$val['case_on_delivery'] = '<span class="wpc-status-available">' . esc_html__( 'Available', 'woo-pincode-checker' ) . '</span>';
			} else {
				$val['case_on_delivery'] = '<span class="wpc-status-unavailable">' . esc_html__( 'Unavailable', 'woo-pincode-checker' ) . '</span>';
			}

			// Format shipping amount
			if ( isset( $val['shipping_amount'] ) && $val['shipping_amount'] > 0 ) {
				$val['shipping_amount'] = wc_price( $val['shipping_amount'] );
			} else {
				$val['shipping_amount'] = '<span class="wpc-free">' . esc_html__( 'Free', 'woo-pincode-checker' ) . '</span>';
			}

			// Format COD amount
			if ( isset( $val['cod_amount'] ) && $val['cod_amount'] > 0 ) {
				$val['cod_amount'] = wc_price( $val['cod_amount'] );
			} else {
				$val['cod_amount'] = '<span class="wpc-free">' . esc_html__( 'Free', 'woo-pincode-checker' ) . '</span>';
			}

			// Format delivery days
			if ( isset( $val['delivery_days'] ) ) {
				$days = intval( $val['delivery_days'] );
				if ( $days === 1 ) {
					$val['delivery_days'] = sprintf( esc_html__( '%d day', 'woo-pincode-checker' ), $days );
				} else {
					$val['delivery_days'] = sprintf( esc_html__( '%d days', 'woo-pincode-checker' ), $days );
				}
			}

			// Format created date
			if ( isset( $val['created_at'] ) && ! empty( $val['created_at'] ) ) {
				$val['created_at'] = wp_date( get_option( 'date_format' ), strtotime( $val['created_at'] ) );
			}

			// Escape output for security
			$val['pincode'] = esc_html( $val['pincode'] );
			$val['city'] = esc_html( $val['city'] );
			$val['state'] = esc_html( $val['state'] );
		}
		unset( $val );

		return $query_results;
	}

	/**
	 * Display default column with improved security.
	 *
	 * @param array  $item Display pincode.
	 * @param string $column_name Display the column name.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'pincode':
			case 'city':
			case 'state':
			case 'delivery_days':
				return '<strong>' . $item[ $column_name ] . '</strong>';
			case 'shipping_amount':
			case 'case_on_delivery':
			case 'cod_amount':
			case 'created_at':
				return $item[ $column_name ];
			default:
				return '';
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
			esc_attr( $item['id'] )
		);
	}

	/**
	 * Display edit/delete option with improved security and nonces.
	 *
	 * @param array $item Display the pincodes.
	 */
	public function column_pincode( $item ) {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			return '<strong>' . esc_html( $item['pincode'] ) . '</strong>';
		}

		$actions = array();
		
		$edit_url = add_query_arg(
			array(
				'page'   => 'add_wpc_pincode',
				'action' => 'edit',
				'id'     => intval( $item['id'] ),
			),
			admin_url( 'admin.php' )
		);
		
		// Add nonce to delete URL
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'   => sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? '' ) ),
					'action' => 'delete',
					'id'     => intval( $item['id'] ),
				),
				admin_url( 'admin.php' )
			),
			'delete-pincode-' . intval( $item['id'] )
		);

		$actions['edit'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $edit_url ),
			esc_attr( sprintf( __( 'Edit pincode %s', 'woo-pincode-checker' ), $item['pincode'] ) ),
			esc_html__( 'Edit', 'woo-pincode-checker' )
		);

		$actions['delete'] = sprintf(
			'<a href="%s" aria-label="%s" onclick="return confirm(\'%s\')">%s</a>',
			esc_url( $delete_url ),
			esc_attr( sprintf( __( 'Delete pincode %s', 'woo-pincode-checker' ), $item['pincode'] ) ),
			esc_js( __( 'Are you sure you want to delete this pincode?', 'woo-pincode-checker' ) ),
			esc_html__( 'Delete', 'woo-pincode-checker' )
		);

		return sprintf( 
			'<strong>%1$s</strong> %2$s', 
			esc_html( $item['pincode'] ), 
			$this->row_actions( $actions ) 
		);
	}

	/**
	 * Sortable column.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'pincode'        => array( 'pincode', true ),
			'city'           => array( 'city', true ),
			'state'          => array( 'state', true ),
			'delivery_days'  => array( 'delivery_days', false ),
			'shipping_amount' => array( 'shipping_amount', false ),
			'created_at'     => array( 'created_at', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Handle table actions with improved security and proper nonce verification.
	 */
	public function handle_table_actions() {
		// Check user capabilities
		if ( ! $this->check_admin_capabilities() ) {
			return;
		}

		global $wpdb;

		// Delete single item action
		if ( isset( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action'] ) {
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
			
			if ( $id > 0 ) {
				// Verify nonce for delete action
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete-pincode-' . $id ) ) {
					wp_die( esc_html__( 'Security check failed. Invalid nonce.', 'woo-pincode-checker' ) );
				}
				
				self::delete_pincode( $id );
				
				// Redirect to avoid resubmission
				$redirect_url = remove_query_arg( array( 'action', 'id', '_wpnonce' ) );
				wp_redirect( add_query_arg( 'deleted', '1', $redirect_url ) );
				exit;
			}
		}

		// Bulk delete action
		if ( isset( $_REQUEST['action'] ) && 'bulk-delete' === $_REQUEST['action'] ) {
			$delete_ids = isset( $_REQUEST['bulk-delete'] ) ? array_map( 'intval', wp_unslash( $_REQUEST['bulk-delete'] ) ) : array();
			
			if ( ! empty( $delete_ids ) ) {
				// Verify nonce for bulk actions
				if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] ) ) {
					wp_die( esc_html__( 'Security check failed. Invalid nonce.', 'woo-pincode-checker' ) );
				}
				
				$deleted_count = 0;
				foreach ( $delete_ids as $id ) {
					if ( $id > 0 && self::delete_pincode( $id ) ) {
						$deleted_count++;
					}
				}
				
				$redirect_url = remove_query_arg( array( 'action', 'bulk-delete', '_wpnonce' ) );
				wp_redirect( add_query_arg( 'bulk-deleted', $deleted_count, $redirect_url ) );
				exit;
			}
		}
	}

	/**
	 * Delete Query with improved security.
	 */
	public static function delete_pincode( $id ) {
		// Validate ID
		$id = intval( $id );
		if ( $id <= 0 ) {
			return false;
		}

		global $wpdb;

		// Get pincode before deletion for cache clearing
		$pincode_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT pincode FROM {$wpdb->prefix}pincode_checker WHERE id = %d",
			$id
		) );

		$result = $wpdb->delete(
			"{$wpdb->prefix}pincode_checker",
			array( 'id' => $id ),
			array( '%d' )
		);

		// Clear related cache if deletion was successful
		if ( $result !== false && $pincode_data ) {
			wp_cache_delete( 'wpc_pincode_' . md5( $pincode_data->pincode ), 'woo_pincode_checker' );
		}

		return $result !== false;
	}

	/**
	 * Delete Action.
	 */
	public function get_bulk_actions() {
		$actions = array( 
			'bulk-delete' => esc_html__( 'Delete', 'woo-pincode-checker' ) 
		);
		return $actions;
	}

	/**
	 * Display when no items found
	 */
	public function no_items() {
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
			printf(
				esc_html__( 'No pincodes found matching "%s". Try a different search term.', 'woo-pincode-checker' ),
				esc_html( $search_term )
			);
		} else {
			esc_html_e( 'No pincodes found. Add your first pincode to get started.', 'woo-pincode-checker' );
		}
	}

	/**
	 * Extra table navigation
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
			<div class="alignleft actions">
				<select name="action2" id="bulk-action-selector-<?php echo esc_attr( $which ); ?>">
					<option value="-1"><?php esc_html_e( 'Bulk Actions', 'woo-pincode-checker' ); ?></option>
					<option value="bulk-delete"><?php esc_html_e( 'Delete', 'woo-pincode-checker' ); ?></option>
				</select>
				<?php submit_button( __( 'Apply', 'woo-pincode-checker' ), 'action', '', false, array( 'id' => "doaction{$which}" ) ); ?>
			</div>
			
			<div class="alignright">
				<span class="wpc-stats">
					<?php
					$total_pincodes = self::record_count();
					printf(
						esc_html( _n( '%s pincode total', '%s pincodes total', $total_pincodes, 'woo-pincode-checker' ) ),
						'<strong>' . number_format_i18n( $total_pincodes ) . '</strong>'
					);
					?>
				</span>
			</div>
			
			<style>
			.wpc-status-available {
				color: #00a32a;
				font-weight: 600;
			}
			.wpc-status-unavailable {
				color: #d63638;
			}
			.wpc-free {
				color: #00a32a;
				font-style: italic;
			}
			.wpc-stats {
				color: #646970;
				font-size: 13px;
				line-height: 2.15384615;
			}
			.tablenav .actions {
				overflow: visible;
			}
			</style>
			<?php
		}
	}

	/**
	 * Generate the table navigation above or below the table
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}
}