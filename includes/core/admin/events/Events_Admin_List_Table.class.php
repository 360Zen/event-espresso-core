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
 * @version		3.2.P
 *
 * ------------------------------------------------------------------------
 *
 * Events_Admin_List_Table
 *
 * Class for preparing the table listing all the events
 *
 * note: anywhere there are no php docs it is because the docs are available in the parent class.
 *
 * @package		Events_Admin_List_Table
 * @subpackage	includes/core/admin/events/Events_Admin_List_Table.class.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */

class Events_Admin_List_Table extends EE_Admin_List_Table {

	private $_event_status;

	public function __construct( $admin_page ) {
		parent::__construct($admin_page);
		require_once( 'EE_DTT_Helper.helper.php' );
	}

	protected function _setup_data() {
		$this->_per_page = $this->get_items_per_page($this->_screen . '_per_page');
		$this->_data = $this->_admin_page->get_events($this->_per_page, $this->_current_page);
 		$this->_all_data_count = $this->_admin_page->get_events( $this->_per_page, $this->_current_page, TRUE );
	}

	protected function _set_properties() {
		$this->_wp_list_args = array(
			'singular' => __('event', 'event_espresso'),
			'plural' => __('events', 'event_espresso'),
			'ajax' => TRUE, //for now
			'screen' => $this->_admin_page->get_current_screen()->id
			);


		$this->_columns = array(
			'cb' => '<input type="checkbox" />',
			'id' => __('ID', 'event_espresso'),
			'name' => __('Name', 'event_espresso'),
			'venue' => __('Venue', 'event_espresso'),
			'start_date' => __('Start Date', 'event_espresso'),
			'start_time' => __('Start Time', 'event_espresso'),
			'reg_begins' => __('Reg Begins', 'event_espresso'),
			'status' => __('Status', 'event_espresso'),
			'attendees' => __('Attendees', 'event_espresso'),
			'actions' => __('Actions', 'event_espresso')
			);


		$this->_sortable_columns = array(
			'id' => array( 'e.id' => true ),
			'name' => array( 'e.event_name' => false ),
			'venue' => array( 'v.name' => false ),
			'start_date' => array('dtt.DTT_EVT_start' => false),
			'start_time' => array('dtt.DTT_EVT_start' => false),
			'reg_begins' => array('dtt.DTT_REG_start' => false),
			'status' => array('e.event_status' => false)
			);

		$this->_hidden_columns = array();
	}


	protected function _get_table_filters() {
		$filters = array();

		//todo we're currently using old functions here. We need to move things into the Events_Admin_Page() class as methods.
		require_once EE_CORE_ADMIN . 'admin_helper.php';

		//first month/year filters
		$filters[] = $this->_admin_page->espresso_event_months_dropdown( isset($this->_req_data['month_range']) ? $this->_req_data['month_range'] : '' );

		//category filter
		$filters[] = espresso_category_dropdown( isset($this->_req_data['category_id']) ? $this->_req_data['category_id'] : '' );

		//status dropdown
		$status = array(array('id' => '', 'text' => __('Show Active/Inactive', 'event_espresso')), array('id' => 'A', 'text' => __('Active', 'event_espresso')), array('id' => 'IA', 'text' => __('Inactive', 'event_espresso')), array('id' => 'P', 'text' => __('Pending', 'event_espresso')), array('id' => 'R', 'text' => __('Draft', 'event_espresso')), array('id' => 'S', 'text' => __('Waitlist', 'event_espresso')), array('id' => 'O', 'text' => __('Ongoing', 'event_espresso')), array('id' => 'X', 'text' => __('Denied', 'event_espresso')), array('id' => 'D', 'text' => __('Deleted', 'event_espresso')));
		
		$this->_event_status = isset($this->_req_data['event_status']) ? $this->_req_data['event_status'] : '';

		$filters[] = select_input( 'event_status', $status, $this->_event_status );
		return $filters;	
	}





	protected function _add_view_counts() {
		$this->_views['all']['count'] = $this->_admin_page->total_events();
		$this->_views['today']['count'] = $this->_admin_page->total_events_today();
		$this->_views['month']['count'] = $this->_admin_page->total_events_this_month();
	}







	public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="EVT_IDs[]" value="%s" />', $item->event_id
        );    
    }



	public function column_default($item) {
		return isset( $item->$column_name ) ? $item->$column_name : '';
	}




	public function column_id($item) {
		return $item->event_id;
	}



	public function column_name($item) {
		//todo: remove when attendees is active
		if ( !defined('REG_ADMIN_URL') )
			define('REG_ADMIN_URL', EVENTS_ADMIN_URL);

		$edit_query_args = array(
				'action' => 'edit_event',
				'EVT_ID' => $item->event_id
			);
		$edit_link = EE_Admin_Page::add_query_args_and_nonce( $edit_query_args, EVENTS_ADMIN_URL );

		$attendees_query_args = array(
				'action' => 'default',
				'event_id' => $item->event_id
			);
		$attendees_link = EE_Admin_Page::add_query_args_and_nonce( $attendees_query_args, REG_ADMIN_URL );

		$export_query_args = array(
				'action' => 'export_events',
				'EVT_ID' => $item->event_id
			);
		$export_event_link = EE_Admin_Page::add_query_args_and_nonce( $export_query_args, EVENTS_ADMIN_URL );

		$trash_event_query_args = array(
				'action' => 'trash_event',
				'EVT_ID' => $item->event_id
			);
		$trash_event_link = EE_Admin_Page::add_query_args_and_nonce( $trash_event_query_args, EVENTS_ADMIN_URL );

		$restore_event_query_args = array(
				'action' => 'restore_event',
				'EVT_ID' => $item->event_id
			);
		$restore_event_link = EE_Admin_Page::add_query_args_and_nonce( $restore_event_query_args, EVENTS_ADMIN_URL );

		$delete_event_query_args = array(
				'action' => 'delete_event',
				'EVT_ID' => $item->event_id
			);
		$delete_event_link = EE_Admin_Page::add_query_args_and_nonce( $delete_event_query_args, EVENTS_ADMIN_URL );

		$view_link = espresso_reg_url( $item->event_id, $item->slug );

		$actions = array(
			'view' => '<a href="' . $view_link . '" title="' . __('View Event', 'event_espresso') . '">' . __('View', 'event_espresso') . '</a>',
			'edit' => '<a href="' . $edit_link . '" title="' . __('Edit Event', 'event_espresso') . '">' . __('Edit', 'event_espresso') . '</a>',
			'attendees' => '<a href="' . $attendees_link . '" title="' . __('View Attendees', 'event_espresso') . '">' . __('Attendees', 'event_espresso') . '</a>',
			'export' => '<a href="' . $export_event_link . '" title="' . __('Export Event', 'event_espresso') . '">' . __('Export', 'event_espresso') . '</a>'
			);
			
		switch ( $this->_event_status ) {
			case 'D' :
					$actions['restore from trash'] = '<a href="' . $restore_event_link . '" title="' . __('Restore from Trash', 'event_espresso') . '">' . __('Restore from Trash', 'event_espresso') . '</a>';
					$actions['delete permanently'] = '<a href="' . $delete_event_link . '" title="' . __('Delete Permanently', 'event_espresso') . '">' . __('Delete Permanently', 'event_espresso') . '</a>';
				break;
			default :
					$actions['move to trash'] = '<a href="' . $trash_event_link . '" title="' . __('Trash Event', 'event_espresso') . '">' . __('Move to Trash', 'event_espresso') . '</a>';
		}

		$content = '<strong><a class="row-title" href="' . $edit_link . '">' . stripslashes_deep($item->event_name) . '</a></strong>';
		$content .= $this->row_actions($actions);
		return $content;

	}


	



	
	public function column_venue($item) {
		return $item->venue_title;
	}




	public function column_start_date($item) {
		return EE_DTT_Helper::prepare_dtt_from_db( $item->DTT_EVT_start, 'D, M d, Y' );
	}




	public function column_start_time($item) {
		return EE_DTT_Helper::prepare_dtt_from_db( $item->DTT_EVT_start, get_option( 'time_format' ) );
	}




	public function column_reg_begins($item) {
		require_once( EVENT_ESPRESSO_INCLUDES_DIR . 'models/EEM_Datetime.model.php' );
		$DTM = EEM_Datetime::instance();
		// grab reg times
		$reg_time = array_shift( $DTM->get_primary_reg_date_for_event( $item->event_id ));
		$reg_start = $reg_time->reg_start();
		$reg_end = $reg_time->reg_end();

		$registration_start = isset($reg_start) ? $reg_start : '';
		return date('D, M d, Y @ g:i a', $registration_start);
	}




	public function column_status($item) {
		$status = array();
		$status = event_espresso_get_is_active($item->event_id);
		return $status['display'] == 'OPEN' ? '<span style="color:green;"><b>' . $status['display'] . '</b></span>' : $status['display'];
	}




	public function column_attendees($item) {
		$attendees_query_args = array(
				'action' => 'default',
				'event_id' => $item->event_id
			);
		$attendees_link = EE_Admin_Page::add_query_args_and_nonce( $attendees_query_args, REG_ADMIN_URL );	
		//require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'models/EEM_Attendee.model.php');
//		$registered_attendees = EEM_Attendee::instance()->get_event_attendees( $item->event_id, FALSE, FALSE, FALSE, 'REG_date', 'DESC', FALSE, 'COUNT' );
//		$registered_attendees = $registered_attendees ? $registered_attendees : 0;
//		return '<a href="' . $attendees_link . '">' . $registered_attendees . '/' . $item->reg_limit . '</a>';
		$registered_attendees = get_number_of_attendees_reg_limit( $item->EVT_ID, 'num_attendees_slash_reg_limit', $item->reg_limit ); 
		return '<a href="' . $attendees_link . '">' . $registered_attendees . '</a>';
	}



	public function column_actions($item) {
		//todo: remove when attendees is active
		if ( !defined('REG_ADMIN_URL') )
			define('REG_ADMIN_URL', EVENTS_ADMIN_URL);

		$edit_query_args = array(
				'action' => 'edit_event',
				'event_id' => $item->event_id
			);

		$trash_event_query_args = array(
				'action' => 'trash_event',
				'EVT_ID' => $item->event_id
			);

		$attendees_query_args = array(
				'action' => 'default',
				'event_id' => $item->event_id
			);

		$reports_query_args = array(
				'action' => 'reports',
				'EVT_ID' => $item->event_id
			);

		$export_query_args = array(
				'action' => 'export_events',
				'EVT_ID' => $item->event_id
			);


		$edit_link = EE_Admin_Page::add_query_args_and_nonce( $edit_query_args, EVENTS_ADMIN_URL );
		$view_link = espresso_reg_url( $item->event_id, $item->slug );
		$trash_event_link = EE_Admin_Page::add_query_args_and_nonce( $trash_event_query_args, EVENTS_ADMIN_URL );
		$attendees_link = EE_Admin_Page::add_query_args_and_nonce( $attendees_query_args, REG_ADMIN_URL );
		$reports_link = EE_Admin_Page::add_query_args_and_nonce( $reports_query_args, REG_ADMIN_URL );
		$export_event_link = EE_Admin_Page::add_query_args_and_nonce( $export_query_args, EVENTS_ADMIN_URL );
		
		$content = '<a href="' .  $view_link . '" title="' . __('View Event', 'event_espresso') . '" target="_blank">' . "\n\t";
		$content .= '<div class="view_btn"></div></a>' . "\n\t";
		$content .= '<a href="' . $edit_link . '" title="' . __('Edit Event', 'event_espresso') . '"><div class="edit_btn"></div></a>' . "\n\t";
		$content .= '<a href="' . $attendees_link . '" title="' . __('View Attendees', 'event_espresso') . '"><div class="complete_btn"></div></a>' . "\n\t";
		$content .= '<a href="' . $reports_link . '" title="' .  __('View Report', 'event_espresso') . '"><div class="reports_btn"></div></a>' . "\n\t";
		$content .= '<a href="#" onclick="prompt(\'Shortcode:\', jQuery(\'#shortcode-' . $item->event_id . '\').val()); return false;" title="' . __('Get Short URL/Shortcode', 'event_espresso') . '"><div class="shortcode_btn"></div></a>' . "\n\t";
		$content .= '<a href="'.$export_event_link.'" title="' . __('Export to CSV', 'event_espresso') . '"><div class="csv_exp_btn"></div></a>' . "\n";
		$content .= '
		<a href="' . $trash_event_link . '" title="' . __('Trash Event', 'event_espresso') . '">
			<img width="16" height="16" alt="trash" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/trash-small-16x16.png" style="margin-top:3px;">			
		</a>' . "\n\t";
		
		//todo: we need to put back in a email attendees link via the new messages system
		$content .= '<div id="unique_id_info_' . $item->event_id . '" style="display:none">' . "\n\t";
		$content .= sprintf( __('<h2>Short URL/Shortcode</h2><p>This is the short URL to this event:</p><p><span  class="updated fade">%s</span></p><p>This will show the registration form for this event just about anywhere. Copy and paste the following shortcode into any page or post.</p><p><span  class="updated fade">[SINGLEEVENT single_event_id="%s"]</span></p> <p class="red_text"> Do not use in place of the main events page that is set in the Organization Settings page.', 'event_espresso'), $view_link, stripslashes_deep($item->event_identifier) );
		$content .= "\n";
		$content .= '</div>';
		$content .= '<input id="shortcode-' . $item->event_id . '" type="hidden" value="[SINGLEEVENT single_event_id='.stripslashes_deep($item->event_identifier).']">';	
		return $content;
	}


}