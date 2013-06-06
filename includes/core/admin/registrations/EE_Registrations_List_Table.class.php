<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			{@link http://eventespresso.com/support/terms-conditions/}   * see Plugin Licensing *
 * @ link			{@link http://www.eventespresso.com}
 * @ since		 	3.2.P
 *
 * ------------------------------------------------------------------------
 *
 * Registrations Table class
 *
 * @package			Event Espresso
 * @subpackage		includes/admin_screens/Registrations_List_Table.class.php
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */


class EE_Registrations_List_Table extends EE_Admin_List_Table {



	private $_status;



	/**
	 * 		constructor
	*/
	function __construct( $admin_page ){
        parent::__construct($admin_page);
        $this->_status = $this->_admin_page->get_registration_status_array();
		require_once ( EE_HELPERS . 'EE_Datetimes.helper' );
	}





	protected function _setup_data() {
		$this->_per_page = $this->get_items_per_page( $this->_screen . '_per_page' );
		$this->_data = $this->_admin_page->get_registrations( $this->_per_page );
		$this->_all_data_count = $this->_admin_page->get_registrations( $this->_per_page, TRUE );
	}




	protected function _set_properties() {
		$this->_wp_list_args = array(
			'singular' => __('registration', 'event_espresso'),
			'plural' => __('registrations', 'event_espresso'),
			'ajax' => TRUE,
			'screen' => $this->_admin_page->get_current_screen()->id
			);

		
		if ( isset( $_GET['event_id'] )) {
			$this->_columns = array(
            	'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
	           	'REG_ID' => __( 'ID', 'event_espresso' ),
				'REG_date' => __( 'Registration Date', 'event_espresso' ),
	           	'ATT_fname' => __( 'Attendee Name', 'event_espresso' ),
				'ATT_email' =>  __('Email Address', 'event_espresso'),
				'REG_count' => __('Att #', 'event_espresso'),
				'REG_code' => __( 'Registration Code', 'event_espresso' ),
				'Reg_status' => __( 'Reg Status', 'event_espresso' ),
	  			'PRC_amount' => __( 'Ticket Price', 'event_espresso' ),
	  			'REG_final_price' => __( 'Final Price', 'event_espresso' ),
	  			'TXN_total' => __( 'Total Txn', 'event_espresso' ),
 				'TXN_paid' => __('Paid', 'event_espresso'),
	           	'actions' => __( 'Actions', 'event_espresso' )
	        );			
		} else {
			$this->_columns = array(
            	'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
	           	'REG_ID' => __( 'ID', 'event_espresso' ),
				'REG_date' => __( 'Registration Date', 'event_espresso' ),
				'event_name' => __( 'Event Title', 'event_espresso' ),
	   	       	'DTT_EVT_start' => __( 'Event Date & Time', 'event_espresso' ),
//	   	       	'DTT_reg_limit' => __( 'Reg Limit', 'event_espresso' ),
				'REG_count' => __('Att #', 'event_espresso'),
	           	'ATT_fname' => __( 'Attendee Name', 'event_espresso' ),
				'REG_code' => __( 'Registration Code', 'event_espresso' ),
				'Reg_status' => __( 'Reg Status', 'event_espresso' ),
	  			'REG_final_price' => __( 'Price', 'event_espresso' ),
            	'actions' => __( 'Actions', 'event_espresso' )
	        );			
		}
		

        $this->_sortable_columns = array(
          	'REG_date' => array( 'REG_date' => TRUE ),   //true means its already sorted
           	'ATT_fname' => array( 'ATT_fname' => FALSE ),
           	'event_name' => array( 'event_name' => FALSE ),
           	'DTT_EVT_start'	=> array( 'DTT_EVT_start' => FALSE ),
           	'Reg_status' => array( 'Reg_status' => FALSE ),
			'REG_ID' => array( 'REG_ID' => FALSE ),
        	);

        $this->_hidden_columns = array();
	}





	protected function _get_table_filters() {
		$filters = array();

		//todo we're currently using old functions here. We need to move things into the Events_Admin_Page() class as methods.
		require_once EE_CORE_ADMIN . 'admin_helper.php';
		require_once EE_FF_HELPER;

		$filters[] = espresso_event_months_dropdown( isset($this->_req_data['month_range']) ? sanitize_key($this->_req_data['month_range']) : '' );
		$filters[] = espresso_category_dropdown( isset($this->_req_data['category_id']) ? sanitize_key( $this->_req_data['category_id'] ) : '' );
		$status = array();
		$status[] = array( 'id' => 0, 'text' => __('Select Status', 'event_espresso') );
		foreach ( $this->_status as $key => $value ) {
                $status[] = array( 'id' => $key, 'text' => $value );
            }
		$filters[] = EE_Form_Fields::select_input('reg_status', $status, isset($this->_req_data['reg_status']) ? strtoupper(sanitize_key( $this->_req_data['reg_status'] ) ): '');

		return $filters;
	}




	protected function _add_view_counts() {
		require_once( EE_CORE_ADMIN . 'total_count_queries.php' );
		$this->_views['all']['count'] = espresso_total_all_attendees();
		$this->_views['month']['count'] = espresso_total_attendees_this_month();
		$this->_views['today']['count'] = espresso_total_attendees_today();
	}








	/**
	 * 		column_default
	*/
   	function column_default( $item, $column_name ){
		return isset( $item->$column_name ) ? $item->$column_name : '';
    }







	/**
	 * 		column_cb
	*/
    function column_cb($item){
        return sprintf( '<input type="checkbox" name="REG_ID[]" value="%1$s" />', $item->REG_ID );
    }





	/**
	 * 		REG_date
	*/
	function column_REG_date($item){
		
		//Build row actions
		$actions = array();

        //Build row actions
 		$check_in_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'event_registrations', 'event_id'=>$item->EVT_ID ), REG_ADMIN_URL );
		$actions['check_in'] = '
			<a href="'.$check_in_url.'" title="' . __( 'The Check In List allows you to easily toggle attendee check in status for this event', 'event_espresso' ) . '">' . __( 'View Attendee Check In List', 'event_espresso' ) . '</a>';
		
		$view_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_registration', '_REG_ID'=>$item->REG_ID ), REG_ADMIN_URL );	
		$REG_date = '<a href="'.$view_lnk_url.'" title="' . __( 'View Registration Details', 'event_espresso' ) . '">' . EE_DTT_Helper::prepare_dtt_from_db( $item->REG_date, 'D M j, Y  g:i a' ) . '</a>';	

		return sprintf('%1$s %2$s', $REG_date, $this->row_actions($actions) );		

	}




	/**
	* 		column_event_name
	*/
	function column_event_name($item){
		
		// page=espresso_events&action=edit_event&EVT_ID=2&edit_event_nonce=cf3a7e5b62
		$edit_event_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'edit_event', 'EVT_ID'=>$item->EVT_ID ), EVENTS_ADMIN_URL );
		$event_name = stripslashes( html_entity_decode( $item->event_name, ENT_QUOTES, 'UTF-8' ));
		$edit_event = '<a href="' . $edit_event_url . '" title="' . sprintf( __( 'Edit Event: %s', 'event_espresso' ), $item->event_name ) .'">' .  wp_trim_words( $event_name, 30, '...' ) . '</a>';
		
		$edit_event_url = EE_Admin_Page::add_query_args_and_nonce( array( 'event_id'=>$item->EVT_ID ), REG_ADMIN_URL );
		$event_name = stripslashes( html_entity_decode( $item->event_name, ENT_QUOTES, 'UTF-8' ));
		$actions['event_filter'] = '<a href="' . $edit_event_url . '" title="' . sprintf( __( 'Filter this list to only show registrations for %s', 'event_espresso' ), $item->event_name ) .'">' .  __( 'Only Show Registrations For This Event', 'event_espresso' ) . '</a>';
		
		return sprintf('%1$s %2$s', $edit_event, $this->row_actions($actions) );		
	}








	/**
	 * 		column_default
	*/
   	function column_DTT_EVT_start($item){
		return EE_DTT_Helper::prepare_dtt_from_db( $item->DTT_EVT_start, 'D M j, Y  g:i a' );
    }





	/**
	 * 		DTT_reg_limit
	*/
	function column_DTT_reg_limit($item){ 
	
		if ( empty( $item->DTT_reg_limit ) || ! is_int( $item->DTT_reg_limit )) {
			$item->DTT_reg_limit = '<span class="big-text">&infin;</span>';
		}
		return $item->DTT_reg_limit;
	}




	/**
	 * 		column_ATT_fname
	*/
   	function column_ATT_fname($item){
		$edit_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'edit_attendee', 'id'=>$item->ATT_ID ), REG_ADMIN_URL );
		$link = '<a href="'.$edit_lnk_url.'" title="' . __( 'View Attendee Details', 'event_espresso' ) . '">' . ucwords( stripslashes( $item->REG_att_name )) . '</a>';
		$link .= $item->REG_count == 1 ? '<img class="primary-attendee-star-img" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/star-8x8.png" width="8" height="8" alt="this is the primary attendee"/>' : '';
		return $link;
	}





	/**
	 * 		column_REG_count
	*/
	function column_REG_count($item){
		return $item->REG_count . __( ' of ', 'event_espresso' ) . $item->REG_group_size;
	}




	/**
	 * 		column_Reg_status
	*/
   	function column_Reg_status($item){
		return '<span class="status-'. $item->REG_status .'">' . str_replace ( '_', ' ', $this->_status[ $item->REG_status ] ) . '</span>';
	}







	/**
	 * 		column_PRC_amount
	*/
	function column_PRC_amount($item){
	
		global $org_options;
		$item->PRC_amount = abs( $item->PRC_amount );
		
		if ( $item->PRC_amount > 0 ) {
			return '<span class="reg-pad-rght">' . $org_options['currency_symbol'] . ' ' . number_format( $item->PRC_amount, 2 ) . '</span>';			
		} else {
			// free event
			return '<span class="reg-overview-free-event-spn reg-pad-rght">' . $org_options['currency_symbol'] . __( 'free', 'event_espresso' ) . '</span>';
		}
		
	}






	/**
	 * 		column_REG_final_price
	*/
	function column_REG_final_price($item){
	
		global $org_options;
		$item->REG_final_price = abs( $item->REG_final_price );
		$item->REG_final_price = $item->REG_final_price > 0 ? number_format( $item->REG_final_price, 2 ) : '0.00';
		return '<span class="reg-pad-rght">' . $org_options['currency_symbol'] . ' ' . $item->REG_final_price . '</span>';			
		
	}





	/**
	 * 		column_TXN_total
	*/
	function column_TXN_total($item){
	
		global $org_options;
		$item->TXN_total = abs( $item->TXN_total );
		$item->TXN_total = $item->TXN_total > 0 ? number_format( $item->TXN_total, 2 ) : '0.00';
		
		$view_txn_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_transaction', 'TXN_ID'=>$item->TXN_ID ), TXN_ADMIN_URL );
		return '<span class="reg-pad-rght"><a class="status-'. $item->txn_status .'" href="'.$view_txn_lnk_url.'"  title="' . __( 'View Transaction', 'event_espresso' ) . '">' . $org_options['currency_symbol'] . ' ' . $item->TXN_total . '</a></span>';			
		
	}





	/**
	 * 		column_TXN_paid
	*/
	function column_TXN_paid($item){
		
		if ( $item->REG_count == 1 ) {
			global $org_options;
			$item->TXN_paid = abs( $item->TXN_paid );
			$item->TXN_total = abs( $item->TXN_total );
			
			if ( $item->TXN_paid >= $item->TXN_total ) {
				return '<span class="reg-pad-rght"><img class="" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/check-mark-16x16.png" width="16" height="16" alt="Paid in Full"/></span>';
			} else {
				$view_txn_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_transaction', 'TXN_ID'=>$item->TXN_ID ), TXN_ADMIN_URL );
				$owing = number_format( $item->TXN_paid, 2, '.', ',' );
				return '<span class="reg-pad-rght"><a class="status-'. $item->txn_status .'" href="'.$view_txn_lnk_url.'"  title="' . __( 'View Transaction', 'event_espresso' ) . '">' . $org_options['currency_symbol'] . $owing . '</a><span>';
			}			
		}
		
		return '&nbsp;';
		
	}







	/**
	 * 		column_actions
	*/
	function column_actions($item) {

	        //Build row actions
		$view_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_registration', '_REG_ID'=>$item->REG_ID ), REG_ADMIN_URL );
		$edit_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'edit_attendee', 'id'=>$item->ATT_ID ), REG_ADMIN_URL );
		
		// page=attendees&event_admin_reports=resend_email&registration_id=43653465634&event_id=2&form_action=resend_email
		//$resend_reg_lnk_url_params = array( 'action'=>'resend_registration', '_REG_ID'=>$item->REG_ID );
		$resend_reg_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'resend_registration', '_REG_ID'=>$item->REG_ID ), REG_ADMIN_URL );
		

	        //Build row actions
	        $view_lnk = '
		<li>
			<a href="'.$view_lnk_url.'" title="' . __( 'View Registration Details', 'event_espresso' ) . '">
				<img width="16" height="16" alt="' . __( 'View Registration Details', 'event_espresso' ) . '" src="'. EVENT_ESPRESSO_PLUGINFULLURL .'/images/magnifier.png">
			</a>
		</li>';

	       $edit_lnk = '
		<li>
			<a href="'.$edit_lnk_url.'" title="' . __( 'Edit Attendee Details', 'event_espresso' ) . '">
				<img width="16" height="16" alt="' . __( 'Edit Attendee Details', 'event_espresso' ) . '" src="'. EVENT_ESPRESSO_PLUGINFULLURL .'/images/user_edit.png">
			</a>
		</li>';

	         $resend_reg_lnk = '
		<li>
			<a href="'.$resend_reg_lnk_url.'" title="' . __( 'Resend Registration Details', 'event_espresso' ) . '">
				<img width="16" height="16" alt="' . __( 'Resend Registration Details', 'event_espresso' ) . '" src="'. EVENT_ESPRESSO_PLUGINFULLURL .'/images/email_go.png">
			</a>
		</li>';

	         $dl_tckt_lnk = '
		<li>
			<a href="' . add_query_arg( array( 'ticket_launch'=>'true', '_REG_ID'=>$item->REG_url_link,  'html'=>'true' ), site_url() ) . '" target="_blank" title="' . __( 'Download Ticket', 'event_espresso' ) . '">
				<img width="16" height="16" alt="' . __( 'Download Ticket', 'event_espresso' ) . '" src="'. EVENT_ESPRESSO_PLUGINFULLURL .'/images/ticket-arrow-icon.png">
			</a>
		</li>';

			// page=transactions&action=view_transaction&txn=256&_wpnonce=6414da4dbb
		$view_txn_lnk_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_transaction', 'TXN_ID'=>$item->TXN_ID ), TXN_ADMIN_URL );
		$view_txn_lnk = '
		<li>
			<a href="'.$view_txn_lnk_url.'"  title="' . __( 'View Transaction', 'event_espresso' ) . '">
				<img width="16" height="16" alt="View Transaction" src="'. EVENT_ESPRESSO_PLUGINFULLURL .'/images/money.png">
			</a>
		</li>';

			$actions = '
	<ul class="reg-overview-actions-ul">' . 
	$view_lnk . $edit_lnk . $resend_reg_lnk . /*$dl_tckt_lnk .*/ $view_txn_lnk . '
	</ul>';
			
			return $actions;
				
	}

//UPDATE `wp_esp_registration` SET `REG_count`=2,`REG_group_size`=2 WHERE `REG_count` = 0

}