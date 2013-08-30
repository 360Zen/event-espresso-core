<?php
if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for Wordpress
 *
 * @package		Event Espresso
 * @author		Seth Shoultes
 * @copyright	(c)2009-2012 Event Espresso All Rights Reserved.
 * @license		http://eventespresso.com/support/terms-conditions/  ** see Plugin Licensing **
 * @link		http://www.eventespresso.com
 * @version		4.0
 *
 * ------------------------------------------------------------------------
 *
 * General_Settings_Admin_Page
 *
 * This contains the logic for setting up the Custom General_Settings related pages.  Any methods without phpdoc comments have inline docs with parent class. 
 *
 * NOTE:  TODO: This is a straight conversion from the legacy 3.1 settings page.  It is NOT optimized and will need modification to fully use the new system (and also will need adjusted when Questions and Questions groups model is implemented)
 *
 * @package		General_Settings_Admin_Page
 * @subpackage	includes/core/admin/general_settings/General_Settings_Admin_Page.core.php
 * @author			Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class General_Settings_Admin_Page extends EE_Admin_Page {


	/**
	 * _question_group
	 * holds the specific question group object for the question group details screen
	 * @var object
	 */
	protected $_question_group;



	public function __construct( $routing = TRUE ) {
		parent::__construct( $routing );
	}



	protected function _init_page_props() {
		$this->page_slug = GEN_SET_PG_SLUG;
		$this->page_label = GEN_SET_LABEL;
	}




	protected function _ajax_hooks() {
		add_action('wp_ajax_espresso_display_country_settings', array( $this, 'display_country_settings'));
		add_action('wp_ajax_espresso_display_country_states', array( $this, 'display_country_states'));
		add_action('wp_ajax_espresso_delete_state', array( $this, 'delete_state'));
		add_action('wp_ajax_espresso_add_new_state', array( $this, 'add_new_state'));
	}





	protected function _define_page_props() {
		$this->_admin_base_url = GEN_SET_ADMIN_URL;
		$this->_admin_page_title = GEN_SET_LABEL;
		$this->_labels = array(
			'publishbox' => __('Update Settings', 'event_espresso')
			);
	}




	protected function _set_page_routes() {
		$this->_page_routes = array(
		
			'default' => '_espresso_page_settings',
			'update_espresso_page_settings' => array(
				'func' => '_update_espresso_page_settings',
				'noheader' => TRUE,
				),
				
			'template_settings' => '_template_settings',
			
			'update_template_settings' => array(
				'func' => '_update_template_settings',
				'noheader' => TRUE,
				),
				
			'copy_templates' => array(
				'func' => '_copy_templates',
				'noheader' => TRUE,
				),
				
			'google_map_settings' => '_google_map_settings',
			
			'update_google_map_settings' => array(
				'func' => '_update_google_map_settings',
				'noheader' => TRUE,
				),
				
			'your_organization_settings' => '_your_organization_settings',
			
			'update_your_organization_settings' => array(
				'func' => '_update_your_organization_settings',
				'noheader' => TRUE,
				),
				
			'admin_option_settings' => '_admin_option_settings',
			
			'update_admin_option_settings' => array(
				'func' => '_update_admin_option_settings',
				'noheader' => TRUE,
				),
				
			'country_settings' => '_country_settings',

			'update_country_settings' => array(
				'func' => '_update_country_settings',
				'noheader' => TRUE,
				),

			'display_country_settings' => array(
				'func' => 'display_country_settings',
				'noheader' => TRUE,
				),

			'add_new_state' => array(
				'func' => 'add_new_state',
				'noheader' => TRUE,
				),

			'delete_state' => array(
				'func' => 'delete_state',
				'noheader' => TRUE,
				)				
			);
	}





	protected function _set_page_config() {
		$this->_page_config = array(
			'default' => array(
				'nav' => array(
					'label' => __('Critical Pages'),
					'order' => 20
					),
				'metaboxes' => array( '_publish_post_box', '_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box' ),
				'help_tabs' => array(
					'registration_page_info' => array(
						'title' => __('Event Registration Page', 'event_espresso'),
						'callback' => 'registration_page_info_help_tab'
						),
					'notify_url_info' => array(
						'title' => __('Transaction Page', 'event_espresso'),
						'callback' => 'transactions_info_help_tab'
						),
					'return_url_info' => array(
						'title' => __('Thank You Page', 'event_espresso'),
						'callback' => 'thank_you_page_info_help_tab'
						),
					'cancel_return_info' => array(
						'title' => __('Cancel/Return Page', 'event_espresso'),
						'callback' => 'cancel_return_info_help_tab'
						),
					)
				),
			'template_settings' => array(
				'nav' => array(
					'label' => __('Templates'),
					'order' => 30
					),
				'metaboxes' => array( '_publish_post_box', '_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box' ),
				'help_tabs' => array(
					'enable_styles_info' => array(
						'title' => __('Enable Styles', 'event_espresso'),
						'callback' => 'enable_styles_info_help_tab'
						),
					'themeroller_info' => array(
						'title' => __('Themeroller', 'event_espresso'),
						'callback' => 'themeroller_info_help_tab'
						),
					'custom_templates_info' => array(
						'title' => __('Custom Templates', 'event_espresso'),
						'callback' => 'custom_templates_info_help_tab'
						),
					)
				),
			'google_map_settings' => array(
				'nav' => array(
					'label' => __('Google Maps'),
					'order' => 40
					),
				'metaboxes' => array('_publish_post_box',  '_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box' ),
				'help_tabs' => array(
					'gmaps_info' => array(
						'title' => __('Google Maps Configuration', 'event_espresso'),
						'callback' => 'gmaps_info_help_tab'
						)
					)
				),
			'your_organization_settings' => array(
				'nav' => array(
					'label' => __('Your Organization'),
					'order' => 50
					),
				'metaboxes' => array('_publish_post_box',  '_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box' )
				),
			'admin_option_settings' => array(
				'nav' => array(
					'label' => __('Admin Options'),
					'order' => 60
					),
				'metaboxes' => array( '_publish_post_box', '_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box' ),
				'help_tabs' => array(
					'full_logging_info' => array(
						'title' => __('Full Logging', 'event_espresso'),
						'callback' => 'full_logging_info_help_tab'
						),
					'remote_logging_info' => array(
						'title' => __('Remote Logging', 'event_espresso'),
						'callback' => 'remote_logging_info_help_tab'
						),
					'remote_logging_url_info' => array(
						'title' => __("Remote Logging URL", 'event_espresso'),
						'callback' => 'remote_logging_url_info_help_tab'
						),
					'affiliate_info' => array(
						'title' => __('Affiliate', 'event_espresso'),
						'callback' => 'affiliate_info_help_tab'
						),
					)
				),
			'country_settings' => array(
				'nav' => array(
					'label' => __('Countries'),
					'order' => 70
					),
				'metaboxes' => array( '_publish_post_box', '_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box' )
				)
			);
	}



	/**
	 * general settings critical pages help tabs
	 * @param  string $tab what tab content to retrieve
	 * @return string      html content for help tab
	 */
	public function general_settings_critical_pages_help_tabs( $tab ) {
		require_once GEN_SET_TEMPLATE_PATH . 'general_settings_critical_pages_help_tabs.template.php';
		$template = call_user_func( $tab . '_html' );
		espresso_display_template($template);
	}
	public function registration_page_info_help_tab(){
		$this->general_settings_critical_pages_help_tabs( __FUNCTION__ );
	}
	public function transactions_info_help_tab() {
		$this->general_settings_critical_pages_help_tabs( __FUNCTION__ );
	}
	public function thank_you_page_info_help_tab(){
		$this->general_settings_critical_pages_help_tabs( __FUNCTION__ );
	}
	public function cancel_return_info_help_tab(){
		$this->general_settings_critical_pages_help_tabs( __FUNCTION__ );
	}




	/**
	 * template settins help tabs
	 * @param  string $tab what tab content to retrieve
	 * @return string      html content for help tab
	 */
	public function template_settings_help_tabs( $tab ) {
		require_once GEN_SET_TEMPLATE_PATH . 'template_settings_help_tabs.template.php';
		$template = call_user_func( $tab . '_html' );
		espresso_display_template( $template );
	}
	public function enable_styles_info_help_tab() {
		$this->template_settings_help_tabs( __FUNCTION__ );
	}
	public function themeroller_info_help_tab() {
		$this->template_settings_help_tabs( __FUNCTION__ );
	}
	public function custom_templates_info_help_tab() {
		$this->template_settings_help_tabs( __FUNCTION__ );
	}



	public function gmaps_info_help_tab() {
		$template = GEN_SET_TEMPLATE_PATH . 'map_confg_help.php';
		espresso_display_template( $template );
	}



	/**
	 * admin options help tabs
	 * @param  string $tab what tab content to retrieve
	 * @return string      html content for tab 
	 */
	public function admin_options_help_tabs( $tab ) {
		require_once GEN_SET_TEMPLATE_PATH . 'admin_options_help_tabs.template.php';
		$template = call_user_func( $tab . '_html' );
		espresso_display_template( $template );
	}
	public function full_logging_info_help_tab() {
		$this->admin_options_help_tabs( __FUNCTION__ );
	}
	public function remote_logging_info_help_tab() {
		$this->admin_options_help_tabs( __FUNCTION__ );
	}
	public function remote_logging_url_info_help_tab() {
		$this->admin_options_help_tabs( __FUNCTION__ );
	}
	public function affiliate_info_help_tab() {
		$this->admin_options_help_tabs( __FUNCTION__ );
	}



	protected function _add_screen_options() {
	}

	protected function _add_screen_options_default() {
		$this->_per_page_screen_option();
	}

	protected function _add_screen_options_question_groups() {
		$this->_per_page_screen_option();
	}

	protected function _add_feature_pointers() {}
	public function load_scripts_styles() {
		//styles
		wp_enqueue_style('jquery-ui-style');
		//scripts
		wp_enqueue_script('ee_admin_js');		
	}
	public function admin_init() {}
	public function admin_notices() {}
	public function admin_footer_scripts() {}


	public function load_scripts_styles_your_organization_settings() {	
		//styles
		wp_enqueue_style('thickbox');
		//scripts
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_register_script( 'organization_settings', GEN_SET_ASSETS_URL . 'your_organization_settings.js', array( 'jquery','media-upload','thickbox' ), EVENT_ESPRESSO_VERSION, TRUE );
		wp_register_style( 'organization-css', GEN_SET_ASSETS_URL . 'organization.css', array(), EVENT_ESPRESSO_VERSION );
		wp_enqueue_script( 'organization_settings' );	
		wp_enqueue_style( 'organization-css' );
		$confirm_image_delete = array( 'text' => __('Do you really want to delete this image? Please remember to save your settings to complete the removal.', 'event_espresso')); 
		wp_localize_script( 'organization_settings', 'confirm_image_delete', $confirm_image_delete );

	}

	public function load_scripts_styles_country_settings() {	
		//scripts
		wp_register_script( 'gen_settings_countries', GEN_SET_ASSETS_URL . 'gen_settings_countries.js', array( 'jquery' ), EVENT_ESPRESSO_VERSION, TRUE );
		wp_register_style( 'organization-css', GEN_SET_ASSETS_URL . 'organization.css', array(), EVENT_ESPRESSO_VERSION );
		wp_enqueue_script( 'gen_settings_countries' );	
		wp_enqueue_style( 'organization-css' );

		global $eei18n_js_strings;
		$eei18n_js_strings['invalid_server_response'] = __( 'An error occured! Your request may have been processed, but a valid response from the server was not received. Please refresh the page and try again.', 'event_espresso' );
		$eei18n_js_strings['error_occured'] = __(  'An error occured! Please refresh the page and try again.', 'event_espresso' );
		$eei18n_js_strings['confirm_delete_state'] = __(  'Are you sure you want to delete this State / Province?', 'event_espresso' );
		$protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
		$eei18n_js_strings['ajax_url'] = admin_url( 'admin-ajax.php?page=espresso_general_settings' , $protocol );
		wp_localize_script( 'gen_settings_countries', 'eei18n', $eei18n_js_strings );
	}


	/*************		Espresso Pages 		*************/


	protected function _espresso_page_settings() {
	
		$this->_transient_garbage_collection();
		$this->_template_args['values'] = $this->_yes_no_values;

		$this->_template_args['reg_page_id'] = isset($this->EE->CFG->core->reg_page_id ) ?$this->EE->CFG->core->reg_page_id : NULL;
		$this->_template_args['reg_page_obj'] = isset($this->EE->CFG->core->reg_page_id ) ? get_page($this->EE->CFG->core->reg_page_id ) : FALSE;

		$this->_template_args['txn_page_id'] = isset($this->EE->CFG->core->txn_page_id) ?$this->EE->CFG->core->txn_page_id : NULL;
		$this->_template_args['txn_page_obj'] = isset($this->EE->CFG->core->txn_page_id ) ? get_page($this->EE->CFG->core->txn_page_id ) : FALSE;

		$this->_template_args['thank_you_page_id'] = isset($this->EE->CFG->core->thank_you_page_id ) ?$this->EE->CFG->core->thank_you_page_id : NULL;
		$this->_template_args['thank_you_page_obj'] = isset($this->EE->CFG->core->thank_you_page_id ) ? get_page($this->EE->CFG->core->thank_you_page_id ) : FALSE;

		$this->_template_args['cancel_page_id'] = isset($this->EE->CFG->core->cancel_page_id ) ?$this->EE->CFG->core->cancel_page_id : NULL;
		$this->_template_args['cancel_page_obj'] = isset($this->EE->CFG->core->cancel_page_id ) ? get_page($this->EE->CFG->core->cancel_page_id ) : FALSE;
		
		$this->_set_add_edit_form_tags( 'update_espresso_page_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args['admin_page_content'] = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'espresso_page_settings.template.php', $this->_template_args, TRUE );
		$this->display_admin_page_with_sidebar();	
		
	}

	protected function _update_espresso_page_settings() {
		
		$this->EE->CFG->core->reg_page_id = isset( $this->_req_data['reg_page_id'] ) ? absint( $this->_req_data['reg_page_id'] ) : $this->EE->CFG->core->reg_page_id;
		$this->EE->CFG->core->txn_page_id = isset( $this->_req_data['txn_page_id'] ) ? absint( $this->_req_data['txn_page_id'] ) : $this->EE->CFG->core->txn_page_id;
		$this->EE->CFG->core->thank_you_page_id = isset( $this->_req_data['thank_you_page_id'] ) ? absint( $this->_req_data['thank_you_page_id'] ) : $this->EE->CFG->core->thank_you_page_id;
		$this->EE->CFG->core->cancel_page_id = isset( $this->_req_data['cancel_page_id'] ) ? absint( $this->_req_data['cancel_page_id'] ) : $this->EE->CFG->core->cancel_page_id;

		$this->EE->CFG->core = apply_filters( 'FHEE_page_settings_save', $this->EE->CFG->core, $this->_req_data );
		
		$what = 'Critical Pages & Shortcodes';
		$success = $this->_update_espresso_configuration( $what, $this->EE->CFG->core, __FILE__, __FUNCTION__, __LINE__ );
		$this->_redirect_after_action( $success, 'Template Settings', 'updated', array() );
		
	}









	/*************		Templates 		*************/


	protected function _template_settings() {
		$this->_template_args['values'] = $this->_yes_no_values;
		
		$this->_template_args['display_address_in_regform'] = 
				isset( $this->EE->CFG->template_settings->display_address_in_regform ) && ! empty( $this->EE->CFG->template_settings->display_address_in_regform ) 
				? $this->EE->CFG->template_settings->display_address_in_regform
				: TRUE;
		
		$this->_template_args = apply_filters( 'FHEE__General_Settings_Admin_Page__template_settings__template_args', $this->_template_args );

		$this->_set_add_edit_form_tags( 'update_template_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args['admin_page_content'] = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'template_settings.template.php', $this->_template_args, TRUE );
		$this->display_admin_page_with_sidebar();	
	}




	protected function _update_template_settings() {
		
		$this->EE->CFG->template_settings->display_address_in_regform = 
				 isset( $this->_req_data['display_address_in_regform'] ) 
				? absint( $this->_req_data['display_address_in_regform'] ) 
				: $this->EE->CFG->template_settings->display_address_in_regform;
		
		$this->EE->CFG->template_settings = apply_filters( 'FHEE__General_Settings_Admin_Page__update_template_settings__data', $this->EE->CFG->template_settings, $this->_req_data );
		
		$what = 'Template Settings';
		$success = $this->_update_espresso_configuration( $what, $this->EE->CFG->template_settings, __FILE__, __FUNCTION__, __LINE__ );
		$this->_redirect_after_action( $success, $what, 'updated', array( 'action' => 'template_settings' ) );
		
	}





	/*************		Google Maps 		*************/


	protected function _google_map_settings() {
	
		
		$this->_template_args['values'] = $this->_yes_no_values;
		$default_map_settings->use_google_maps = TRUE;
		// for event details pages (reg page)
		$default_map_settings->event_details_map_width = 585; 			// ee_map_width_single
		$default_map_settings->event_details_map_height = 362; 			// ee_map_height_single
		$default_map_settings->event_details_map_zoom = 14; 			// ee_map_zoom_single
		$default_map_settings->event_details_display_nav = TRUE; 			// ee_map_nav_display_single
		$default_map_settings->event_details_nav_size = FALSE; 			// ee_map_nav_size_single
		$default_map_settings->event_details_control_type = 'default'; 		// ee_map_type_control_single
		$default_map_settings->event_details_map_align = 'center'; 			// ee_map_align_single
		// for event list pages
		$default_map_settings->event_list_map_width = 300; 			// ee_map_width
		$default_map_settings->event_list_map_height = 185; 		// ee_map_height
		$default_map_settings->event_list_map_zoom = 12; 			// ee_map_zoom
		$default_map_settings->event_list_display_nav = FALSE; 		// ee_map_nav_display
		$default_map_settings->event_list_nav_size = TRUE; 			// ee_map_nav_size
		$default_map_settings->event_list_control_type = 'dropdown'; 		// ee_map_type_control
		$default_map_settings->event_list_map_align = 'center'; 			// ee_map_align
		
		$this->_template_args['map_settings'] = 
				isset( $this->EE->CFG->map_settings ) && ! empty( $this->EE->CFG->map_settings ) 
				? (object)array_merge( (array)$default_map_settings, (array)$this->EE->CFG->map_settings )
				: $default_map_settings;		

		$this->_set_add_edit_form_tags( 'update_google_map_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args['admin_page_content'] = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'google_map.template.php', $this->_template_args, TRUE );
		$this->display_admin_page_with_sidebar();	
	}

	protected function _update_google_map_settings() {

		$this->EE->CFG->map_settings->use_google_maps = 
				 isset( $this->_req_data['use_google_maps'] ) 
				? absint( $this->_req_data['use_google_maps'] ) 
				: $this->EE->CFG->map_settings->use_google_maps;

		$this->EE->CFG->map_settings->event_details_map_width = 
				 isset( $this->_req_data['event_details_map_width'] ) 
				? absint( $this->_req_data['event_details_map_width'] ) 
				: $this->EE->CFG->map_settings->event_details_map_width;

		$this->EE->CFG->map_settings->event_details_map_height = 
				 isset( $this->_req_data['event_details_map_height'] ) 
				? absint( $this->_req_data['event_details_map_height'] ) 
				: $this->EE->CFG->map_settings->event_details_map_height;

		$this->EE->CFG->map_settings->event_details_map_zoom = 
				 isset( $this->_req_data['event_details_map_zoom'] ) 
				? absint( $this->_req_data['event_details_map_zoom'] ) 
				: $this->EE->CFG->map_settings->event_details_map_zoom;

		$this->EE->CFG->map_settings->event_details_display_nav = 
				 isset( $this->_req_data['event_details_display_nav'] ) 
				? absint( $this->_req_data['event_details_display_nav'] ) 
				: $this->EE->CFG->map_settings->event_details_display_nav;

		$this->EE->CFG->map_settings->event_details_nav_size = 
				 isset( $this->_req_data['event_details_nav_size'] ) 
				? absint( $this->_req_data['event_details_nav_size'] ) 
				: $this->EE->CFG->map_settings->event_details_nav_size;

		$this->EE->CFG->map_settings->event_details_control_type = 
				 isset( $this->_req_data['event_details_control_type'] ) 
				? sanitize_text_field( $this->_req_data['event_details_control_type'] ) 
				: $this->EE->CFG->map_settings->event_details_control_type;

		$this->EE->CFG->map_settings->event_details_map_align = 
				 isset( $this->_req_data['event_details_map_align'] ) 
				? sanitize_text_field( $this->_req_data['event_details_map_align'] ) 
				: $this->EE->CFG->map_settings->event_details_map_align;

		$this->EE->CFG->map_settings->event_list_map_width = 
				 isset( $this->_req_data['event_list_map_width'] ) 
				? absint( $this->_req_data['event_list_map_width'] ) 
				: $this->EE->CFG->map_settings->event_list_map_width;

		$this->EE->CFG->map_settings->event_list_map_height = 
				 isset( $this->_req_data['event_list_map_height'] ) 
				? absint( $this->_req_data['event_list_map_height'] ) 
				: $this->EE->CFG->map_settings->event_list_map_height;

		$this->EE->CFG->map_settings->event_list_map_zoom = 
				 isset( $this->_req_data['event_list_map_zoom'] ) 
				? absint( $this->_req_data['event_list_map_zoom'] ) 
				: $this->EE->CFG->map_settings->event_list_map_zoom;

		$this->EE->CFG->map_settings->event_list_display_nav = 
				 isset( $this->_req_data['event_list_display_nav'] ) 
				? absint( $this->_req_data['event_list_display_nav'] ) 
				: $this->EE->CFG->map_settings->event_list_display_nav;

		$this->EE->CFG->map_settings->event_list_nav_size = 
				 isset( $this->_req_data['event_list_nav_size'] ) 
				? absint( $this->_req_data['event_list_nav_size'] ) 
				: $this->EE->CFG->map_settings->event_list_nav_size;

		$this->EE->CFG->map_settings->event_list_control_type = 
				 isset( $this->_req_data['event_list_control_type'] ) 
				? sanitize_text_field( $this->_req_data['event_list_control_type'] ) 
				: $this->EE->CFG->map_settings->event_list_control_type;

		$this->EE->CFG->map_settings->event_list_map_align = 
				 isset( $this->_req_data['event_list_map_align'] ) 
				? sanitize_text_field( $this->_req_data['event_list_map_align'] ) 
				: $this->EE->CFG->map_settings->event_list_map_align;

		$this->EE->CFG->map_settings = apply_filters('FHEE_google_map_settings_save', $this->EE->CFG->map_settings);	
		
		$what = 'Google Map Settings';
		$success = $this->_update_espresso_configuration( $what, $this->EE->CFG->map_settings, __FILE__, __FUNCTION__, __LINE__ );
		$this->_redirect_after_action( $success, $what, 'updated', array( 'action' => 'google_map_settings' ) );
		
	}



	/*************		Your Organization 		*************/


	protected function _your_organization_settings() {
	
		$this->_template_args['site_license_key'] = isset( $this->EE->CFG->core->site_license_key ) ? $this->_display_nice( $this->EE->CFG->core->site_license_key ) : '';
		$this->_template_args['organization_name'] = isset( $this->EE->CFG->organization->name ) ? $this->_display_nice( $this->EE->CFG->organization->name ) : '';
		$this->_template_args['organization_address_1'] = isset( $this->EE->CFG->organization->address_1 ) ? $this->_display_nice( $this->EE->CFG->organization->address_1 ) : '';
		$this->_template_args['organization_address_2'] = isset( $this->EE->CFG->organization->address_2 ) ? $this->_display_nice( $this->EE->CFG->organization->address_2 ) : '';
		$this->_template_args['organization_city'] = isset( $this->EE->CFG->organization->city ) ? $this->_display_nice( $this->EE->CFG->organization->city ) : '';
		$this->_template_args['organization_zip'] = isset( $this->EE->CFG->organization->zip ) ? $this->_display_nice( $this->EE->CFG->organization->zip ) : '';
		$this->_template_args['organization_email'] = isset( $this->EE->CFG->organization->email ) ? $this->_display_nice( $this->EE->CFG->organization->email ) : '';
		$this->_template_args['currency_sign'] = isset( $this->EE->CFG->currency->sign ) ? $this->_display_nice( $this->EE->CFG->currency->sign ) : '$';
		$this->_template_args['organization_logo_url'] = isset( $this->EE->CFG->organization->logo_url ) ? $this->_display_nice( $this->EE->CFG->organization->logo_url ) : FALSE;
		//UXIP settings
		$this->_template_args['ee_ueip_optin'] = get_option( 'ee_ueip_optin', TRUE );
		
		$this->EE->load_helper( 'Form_Fields' );
		$STA_ID = isset( $this->EE->CFG->organization->STA_ID ) ? $this->EE->CFG->organization->STA_ID : 4;
		$this->_template_args['states'] = new EE_Question_Form_Input(
				EE_Question::new_instance ( array(
					'QST_ID'=> 0,
					'QST_display_text'=> __('State/Province', 'event_espresso'),
					'QST_system'=>'admin-state'
				)),
				EE_Answer::new_instance ( array( 
					'ANS_ID' => 0, 
					'ANS_value' => $STA_ID 
				)),
				array( 
					'input_id' => 'organization_state',
					'input_name' => 'organization_state',
					'input_prefix' => '',
					'append_qstn_id' => FALSE
				)
			);

		$CNT_ISO = isset( $this->EE->CFG->organization->CNT_ISO ) ? $this->EE->CFG->organization->CNT_ISO : 'US';
		$this->_template_args['countries'] = new EE_Question_Form_Input(
				EE_Question::new_instance ( array(
					'QST_ID'=> 0,
					'QST_display_text'=> __('Country', 'event_espresso'),
					'QST_system'=>'admin-country'
				)),
				EE_Answer::new_instance ( array( 
					'ANS_ID' => 0, 
					'ANS_value' => $CNT_ISO 
				)),
				array( 
					'input_id' => 'organization_country',
					'input_name' => 'organization_country',
					'input_prefix' => '',
					'append_qstn_id' => FALSE
				)
			);

		add_filter( 'FHEE_form_field_label_html', array( $this, 'country_form_field_label_wrap' ), 10, 1 );
		add_filter( 'FHEE_form_field_input_html', array( $this, 'country_form_field_input__wrap' ), 10, 1 );

		//PUE verification stuff
		$plugin_basename = plugin_basename(EVENT_ESPRESSO_PLUGINPATH);
		$verify_fail = get_option( 'pue_verification_error_' . $plugin_basename );
		$this->_template_args['site_license_key_verified'] = !empty( $verify_fail ) ? '<span class"pue-sl-not-verified"></span>' : '<span class="pue-sl-verified"></span>';		
		
		$this->_set_add_edit_form_tags( 'update_your_organization_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args['admin_page_content'] = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'your_organization_settings.template.php', $this->_template_args, TRUE );

		$this->display_admin_page_with_sidebar();	
	}

	protected function _update_your_organization_settings() {
		
		$this->EE->CFG->core->site_license_key = isset( $this->_req_data['site_license_key'] ) ? sanitize_text_field( $this->_req_data['site_license_key'] ) : $this->EE->CFG->core->site_license_key;
		$this->EE->CFG->organization->name = isset( $this->_req_data['organization_name'] ) ? sanitize_text_field( $this->_req_data['organization_name'] ) : $this->EE->CFG->organization->name;
		$this->EE->CFG->organization->address_1 = isset( $this->_req_data['organization_address_1'] ) ? sanitize_text_field( $this->_req_data['organization_address_1'] ) : $this->EE->CFG->organization->address_1;
		$this->EE->CFG->organization->address_2 = isset( $this->_req_data['organization_address_2'] ) ? sanitize_text_field( $this->_req_data['organization_address_2'] ) : $this->EE->CFG->organization->address_2;
		$this->EE->CFG->organization->city = isset( $this->_req_data['organization_city'] ) ? sanitize_text_field( $this->_req_data['organization_city'] ) : $this->EE->CFG->organization->city;
		$this->EE->CFG->organization->STA_ID = isset( $this->_req_data['organization_state'] ) ? absint( $this->_req_data['organization_state'] ) : $this->EE->CFG->organization->STA_ID;
		$this->EE->CFG->organization->CNT_ISO = isset( $this->_req_data['organization_country'] ) ? sanitize_text_field( $this->_req_data['organization_country'] ) : $this->EE->CFG->organization->CNT_ISO;
		$this->EE->CFG->organization->zip = isset( $this->_req_data['organization_zip'] ) ? sanitize_text_field( $this->_req_data['organization_zip'] ) : $this->EE->CFG->organization->zip;
		$this->EE->CFG->organization->email = isset( $this->_req_data['organization_email'] ) ? sanitize_email( $this->_req_data['organization_email'] ) : $this->EE->CFG->organization->email;
		$this->EE->CFG->organization->logo_url = isset( $this->_req_data['organization_logo_url'] ) ? esc_url_raw( $this->_req_data['organization_logo_url'] ) : $this->EE->CFG->organization->logo_url;
		$this->EE->CFG->core->ee_ueip_optin = isset( $this->_req_data['ueip_optin'] ) && !empty( $this->_req_data['ueip_optin'] ) ? $this->_req_data['ueip_optin'] : $this->EE->CFG->core->ee_ueip_optin; 

		$this->EE->CFG = apply_filters('FHEE_your_organization_settings_save', $this->EE->CFG );	
		
		$what = 'Your Organization Settings';
		$success = $this->_update_espresso_configuration( $what, $this->EE->CFG, __FILE__, __FUNCTION__, __LINE__ );
		$this->_redirect_after_action( $success, $what, 'updated', array( 'action' => 'your_organization_settings' ) );
		
	}


	/*************		Admin Options 		*************/


	protected function _admin_option_settings() {
	
		$this->_template_args['values'] = $this->_yes_no_values;
		$this->_template_args['use_personnel_manager'] = isset( $this->EE->CFG->admin->use_personnel_manager ) ? absint( $this->EE->CFG->admin->use_personnel_manager ) : FALSE;
		$this->_template_args['use_dashboard_widget'] = isset( $this->EE->CFG->admin->use_dashboard_widget ) ? absint( $this->EE->CFG->admin->use_dashboard_widget ) : TRUE;
		$this->_template_args['events_in_dasboard'] = isset( $this->EE->CFG->admin->events_in_dasboard ) ? absint( $this->EE->CFG->admin->events_in_dasboard ) : 30;
		$this->_template_args['use_event_timezones'] = isset( $this->EE->CFG->admin->use_event_timezones ) ? absint( $this->EE->CFG->admin->use_event_timezones ) : FALSE;
		$this->_template_args['use_full_logging'] = isset( $this->EE->CFG->admin->use_full_logging ) ? absint( $this->EE->CFG->admin->use_full_logging ) : FALSE;
		$this->_template_args['use_remote_logging'] = isset( $this->EE->CFG->admin->use_remote_logging ) ? absint( $this->EE->CFG->admin->use_remote_logging ) : FALSE;
		$this->_template_args['remote_logging_url'] = isset( $this->EE->CFG->admin->remote_logging_url ) && ! empty( $this->EE->CFG->admin->remote_logging_url ) ? stripslashes( $this->EE->CFG->admin->remote_logging_url ) : '';
		$this->_template_args['show_reg_footer'] = isset( $this->EE->CFG->admin->show_reg_footer ) ? absint( $this->EE->CFG->admin->show_reg_footer ) : TRUE;
		$this->_template_args['affiliate_id'] = isset( $this->EE->CFG->admin->affiliate_id ) ? $this->_display_nice( $this->EE->CFG->admin->affiliate_id ) : '';
		
		$this->_set_add_edit_form_tags( 'update_admin_option_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args['template_args'] = $this->_template_args;
		$this->_template_args['admin_page_content'] = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'admin_option_settings.template.php', $this->_template_args, TRUE );
		$this->display_admin_page_with_sidebar();	
	}

	protected function _update_admin_option_settings() {
		$this->EE->CFG->admin->use_personnel_manager = isset( $this->_req_data['use_personnel_manager'] ) ? absint( $this->_req_data['use_personnel_manager'] ) : $this->EE->CFG->admin->use_personnel_manager;
		$this->EE->CFG->admin->use_dashboard_widget = isset( $this->_req_data['use_dashboard_widget'] ) ? absint( $this->_req_data['use_dashboard_widget'] ) : $this->EE->CFG->admin->use_dashboard_widget;
		$this->EE->CFG->admin->events_in_dasboard = isset( $this->_req_data['events_in_dasboard'] ) ? absint( $this->_req_data['events_in_dasboard'] ) : $this->EE->CFG->admin->events_in_dasboard;
		$this->EE->CFG->admin->use_event_timezones = isset( $this->_req_data['use_event_timezones'] ) ? absint( $this->_req_data['use_event_timezones'] ) : $this->EE->CFG->admin->use_event_timezones;
		$this->EE->CFG->admin->use_full_logging = isset( $this->_req_data['use_full_logging'] ) ? absint( $this->_req_data['use_full_logging'] ) : $this->EE->CFG->admin->use_full_logging;
		$this->EE->CFG->admin->use_remote_logging = isset( $this->_req_data['use_remote_logging'] ) ? absint( $this->_req_data['use_remote_logging'] ) : $this->EE->CFG->admin->use_remote_logging;
		$this->EE->CFG->admin->remote_logging_url = isset( $this->_req_data['remote_logging_url'] ) ? esc_url_raw( $this->_req_data['remote_logging_url'] ) : $this->EE->CFG->admin->remote_logging_url;
		$this->EE->CFG->admin->show_reg_footer = isset( $this->_req_data['show_reg_footer'] ) ? absint( $this->_req_data['show_reg_footer'] ) : $this->EE->CFG->admin->show_reg_footer;
		$this->EE->CFG->admin->affiliate_id = isset( $this->_req_data['affiliate_id'] ) ? sanitize_text_field( $this->_req_data['affiliate_id'] ) : $this->EE->CFG->admin->affiliate_id;
		
		$this->EE->CFG->admin = apply_filters( 'FHEE_admin_option_settings_save', $this->EE->CFG->admin );	
		
		$what = 'Admin Options';
		$success = $this->_update_espresso_configuration( $what, $this->EE->CFG->admin, __FILE__, __FUNCTION__, __LINE__ );
		$this->_redirect_after_action( $success, $what, 'updated', array( 'action' => 'admin_option_settings' ) );
		
	}





	/*************		Countries 		*************/


	protected function _country_settings() {
	
		global $org_options;
		$CNT_ISO = isset( $this->EE->CFG->organization->CNT_ISO ) ? $this->EE->CFG->organization->CNT_ISO : 'US';
		$CNT_ISO = isset( $this->_req_data['country'] ) ? strtoupper( sanitize_text_field( $this->_req_data['country'] )) : $CNT_ISO;

		//load field generator helper
		$this->EE->load_helper( 'Form_Fields' );
		
		$this->_template_args['values'] = $this->_yes_no_values;
				
		$this->_template_args['countries'] = new EE_Question_Form_Input(
				EE_Question::new_instance ( array(
					'QST_ID'=> 0,
					'QST_display_text'=> __('Select Country', 'event_espresso'),
					'QST_system'=>'admin-country'
				)),
				EE_Answer::new_instance ( array( 
					'ANS_ID' => 0, 
					'ANS_value' => $CNT_ISO 
				)),
				array( 
					'input_id' => 'country',
					'input_name' => 'country',
					'input_prefix' => '',
					'append_qstn_id' => FALSE
				)
			);
//		printr( $this->_template_args['countries'], 'countries  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		
		add_filter( 'FHEE_form_field_label_html', array( $this, 'country_form_field_label_wrap' ), 10, 1 );
		add_filter( 'FHEE_form_field_input_html', array( $this, 'country_form_field_input__wrap' ), 10, 1 );
		$this->_template_args['country_details_settings'] = $this->display_country_settings();
		$this->_template_args['country_states_settings'] = $this->display_country_states();

		$this->_set_add_edit_form_tags( 'update_country_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args['admin_page_content'] = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'countries_settings.template.php', $this->_template_args, TRUE );
		$this->display_admin_page_with_sidebar();
	}

	

	/**
	 * 		display_country_settings
	 *
	 * 		@access 	public
	 * 		@param 	string 		$CNT_ISO
	 * 		@return 		mixed		string | array
	 */
	public function display_country_settings( $CNT_ISO = FALSE ) {
		
		$CNT_ISO = isset( $this->_req_data['country'] ) ? strtoupper( sanitize_text_field( $this->_req_data['country'] )) : $CNT_ISO;
		if ( ! $CNT_ISO ) {
			return '';
		}
		// for ajax
//		if ( ! class_exists( 'EE_Form_Fields' )) {
//			require_once ( EE_HELPERS . 'EE_Form_Fields.helper.php' );
//		}
		$this->EE->load_helper( 'Form_Fields' );
		remove_all_filters( 'FHEE_form_field_label_html' );
		remove_all_filters( 'FHEE_form_field_input_html' );
		add_filter( 'FHEE_form_field_label_html', array( $this, 'country_form_field_label_wrap' ), 10, 1 );
		add_filter( 'FHEE_form_field_input_html', array( $this, 'country_form_field_input__wrap' ), 10, 1 );
		$country = EEM_Country::instance()->get_one_by_ID( $CNT_ISO );
		//printr( $country, '$country  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		$country_input_types = array(
			'CNT_ISO' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'small-text' ),
			'CNT_ISO3' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'small-text' ),
			'RGN_ID' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'small-text' ),
			'CNT_name' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'regular-text' ),
			'CNT_cur_code' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'small-text' ),
			'CNT_cur_single' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'medium-text' ),
			'CNT_cur_plural' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'medium-text' ),
			'CNT_cur_sign' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'small-text', 'htmlentities' => FALSE ),
			'CNT_cur_sign_b4' => array( 'type' => 'SINGLE', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => '', 'options' => $this->_yes_no_values ),
			'CNT_cur_dec_plc' => array( 'type' => 'SINGLE', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => '', 'options' => array( array( 'id' => 0, 'text' => '0' ), array( 'id' => 1, 'text' => '1' ), array( 'id' => 2, 'text' => '2' ), array( 'id' => 3, 'text' => '3' ))),
			'CNT_cur_dec_mrk' => array( 'type' => 'SINGLE', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => '', 'options' => array( array( 'id' => ',', 'text' => __(', (comma)', 'event_espresso')), array( 'id' => '.', 'text' => __('. (decimal)', 'event_espresso')))),
			'CNT_cur_thsnds' => array( 'type' => 'SINGLE', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => '', 'options' => array( array( 'id' => ',', 'text' => __(', (comma)', 'event_espresso')), array( 'id' => '.', 'text' => __('. (decimal)', 'event_espresso')))),
			'CNT_tel_code' => array( 'type' => 'TEXT', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => 'small-text' ),
			'CNT_is_EU' => array( 'type' => 'SINGLE', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => '', 'options' => $this->_yes_no_values ),
			'CNT_active' => array( 'type' => 'SINGLE', 'input_name' => 'cntry[' . $CNT_ISO . ']', 'class' => '', 'options' => $this->_yes_no_values )
		);
		$this->_template_args['inputs'] = EE_Question_Form_Input::generate_question_form_inputs_for_object( $country, $country_input_types );
		$country_details_settings = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'country_details_settings.template.php', $this->_template_args, TRUE );

		if ( defined( 'DOING_AJAX' )) {
			$notices = EE_Error::get_notices( FALSE, FALSE, FALSE );
			echo json_encode( array( 'return_data' => $country_details_settings, 'success' => $notices['success'], 'errors' => $notices['errors'] ));
			die();
		} else {
			return $country_details_settings;
		}

	}




	/**
	 * 		display_country_states
	 *
	 * 		@access 	public
	 * 		@param 	string 		$CNT_ISO
	 * 		@return 		string
	 */
	public function display_country_states( $CNT_ISO = FALSE ) {
		
		$CNT_ISO = isset( $this->_req_data['country'] ) ? sanitize_text_field( $this->_req_data['country'] ) : $CNT_ISO;

		if ( ! $CNT_ISO ) {
			return '';
		}
		// for ajax
		$this->EE->load_helper( 'Form_Fields' );
		remove_all_filters( 'FHEE_form_field_label_html' );
		remove_all_filters( 'FHEE_form_field_input_html' );
		add_filter( 'FHEE_form_field_label_html', array( $this, 'state_form_field_label_wrap' ), 10, 1 );
		add_filter( 'FHEE_form_field_input_html', array( $this, 'state_form_field_input__wrap' ), 10, 1 );
		$states = FALSE;
		$states = EEM_State::instance()->get_all_states_for_these_countries( array( $CNT_ISO => $CNT_ISO ));
		
//			echo '<h4>$CNT_ISO : ' . $CNT_ISO . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//			global $wpdb;
//			echo '<h4>' . $wpdb->last_query . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//			printr( $states, '$states  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		if ( $states ) {
			foreach ( $states as $STA_ID => $state ) {
				//STA_abbrev 	STA_name 	STA_active
				$state_input_types = array(
					'STA_abbrev' => array( 'type' => 'TEXT', 'input_name' => 'states[' . $STA_ID . ']', 'class' => 'small-text' ), 
					'STA_name' => array( 'type' => 'TEXT', 'input_name' => 'states[' . $STA_ID . ']', 'class' => 'regular-text' ), 
					'STA_active' => array( 'type' => 'SINGLE', 'input_name' => 'states[' . $STA_ID . ']', 'options' => $this->_yes_no_values )
				);
				$this->_template_args['states'][ $STA_ID ]['inputs'] = EE_Question_Form_Input::generate_question_form_inputs_for_object( $state, $state_input_types );
				$query_args =  array( 'action' => 'delete_state', 'STA_ID' => $STA_ID, 'CNT_ISO' => $CNT_ISO );
				$this->_template_args['states'][ $STA_ID ]['delete_state_url'] = EE_Admin_Page::add_query_args_and_nonce( $query_args, GEN_SET_ADMIN_URL );
			}	
		} else {
			$this->_template_args['states'] = FALSE;
		}
//		printr( $this->_template_args['states'], 'XXXXXXX  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		$this->_template_args['add_new_state_url'] = EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'add_new_state' ),  GEN_SET_ADMIN_URL );

		$state_details_settings = espresso_display_template( GEN_SET_TEMPLATE_PATH . 'state_details_settings.template.php', $this->_template_args, TRUE );
		
		if ( defined( 'DOING_AJAX' )) {
			$notices = EE_Error::get_notices( FALSE, FALSE, FALSE ); 
			echo json_encode( array( 'return_data' => $state_details_settings, 'success' => $notices['success'], 'errors' => $notices['errors'] ));
			die();
		} else {
			return $state_details_settings;
		}

	}




	/**
	 * 		add_new_state
	 *
	 * 		@access 	public
	 * 		@return 		void
	 */
	public function add_new_state() {
		
		$success = TRUE;
		
		$CNT_ISO = isset( $this->_req_data['CNT_ISO'] ) ? strtoupper( sanitize_text_field( $this->_req_data['CNT_ISO'] )) : FALSE;
		if ( ! $CNT_ISO ) {
			EE_Error::add_error( __( 'An error occured. No Country ISO code or an invalid Country ISO code was received.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			$success = FALSE;
		}
		$STA_abbrev = isset( $this->_req_data['STA_abbrev'] ) ? strtoupper( sanitize_text_field( $this->_req_data['STA_abbrev'] )) : FALSE;		
		if ( ! $STA_abbrev ) {
			EE_Error::add_error( __( 'An error occured. No State ISO code or an invalid State ISO code was received.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			$success = FALSE;
		}
		$STA_name = isset( $this->_req_data['STA_name'] ) ? ucwords( strtolower( sanitize_text_field( $this->_req_data['STA_name'] ))) : FALSE;
		if ( ! $STA_name ) {
			EE_Error::add_error( __( 'An error occured. No State name or an invalid State name was received.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			$success = FALSE;
		}
		
		if ( $success ) {
			$cols_n_values = array(
				'CNT_ISO' => $CNT_ISO,
				'STA_abbrev' => $STA_abbrev,
				'STA_name' => $STA_name,
				'STA_active' => TRUE
			);			
			$success = EEM_State::instance()->insert ( $cols_n_values );			
		}
		
		if ( defined( 'DOING_AJAX' )) {
			$notices = EE_Error::get_notices( FALSE, FALSE, FALSE ); 
			echo json_encode( array( 'return_data' => $CNT_ISO, 'success' => __( 'The State was added successfully.', 'event_espresso' ), 'errors' => $notices['errors'] ));
			die();
		} else {
			$this->_redirect_after_action( $success, 'State', 'added', array( 'action' => 'country_settings' ) );
		}
	}



	/**
	 * 		delete_state
	 *
	 * 		@access 	public
	 * 		@return 		void
	 */
	public function delete_state() {
		$STA_ID = isset( $this->_req_data['STA_ID'] ) ? sanitize_text_field( $this->_req_data['STA_ID'] ) : FALSE;
		if ( ! $STA_ID ) {
			EE_Error::add_error( __( 'An error occured. No State ID or an invalid State ID was received.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}
		$success = EEM_State::instance()->delete_by_ID( $STA_ID );
		
		if ( defined( 'DOING_AJAX' )) {
			$notices = EE_Error::get_notices( FALSE, FALSE, FALSE ); 
			echo json_encode( array( 'return_data' => true, 'success' => __( 'The State was deleted successfully.', 'event_espresso' ), 'errors' => $notices['errors'] ));
			die();
		} else {
			$this->_redirect_after_action( $success, 'State', 'deleted', array( 'action' => 'country_settings' ) );
		}
	}




	/**
	 * 		_update_country_settings
	 *
	 * 		@access 	protected
	 * 		@return 		void
	 */
	protected function _update_country_settings() {
		//printr( $this->_req_data, '$this->_req_data  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		// grab the country ISO code
		$CNT_ISO = isset( $this->_req_data['country'] ) ? strtoupper( sanitize_text_field( $this->_req_data['country'] )) : FALSE;
		if ( ! $CNT_ISO ) {
			EE_Error::add_error( __( 'An error occured. No Country ISO code or an invalid Country ISO code was received.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}
		$success = TRUE;
		$cols_n_values = array();
		$cols_n_values['CNT_ISO3'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_ISO3'] ) ? strtoupper( sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_ISO3'] )) : FALSE;
		$cols_n_values['RGN_ID'] = isset( $this->_req_data['cntry'][$CNT_ISO]['RGN_ID'] ) ? absint( $this->_req_data['cntry'][$CNT_ISO]['RGN_ID'] ) : NULL;
		$cols_n_values['CNT_name'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_name'] ) ? sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_name'] ) : NULL;
		$cols_n_values['CNT_cur_code'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_code'] ) ? strtoupper( sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_code'] )) : 'USD';
		$cols_n_values['CNT_cur_single'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_single'] ) ? sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_single'] ) : 'dollar';
		$cols_n_values['CNT_cur_plural'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_plural'] ) ? sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_plural'] ) : 'dollars';
		$cols_n_values['CNT_cur_sign'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_sign'] ) ? sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_sign'] ) : '$';
		$cols_n_values['CNT_cur_sign_b4'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_sign_b4'] ) ? absint( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_sign_b4'] ) : TRUE;
		$cols_n_values['CNT_cur_dec_plc'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_dec_plc'] ) ? absint( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_dec_plc'] ) : 2;
		$cols_n_values['CNT_cur_dec_mrk'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_dec_mrk'] ) ? sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_dec_mrk'] ) : '.';
		$cols_n_values['CNT_cur_thsnds'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_thsnds'] ) ? sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_cur_thsnds'] ) : ',';
		$cols_n_values['CNT_tel_code'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_tel_code'] ) ? sanitize_text_field( $this->_req_data['cntry'][$CNT_ISO]['CNT_tel_code'] ) : NULL;
		$cols_n_values['CNT_is_EU'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_is_EU'] ) ? absint( $this->_req_data['cntry'][$CNT_ISO]['CNT_is_EU'] ) : FALSE;
		$cols_n_values['CNT_active'] = isset( $this->_req_data['cntry'][$CNT_ISO]['CNT_active'] ) ? absint( $this->_req_data['cntry'][$CNT_ISO]['CNT_active'] ) : FALSE;
		// allow filtering of country data
		$cols_n_values = apply_filters( 'FHEE_general_settings_country_save', $cols_n_values );
		//printr( $cols_n_values, '$cols_n_values  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		// where values
		$where_cols_n_values = array( array( 'CNT_ISO' => $CNT_ISO ));
		// run the update
		$success = EEM_Country::instance()->update( $cols_n_values, $where_cols_n_values );
		//global $wpdb;
		//echo '<h4>' . $wpdb->last_query . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
		//echo '<h4>$success : ' . $success . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
		if ( isset( $this->_req_data['states'] ) && is_array( $this->_req_data['states'] ) && $success ) {
			// allow filtering of states data
			$states = apply_filters( 'FHEE_general_settings_states_save', $this->_req_data['states'] );
			// loop thru state data ( looks like : states[75][STA_name] )
			foreach( $states as $STA_ID => $state ) {
				$cols_n_values = array(
					'CNT_ISO' 		=> $CNT_ISO,
					'STA_abbrev' => strtoupper( sanitize_text_field( $state['STA_abbrev'] )),
					'STA_name' 	=> ucwords( strtolower( sanitize_text_field( $state['STA_name'] ))),
					'STA_active' 	=> (bool)absint( $state['STA_active'] )
				);
				// where values
				$where_cols_n_values = array( array( 'STA_ID' => $STA_ID ));
				// run the update
				$success = EEM_State::instance()->update( $cols_n_values, $where_cols_n_values );
			}
		}
		$this->_redirect_after_action( $success, 'Countries', 'updated', array( 'action' => 'country_settings', 'country' => $CNT_ISO ));
	}





	/**
	 * 		form_form_field_label_wrap
	 *
	 * 		@access 		public
	 * 		@param 		string 		$label
	 * 		@return 		string
	 */
	public function country_form_field_label_wrap( $label ) {
		return '
			<tr>
				<th>
					' . $label  . '
				</th>';		
	}




	/**
	 * 		form_form_field_input__wrap
	 *
	 * 		@access 		public
	 * 		@param 		string 		$label
	 * 		@return 		string
	 */
	public function country_form_field_input__wrap( $input ) {
		return '
				<td class="general-settings-country-input-td">
					' . $input . ' 
				</td>
			</tr>';		
	}




	/**
	 * 		form_form_field_label_wrap
	 *
	 * 		@access 		public
	 * 		@param 		string 		$label
	 * 		@return 		string
	 */
	public function state_form_field_label_wrap( $label ) {
		return '';		
	}




	/**
	 * 		form_form_field_input__wrap
	 *
	 * 		@access 		public
	 * 		@param 		string 		$label
	 * 		@return 		string
	 */
	public function state_form_field_input__wrap( $input ) {
		return '
				<td class="general-settings-country-state-input-td">
					' . $input . ' 
				</td>';		
	}






	/***********/





	/**
	 * displays edit and view links for critical EE pages
	 *
	 * @access public 
	 * @param int $ee_page_id
	 * @return string
	 */
	public static function edit_view_links( $ee_page_id ) {
		$links = '<a href="' . add_query_arg( array( 'post' => $ee_page_id, 'action' => 'edit' ),  admin_url( 'post.php' )) . '" >' . __('Edit', 'event_espresso') . '</a>';
		$links .= ' &nbsp;|&nbsp; ';
		$links .= '<a href="' . get_permalink( $ee_page_id ) . '" >' . __('View', 'event_espresso') . '</a>';
		return $links;
	}
	
	
	

	/**
	 * displays page and shortcode status for critical EE pages
	 *
	 * @param WP page object $ee_page
	 * @return string
	 */
	public static function page_and_shortcode_status( $ee_page, $shortcode ) {

		// page status
		if ( isset( $ee_page->post_status ) && $ee_page->post_status == 'publish') { 
			$pg_colour = 'green';
			$pg_status = sprintf( __('Page%sStatus%sOK', 'event_espresso'), '&nbsp;' , '&nbsp;' );
		 } else { 
			$pg_colour = 'red';
			$pg_status = sprintf( __('Page%sVisibility%sProblem', 'event_espresso'), '&nbsp;', '&nbsp;'  );
		}
		
		// shortcode status
		if ( isset( $ee_page->post_content ) && strpos( $ee_page->post_content, $shortcode ) !== FALSE ) { 
			$sc_colour = 'green';
			$sc_status = sprintf( __('Shortcode%sOK', 'event_espresso'), '&nbsp;' );
		 } else { 
			$sc_colour = 'red';
			$sc_status = sprintf( __('Shortcode%sProblem', 'event_espresso'), '&nbsp;' );
		}

		return '<span style="color:' . $pg_colour . '; margin-right:2em;"><strong>' . $pg_status . '</strong></span><span style="color:' . $sc_colour . '"><strong>' . $sc_status . '</strong></span>';		

	}
	
	
	

	/**
	 * generates a dropdown of all parent pages - copied from WP core
	 *
	 * @param unknown_type $default
	 * @param unknown_type $parent
	 * @param unknown_type $level
	 * @return unknown
	 */
	public static function page_settings_dropdown( $default = 0, $parent = 0, $level = 0 ) {
		global $wpdb;
		$items = $wpdb->get_results( $wpdb->prepare("SELECT ID, post_parent, post_title FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'page' AND post_status != 'trash' ORDER BY menu_order", $parent) );

		if ( $items ) {
			foreach ( $items as $item ) {
				$pad = str_repeat( '&nbsp;', $level * 3 );
				if ( $item->ID == $default)
					$current = ' selected="selected"';
				else
					$current = '';

				echo "\n\t<option class='level-$level' value='$item->ID'$current>$pad " . esc_html($item->post_title) . "</option>";
				parent_dropdown( $default, $item->ID, $level +1 );
			}
		} else {
			return false;
		}
	}


} //ends Forms_Admin_Page class
