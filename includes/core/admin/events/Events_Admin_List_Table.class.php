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

	private $_dtt;

	public function __construct( $admin_page ) {
		parent::__construct($admin_page);
		require_once( EE_HELPERS . 'EEH_DTT_helper.helper.php' );
	}

	protected function _setup_data() {
		$this->_per_page = $this->get_items_per_page($this->_screen . '_per_page');
		$this->_data = $this->_admin_page->get_events($this->_per_page, $this->_current_page);
 		$this->_all_data_count = $this->_admin_page->get_events(0,0, TRUE);
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
			'tkts_sold' => __('TKTs sold', 'event_espresso'),
			'actions' => __('Actions', 'event_espresso')
			);


		$this->_sortable_columns = array(
			'id' => array( 'EVT_ID' => true ),
			'name' => array( 'EVT_name' => false ),
			'venue' => array( 'VNU_name' => false ),
			'start_date' => array('Datetime.DTT_EVT_start' => false),
			'start_time' => array('Datetime.DTT_EVT_start' => false),
			'reg_begins' => array('Datetime.Ticket.TKT_start_date' => false),
			//'status' => array('Event.status' => false)
			);

		$this->_hidden_columns = array();
		}


	protected function _get_table_filters() {
		return array(); //no filters with decaf
	}





	protected function _add_view_counts() {
		$this->_views['all']['count'] = $this->_admin_page->total_events();
		$this->_views['draft']['count'] = $this->_admin_page->total_events_draft();
		$this->_views['trash']['count'] = $this->_admin_page->total_trashed_events();
	}







	public function column_cb($item) {
		$this->_dtt = $item->primary_datetime(); //set this for use in other columns

		//does event have any attached registrations?
		$regs = $item->count_related('Registration');
        return $regs > 0 && $this->_view == 'trash' ? '<span class="lock-icon"></span>' : sprintf(
            '<input type="checkbox" name="EVT_IDs[]" value="%s" />', $item->ID()
        );    
    }



	public function column_default($item) {
		return isset( $item->$column_name ) ? $item->$column_name : '';
	}




	public function column_id($item) {
		return $item->ID();
	}



	public function column_name($item) {
		//todo: remove when attendees is active
		if ( !defined('REG_ADMIN_URL') )
			define('REG_ADMIN_URL', EVENTS_ADMIN_URL);

		$edit_query_args = array(
				'action' => 'edit',
				'post' => $item->ID()
			);
		$edit_link = EE_Admin_Page::add_query_args_and_nonce( $edit_query_args, EVENTS_ADMIN_URL );

		$attendees_query_args = array(
				'action' => 'default',
				'event_id' => $item->ID()
			);
		$attendees_link = EE_Admin_Page::add_query_args_and_nonce( $attendees_query_args, REG_ADMIN_URL );

		$export_query_args = array(
				'action' => 'export_events',
				'EVT_ID' => $item->ID()
			);
		$export_event_link = EE_Admin_Page::add_query_args_and_nonce( $export_query_args, EVENTS_ADMIN_URL );

		$trash_event_query_args = array(
				'action' => 'trash_event',
				'EVT_ID' => $item->ID()
			);
		$trash_event_link = EE_Admin_Page::add_query_args_and_nonce( $trash_event_query_args, EVENTS_ADMIN_URL );

		$restore_event_query_args = array(
				'action' => 'restore_event',
				'EVT_ID' => $item->ID()
			);
		$restore_event_link = EE_Admin_Page::add_query_args_and_nonce( $restore_event_query_args, EVENTS_ADMIN_URL );

		$delete_event_query_args = array(
				'action' => 'delete_event',
				'EVT_ID' => $item->ID()
			);
		$delete_event_link = EE_Admin_Page::add_query_args_and_nonce( $delete_event_query_args, EVENTS_ADMIN_URL );

		$view_link = get_permalink($item->ID());

		$actions = array(
			'view' => '<a href="' . $view_link . '" title="' . __('View Event', 'event_espresso') . '">' . __('View', 'event_espresso') . '</a>',
			'edit' => '<a href="' . $edit_link . '" title="' . __('Edit Event', 'event_espresso') . '">' . __('Edit', 'event_espresso') . '</a>',
			'attendees' => '<a href="' . $attendees_link . '" title="' . __('View Attendees', 'event_espresso') . '">' . __('Attendees', 'event_espresso') . '</a>',
			'export' => '<a href="' . $export_event_link . '" title="' . __('Export Event', 'event_espresso') . '">' . __('Export', 'event_espresso') . '</a>'
			);
			
		switch ( $item->get( 'status' ) ) {
			case 'trash' :
					$actions['restore from trash'] = '<a href="' . $restore_event_link . '" title="' . __('Restore from Trash', 'event_espresso') . '">' . __('Restore from Trash', 'event_espresso') . '</a>';
					$actions['delete permanently'] = '<a href="' . $delete_event_link . '" title="' . __('Delete Permanently', 'event_espresso') . '">' . __('Delete Permanently', 'event_espresso') . '</a>';
				break;
			default :
					$actions['move to trash'] = '<a href="' . $trash_event_link . '" title="' . __('Trash Event', 'event_espresso') . '">' . __('Move to Trash', 'event_espresso') . '</a>';
		}

		$status = $item->status() !== 'publish' ? ' (' . $item->status() . ')' : '';
		$content = '<strong><a class="row-title" href="' . $edit_link . '">' . $item->name() . '</a></strong>' . $status;
		$content .= $this->row_actions($actions);
		return $content;

	}


	



	
	public function column_venue($item) {
		$venue = $item->get_first_related( 'Venue' );
		return !empty( $venue ) ? $venue->name() : __('No Venue attached', 'event_espresso');
	}




	public function column_start_date($item) {
		!empty( $this->_dtt ) ? $this->_dtt->e_start_date( 'D, M d, Y') : _e('No Date was saved for this Event', 'event_espresso');
		//display in user's timezone?
		echo !empty( $this->_dtt ) ? $this->_dtt->display_in_my_timezone('DTT_EVT_start', 'get_date', 'D, M d, Y', 'My Timezone: ' ) : '';

	}




	public function column_start_time($item) {
		!empty( $this->_dtt ) ? $this->_dtt->e_start_time( get_option( 'time_format' ) ) : _e('No Date was saved for this Event', 'event_espresso');
		//display in user's timezone?
		echo !empty( $this->_dtt ) ? $this->_dtt->display_in_my_timezone( 'DTT_EVT_start', 'get_time', get_option('time_format'), 'My Timezone: ' ) : '';
	}




	public function column_reg_begins($item) {
		$reg_start = $item->get_ticket_with_earliest_start_time();
		!empty( $reg_start ) ? $reg_start->e_datetime('TKT_start_date', 'D, M d, Y', 'g:i a') : _e('No Tickets have been setup for this Event', 'event_espresso');
		//display in user's timezone?
		echo !empty( $reg_start ) ? $reg_start->display_in_my_timezone('TKT_start_date', 'get_datetime', array('D, M d, Y', 'g:i a'), 'My Timezone: ' ) : '';/**/
	}




	public function column_status($item) {
		$item->pretty_active_status();
	}




	public function column_attendees($item) {
		$attendees_query_args = array(
				'action' => 'default',
				'event_id' => $item->ID()
			);
		$attendees_link = EE_Admin_Page::add_query_args_and_nonce( $attendees_query_args, REG_ADMIN_URL );	
		$registered_attendees = EEM_Registration::instance()->get_event_registration_count( $item->ID() ); 
		return '<a href="' . $attendees_link . '">' . $registered_attendees . '</a>';
	}



	public function column_tkts_sold($item) {
		return EEM_Ticket::instance()->sum(array( array('Datetime.EVT_ID' => $item->ID() )), 'TKT_sold' );
	}



	public function column_actions($item) {
		//todo: remove when attendees is active
		if ( !defined('REG_ADMIN_URL') )
			define('REG_ADMIN_URL', EVENTS_ADMIN_URL);
		$actionlinks = array();

		$edit_query_args = array(
				'action' => 'edit',
				'post' => $item->ID()
			);

		$trash_event_query_args = array(
				'action' => 'trash_event',
				'EVT_ID' => $item->ID()
			);

		$attendees_query_args = array(
				'action' => 'default',
				'event_id' => $item->ID()
			);

		$reports_query_args = array(
				'action' => 'reports',
				'EVT_ID' => $item->ID()
			);

		$export_query_args = array(
				'action' => 'export_events',
				'EVT_ID' => $item->ID()
			);


		$edit_link = EE_Admin_Page::add_query_args_and_nonce( $edit_query_args, EVENTS_ADMIN_URL );
		$view_link = get_permalink($item->ID());
		$trash_event_link = EE_Admin_Page::add_query_args_and_nonce( $trash_event_query_args, EVENTS_ADMIN_URL );
		$attendees_link = EE_Admin_Page::add_query_args_and_nonce( $attendees_query_args, REG_ADMIN_URL );
		$reports_link = EE_Admin_Page::add_query_args_and_nonce( $reports_query_args, REG_ADMIN_URL );
		$export_event_link = EE_Admin_Page::add_query_args_and_nonce( $export_query_args, EVENTS_ADMIN_URL );

		$actionlinks[] = '<a href="' .  $view_link . '" title="' . __('View Event', 'event_espresso') . '" target="_blank">';
		$actionlinks[] = '<div class="view_btn"></div></a>';
		$actionlinks[] = '<a href="' . $edit_link . '" title="' . __('Edit Event', 'event_espresso') . '"><div class="edit_btn"></div></a>';
		$actionlinks[] = '<a href="' . $attendees_link . '" title="' . __('View Attendees', 'event_espresso') . '"><div class="complete_btn"></div></a>';
		$actionlinks[] = '<a href="' . $reports_link . '" title="' .  __('View Report', 'event_espresso') . '"><div class="reports_btn"></div></a>' . "\n\t";
		$actionlinks[] = '<a href="#" onclick="window.location=\'' . $export_event_link . '\'" title="' . __('Export to Excel', 'event_espresso') . '"><div class="excel_exp_btn"></div></a>';
		$actionlinks[] = '<a href="#" onclick="window.location=\'' . $export_event_link . '\'" title="' . __('Export to CSV', 'event_espresso') . '"><div class="csv_exp_btn"></div>
			</a>';
		$actionlinks[] = '<a href="' . $trash_event_link . '" title="' . __('Trash Event', 'event_espresso') . '">
				<img width="16" height="16" alt="trash" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/trash-small-16x16.png" style="margin-top:3px;">			
			</a>' . "\n\t";

		$actionlinks = apply_filters('FHEE_list_table_events_actions_column_action_links', $actionlinks, $item );

		$content = '<div style="width:100%;">' . "\n\t";
		$content .= implode( "\n\t", $actionlinks );
		//todo: we need to put back in a email attendees link via the new messages system
		$content .= "\n" . '</div>' . "\n";	
		return $content;
	}


}
