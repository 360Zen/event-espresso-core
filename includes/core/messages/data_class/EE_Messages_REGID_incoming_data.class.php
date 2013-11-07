<?php

if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version		4.0
 *
 * ------------------------------------------------------------------------
 *
 * EE_Messages_REGID_incoming_data
 *
 * This prepares dummy data for all messages previews run in the back end.  The Preview Data is going to use a given event id for the data.  If that event is NOT provided then we'll retrieve the first three published events from the users database as a sample (or whatever is available if there aren't three).
 *
 * To assemble the preview data, I basically used the EE_Single_Page_Checkout class to server as a guide for what data objects are setup etc.  Any place there is input expected from registrants we just setup some dummy inputs.  Remember none of this is actually saved to the database.  It is all one time use for any generated previews.
 *
 * @package		Event Espresso
 * @subpackage	includes/core/messages/data_class/EE_Messages_REGID_incoming_data.core.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */

class EE_Messages_REGID_incoming_data extends EE_Messages_incoming_data {

	//some specific properties we need for this class
	private $_events = array();
	private $_attendees = array();

	//will hold any incoming data that might be available here
	private $_reg_id;

	//for models used in here
	private $_EEM_att;
	private $_EEM_reg;


	//hold objects that might be created
	public $reg_obj;


	/**
	 * For the constructor of this special preview class. 
	 *
	 * The data is expected to be an array that came from the $_POST and $_GET and should have at least one property from the list looked for.
	 * 
	 * @param array $data
	 */
	public function __construct( $data ) {
		
		//make sure data is an array.
		if ( !is_array($data) ) return FALSE;

		//assign properties
		$this->_reg_id = isset($data['_REG_ID']) ? $data['_REG_ID'] : NULL;

		//if all of the above are NULL we can't do anything so get out!
		if ( empty( $this->_reg_id ) ) return FALSE;

		//require the models we need;
		$this->_EEM_att = EEM_Attendee::instance();	
		$this->_EEM_reg = EEM_Registration::instance();

		//made it here so lets continue!
		$this->_setup_attendees_events();
		parent::__construct($data);
	}



	/**
	 * This will just setup the _events property in the expected format.
	 * @return void
	 */
	private function _setup_attendees_events() {

		$events = $this->_get_some_events();


		//now let's loop and set up the _events property.  At the same time we'll set up attendee properties.

		//a variable for tracking totals
		$running_total = 0;

		//get txn
		$this->txn = $this->reg_obj->transaction();

		//get reg_objs for txn
		$this->reg_objs = $this->txn->registrations();

		//we'll actually use the generated line_item identifiers for our loop
		foreach( $events as $id => $event ) {
			$line_item = $id . '_reg';
			$this->_events[$line_item]['ID'] = $id;
			$this->_events[$line_item]['line_ref'] = $line_item;
			$this->_events[$line_item]['name'] = $event->get('EVT_name');

			$daytime_id = $event->get_first_related('Datetime')->ID();
			$this->_events[$line_item]['daytime_id'] = $daytime_id;
			
			$TKT = $this->reg_obj->get_first_related('Ticket');

			$this->_events[$line_item]['ticket_obj'] = $TKT;
			$this->_events[$line_item]['ticket_price'] = $TKT->get_ticket_subtotal();
			$this->_events[$line_item]['ticket_id'] = $TKT->ID();
			$this->_events[$line_item]['ticket_desc'] = $TKT->get('TKT_description');
			$this->_events[$line_item]['pre_approval'] = $event->get('EVT_require_pre_approval'); 
			$this->_events[$line_item]['meta'] = array();

			
			$att_count = 0;
			if ( !empty( $this->reg_objs ) ) {
				foreach ( $this->reg_objs as $reg ) {
					$this->_attendees[$att_count]['line_ref'][] = $line_item;
					$this->_attendees[$att_count]['att_obj'] = $this->_EEM_att->get_one_by_ID( $reg->attendee_ID() );
					$this->_attendees[$att_count]['reg_objs'][$id] = $reg;
					$att_count++;
				}
			}

			$line_total = $att_count * $TKT->get_ticket_subtotal() ;
			$this->_events[$line_item]['line_total'] = $line_total;
			$running_total = $running_total + $line_total;

		}

		$this->_running_total = $running_total;

	}



	/**
	 * Return an array of event objects from the database
	 * 
	 * @return array    An array of event objects from the db.
	 */
	private function _get_some_events() {
		global $wpdb;
		$events = array();

		$this->reg_obj = $this->_EEM_reg->get_one_by_ID( $this->_reg_id );
		$events = $this->reg_obj->get_many_related('Event');
		
		return $events;
	}






	protected function _setup_data() {

		$payment = $this->txn->get_first_related('Payment');

		$this->taxes = $this->txn->tax();
		$grand_total = $this->txn->total();
		$this->billing = !empty( $payment ) ? $payment->details() : array();


		//events and attendees
		$this->events = $this->_events;
		$this->attendees = $this->_attendees;

		//setup primary attendee property
		$this->primary_attendee = array(
			'fname' => $this->_attendees[0]['att_obj']->fname(),
			'lname' => $this->_attendees[0]['att_obj']->lname(),
			'email' => $this->_attendees[0]['att_obj']->email()
			);

		//reg_info property
		//note this isn't referenced by any shortcode parsers so we'll ignore for now.
		$this->reg_info = array();

		//get txn session data
		$session = $this->txn->session_data()->get_session_data();

		$this->user_id = isset($session['user_id']) ? $session['user_id'] : NULL;
		$this->ip_address =	isset($session['ip_address']) ? $session['ip_address'] : NULL;
		$this->user_agent = isset($session['user_agent']) ? $session['user_agent'] : NULL;
		$this->init_access = isset($session['init_access']) ? $session['init_access'] : NULL;
		$this->last_access = isset($session['last_access']) ? $session['last_access'] : NULL;

	}


} //end EE_Messages_REGID_incoming_data class