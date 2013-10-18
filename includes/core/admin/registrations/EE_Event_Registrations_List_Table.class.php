<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');


class EE_Event_Registrations_List_Table extends EE_Admin_List_Table {

	/**
	 * This property will hold the related Datetimes on an event IF the event id is included in the request.
	 * @var EE_Datetime[]
	 */
	protected $_dtts_for_event = array();


	public function __construct( $admin_page ) {
		parent::__construct($admin_page);
        $this->_status = $this->_admin_page->get_registration_status_array();
	}




	protected function _setup_data() {
		$this->_per_page = $this->get_items_per_page( $this->_screen . '_per_page' );
		$this->_data = $this->_view != 'trash' ? $this->_admin_page->get_event_attendees( $this->_per_page ) : $this->_admin_page->get_event_attendees( $this->_per_page, FALSE, TRUE );
		$this->_all_data_count = $this->_view != 'trash' ? $this->_admin_page->get_event_attendees(  $this->_per_page, TRUE ) : $this->_admin_page->get_event_attendees(  $this->_per_page, TRUE, TRUE );
	}




	protected function _set_properties() {
		$evt_id = isset( $this->_req_data['event_id'] ) ? $this->_req_data['event_id'] : NULL;

		$this->_wp_list_args = array(
			'singular' => __('attendee', 'event_espresso'),
			'plural' => __('attendees', 'event_espresso'),
			'ajax' => TRUE,
			'screen' => $this->_admin_page->get_current_screen()->id
			);

		$columns = array();
		if ( !empty( $evt_id ) )
			$columns['cb'] =  '<input type="checkbox" />'; //Render a checkbox instead of text

		$this->_columns = array(
				'REG_att_checked_in' => __('Check In', 'event_espresso'),
				'REG_count' => __('Att #', 'event_espresso'),
				'ATT_name' =>  __('Attendee', 'event_espresso'),
				'ATT_email' =>  __('Email Address', 'event_espresso'),
				'REG_date' => __('Registration Date', 'event_espresso'),
				'REG_code' => __( 'Registration Code', 'event_espresso' ),
				'Reg_status' => __( 'Reg Status', 'event_espresso' ),
				'REG_final_price' => __('Ticket Price', 'event_espresso'),
				'TXN_paid' => __('Paid', 'event_espresso'),
				'TXN_total' => __('Total', 'event_espresso'),
				'PRC_name' => __('Ticket Option', 'event_espresso')
			);

		$this->_columns = array_merge( $columns, $this->_columns);

		if ( !empty( $evt_id ) ) {
			$this->_columns['actions'] = __('Actions', 'event_espresso');
			$this->_bottom_buttons = array(
			'report'=> array(
				'route' => 'registrations_report',
				'extra_request' => !empty($evt_id) ? array('EVT_ID'=>$evt_id) : NULL
				)
		);
		}

		$this->_sortable_columns = array(
			 //true means its already sorted
			'ATT_name' => array( 'ATT_name' => TRUE ),
			'REG_code' => array( 'REG_code' => TRUE ),
			'REG_date' => array( 'REG_date' => FALSE )
		);

		$this->_hidden_columns = array();
		
		

		$this->_dtts_for_event = !empty($evt_id) ? EEM_Event::instance()->get_one_by_ID($evt_id)->get_many_related('Datetime') : array();

	}





	protected function _get_table_filters() {
		$filters = array();
		EE_Registry::instance()->load_helper( 'Form_Fields' );

		if ( empty( $this->_dtts_for_event ) ) {
			//this means we don't have an event so let's setup a filter dropdown for all the events to select
			$events = EEM_Event::instance()->get_all(array(array(), 'order_by' => array( 'EVT_name' => 'asc' ) ) );
			$evts[] = array('id' => 0, 'text' => __('To toggle checkin status for attendees, select an event', 'event_espresso') );
			foreach ( $events as $evt ) {
				//any registrations for this event?
				if ( ! $evt->get_count_of_all_registrations() )
					continue;
				$evts[] = array( 'id' => $evt->ID(), 'text' => $evt->get('EVT_name') );
			}
			$filters[] = EEH_Form_Fields::select_input( 'event_id', $evts );
			
		} else {
			//DTT datetimes filter
			$cur_dtt = isset( $this->_req_data['DTT_ID'] ) ? $this->_req_data['DTT_ID'] : NULL;
			$dtts = array();
			foreach ( $this->_dtts_for_event as $dtt ) {
				$datetime_string = $dtt->start_date_and_time('D M j, Y', ' g:i a') . ' - ' . $dtt->end_date_and_time('D M j, Y', ' g:i a');
				$dtts[] = array('id' => $dtt->ID(), 'text' => $datetime_string );
				$cur_dtt = empty( $cur_dtt ) && $dtt->get('DTT_is_primary') ? $dtt->ID() : $cur_dtt;
			}
			$filters[] = EEH_Form_Fields::select_input('DTT_ID', $dtts, $cur_dtt);
		}

		return $filters;
	}





	protected function _add_view_counts() {
		$this->_views['all']['count'] = $this->_admin_page->get_event_attendees( $this->_per_page, TRUE );
	}





	function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? $item->$column_name : '';
	}





	function column_cb(EE_Registration $item) {
		return sprintf( '<input type="checkbox" name="checkbox[%1$s]" />', $item->ID() );
	}








	/**
	 * 		column_REG_att_checked_in
	*/
	function column_REG_att_checked_in(EE_Registration $item){
		$DTT_ID = isset( $this->_req_data['DTT_ID'] ) ? $this->_req_data['DTT_ID'] : 0;
		$checkinstatus = $item->check_in_status_for_datetime($DTT_ID);
		$nonce = wp_create_nonce('checkin_nonce');
		$toggle_active = isset ( $this->_req_data['event_id'] ) ? ' clickable trigger-checkin' : '';

		 return '<span class="checkin-icons checkedin-status-' . $checkinstatus . $toggle_active . '" data-regid="' . $item->ID() . '" data-dttid="' . $DTT_ID . '" data-nonce="' . $nonce . '"></span>';
	}





	function column_ATT_name(EE_Registration $item) {
		// edit attendee link
		$edit_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'edit_attendee', 'post'=>$item->attendee_ID() ), REG_ADMIN_URL );
		$name_link = '<a href="'.$edit_lnk_url.'" title="' . __( 'Edit Attendee', 'event_espresso' ) . '">' . $item->attendee()->full_name() . '</a>';
		$name_link .= $item->count() == 1 ? '<img class="primary-attendee-star-img" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/star-8x8.png" width="8" height="8" alt="this is the primary attendee"/>' : '';

		$actions = array();
		$DTT_ID = !empty( $this->_req_data['DTT_ID'] ) ? $this->_req_data['DTT_ID'] : NULL;
		$DTT_ID = empty( $DTT_ID ) && !empty( $this->_req_data['event_id'] ) ? EEM_Event::instance()->get_one_by_ID( $this->_req_data['event_id'] )->primary_datetime()->ID() : $DTT_ID;
		
		if ( !empty($DTT_ID) ) {
			$checkin_list_url = EE_Admin_Page::add_query_args_and_nonce( array('action' => 'registration_checkins', 'REGID' => $item->ID(), 'DTT_ID' => $DTT_ID));
			$actions['checkin'] = '<a href="' . $checkin_list_url . '" title="' . __('Click here to view all the checkins and checkouts for this attendee', 'event_espresso' ) . '">' . __('View Attendee Checkins and Checkouts', 'event_espresso') . '</a>';
		}

		return !empty( $DTT_ID ) ? sprintf( '%1$s %2$s', $name_link, $this->row_actions($actions) ) : $name_link;
	}



	function column_ATT_email( EE_Registration $item ) {
		return $item->get_first_related('Attendee')->email();
	}


	/**
	 * 		column_REG_count
	*/
	function column_REG_count(EE_Registration $item){
		return sprintf(__( '%s of %s', 'event_espresso' ),$item->count(), $item->group_size());
	}


	/**
	 * REG_code
	 * @param  EE_Registration $item EE_Registration object
	 * @return string                Registration code
	 */
	function column_REG_code(EE_Registration $item){
		return $item->get('REG_code');
	}



	/**
	 * 		column_Reg_status
	*/
   	function column_Reg_status(EE_Registration $item){
		return '<span class="status-'. $item->status_ID() .'">' . __( $this->_status[ $item->status_ID() ], 'event_espresso' ) . '</span>';
	}





	function column_REG_date(EE_Registration $item) {
		$view_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_registration', '_REG_ID'=>$item->ID() ), REG_ADMIN_URL );	
		$REG_date = '<a href="'.$view_lnk_url.'" title="' . __( 'View Registration Details', 'event_espresso' ) . '">' . $item->reg_date()./*date( 'D M j, Y  g:i a',	 ) .*/ '</a>';	
		return $REG_date;	
	}





	function column_PRC_name(EE_Registration $item){		
		return $item->ticket() ? $item->ticket()->name() : __("Unknown", "event_espresso");
	}






	/**
	 * 		column_REG_final_price
	*/
	function column_REG_final_price(EE_Registration $item){
		return '<span class="reg-pad-rght">' .  ' ' . $item->pretty_price_paid() . '</span>';	
	}





	/**
	 * 		column_TXN_paid
	*/
	function column_TXN_paid(EE_Registration $item){
	
		if ( $item->count() == 1 ) {
			
			if ( $item->transaction()->paid() >= $item->transaction()->total() ) {
				return '<span class="reg-pad-rght"><img class="" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/check-mark-16x16.png" width="16" height="16" alt="Paid in Full"/></span>';
			} else {
				$view_txn_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_transaction', 'TXN_ID'=>$item->transaction_ID() ), TXN_ADMIN_URL );
				return '
				<span class="reg-pad-rght">
					<a class="status-'. $item->transaction()->status_ID() .'" href="'.$view_txn_lnk_url.'"  title="' . __( 'View Transaction', 'event_espresso' ) . '">
						' . $item->transaction()->pretty_paid(). '
					</a>
				<span>';
			}			
		} else {
			return '<span class="reg-pad-rght"></span>';
		}
		
	}




	/**
	 * 		column_TXN_total
	*/
	function column_TXN_total(EE_Registration $item){	
		if ( $item->get('REG_count') == 1 ) {
			return '<span class="reg-pad-rght">'. $item->transaction()->pretty_paid()  .'</span>';
		} else {
			return '<span class="reg-pad-rght"></span>';
		}		
	}



	function column_actions( EE_Registration $item ) {
		$view_link_url = EE_Admin_Page::add_query_args_and_nonce( array('action' => 'registration_checkins', 'REGID' => $item->ID(), 'DTT_ID' => EEM_Event::instance()->get_one_by_ID( $this->_req_data['event_id'] )->primary_datetime()->ID()));

		$view_lnk = '<li><a href="' . $view_link_url . '" title="' . __('View Attendee Checkin Records', 'event_espresso' ) . '"><img width="16" height="16" alt="' . __( 'View Registration Details', 'event_espresso' ) . '" src="'. EVENT_ESPRESSO_PLUGINFULLURL .'/images/magnifier.png"></a></li>';

		return '<ul class="reg-overview-actions-ul">' . $view_lnk . '</ul>';
	}
}
