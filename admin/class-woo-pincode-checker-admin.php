<?php
require_once('class-woo-pincode-checker-listing.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wbcomdesigns.com/plugins
 * @since      1.0.0
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/admin
 */

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
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
    
	private $plugin_settings_tabs;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Pincode_Checker_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Pincode_Checker_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-pincode-checker-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Pincode_Checker_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Pincode_Checker_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-pincode-checker-admin.js', array( 'jquery' ), $this->version, false );

	}
	
	/**
	 * Add Woo Pincode Checker Menu in admin.
	 *
	 * @since    1.0.0
	 */
	public function wpc_admin_menu() {
		
		add_menu_page( esc_html__('Pincodes','woo-pincode-checker'), esc_html__('Pincodes','woo-pincode-checker'), 'manage_options' , 'pincode_lists' , array( $this,'wpc_pincode_lists_func') , '' , '50');
		
		$page_hook = add_submenu_page('pincode_lists', esc_html__('Pincode List','woo-pincode-checker'), esc_html__('Pincode List','woo-pincode-checker'), 'manage_options', 'pincode_lists', array( $this,'wpc_pincode_lists_func') );
		
		add_submenu_page('pincode_lists', esc_html__('Add Pincode','woo-pincode-checker'), esc_html__('Add Pincode','woo-pincode-checker'), 'manage_options', 'add_wpc_pincode', array( $this,'wpc_add_pincode_func') );
		
		add_submenu_page('pincode_lists', esc_html__('Upload pincodes','woo-pincode-checker'), esc_html__('Upload pincodes','woo-pincode-checker'), 'manage_options', 'wpc_upload_pincodes', array( $this,'wpc_upload_pincodes_func'));
		
		/* add sub menu in wnplugin setting page */
		if ( empty ( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
			add_menu_page( esc_html__( 'WB Plugins', 'woo-pincode-checker' ), esc_html__( 'WB Plugins', 'woo-pincode-checker' ), 'manage_options', 'wbcomplugins', array( $this, 'wpc_admin_settings_page' ), 'dashicons-lightbulb', 59 );
		 	add_submenu_page( 'wbcomplugins', esc_html__( 'General', 'woo-pincode-checker' ), esc_html__( 'General', 'woo-pincode-checker' ), 'manage_options', 'wbcomplugins' );
		}
		add_submenu_page( 'wbcomplugins', esc_html__( 'Woo Pincode Checker', 'woo-pincode-checker' ), esc_html__( 'Woo Pincode Checker', 'woo-pincode-checker' ), 'manage_options', 'woo-pincode-checker', array( $this, 'wpc_admin_settings_page' ) );
		
		/* screen Option */
		add_action( 'load-'.$page_hook, array( $this, 'load_user_list_table_screen_options' ) );
    }
	
	public function wpc_admin_settings_page() {
		$current = ( filter_input( INPUT_GET, 'tab' ) !== null ) ? filter_input( INPUT_GET, 'tab' ) : 'wpc-general';
		
		?>
	
		 <div class="wrap">
			<div class="ess-admin-header">
				<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
				<h1 class="wbcom-plugin-heading">
					<?php esc_html_e( 'Woo Pincode Checker', 'woo-pincode-checker' ); ?>
				</h1>
			</div>
			<div class="wbcom-admin-settings-page">
				<?php
				$this->wpc_plugin_settings_tabs();
				settings_fields( $current );
				do_settings_sections( $current );
				?>
			</div>
		 </div>
		<?php 
	}
	
	/**
	  * add tab in setting page 
	  */
	public function wpc_plugin_settings_tabs() {
		$current = ( filter_input( INPUT_GET, 'tab' ) !== null ) ? filter_input( INPUT_GET, 'tab' ) : 'wpc-general';
		
		$tab_html = '<div class="wbcom-tabs-section"><h2 class="nav-tab-wrapper">';
		
		foreach ( $this->plugin_settings_tabs as $edd_tab => $tab_name ) {
			$class     = ( $edd_tab === $current ) ? 'nav-tab-active' : '';
			$page      = 'woo-pincode-checker';
			$tab_html .= '<a id="' . $edd_tab . '" class="nav-tab ' . $class . '" href="admin.php?page=' . $page . '&tab=' . $edd_tab . '">' . $tab_name . '</a>';
		} 
		$tab_html .= '</h2></div>';
		echo $tab_html;
	}
	
	/**
	  * Get general settings html.
	  */
	public function wpc_general_settings_content() {
		require_once 'partials/woo-pincode-checker-admin-display.php';
	}
	
	/**
	  * Register all settings.
	  */
	public function wpc_add_admin_register_setting() {
		$this->plugin_settings_tabs['wpc-general'] = esc_html__( 'General', 'woo-pincode-checker' );
		register_setting( 'wpc_general_settings', 'wpc_general_settings' );
		add_settings_section( 'wpc-general', ' ', array( $this, 'wpc_general_settings_content' ), 'wpc-general' ); 
	} 
	
	
	/**
	  * add screen option 
	  */
	public function load_user_list_table_screen_options() {
		$arguments = array(
			'label'		=>	__( 'Pincode Per Page', 'woo-pincode-checker' ),
			'default'	=>	20,
			'option'	=>	'pincode_checker_per_page'
		);
		add_screen_option( 'per_page', $arguments );
		$this->Woo_Pincode_Checker_Listing = new Woo_Pincode_Checker_Listing( 'woo-pincode-checker' );		
	}
	
	/**
	  * Save screen option 
	  */
	public function pincode_per_page_set_option( $status, $option, $value ) {

		if ( 'pincode_checker_per_page' == $option ) {
			return $value;
		}
		return $status;
	}
	
	/**
	 * Display Pincode Lists table in admin.
	 *
	 * @since    1.0.0
	 */
	public function wpc_pincode_lists_func() {
		?>
		<div class="wrap">
			
			<h2>
				<?php esc_html_e('Pincode Lists','woo-pincode-checker' ); ?>
				<a class="add-new-h2" href="<?php echo admin_url( 'admin.php?page=add_wpc_pincode' ); ?>"><?php esc_html_e('Add New','woo-pincode-checker'); ?></a>
			</h2>
			
			<div class="pincode-listing">
			    <form id="nds-user-list-form" method="get">
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
					<?php 
					$pincode_list = new Woo_Pincode_Checker_Listing();
					
					if( isset($_GET['s']) ){
						$pincode_list->prepare_items($_GET['s']);
					} else {
						$pincode_list->prepare_items();
					}
					$pincode_list->search_box( 'Search Pincode', 'woo-pincode-checker' );
					$pincode_list->display(); ?>
				</form>
			</div>
		</div>
		
		<?php
	}
	
	/**
	 * Add Pincode.
	 *
	 * @since    1.0.0
	 */
	public function wpc_add_pincode_func() {
		
		global $wpdb;
		
		$wpc_message = $message_type = '';
		
		if (  isset($_POST['wpc-pincode-submit']) && $_POST['wpc-pincode-submit'] != ''  ) {			
			$wpc_pincode 			= sanitize_text_field( $_POST['wpc-pincode'] );		
			$wpc_city 				= sanitize_text_field( $_POST['wpc-city'] );		
			$wpc_state 				= sanitize_text_field( $_POST['wpc-state'] );		
			$wpc_delivery_days 		= sanitize_text_field( $_POST['wpc-delivery-days'] );
			$wpc_case_on_delivery	= sanitize_text_field( $_POST['wpc-case-on-delivery'] );
			
			if ( $wpc_pincode != '' ) {
				
				$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
				$num_rows = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ".$pincode_checker_table_name." where `pincode` = %s", $wpc_pincode ) );	
       				
				if( $num_rows == 0 ) {
					/* insert Record */
					$wpdb->insert(
						$pincode_checker_table_name,
						array(
							'pincode'       	=> $wpc_pincode,
							'city'         		=> $wpc_city,
							'state'        		=> $wpc_state,
							'delivery_days'     => $wpc_delivery_days,
							'case_on_delivery'  => $wpc_case_on_delivery,						
						),
						array( '%s', '%s', '%s', '%d', '%d' )
					);
					$message_type = 'updated';
					$wpc_message = esc_html__( 'Added Pincode Successfully.', 'woo-pincode-checker');
				} else {
					/* update Record */
					if( $_REQUEST['action'] == 'edit' ) {						
						$id = $_REQUEST['id'];
						$wpdb->update(
								$pincode_checker_table_name,
								array(
									'pincode'       	=> $wpc_pincode,
									'city'         		=> $wpc_city,
									'state'        		=> $wpc_state,
									'delivery_days'     => $wpc_delivery_days,
									'case_on_delivery'  => $wpc_case_on_delivery,						
								),
								array( 'id' => $id )
							);
						$message_type = 'updated';
						$wpc_message = esc_html__('Update Pincode Successfully .','woo-pincode-checker');
					}
				}
			} else {
				$message_type = 'error';
				$wpc_message = esc_html__( 'Please fill valid pincode info.', 'woo-pincode-checker');
			}
		} 
		
		if ( $wpc_message != '' ) { ?>
			<div class="<?php echo esc_attr($message_type); ?> below-h2" id="message">
				<p><?php echo $wpc_message;?></p>
			</div>
			<?php
		}
		
		/* if edit action then display record */
		if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' ) {
			$id = $_REQUEST['id'];
			$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
			$sql = "SELECT * FROM ".$pincode_checker_table_name." Where `id` =".$id;
			$query_results = $wpdb->get_results( $sql, ARRAY_A  );
		}
		?>
		
		<div class="wrap wpc-add-pincode-wrap">	
           		
			<h2>
			   <?php if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' ) {
					esc_html_e('Edit Pincode','woo-pincode-checker' ); 
			   } else {
				   esc_html_e('Add Pincode','woo-pincode-checker' ); 
			   }?>
			</h2>
			
			<div class="wpc-add-pincode-section">
				<form action="" method="post" name="wpc-picode-form" id="wpc-picode-form">
					<table class="form-table">
						<tbody>
							<tr>
								<th>
									<label for="wpc-pincode"><?php esc_html_e('Pincode','woo-pincode-checker'); ?></label>
								</th>
								
								<td>
									<input type="text"  pattern="[a-zA-Z0-9\s]+" required="required" class="regular-text" id="wpc-pincode" value="<?php echo ( isset($query_results[0]['pincode'])) ? $query_results[0]['pincode'] : '';?>" name="wpc-pincode">
								</td>
							</tr>
							<tr>

								<th>
									<label for="wpc-city"><?php esc_html_e('City','woo-pincode-checker'); ?></label>
								</th>

								<td>
									<input type="text" required="required" class="regular-text" id="wpc-city" value="<?php echo (isset($query_results[0]['city'])) ? $query_results[0]['city'] : '';?>" name="wpc-city">
								</td>

							</tr>
							<tr>

								<th>
									<label for="wpc-state"><?php esc_html_e('State','woo-pincode-checker'); ?></label>
								</th>

								<td>
									<input type="text" required="required" class="regular-text" id="wpc-state" name="wpc-state" value="<?php echo (isset($query_results[0]['state'])) ? $query_results[0]['state'] : '';?>">
								</td>

							</tr>

							<tr>
								<th>
									<label for="wpc-delivery-days"><?php esc_html_e('Delivery within days','woo-pincode-checker'); ?></label>
								</th>

								<td><input type="number" min="1" max="365" step="1" class="regular-text" id="wpc-delivery-days" name="wpc-delivery-days" value="<?php echo (isset($query_results[0]['delivery_days'])) ? $query_results[0]['delivery_days'] : '';?>"></td>
							</tr>
							<tr>
								<th>
									<label for="wpc-case-on-delivery"><?php esc_html_e('Cash on Delivery','woo-pincode-checker'); ?></label>
								</th>

								<td><input type="checkbox" value="1" class="regular-text" id="wpc-case-on-delivery" name="wpc-case-on-delivery" <?php checked( '1',  (isset($query_results[0]['case_on_delivery'])) ? $query_results[0]['case_on_delivery'] : '' ); ?>> &nbsp; <?php esc_html_e( 'Enable Cash on deliver for this pincode', 'woo-pincode-checker' )?></td>
							</tr>
						</tbody>
					</table>
					<?php if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' ) {
						submit_button( __('Edit Picode', 'woo-pincode-checker' )); 
					} else {
						submit_button( __('Add Picode', 'woo-pincode-checker' )); 
					}?>
					
					<?php wp_nonce_field( 'wpc-pincode-submit', 'wpc-pincode-submit' ); ?>
				</form>
			</div>
			
		</div>
		
		<?php
	}
	
	/**
	 * Upload Pincod.
	 *
	 * @since    1.0.0
	 */
	public function wpc_upload_pincodes_func() {
		global $wpdb;
		$wpc_message = '';
		$pincode_checker_table_name = $wpdb->prefix . 'pincode_checker';
		$bytes      = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size       = size_format( $bytes );
		
		if( isset( $_POST['upload_pincodes'] ) ) {
			$is_import=true;
			$filetype = wp_check_filetype( wc_clean( wp_unslash( $_FILES['import']['name'] ) ), array(
						'csv' => 'text/csv',
					) );
			if ( ! in_array( $filetype['type'], array('csv' => 'text/csv'), true ) ) {
				$message_type = 'error';
				$wpc_message = esc_html__( 'Invalid file type. The importer supports CSV file formats.', 'wpc-pincode-submit' ) ;
				$is_import=false;
			}
			
			if ($is_import == true) {
				
				if( $_FILES["import"]["size"] > 0) {					
					
					$file = fopen( $_FILES["import"]["tmp_name"], "r");
					$i	  = 0;
					
					
					while (($getData = fgetcsv($file, 100000, ",")) !== FALSE ) {
						if ( $i != 0 ) {
							$num_rows = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ".$pincode_checker_table_name." where `pincode` = %s", $getData[0] ) );	
							
							if( $num_rows == 0 ) {
								$wpdb->insert(
									$pincode_checker_table_name,
									array(
										'pincode'       	=> $getData[0],
										'city'         		=> $getData[1],
										'state'        		=> $getData[2],
										'delivery_days'     => $getData[3],
										'case_on_delivery'  => $getData[4],						
									),
									array( '%s', '%s', '%s', '%d', '%d' )
								); 
							}
						}

						$i++;
					}
					fclose($file);
					$message_type = 'updated';
					$wpc_message = esc_html__( 'Import Pincodes CSV file Successfully.', 'wpc-pincode-submit' ) ;
				 }
			}
		}
		
		
		if ( $wpc_message != '' ) { ?>
			<div class="<?php echo esc_attr($message_type); ?> below-h2" id="message">
				<p><?php echo $wpc_message;?></p>
			</div>
			<?php
		}
		?>
		<div class="wrap wpc-upload-pincode-wrap">			
			<h2>
				<?php esc_html_e('Upload pincodes from a CSV file','woo-pincode-checker' ); ?>
			</h2>
			
			<div class="wpc-upload-pincode-section">
				<form enctype="multipart/form-data" method="post">
					<section>
						<table class="form-table wpc-pincode-submit-importer-options">
							<tbody>								
								<tr>
									<th scope="row">
										<label for="upload">
											<?php esc_html_e( 'Choose a CSV file from your computer:', 'wpc-pincode-submit' ); ?>
										</label>
									</th>
									<td>
										<input type="file" id="upload" name="import" size="25" />
										<input type="hidden" name="action" value="save" />
										<input type="hidden" name="max_file_size" value="<?php echo esc_attr( $bytes ); ?>" />
										<br>
										<small>
											<?php
											printf(
												/* translators: %s: maximum upload size */
												esc_html__( 'Maximum size: %s', 'wpc-pincode-submit' ),
												esc_html( $size )
											);
											?>
										</small>
									</td>
								</tr>

							</tbody>
						</table>
					</section>
					<div class="wc-actions">
						<button type="submit" class="button button-primary button-next" value="<?php esc_attr_e( 'Import CSV File', 'wpc-pincode-submit' ); ?>" name="upload_pincodes"><?php esc_html_e( 'Import CSV File', 'wpc-pincode-submit' ); ?></button>

					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
