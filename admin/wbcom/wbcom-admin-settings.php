<?php

defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'Wbcom_Admin_Settings' ) ) {

	/**
	 * Class to add wbcom plugin's admin settings.
	 *
	 * @author   Wbcom Designs
	 * @since    1.0.0
	 */
	class Wbcom_Admin_Settings {

		/**
		 * Wbcom_Admin_Settings Constructor.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function __construct() {
			add_shortcode( 'wbcom_admin_setting_header', array( $this, 'wbcom_admin_setting_header_html' ) );
			add_action( 'admin_menu', array( $this, 'wbcom_admin_additional_pages' ), 999 );
			add_action( 'admin_enqueue_scripts', array( $this, 'wbcom_enqueue_admin_scripts' ) );
			//add_action( 'wp_ajax_wbcom_manage_plugin_installation', array( $this, 'wbcom_do_plugin_action' ) );
		}
		
		public function wbcom_admin_setting_header_html() {
			$page			 = filter_input( INPUT_GET, 'page' ) ? filter_input( INPUT_GET, 'page' ) : 'wbcom-themes-page';
			
			$plugin_active	 = $theme_active = $support_active = $settings_active = '';
			switch ( $page ) {
				
				case 'wbcom-plugins-page': $plugin_active	 = 'is_active';
					break;
				case 'wbcom-themes-page' : $theme_active	 = 'is_active';
					break;
				case 'wbcom-support-page' : $support_active	 = 'is_active';
					break;
				case 'wbcom-license-page' : $license_active	 = 'is_active';
					break;
				default : $settings_active = 'is_active';
			}
			?>
			<div id="wb_admin_header" class="wp-clearfix">

				<div id="wb_admin_logo">
					<img src="<?php echo plugin_dir_url(__FILE__) . '/assets/imgs/logowbcom.png'; ?>">
					<div class="wb_admin_right"></div>
				</div>

				<nav id="wb_admin_nav">
					<ul>
						<li class="wb_admin_nav_item <?php echo esc_attr( $settings_active ); ?>">
							<a href="<?php echo get_admin_url() . 'admin.php?page=wbcomplugins'; ?>" id="wb_admin_nav_trigger_settings">
								<i class="fas fa-sliders-h"></i>
								<h4><?php esc_html_e( 'Settings', 'woo-pincode-checker' ); ?></h4>
							</a>
						</li>
						<li class="wb_admin_nav_item <?php echo esc_attr( $plugin_active ); ?>">
							<a href="<?php echo get_admin_url() . 'admin.php?page=wbcom-plugins-page'; ?>" id="wb_admin_nav_trigger_extensions">
								<i class="fas fa-th"></i>
								<h4><?php esc_html_e( 'Components', 'woo-pincode-checker' ); ?></h4>
							</a>
						</li>
						<li class="wb_admin_nav_item <?php echo esc_attr( $theme_active ); ?>">
							<a href="<?php echo get_admin_url() . 'admin.php?page=wbcom-themes-page'; ?>" id="wb_admin_nav_trigger_themes">
								<i class="fas fa-magic"></i>
								<h4><?php esc_html_e( 'Themes', 'woo-pincode-checker' ); ?></h4>
							</a>
						</li>
						<li class="wb_admin_nav_item <?php echo esc_attr( $support_active ); ?>">
							<a href="<?php echo get_admin_url() . 'admin.php?page=wbcom-support-page'; ?>" id="wb_admin_nav_trigger_support">
								<i class="fas fa-question-circle"></i>
								<h4><?php esc_html_e( 'Support', 'woo-pincode-checker' ); ?></h4>
							</a>
						</li>
						<?php do_action( 'wbcom_add_header_menu' ); ?>
					</ul>
				</nav>
			</div><?php
		}
		
		/**
		 * Function for add plugin's admin panel header pages.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function wbcom_admin_additional_pages() {
			add_submenu_page(
			'wbcomplugins', esc_html__( 'Components', 'woo-pincode-checker' ), esc_html__( 'Components', 'woo-pincode-checker' ), 'manage_options', 'wbcom-plugins-page', array( $this, 'wbcom_plugins_submenu_page_callback' )
			);
			add_submenu_page(
			'wbcomplugins', esc_html__( 'Themes', 'woo-pincode-checker' ), esc_html__( 'Themes', 'woo-pincode-checker' ), 'manage_options', 'wbcom-themes-page', array( $this, 'wbcom_themes_submenu_page_callback' )
			);
			add_submenu_page(
			'wbcomplugins', esc_html__( 'Support', 'woo-pincode-checker' ), esc_html__( 'Support', 'woo-pincode-checker' ), 'manage_options', 'wbcom-support-page', array( $this, 'wbcom_support_submenu_page_callback' )
			);
		}
		
		/**
		 * Function for include wbcom plugins list page.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function wbcom_plugins_submenu_page_callback() {
			include 'templates/wbcom-plugins-page.php';
		}

		/**
		 * Function for include themes list page.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function wbcom_themes_submenu_page_callback() {
			include 'templates/wbcom-themes-page.php';
		}

		/**
		 * Function for include support page.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function wbcom_support_submenu_page_callback() {
			include 'templates/wbcom-support-page.php';
		}
		
		/**
		 * Enqueue js & css related to wbcom plugin.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function wbcom_enqueue_admin_scripts() {
			if ( ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
				wp_enqueue_style( 'font-awesome', '//use.fontawesome.com/releases/v5.5.0/css/all.css' );
			}
			
			if ( ! wp_script_is( 'wbcom-admin-setting-js', 'enqueued' ) ) {

				wp_register_script(
				$handle		 = 'wbcom_admin_setting_js', $src		 = plugin_dir_url(__FILE__) . 'assets/js/wbcom-admin-setting.js', $deps		 = array( 'jquery' ), $ver		 = time(), $in_footer	 = true
				);
				wp_localize_script(
				'wbcom_admin_setting_js', 'wbcom_plugin_installer_params', array(
					'ajax_url'			 => admin_url( 'admin-ajax.php' ),
					'activate_text'		 => esc_html__( 'Activate', 'woo-pincode-checker' ),
					'deactivate_text'	 => esc_html__( 'Deactivate', 'woo-pincode-checker' )
				)
				);
				wp_enqueue_script( 'wbcom_admin_setting_js' );

			}

			if ( !wp_style_is( 'wbcom-admin-setting-css', 'enqueued' ) ) {
				wp_enqueue_style( 'wbcom-admin-setting-css', plugin_dir_url(__FILE__) . 'assets/css/wbcom-admin-setting.css' );
			}

			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( 'toplevel_page_wbcomplugins' === $screen->base ) {
					if ( !wp_script_is( 'jquery', 'enqueued' ) ) {
						wp_enqueue_script( 'jquery' );
					}
					if ( !wp_script_is( 'jquery-ui-sortable', 'enqueued' ) ) {
						wp_enqueue_script( 'jquery-ui-sortable' );
					}
					if ( ! wp_script_is( 'woo-sell-services-selectize-js', 'enqueued' ) ) {
						wp_enqueue_script( 'woo-sell-services-selectize-js', plugins_url() . '/woo-pincode-checker/admin/js/selectize.min.js', array( 'jquery' ), '', false );
					}
					if ( ! wp_style_is( 'woo-sell-services-selectize-css', 'enqueued' ) ) {
						wp_enqueue_style( 'woo-sell-services-selectize-css', plugins_url() . '/woo-pincode-checker/admin/css/selectize.css', array(), '', 'all' );
					}
					
				}
			}
		}
		
		/**
		 * Function for get all wbcom free plugin's details.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function wbcom_all_free_plugins() {
			$free_plugins = array(
				'0'	 => array(
					'name'			 => esc_html__( 'Custom Font Uploader', 'woo-pincode-checker' ),
					'slug'			 => 'custom-font-uploader',
					'description'	 => esc_html__( 'It also allows you to upload your own custom font to your site and use them using custom css.', 'woo-sell-services' ),
					'status'		 => $this->wbcom_plugin_status( 'custom-font-uploader' ),
					'wp_url'		 => 'https://wordpress.org/plugins/custom-font-uploader/',
					'icon'			 => 'fas fa-2x fa-upload'
				),
				'1'	 => array(
					'name'			 => esc_html__( 'BuddyPress Activity Filter', 'woo-pincode-checker' ),
					'slug'			 => 'bp-activity-filter',
					'description'	 => esc_html__( 'Admin can set default and customised activities to be listed on front-end.', 'woo-sell-services' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-activity-filter' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-activity-filter/',
					'icon'			 => 'fas fa-2x fa-filter'
				),
				'2'	 => array(
					'name'			 => esc_html__( 'BuddyPress Activity Social Share', 'woo-pincode-checker' ),
					'slug'			 => 'bp-activity-social-share',
					'description'	 => esc_html__( 'This plugin allows anyone easily share BuddyPress Activites on major social media (Facebook, Twitter, Google+, Linkedin ).', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-activity-social-share' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-activity-social-share/',
					'icon'			 => 'fas fa-2x fa-share-alt'
				),
				'3'	 => array(
					'name'			 => esc_html__( 'BuddyPress Create Group Type', 'woo-pincode-checker' ),
					'slug'			 => 'bp-create-group-type',
					'description'	 => esc_html__( 'It will help to create group type for BuddyPress Groups.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-create-group-type' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-create-group-type/',
					'icon'			 => 'fas fa-2x fa-sitemap'
				),
				'4'	 => array(
					'name'			 => esc_html__( 'BuddyPress Member Reviews', 'woo-pincode-checker' ),
					'slug'			 => 'bp-user-profile-reviews',
					'description'	 => esc_html__( 'This plugin allows only site members to add reviews to the buddypress members on the site and even rate the member’s profile out of 5 points with multiple review criteria.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-user-profile-reviews' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-user-profile-reviews/',
					'icon'			 => 'fas fa-2x fa-user'
				),
				'5'	 => array(
					'name'			 => esc_html__( 'BuddyPress Group Reviews', 'woo-pincode-checker' ),
					'slug'			 => 'review-buddypress-groups',
					'description'	 => esc_html__( 'This plugin allows the BuddyPress Members to give reviews to the BuddyPress groups on the site. The review form allows the users to give text review, even rate the group on the basis of multiple criterias.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'review-buddypress-groups' ),
					'wp_url'		 => 'https://wordpress.org/plugins/review-buddypress-groups/',
					'icon'			 => 'fas fa-2x fa-users'
				),
				'6'	 => array(
					'name'			 => esc_html__( 'BuddyPress Favorite Notification', 'woo-pincode-checker' ),
					'slug'			 => 'bp-favorite-notification',
					'description'	 => esc_html__( 'BuddyPress Favorite Notification adds a notification for BuddyPress activity.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-favorite-notification' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-favorite-notification/',
					'icon'			 => 'fas fa-2x fa-bell'
				),
				'7'	 => array(
					'name'			 => esc_html__( 'Custom Email Options', 'woo-pincode-checker' ),
					'slug'			 => 'custom-email-options',
					'description'	 => esc_html__( 'Override default email options of Worpdress.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'custom-email-options' ),
					'wp_url'		 => 'https://wordpress.org/plugins/custom-email-options/',
					'icon'			 => 'fas fa-2x fa-at'
				),
				'8'	 => array(
					'name'			 => esc_html__( 'BuddyPress Checkins', 'woo-pincode-checker' ),
					'slug'			 => 'bp-check-in',
					'description'	 => esc_html__( 'This plugin allows BuddyPress members to share their location when they are posting activities, you can add places where you visited, nearby locations based on google places.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-check-in' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-check-in/',
					'icon'			 => 'fas fa-2x fa-map-marker-alt'
				),
				'9'	 => array(
					'name'			 => esc_html__( 'BuddyPress Job Manager', 'woo-pincode-checker' ),
					'slug'			 => 'bp-job-manager',
					'description'	 => esc_html__( 'Incorporates BuddyPress with the WP Job Manager plugin by creating specific tabs in employer’s and candidate’s profiles.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-job-manager' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-job-manager/',
					'icon'			 => 'fas fa-2x fa-briefcase'
				),
				'10' => array(
					'name'			 => esc_html__( 'BuddyPress user ToDo List', 'woo-pincode-checker' ),
					'slug'			 => 'bp-user-to-do-list',
					'description'	 => esc_html__( 'This plugin allows you to create your personal task list with timestamp. You can mark them complete when you are done with them. It will also send reminder when you have any overdue task.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-user-to-do-list' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-user-to-do-list/',
					'icon'			 => 'fas fa-2x fa-list-ol'
				),
				'11' => array(
					'name'			 => esc_html__( 'Shortcodes for BuddyPress', 'woo-pincode-checker' ),
					'slug'			 => 'shortcodes-for-buddypress',
					'description'	 => esc_html__( 'This plugin will add an extended feature to the big name “BuddyPress” that will generate Shortcode for Listing Activity Streams , Members and Groups on any post/page in website.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'shortcodes-for-buddypress' ),
					'wp_url'		 => 'https://wordpress.org/plugins/shortcodes-for-buddypress/',
					'icon'			 => 'fas fa-2x fa-code'
				),
				'12' => array(
					'name'			 => esc_html__( 'Woo Open Graph', 'woo-pincode-checker' ),
					'slug'			 => 'woo-open-graph',
					'description'	 => esc_html__( 'This plugin will add an extended feature to the big name “WooCommerce” that will adds well executed and accurate Open Graph Meta Tags to your site with title,description and WooCommerce featured image.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'woo-open-graph' ),
					'wp_url'		 => 'https://wordpress.org/plugins/woo-open-graph/',
					'icon'			 => 'fas fa-2x fa-chart-bar'
				),
				'13' => array(
					'name'			 => esc_html__( 'BuddyPress Lock', 'woo-pincode-checker' ),
					'slug'			 => 'lock-my-bp',
					'description'	 => esc_html__( 'This plugin allows the administrator to lock the certain parts of their site. It help to create private BuddyPress community by locking certain BuddyPress Components, WordPress Pages, Custom Post Types for public view without using any membership plugin.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'lock-my-bp' ),
					'wp_url'		 => 'https://wordpress.org/plugins/lock-my-bp/',
					'icon'			 => 'fas fa-2x fa-lock'
				),
				'14' => array(
					'name'			 => esc_html__( 'Woo Audio Preview', 'woo-pincode-checker' ),
					'slug'			 => 'woo-audio-preview',
					'description'	 => esc_html__( 'This plugin Allows playing the audio files in sample mode to prevent unauthorized downloading of the audio files. It helps to display sample files at single product page.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'woo-audio-preview' ),
					'wp_url'		 => 'https://wordpress.org/plugins/woo-audio-preview/',
					'icon'			 => 'fas fa-2x fa-volume-up'
				),
				'15' => array(
					'name'			 => esc_html__( 'WordPress System Log', 'woo-pincode-checker' ),
					'slug'			 => 'wp-system-log',
					'description'	 => esc_html__( 'This plugin helps administrators of the site see their environment on which the site is currently running that includes WordPress environment, tha database it requires, Server Environment and the plugins installed and activated on the site.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'wp-system-log' ),
					'wp_url'		 => 'https://wordpress.org/plugins/wp-system-log/',
					'icon'			 => 'fas fa-2x fa-file-alt'
				),
				'16' => array(
					'name'			 => esc_html__( 'BP Post From Anywhere', 'woo-pincode-checker' ),
					'slug'			 => 'bp-post-from-anywhere',
					'description'	 => esc_html__( 'This plugin will generate shortcode and widgets for post updates section for activities so you can post update from anywhere, it might be sidebar, some page or any template file.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'bp-post-from-anywhere' ),
					'wp_url'		 => 'https://wordpress.org/plugins/bp-post-from-anywhere/',
					'icon'			 => 'fas fa-2x fa-edit'
				),
				'17' => array(
					'name'			 => esc_html__( 'Woo Document Preview', 'woo-pincode-checker' ),
					'slug'			 => 'woo-document-preview',
					'description'	 => esc_html__( 'This will allow you to add document preview at single product page. Which helps to offer more better idea when you are selling ebooks, pdf or some documents.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'woo-document-preview' ),
					'wp_url'		 => 'https://wordpress.org/plugins/woo-document-preview/',
					'icon'			 => 'fas fa-2x fa-file'
				),
				'18' => array(
					'name'			 => esc_html__( 'WordPress Media Category', 'woo-pincode-checker' ),
					'slug'			 => 'media-category',
					'description'	 => esc_html__( 'This plugin helps administrators of the site categorize their wordpress media.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'media-category' ),
					'wp_url'		 => 'https://wordpress.org/plugins/media-category/',
					'icon'			 => 'fas fa-2x fa-images'
				),
				'19' => array(
					'name'			 => esc_html__( 'Woo Price Quotes', 'woo-pincode-checker' ),
					'slug'			 => 'woo-price-quote-inquiry',
					'description'	 => esc_html__( 'This plugin helps in quoting the products that admin wishes to hide its purchasing details.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'woo-price-quote-inquiry' ),
					'wp_url'		 => 'https://wordpress.org/plugins/woo-price-quote-inquiry/',
					'icon'			 => 'fas fa-2x fa-dollar-sign'
				),
				'20' => array(
					'name'			 => esc_html__( 'EDD Service Extended', 'woo-pincode-checker' ),
					'slug'			 => 'edd-service-extended',
					'description'	 => esc_html__( 'This plugin helps administrators of the site categorize their wordpress media.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'edd-service-extended' ),
					'wp_url'		 => 'https://wordpress.org/plugins/edd-service-extended/',
					'icon'			 => 'fab fa-2x fa-product-hunt'
				),
				'21' => array(
					'name'			 => esc_html__( 'WB Ads Rotator with Split Test', 'woo-pincode-checker' ),
					'slug'			 => 'wb-ads-rotator-with-split-test',
					'description'	 => esc_html__( 'This plugin is designed for the SPLIT TESTING, you can check performance of your ads layout and on the basis of them you can select one of them for your regular use.', 'woo-pincode-checker' ),
					'status'		 => $this->wbcom_plugin_status( 'wb-ads-rotator-with-split-test' ),
					'wp_url'		 => 'https://wordpress.org/plugins/wb-ads-rotator-with-split-test/',
					'icon'			 => 'fas fa-2x fa-ad'
				)
			);
			return $free_plugins;
		}
		
		/**
		 * Function for get all wbcom paid plugin's details.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function wbcom_all_paid_plugins() {
			$paid_plugins = array(
				'0'	 => array(
					'name'			 => esc_html__( 'BuddyPress Moderation Pro', 'woo-pincode-checker' ),
					'description'	 => esc_html__( 'BuddyPress Community Moderation offers a solution for site owners to keep their communities straight. With community policing strategy, members of the community have an option for moderation sitewide by attaching flags to content created within the various components.', 'woo-pincode-checker' ),
					'download_url'	 => 'https://wbcomdesigns.com/downloads/buddypress-moderation-pro/',
					'icon'			 => 'fas fa-2x fa-exclamation-triangle'
				),
				'1'	 => array(
					'name'			 => esc_html__( 'BuddyPress Polls', 'woo-pincode-checker' ),
					'description'	 => esc_html__( 'Use BuddyPress Polls plugin to create polls inside the activity, let your user response to your polls. Members can create pools like activities, easily votes on them.', 'woo-pincode-checker' ),
					'download_url'	 => 'https://wbcomdesigns.com/downloads/buddypress-polls/',
					'icon'			 => 'fas fa-2x fa-poll'
				),
				'2'	 => array(
					'name'			 => esc_html__( 'BuddyPress Resume Manager', 'woo-pincode-checker' ),
					'description'	 => esc_html__( 'BuddyPress Resume Manager adds a separate BuddyPress Resume menu at a user’s BuddyPress Profile Page to display individual member resume. We have added predefined fields for the resumes and site admin and enable and disable them.', 'woo-pincode-checker' ),
					'download_url'	 => 'https://wbcomdesigns.com/downloads/buddypress-resume-manager/',
					'icon'			 => 'fas fa-2x fa-file'
				),
				'3'	 => array(
					'name'			 => esc_html__( 'BuddyPress Profanity', 'woo-pincode-checker' ),
					'description'	 => esc_html__( 'Use BuddyPress Profanity plugin to censor content in your community! Easily Censor all the unwanted words in activities, private messages contents by specifying a list of keywords to be filtered.', 'woo-pincode-checker' ),
					'download_url'	 => 'https://wbcomdesigns.com/downloads/buddypress-profanity/',
					'icon'			 => 'fab fa-2x fa-galactic-republic'
				),
				'4'	 => array(
					'name'			 => esc_html__( 'BuddyPress Private Community Pro', 'woo-pincode-checker' ),
					'description'	 => esc_html__( 'This plugin offers a lockdown for BuddyPress Component and will ask users to log in go further to check profile or any other protected details.', 'woo-pincode-checker' ),
					'download_url'	 => 'https://wbcomdesigns.com/downloads/buddypress-private-community-pro/',
					'icon'			 => 'fas fa-2x fa-user-lock'
				),
				'5'	 => array(
					'name'			 => esc_html__( 'BuddyPress Profile Pro', 'woo-pincode-checker' ),
					'description'	 => esc_html__( 'This plugin gives you the power to extend BuddyPress Profiles with repeater fields and groups. You can easily add multiple field groups and display them at member’s profile.', 'woo-pincode-checker' ),
					'download_url'	 => 'https://wbcomdesigns.com/downloads/buddypress-profile-pro/',
					'icon'			 => 'far fa-2x fa-user-circle'
				),
			);
			return $paid_plugins;
		}
		
		/**
		 * Function for check plugin's status.
		 *
		 * @since  1.0.0
		 * @access public
		 * @param  string $slug Plugin's slug.
		 */
		public function wbcom_plugin_status( $slug ) {
			if ( $this->wbcom_is_plugin_installed( $slug ) ) {
				if ( $this->wbcom_is_plugin_active( $slug ) ) {
					return 'activated';
				} else {
					return 'installed';
				}
			} else {
				return 'not_installed';
			}
		}
		
		/**
		 * Function for check plugin is installed or not.
		 *
		 * @since  1.0.0
		 * @access public
		 * @param  string $slug Plugin's slug.
		 */
		function wbcom_is_plugin_installed( $slug ) {
			if ( !function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins = get_plugins();
			$keys		 = array_keys( $all_plugins );
			foreach ( $keys as $key ) {
				if ( preg_match( '|^' . $slug . '/|', $key ) ) {
					return true;
				}
			}
			return false;
		}
		
	}
	
	function instantiate_wbcom_plugin_manager() {
		new Wbcom_Admin_Settings();
	}

	instantiate_wbcom_plugin_manager();
	
}