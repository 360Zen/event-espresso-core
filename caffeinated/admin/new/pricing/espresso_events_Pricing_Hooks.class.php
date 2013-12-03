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
 * espresso_events_Pricing_Hooks
 * Hooks various messages logic so that it runs on indicated Events Admin Pages.
 * Commenting/docs common to all children classes is found in the EE_Admin_Hooks parent.
 * 
 *
 * @package		espresso_events_Pricing_Hooks
 * @subpackage	caffeinated/admin/new/pricing/espresso_events_Pricing_Hooks.class.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class espresso_events_Pricing_Hooks extends EE_Admin_Hooks {

	protected function _set_hooks_properties() {
		$this->_name = 'pricing';
		//if we were going to add our own metaboxes we'd use the below.
		$this->_metaboxes = array(
			0 => array(
				'page_route' => array('edit','create_new'),
				'func' => 'pricing_metabox',
				'label' => __('Event Tickets & Datetimes', 'event_espresso'),
				'priority' => 'high',
				'context' => 'normal'
				),

			);/**/

		$this->_remove_metaboxes = array(
			0 => array(
				'page_route' => array('edit', 'create_new'),
				'id' => 'espresso_event_editor_tickets',
				'context' => 'normal'
				)
			);

		$this->_scripts_styles = array(
			'registers' => array(
				'ee-tickets-datetimes-css' => array(
					'url' => PRICING_ASSETS_URL . 'event-tickets-datetimes.css',
					'type' => 'css'
					),
				'ee-dtt-ticket-metabox' => array(
					'url' => PRICING_ASSETS_URL . 'ee-datetime-ticket-metabox.js',
					'depends' => array('ee-datepicker', 'ee-dialog', 'underscore')
					)
				),
			'deregisters' => array(
				'event-editor-css' => array('type' => 'css' ),
				'event-datetime-metabox' => array('type' => 'js')
				),
			'enqueues' => array(
				'ee-tickets-datetimes-css' => array( 'edit', 'create_new' ),
				'ee-dtt-ticket-metabox' => array( 'edit', 'create_new' )
				),
			'localize' => array(
				'ee-dtt-ticket-metabox' => array(
					'DTT_TRASH_BLOCK' => array(
						'main_warning' => __('The Datetime you are attempting to trash is the only datetime selected for the following ticket(s):', 'event_espresso'),
						'after_warning' => __('In order to trash this datetime you must first make sure the above ticket(s) are attached to other datetimes.', 'event_espresso'),
						'cancel_button' => '<button class="button-secondary ee-modal-cancel">' . __('Cancel', 'event_espresso') . '</button>',
						'single_warning' => __('The Datetime you are attempting to unattach from this ticket is the only remaining datetime for this ticket. Tickets must always have at least one datetime active on them', 'event_espresso'),
						'dismiss_button' => '<button class="button-secondary ee-modal-cancel">' . __('Dismiss', 'event_espresso') . '</button>'
						),
					'DTT_ERROR_MSG' => array(
						'no_ticket_name' => __('General Admission', 'event_espresso'),
						'dismiss_button' => '<div class="save-cancel-button-container"><button class="button-secondary ee-modal-cancel">' . __('Dismiss', 'event_espresso') . '</button></div>'
						)
					)
				)
			);


		add_action('AHEE__EE_Admin_Page_CPT_core_do_extra_autosave_stuff_Extend_Events_Admin_Page', array( $this, 'autosave_handling' ), 10 );
		add_filter('FHEE_event_editor_update', array( $this, 'caf_updates' ), 10 );

	}



	public function caf_updates( $update_callbacks ) {
		foreach ( $update_callbacks as $key => $callback ) {
			if ( $callback[1] == '_default_tickets_update' )
				unset( $update_callbacks[$key] );
		}

		$update_callbacks[] = array( $this, 'dtt_and_tickets_caf_update' );
		return $update_callbacks;
	}




	/**
	 * Handles saving everything related to Tickets (datetimes, tickets, prices)
	 * @param  EE_Event $evtobj The Event object we're attaching data to
	 * @param  array    $data   The request data from the form
	 * @return bool             success or fail
	 */
	public function dtt_and_tickets_caf_update( $evtobj, $data ) {
		//first we need to start with datetimes cause they are the "root" items attached to events.
		$saved_dtts = $this->_update_dtts( $evtobj, $data );
		//next tackle the tickets (and prices?)
		$success = $this->_update_tkts( $evtobj, $saved_dtts, $data );
	}



	/**
	 * update event_datetimes
	 * @param  EE_Event 	$evt_obj Event being updated
	 * @param  array    	$data    the request data from the form
	 * @return EE_Datetime           array of EE_Datetime ids created/updated.
	 */
	private function _update_dtts( $evt_obj, $data ) {
		$timezone = isset( $data['timezone_string'] ) ? $data['timezone_string'] : NULL;
		$success = TRUE;

		foreach ( $data['edit_event_datetimes'] as $row => $dtt ) {
			$dtt['DTT_EVT_end'] = isset($dtt['DTT_EVT_end']) && ! empty( $dtt['DTT_EVT_end'] ) ? $dtt['DTT_EVT_end'] : $dtt['DTT_EVT_start'];
			$datetime_values = array(
				'DTT_ID' => !empty( $dtt['DTT_ID'] ) ? $dtt['DTT_ID'] : NULL,
				'DTT_EVT_start' => $dtt['DTT_EVT_start'],
				'DTT_EVT_end' => $dtt['DTT_EVT_end'],
				'DTT_reg_limit' => empty( $dtt['DTT_reg_limit'] ) ? INF : $dtt['DTT_reg_limit'],
				'DTT_order' => $row,
				'DTT_is_primary' => !empty( $dtt['DTT_is_primary'] ) ? $dtt["DTT_is_primary"] : 0
				);

			//if we have an id then let's get existing object first and then set the new values.  Otherwise we instantiate a new object for save.
			
			if ( !empty( $dtt['DTT_ID'] ) ) {
				$DTM = EE_Registry::instance()->load_model('Datetime', array($timezone) )->get_one_by_ID($dtt['DTT_ID'] );
				foreach ( $datetime_values as $field => $value ) {
					$DTM->set( $field, $value );
				}

				//make sure the $dtt_id here is saved just in case after the add_relation_to() the autosave replaces it.  We need to do this so we dont' TRASH the parent DTT.
				$saved_dtts[$DTM->ID()] = $DTM;
			} else {
				$DTM = EE_Registry::instance()->load_class('Datetime', array( $datetime_values, $timezone ), FALSE, FALSE );
			}
			
			$DTM->save();			
			$DTT = $evt_obj->_add_relation_to( $DTM, 'Datetime' );

			//before going any further make sure our dates are setup correctly so that the end date is always equal or greater than the start date.
			if( $DTT->get('DTT_EVT_start') > $DTT->get('DTT_EVT_end') ) {
				$DTT->set('DTT_EVT_end', $DTT->get('DTT_EVT_start') );
				EE_Registry::instance()->load_helper('DTT_Helper');
				$DTT = EEH_DTT_helper::date_time_add($DTT, 'DTT_EVT_end', 'days');
				$DTT->save();
			}

			//now we got to make sure we add the new DTT_ID to the $saved_dtts array  because it is possible there was a new one created for the autosave.
			$saved_dtts[$DTT->ID()] = $DTT;
			$saved_dtt_objs[$DTT->get('DTT_order')] = $DTT;

			$success = !$success ? $success : $DTT; //if ANY of these updates fail then we want the appropriate global error message. //todod this is actually sucky we need a better error message but this is what it is for now.
		}

		//now we need to REMOVE any dtts that got deleted.  Keep in mind that this process will only kick in for DTT's that don't have any DTT_sold on them. So its safe to permanently delete at this point.
		$old_datetimes = explode(',', $data['datetime_IDs'] );
		$old_datetimes = $old_datetimes[0] == '' ? array() : $old_datetimes;

		if ( is_array( $old_datetimes ) ) {
			$dtts_to_delete = array_diff( $old_datetimes, array_keys($saved_dtts) );
			foreach ( $dtts_to_delete as $id ) {
				$id = absint( $id );
				if ( empty( $id ) )
					continue;

				$dtt_to_remove = EE_Registry::instance()->load_model('Datetime')->get_one_by_ID($id);

				//remove tkt relationships.
				$related_tickets = $dtt_to_remove->get_many_related('Ticket');
				foreach ( $related_tickets as $tkt ) {
					$dtt_to_remove->_remove_relation_to($tkt, 'Ticket');
				}
				 

				$evt_obj->_remove_relation_to( $id, 'Datetime' );
			}
		}

		return $saved_dtt_objs;
	}






	/**
	 * update tickets
	 * @param  EE_Event         $evtobj     Event object being updated
	 * @param  EE_Datetime[]    $saved_dtts an array of datetime ids being updated
	 * @param  array            $data       incoming request data
	 * @return bool                 		success or fail
	 */
	private function _update_tkts( $evtobj, $saved_dtts, $data ) {
		//stripslashes because WP filtered the $_POST ($data) array to add slashes
		$data = stripslashes_deep($data);
		$timezone = isset( $data['timezone_string'] ) ? $data['timezone_string'] : NULL;
		$success = TRUE;
		$saved_tickets = $dtts_on_existing = array();
		$update_prices = FALSE;
		$new_default = NULL;
		$old_tickets = isset( $data['ticket_IDs'] ) ? explode(',', $data['ticket_IDs'] ) : array();

		foreach ( $data['edit_tickets'] as $row => $tkt ) {

			//figure out what dtts were added to the ticket and what dtts were removed from the ticket in the session.

			$starting_tkt_dtt_rows = explode(',',$data['starting_ticket_datetime_rows'][$row]);
			$tkt_dtt_rows = explode(',', $data['ticket_datetime_rows'][$row] );
			$dtts_added = array_diff($tkt_dtt_rows, $starting_tkt_dtt_rows);
			$dtts_removed = array_diff($starting_tkt_dtt_rows, $tkt_dtt_rows);

			$ticket_price = isset( $tkt['TKT_price'] ) ? $tkt['TKT_price'] : 0;

			$TKT_values = array(
				'TKT_ID' => !empty( $tkt['TKT_ID'] ) ? $tkt['TKT_ID'] : NULL,
				'TTM_ID' => !empty( $tkt['TTM_ID'] ) ? $tkt['TTM_ID'] : 1,
				'TKT_name' => !empty( $tkt['TKT_name'] ) ? $tkt['TKT_name'] : '',
				'TKT_description' => !empty( $tkt['TKT_description'] ) && $tkt['TKT_description'] != __('You can modify this description', 'event_espresso') ? $tkt['TKT_description'] : '',
				'TKT_start_date' => isset( $tkt['TKT_start_date'] ) ? $tkt['TKT_start_date'] : current_time('mysql'),
				'TKT_end_date' => isset( $tkt['TKT_end_date'] ) ? $tkt['TKT_end_date'] : current_time('mysql'),
				'TKT_qty' => empty( $tkt['TKT_qty'] ) ? INF : $tkt['TKT_qty'],
				'TKT_uses' => empty( $tkt['TKT_uses'] ) ? INF : $tkt['TKT_uses'],
				'TKT_min' => empty( $tkt['TKT_min'] ) ? 0 : $tkt['TKT_min'],
				'TKT_max' => empty( $tkt['TKT_max'] ) ? INF : $tkt['TKT_max'],
				'TKT_row' => $row,
				'TKT_order' => isset( $tkt['TKT_order'] ) ? $tkt['TKT_order'] : 0,
				'TKT_taxable' => isset( $tkt['TKT_taxable'] ) ? 1 : 0
				);



			//if this is a default TKT, then we need to set the TKT_ID to 0 and update accordingly, which means in turn that the prices will become new prices as well.
			if ( isset( $tkt['TKT_is_default'] ) && $tkt['TKT_is_default'] ) {
				$TKT_values['TKT_ID'] = 0;
				$TKT_values['TKT_is_default'] = 0;
				$TKT_values['TKT_price'] = $ticket_price;
				$update_prices = TRUE;
			}

			//if we have a TKT_ID then we need to get that existing TKT_obj and update it
			//we actually do our saves ahead of doing any add_relations to because its entirely possible that this ticket didn't removed or added to any datetime in the session but DID have it's items modified.
			//keep in mind that if the TKT has been sold (and we have changed pricing information), then we won't be updating the tkt but instead a new tkt will be created and the old one archived.
			
			if ( !empty( $TKT_values['TKT_ID'] ) ) {
				$TKT = EE_Registry::instance()->load_model( 'Ticket', array( $timezone ) )->get_one_by_ID( $tkt['TKT_ID'] );

				$ticket_sold = $TKT->tickets_sold() > 0 ? true : false;

				//let's just check the total price for the existing ticket and determine if it matches the new total price.  if they are different then we create a new ticket (if tkts sold) if they aren't different then we go ahead and modify existing ticket.
				$create_new_TKT = $ticket_sold && $ticket_price != $TKT->get('TKT_price') && !$TKT->get('TKT_deleted') ? TRUE : FALSE;

				//set new values
				foreach ( $TKT_values as $field => $value ) {
					$TKT->set( $field, $value );
				}

				//if $create_new_TKT is false then we can safely update the existing ticket.  Otherwise we have to create a new ticket. 
				if ( $create_new_TKT ) {

					//we also need to make sure this new ticket gets the same datetime attachments as the archived ticket (and it'll get updated later if there were dtts added or removed in the session)
					$dtts_on_existing = $TKT->get_many_related('Datetime');

					//archive the old ticket
					$TKT->set('TKT_deleted', 1);
					$TKT->save();

					//make sure this ticket is still recorded in our saved_tkts so we don't run it through the regular trash routine.
					$saved_tickets[$TKT->ID()] = $TKT;


					//create new ticket that's a copy of the existing except a new id of course (and not archived) AND has the new TKT_price associated with it.
					$TKT->set( 'TKT_ID', 0 );
					$TKT->set( 'TKT_deleted', 0 );
					$TKT->set( 'TKT_price', $ticket_price );
					$TKT->set( 'TKT_sold', 0 );

					//now we need to make sure that $new prices are created as well and attached to new ticket.
					$update_prices = TRUE; 
				}
				
				//make sure price is set if it hasn't been already
				$TKT->set( 'TKT_price', $ticket_price );

			} else {
				//no TKT_id so a new TKT
				$TKT_values['TKT_price'] = $ticket_price;
				$TKT = EE_Registry::instance()->load_class('Ticket', array( $TKT_values, $timezone ), FALSE, FALSE );
				$update_prices = TRUE;
			}

			//before going any further make sure our dates are setup correctly so that the end date is always equal or greater than the start date.
			if( $TKT->get('TKT_start_date') > $TKT->get('TKT_end_date') ) {
				$TKT->set('TKT_end_date', $TKT->get('TKT_start_date') );
				$TKT = EEH_DTT_helper::date_time_add($TKT, 'TKT_end_date', 'days');
			}

			//possible this is a new ticket because of edited prices when ticket was sold, so let's make sure we attache the datetimes from the archived ticket
			foreach ( $dtts_on_existing as $adddtt ) {
				$TKT = $adddtt->_add_relation_to( $TKT, 'Ticket' );
			}

			$TKT->save();

			$saved_tickets[$TKT->ID()] = $TKT;

			//add prices to ticket
			$TKT = $this->_add_prices_to_ticket( $data['edit_prices'][$row], $TKT, $update_prices );


			//handle CREATING a default tkt from the incoming tkt but ONLY if this isn't an autosave.
			if ( ! defined('DOING_AUTOSAVE' ) ) {
				if ( !empty($tkt['TKT_is_default_selector'] ) ) {
					$new_default = clone $TKT;
					$new_default->set( 'TKT_ID', 0 );
					$new_default->set( 'TKT_is_default', 1 );
					$new_default->set( 'TKT_order', 0 );
					$new_default->set( 'TKT_row', 1 );
					$new_default->set( 'TKT_price', $ticket_price );
					//remove any dtt relations cause we DON'T want dtt relations attached (note this is just removing the cached relations in the object)
					$new_default->_remove_relations('Datetime');
					$new_default->save();
					//todo we need to add the current attached prices as new prices to the new default ticket.
					$new_default = $this->_add_prices_to_ticket($data['edit_prices'][$row], $new_default, $update_prices);
				}
			}

			//now we just have to add the ticket to all the datetimes its supposed to be with and removing the ticket from datetimes it got removed from.
			
			
			//first let's do the add_relation_to()
			$dtts_added = empty( $dtts_added ) || ( is_array( $dtts_added ) && ( isset( $dtts_added[0] ) && $dtts_added[0] == '' ) ) ? array() : $dtts_added;
			foreach ( $dtts_added as $dttrow ) {
				$saved_dtts[$dttrow]->_add_relation_to( $TKT, 'Ticket' );
				$saved_dtts[$dttrow]->save();
			}

			$dtts_removed = empty( $dtts_added ) || ( is_array( $dtts_removed ) && isset( $dtts_removed[0] ) && $dtts_removed[0] == '' ) ? array() : $dtts_removed;
			//now let's do the remove_relation_to()
			foreach ( $dtts_removed as $dttrow ) {
				$saved_dtts[$dttrow]->_remove_relation_to( $TKT, 'Ticket' );
				$saved_dtts[$dttrow]->save();
			}


			//DO ALL dtt relationships for both current tickets and any archived tickets for the given dtt that are related to the current ticket. TODO... not sure exactly how we're going to do this considering we don't know what current ticket the archived tickets are related to (and TKT_parent is used for autosaves so that's not a field we can reliably use).
			
		}

		//now we need to handle tickets actually "deleted permanently".  There are cases where we'd want this to happen (i.e. autosaves are happening and then in between autosaves the user trashes a ticket).  Or a draft event was saved and in the process of editing a ticket is trashed.  No sense in keeping all the related data in the db!
		$old_tickets = isset( $old_tickets[0] ) && $old_tickets[0] == '' ? array() : $old_tickets;
		$tickets_removed = array_diff( $old_tickets, array_keys($saved_tickets) );
		/*var_dump($old_tickets);
		var_dump($saved_tickets);
		var_dump($tickets_removed);*/

		foreach ( $tickets_removed as $id ) {
			$id = absint( $id );

			//get the ticket for this id
			$tkt_to_remove = EE_Registry::instance()->load_model('Ticket')->get_one_by_ID($id);

			//if this tkt is a default tkt we leave it alone cause it won't be attached to the datetime
			if ( $tkt_to_remove->get('TKT_is_default') )
				continue;

			//need to get all the related datetimes on this ticket and remove from every single one of them (remember this process can ONLY kick off if there are NO tkts_sold)
			$dtts = $tkt_to_remove->get_many_related('Datetime');

			foreach( $dtts as $dtt ) {
				$tkt_to_remove->_remove_relation_to($dtt, 'Datetime');
			}

			//need to do the same for prices (except these prices can also be deleted because again, tickets can only be trashed if they don't have any TKTs sold (otherwise they are just archived))
			$tkt_to_remove->delete_related_permanently('Price');
			

			//finally let's delete this ticket (which should not be blocked at this point b/c we've removed all our relationships)
			$tkt_to_remove->delete_permanently();
		}
	}





	/**
	 * This attaches a list of given prices to a ticket.
	 * Note we dont' have to worry about ever removing relationships (or archiving prices) because if there is a change in price information on a ticket, a new ticket is created anyways so the archived ticket will retain the old price info and prices are automatically "archived" via the ticket.
	 *
	 * @access  private
	 * @param array  	$prices  	Array of prices from the form.
	 * @param EE_Ticket $ticket  	EE_Ticket object that prices are being attached to.
	 * @param bool 		$new_prices Whether attach existing incoming prices or create new ones.
	 * @return  void
	 */
	private function  _add_prices_to_ticket( $prices, EE_Ticket $ticket, $new_prices = FALSE ) {
		foreach ( $prices as $row => $prc ) {
			$PRC_values = array(
				'PRC_ID' => !empty( $prc['PRC_ID'] ) ? $prc['PRC_ID'] : NULL,
				'PRT_ID' => !empty( $prc['PRT_ID'] ) ? $prc['PRT_ID'] : NULL,
				'PRC_amount' => !empty( $prc['PRC_amount'] ) ? $prc['PRC_amount'] : 0,
				'PRC_name' => !empty( $prc['PRC_name'] ) ? $prc['PRC_name'] : '',
				'PRC_desc' => !empty( $prc['PRC_desc'] ) ? $prc['PRC_desc'] : '',
				'PRC_order' => $row
				);
			if ( $new_prices || empty( $PRC_values['PRC_ID'] ) ) {
				$PRC_values['PRC_ID'] = 0;
				$PRC = EE_Registry::instance()->load_class('Price', array( $PRC_values ), FALSE, FALSE);
			} else {
				$PRC = EE_Registry::instance()->load_model( 'Price' )->get_one_by_ID( $prc['PRC_ID'] );
				//update this price with new values
				foreach ( $PRC_values as $field => $newprc ) {
					$PRC->set( $field, $newprc );
				}
				$PRC->save();
			}

			$ticket->_add_relation_to( $PRC, 'Price' );
		}
		$ticket->save();
		return $ticket;
	}



	public function autosave_handling( $event_admin_obj ) {
		return $event_admin_obj; //doing nothing for the moment.
		//todo when I get to this remember that I need to set the template args on the $event_admin_obj (use the set_template_args() method)
		
		/**
		 * need to remember to handle TICKET DEFAULT saves correctly:  I've got two input fields in the dom:
		 *
		 * 1. TKT_is_default_selector (visible)
		 * 2. TKT_is_default (hidden)
		 *
		 * I think we'll use the TKT_is_default for recording whether the ticket displayed IS a default ticket (on new event creations). Whereas the TKT_is_default_selector is for the user to indicate they want this ticket to be saved as a default.
		 *
		 * The tricky part is, on an initial display on create or edit (or after manually updating), the TKT_is_default_selector will always be unselected and the TKT_is_default will only be true if this is a create.  However, after an autosave, users will want some sort of indicator that the TKT HAS been saved as a default.. in other words we don't want to remove the check on TKT_is_default_selector. So here's what I'm thinking.
		 * On Autosave:
		 * 1. If TKT_is_default is true: we create a new TKT, send back the new id and add id to related elements, then set the TKT_is_default to false.
		 * 2. If TKT_is_default_selector is true: we create/edit existing ticket (following conditions above as well).  We do NOT create a new default ticket.  The checkbox stays selected after autosave.
		 * 3. only on MANUAL update do we check for the selection and if selected create the new default ticket. 
		 */
	}




	public function pricing_metabox() {
		$existing_datetime_ids = $existing_ticket_ids = $datetime_tickets = $ticket_datetimes = array();

		$evtobj = $this->_adminpage_obj->get_cpt_model_obj();

		//default main template args
		$main_template_args = array(
			'event_datetime_help_link' => EEH_Template::get_help_tab_link('event_date_info', $this->_adminpage_obj->page_slug, $this->_adminpage_obj->get_req_action(), FALSE, FALSE ), //todo need to add a filter to the template for the help text in the Events_Admin_Page core file so we can add further help
			'existing_datetime_ids' => '',
			'total_dtt_rows' => 1,
			'add_new_dtt_help_link' => EEH_Template::get_help_tab_link('add_new_dtt_info', $this->_adminpage_obj->page_slug, $this->_adminpage_obj->get_req_action(), FALSE, FALSE ), //todo need to add this help info id to the Events_Admin_Page core file so we can access it here.
			'datetime_rows' => '',
			'show_tickets_container' => $this->_adminpage_obj->get_cpt_model_obj()->ID() > 1 ? ' style="display:none;"' : '',
			'ticket_rows' => '',
			'existing_ticket_ids' => '',
			'total_ticket_rows' => 1,
			'ticket_js_structure' => '',
			'ee_collapsible_status' => $this->_adminpage_obj->get_cpt_model_obj()->ID() > 0 ? ' ee-collapsible-closed' : ' ee-collapsible-open'
			);

		$event_id = is_object( $evtobj ) ? $evtobj->ID() : NULL;
		$timezone = is_object( $evtobj ) ? $evtobj->timezone_string() : NULL;

		do_action('AHEE_log', __FILE__, __FUNCTION__, '');

		/**
		 * 1. Start with retrieving Datetimes
		 * 2. For each datetime get related tickets
		 * 3. For each ticket get related prices
		 */
		
		$DTM = EE_Registry::instance()->load_model('Datetime', array($timezone) );
		$times = $DTM->get_all_event_dates( $event_id );

		
		
		$main_template_args['total_dtt_rows'] = count($times);
		foreach ( $times as $time ) {
			$dttid = $time->get('DTT_ID');
			$dttrow = $time->get('DTT_order');
			$existing_datetime_ids[] = $dttid;

			//tickets attached
			$related_tickets = $time->ID() > 0 ? $time->get_many_related('Ticket', array( array( 'OR' => array( 'TKT_deleted' => 1, 'TKT_deleted*' => 0 ) ), 'default_where_conditions' => 'none' ) ) : array();

			//if there are no related tickets this is likely a new event so we need to generate the default tickets CAUSE dtts ALWAYS have at least one related ticket!!.
			if ( empty ( $related_tickets ) && empty( $event_id ) ) {
				$related_tickets = EE_Registry::instance()->load_model('Ticket')->get_all_default_tickets();
			}


			//we can't actually setup rows in this loop yet cause we don't know all the unique tickets for this event yet (tickets are linked through all datetimes). So we're going to temporarily cache some of that information.

			//loop through and setup the ticket rows
			foreach ( $related_tickets as $ticket ) {
				$tktid = $ticket->get('TKT_ID');
				$tktrow = $ticket->get('TKT_row');
				//we only want unique tickets in our final display!!
				if ( !in_array( $tktid, $existing_ticket_ids ) ) {
					$existing_ticket_ids[] = $tktid;
					$all_tickets[] = $ticket;
				}
				
				//temporary cache of this ticket info for this datetime for later processing of datetime rows.
				$datetime_tickets[$dttid][] = $tktrow;

				//temporary cache of this datetime info for this ticket for later processing of ticket rows.
				if ( !isset( $ticket_datetimes[$tktid] ) || ! in_array( $dttrow, $ticket_datetimes[$tktid] ) )
					$ticket_datetimes[$tktid][] = $dttrow;
			}
		}

		$main_template_args['total_ticket_rows'] = count( $existing_ticket_ids );
		$main_template_args['existing_ticket_ids'] = implode( ',', $existing_ticket_ids );
		$main_template_args['existing_datetime_ids'] = implode( ',', $existing_datetime_ids );

		//k NOW we have all the data we need for setting up the dtt rows and ticket rows so we start our dtt loop again.
		$dttrow = 1;
		foreach ( $times as $time ) {
			$main_template_args['datetime_rows'] .= $this->_get_datetime_row( $dttrow, $time, $datetime_tickets, $all_tickets, FALSE, $times );
			$dttrow++;
		}

		//then loop through all tickets for the ticket rows.
		$tktrow = 1;
		foreach ( $all_tickets as $ticket ) {
			$main_template_args['ticket_rows'] .= $this->_get_ticket_row( $tktrow, $ticket, $ticket_datetimes, $times, FALSE, $all_tickets );
			$tktrow++;
		}

		$main_template_args['ticket_js_structure'] = $this->_get_ticket_js_structure($times, $all_tickets);
		$template = PRICING_TEMPLATE_PATH . 'event_tickets_metabox_main.template.php';
		EEH_Template::display_template( $template, $main_template_args );
		return;
	}



	private function _get_datetime_row( $dttrow, EE_Datetime $dtt, $datetime_tickets, $all_tickets, $default = FALSE, $all_dtts = array() ) {

		$dtt_display_template_args = array(
			'dtt_display_row' => $this->_get_dtt_display_row( $dttrow, $dtt, $default, $all_dtts ),
			'dtt_edit_row' => $this->_get_dtt_edit_row( $dttrow, $dtt, $default ),
			'dtt_attached_tickets_row' => $this->_get_dtt_attached_tickets_row( $dttrow, $dtt, $datetime_tickets, $all_tickets, $default ),
			'dtt_row' => $default ? 'DTTNUM' : $dttrow
			);
		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_row_wrapper.template.php';
		return EEH_Template::display_template( $template, $dtt_display_template_args, TRUE);
	}	


	private function _get_dtt_display_row( $dttrow, $dtt, $default = FALSE, $all_dtts = array() ) {
		$template_args = array(
			'dtt_row' => $default ? 'DTTNUM' : $dttrow,
			'dtt_name' => $default ? '' : $dtt->get_dtt_display_name(),
			'dtt_sold' => $default ? '0' : $dtt->get('DTT_sold'),
			'clone_icon' => !empty( $dtt ) && $dtt->get('DTT_sold') > 0 ? '' : 'clone-icon clickable',
			'trash_icon' => !empty( $dtt ) && $dtt->get('DTT_sold') > 0  ? 'lock-icon' : 'trash-icon clickable'
			);

		$template_args['show_trash'] = count( $all_dtts ) === 1 && $template_args['trash_icon'] !== 'lock-icon' ? ' style="display:none"' : '';
		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_display_row.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE);
	}


	private function _get_dtt_edit_row( $dttrow, $dtt, $default ) {
		$template_args = array(
			'dtt_row' => $default ? 'DTTNUM' : $dttrow,
			'display_dtt_edit_row' => $this->_adminpage_obj->get_cpt_model_obj()->ID() > 0 ? 'style="display:none;"' : '',
			'event_datetimes_name' => $default ? 'DTTNAMEATTR' : 'edit_event_datetimes',
			'DTT_ID' => $default ? '' : $dtt->ID(),
			'DTT_is_primary' => $default ? '' : $dtt->get('DTT_is_primary'),
			'DTT_EVT_start' => $default ? '' : $dtt->start_date( 'Y-m-d h:i a'),
			'DTT_EVT_end' => $default ? '' : $dtt->end_date( 'Y-m-d h:i a'),
			'DTT_reg_limit' => $default ? '' : $dtt->get_pretty('DTT_reg_limit','input')
			);

		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_edit_row.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE );
	}


	private function _get_dtt_attached_tickets_row( $dttrow, $dtt, $datetime_tickets, $all_tickets, $default ) {
		$template_args = array(
			'dtt_row' => $default ? 'DTTNUM' : $dttrow,
			'datetime_tickets_list' => $default ? '<li class="hidden"></li>' : '',
			'add_new_datetime_ticket_help_link' => EEH_Template::get_help_tab_link('add_new_ticket_via_datetime', $this->_adminpage_obj->page_slug, $this->_adminpage_obj->get_req_action(), FALSE, FALSE ), //todo need to add this help info id to the Events_Admin_Page core file so we can access it here.
			);

		//need to setup the list items (but only if this isnt' a default skeleton setup)
		if ( !$default ) {
			$tktrow = 1;
			foreach ( $all_tickets as $ticket ) {
				$template_args['datetime_tickets_list'] .= $this->_get_datetime_tickets_list_item( $dttrow, $tktrow, $dtt, $ticket, $datetime_tickets, $default );
				$tktrow++;
			}
		}

		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_attached_tickets_row.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE );
	}



	private function _get_datetime_tickets_list_item( $dttrow, $tktrow, $dtt, $ticket, $datetime_tickets, $default ) {
		$tktid = !empty( $ticket ) ? $ticket->ID() : 0;
		$dtt_tkts = $dtt instanceof EE_Datetime && isset( $datetime_tickets[$dtt->ID()] ) ? $datetime_tickets[$dtt->ID()] : array();
		$displayrow = !empty( $ticket ) ? $ticket->get('TKT_row') : 0;
		$template_args = array(
			'dtt_row' => $default ? 'DTTNUM' : $dttrow,
			'tkt_row' => $default && empty( $ticket ) ? 'TICKETNUM' : $tktrow,
			'datetime_ticket_checked' => in_array($displayrow, $dtt_tkts) ? ' checked="checked"' : '',
			'ticket_selected' => in_array($displayrow, $dtt_tkts) ? ' ticket-selected' : '',
			'TKT_name' => $default && empty( $ticket ) ? 'TKTNAME' : $ticket->get('TKT_name')
			);

		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_dtt_tickets_list.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE );
	}




	private function _get_ticket_row( $tktrow, $ticket, $ticket_datetimes, $all_dtts, $default = FALSE, $all_tickets = array() ) {
		$prices = !empty($ticket) && !$default ? $ticket->get_many_related('Price', array('default_where_conditions' => 'none') ) : array();


		// check if we're dealing with a default dtt (in which we want to link the first ticket(s) with it BUT not indicate it as a starting ticket (otherwise there won't be any new relationships created))
		$default_dtt = isset( $all_dtts[0] ) && $all_dtts[0] instanceof EE_Datetime && $all_dtts[0]->ID() === 0 ? TRUE : FALSE;

		$tkt_dtts = $ticket instanceof EE_Ticket && isset( $ticket_datetimes[$ticket->ID()] ) ? $ticket_datetimes[$ticket->ID()] : array();

		$ticket_subtotal = !empty( $ticket ) ? $ticket->get_ticket_subtotal() : 0;
		
		$template_args = array(
			'tkt_row' => $default ? 'TICKETNUM' : $tktrow,
			'display_edit_tkt_row' => $this->_adminpage_obj->get_cpt_model_obj()->ID() > 0 || $default ? ' style="display:none"' : '',
			'edit_tkt_expanded' => $this->_adminpage_obj->get_cpt_model_obj()->ID() > 0 ? '' : ' ee-edit-editing',
			'edit_tickets_name' => $default ? 'TICKETNAMEATTR' : 'edit_tickets',
			'TKT_name' => $default ? '' : $ticket->get('TKT_name'),
			'TKT_start_date' => $default ? '' : $ticket->get_date('TKT_start_date', 'Y-m-d h:i a'),
			'TKT_end_date' => $default ? '' : $ticket->get_date('TKT_end_date', 'Y-m-d h:i a' ),
			'TKT_status' => $default ? '' : $ticket->ticket_status(TRUE),
			'TKT_price' => $default ? '' : EEH_Template::format_currency($ticket->get_ticket_total_with_taxes()),
			'TKT_price_amount' => $default ? 0 : $ticket->get('TKT_price'),
			'TKT_qty' => $default ? '' : $ticket->get_pretty('TKT_qty','symbol'),
			'TKT_qty_for_input'=> $default ? '' : $ticket->get_pretty('TKT_qty','input'),
			'TKT_uses' => $default ? '' : $ticket->get_pretty('TKT_uses','input'),
			'TKT_min' => $default ? '' : ( $ticket->get('TKT_min') === -1 ? '' : $ticket->get('TKT_min') ),
			'TKT_max' => $default ? '' :  $ticket->get_pretty('TKT_max','input'),
			'TKT_sold' => $default ? 0 : $ticket->tickets_sold('ticket'),
			'TKT_ID' => $default ? 0 : $ticket->get('TKT_ID'),
			'TKT_description' => $default ? '' : $ticket->get('TKT_description'),
			'TKT_is_default' => $default ? 0 : $ticket->get('TKT_is_default'),
			'TKT_is_default_selector' => '',
			'ticket_price_rows' => '',
			'total_price_rows' => count($prices),
			'ticket_datetimes_list' => $default ? '<li class="hidden"></li>' : '',
			'starting_ticket_datetime_rows' => $default || $default_dtt ? '' : implode(',', $tkt_dtts),
			'ticket_datetime_rows' => $default ? '' : implode(',', $tkt_dtts),
			'existing_ticket_price_ids' => $default, '', implode(',', array_keys( $prices) ),
			'ticket_template_id' => $default ? 1 : $ticket->get('TTM_ID'),
			'TKT_taxable' => !empty( $ticket ) && $ticket->get('TKT_taxable') ? ' checked="checked"' : '',
			'display_subtotal' => !empty( $ticket ) && $ticket->get('TKT_taxable') ? '' : ' style="display:none"',
			'price_currency_symbol' => EE_Registry::instance()->CFG->currency->sign,
			'TKT_subtotal_amount_display' => EEH_Template::format_currency($ticket_subtotal, FALSE, FALSE ),
			'TKT_subtotal_amount' => $ticket_subtotal,
			'tax_rows' => $this->_get_tax_rows( $tktrow, $ticket ),
			'disabled' => !empty( $ticket ) && $ticket->get('TKT_deleted') ? ' disabled' : '',
			'ticket_archive_class' => !empty( $ticket ) && $ticket->get('TKT_deleted') ? ' ticket-archived' : '',
			'trash_icon' => !empty( $ticket ) && $ticket->get('TKT_deleted') ? 'lock-icon ' : 'trash-icon clickable',
			'clone_icon' => !empty( $ticket ) && $ticket->get('TKT_deleted') ? '' : 'clone-icon clickable'
			);

		$template_args['trash_hidden'] = count( $all_tickets ) === 1 && $template_args['trash_icon'] != 'lock-icon' ? ' style="display:none"' : '';

		//handle rows that should NOT be empty
		if ( empty( $template_args['TKT_start_date'] ) ) {
			//if empty then the start date will be now.
			$template_args['TKT_start_date'] = date('Y-m-d h:i a', current_time('timestamp'));
		}

		if ( empty( $template_args['TKT_end_date'] ) ) {
			//get the earliest datetime (if present);
			$earliest_dtt = $this->_adminpage_obj->get_cpt_model_obj()->ID() > 0 ? $this->_adminpage_obj->get_cpt_model_obj()->get_first_related('Datetime', array('order_by'=> array('DTT_EVT_start' => 'ASC' ) ) ) : NULL;

			if ( !empty( $earliest_dtt ) )
				$template_args['TKT_end_date'] = $earliest_dtt->get_datetime('DTT_EVT_start', 'Y-m-d', 'h:i a');
			else
				$template_args['TKT_end_date'] = date('Y-m-d h:i a', mktime(0, 0, 0, date("m"), date("d")+7, date("Y") ) );
		}

		//generate ticket_datetime items
		if ( ! $default ) {
			$dttrow = 1;
			foreach ( $all_dtts as $dtt ) {
				$template_args['ticket_datetimes_list'] .= $this->_get_ticket_datetime_list_item( $dttrow, $tktrow, $dtt, $ticket, $ticket_datetimes, $default );
				$dttrow++;
			}
		}

		$prcrow = 1;
		foreach ( $prices as $price ) {
			$show_trash = ( count( $prices ) > 1 && $prcrow === 1 ) || count( $prices ) === 1  ? FALSE : TRUE;
			$show_create = count( $prices ) > 1 && count( $prices ) !== $prcrow ? FALSE : TRUE;
			$template_args['ticket_price_rows'] .= $this->_get_ticket_price_row( $tktrow, $prcrow, $price, $default, $ticket, $show_trash, $show_create );
			$prcrow++;
		}

		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_ticket_row.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE );
	}





	private function _get_tax_rows( $tktrow, $ticket ) {
		$tax_rows = '';
		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_ticket_tax_row.template.php';
		$taxes = empty( $ticket ) ? EE_Taxes::get_taxes_for_admin() : $ticket->get_ticket_taxes_for_admin();
		foreach ( $taxes as $tax ) {
			$tax_added = $this->_get_tax_added( $tax, $ticket );
			$template_args = array(
				'display_tax' => !empty( $ticket ) && $ticket->get('TKT_taxable') ? '' : ' style="display:none;"',
				'tax_id' => $tax->ID(),
				'tkt_row' => $tktrow,
				'tax_label' => $tax->get('PRC_name'),
				'tax_added' => $tax_added,
				'tax_added_display' => EEH_Template::format_currency($tax_added, FALSE, FALSE ),
				'tax_amount' => $tax->get('PRC_amount')
				);
			$tax_rows .= EEH_Template::display_template( $template, $template_args, TRUE );
		}

		return $tax_rows;
	}


	private function _get_tax_added( EE_Price $tax, $ticket ) {
		$subtotal = empty( $ticket ) ? 0 : $ticket->get_ticket_subtotal();
		return $subtotal * $tax->get('PRC_amount') / 100;
	}




	private function _get_ticket_price_row( $tktrow, $prcrow, $price, $default, $ticket, $show_trash = TRUE, $show_create = TRUE ) {
		$template_args = array(
			'tkt_row' => $default && empty($ticket) ? 'TICKETNUM' : $tktrow,
			'PRC_order' => $default && empty($price) ? 'PRICENUM' : $prcrow,
			'edit_prices_name' => $default && empty($price) ? 'PRICENAMEATTR' : 'edit_prices',
			'price_type_selector' => $default && empty( $price ) ? $this->_get_base_price_template( $tktrow, $prcrow, $price, $default ) : $this->_get_price_type_selector( $tktrow, $prcrow, $price, $default ),
			'PRC_ID' => $default && empty($price) ? 0 : $price->ID(),
			'PRC_is_default' => $default && empty($price) ? 0 : $price->get('PRC_is_default'),
			'PRC_name' => $default && empty($price) ? '' : $price->get('PRC_name'),
			'price_currency_symbol' => EE_Registry::instance()->CFG->currency->sign,
			'show_plus_or_minus' => $default && empty($price) ? '' : ' style="display:none;"',
			'show_plus' => $default && empty( $price ) ? ' style="display:none;"' : ( $price->is_discount() || $price->is_base_price() ? ' style="display:none;"' : ''),
			'show_minus' => $default && empty( $price ) ? ' style="display:none;"' : ($price->is_discount() ? '' : ' style="display:none;"'),
			'show_currency_symbol' => $default && empty( $price ) ? ' style="display:none"' : ($price->is_percent() ? ' style="display:none"' : '' ),
			'PRC_amount' => $default && empty( $price ) ? 0 : $price->get('PRC_amount'),
			'show_percentage' => $default && empty( $price ) ? ' style="display:none;"' : ( $price->is_percent() ? '' : ' style="display:none;"' ),
			'show_trash_icon' => $show_trash ? '' : ' style="display:none;"',
			'show_create_button' => $show_create ? '' : ' style="display:none;"',
			'PRC_desc' => $default && empty( $price ) ? '' : $price->get('PRC_desc'),
			'disabled' => !empty( $ticket ) && $ticket->get('TKT_deleted') ? ' disabled' : ''
			);

		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_ticket_price_row.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE );
	}


	private function _get_price_type_selector( $tktrow, $prcrow, $price, $default ) {
		if ( $price->is_base_price() ) {
			return $this->_get_base_price_template( $tktrow, $prcrow, $price, $default );
		} else {
			return $this->_get_price_modifier_template( $tktrow, $prcrow, $price, $default );
		}

	}


	private function _get_base_price_template( $tktrow, $prcrow, $price, $default ) {
		$template_args = array(
				'tkt_row' => $default ? 'TICKETNUM' : $tktrow,
				'PRC_order' => $default && empty( $price ) ? 'PRICENUM' : $prcrow,
				'PRT_ID' => $default && empty( $price ) ? 1 : $price->get('PRT_ID'),
				'PRT_name' => __('Price', 'event_espresso'),
				'price_selected_operator' => '+',
				'price_selected_is_percent' => 0
			);
		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_price_type_base.template.php';

		return EEH_Template::display_template( $template, $template_args, TRUE );
	}



	private function _get_price_modifier_template( $tktrow, $prcrow, $price, $default ) {
		$select_name = $default && empty( $price ) ? 'edit_prices[TICKETNUM][PRICENUM][PRT_ID]' : 'edit_prices[' . $tktrow . '][' . $prcrow . '][PRT_ID]';
		$price_types = EE_Registry::instance()->load_model('Price_Type')->get_all(array( array('OR' => array('PBT_ID' => '2', 'PBT_ID*' => '3' ) ) ) );
		$price_option_span_template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_price_option_span.template.php';
		$all_price_types = $default && empty( $price ) ? array(array('id' => 0, 'text' => __('Select Modifier', 'event_espresso')) ) : array();
		$selected_price_type_id = $default && empty( $price ) ? 0 : $price->type();
		$price_option_spans = '';
		//setup pricetypes for selector
		foreach ( $price_types as $price_type ) {
			$all_price_types[] = array(
				'id' => $price_type->ID(),
				'text' => $price_type->get('PRT_name'),
				);

			//while we're in the loop let's setup the option spans used by js
			$spanargs = array(
				'PRT_ID' => $price_type->ID(),
				'PRT_operator' => $price_type->is_discount() ? '-' : '+',
				'PRT_is_percent' => $price_type->get('PRT_is_percent') ? 1 : 0
				);
			$price_option_spans .= EEH_Template::display_template($price_option_span_template, $spanargs, TRUE );
		}


		$template_args = array(
			'tkt_row' => $default ? 'TICKETNUM' : $tktrow,
			'PRC_order' => $default && empty( $price ) ? 'PRICENUM' : $prcrow,
			'price_modifier_selector' => EEH_Form_Fields::select_input( $select_name, $all_price_types, $selected_price_type_id, 'style="width:auto;"', 'edit-price-PRT_ID' ),
			'price_option_spans' => $price_option_spans,
			'price_selected_operator' => $default && empty( $price ) ? '' : ( $price->is_discount() ? '-' : '+' ),
			'price_selected_is_percent' => $default && empty( $price ) ? '' : ( $price->is_percent() ? 1 : 0 )
			);

		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_price_modifier_selector.template.php';

		return EEH_Template::display_template( $template, $template_args, TRUE );
	}



	private function _get_ticket_datetime_list_item( $dttrow, $tktrow, $dtt, $ticket, $ticket_datetimes, $default ) {
		$dttid = !empty($dtt) ? $dtt->ID() : 0;
		$displayrow = !empty($dtt) ? $dtt->get('DTT_order') : 0;
		$tkt_dtts = $ticket instanceof EE_Ticket && isset( $ticket_datetimes[$ticket->ID()] ) ? $ticket_datetimes[$ticket->ID()] : array();
		$template_args = array(
			'dtt_row' => $default && empty( $dtt ) ? 'DTTNUM' : $dttrow,
			'tkt_row' => $default ? 'TICKETNUM' : $tktrow,
			'ticket_datetime_selected' => in_array( $displayrow, $tkt_dtts ) ? ' ticket-selected' : '',
			'ticket_datetime_checked' => in_array( $displayrow, $tkt_dtts ) ? ' checked="checked"' : '',
			'DTT_name' => $default && empty( $dtt ) ? 'DTTNAME' : $dtt->get_dtt_display_name()
			);

		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_ticket_datetimes_list_item.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE );
	}



	private function _get_ticket_js_structure($all_dtts, $all_tickets) {
		$template_args = array(
			'default_datetime_edit_row' => $this->_get_dtt_edit_row('DTTNUM', NULL, TRUE),
			'default_ticket_row' => $this->_get_ticket_row( 'TICKETNUM', NULL, array(), array(), TRUE),
			'default_price_row' => $this->_get_ticket_price_row( 'TICKETNUM', 'PRICENUM', NULL, TRUE, NULL ),
			'default_price_rows' => '',
			'default_price_modifier_selector_row' => $this->_get_price_modifier_template( 'TICKETNUM', 'PRICENUM', NULL, TRUE ),
			'default_available_tickets_for_datetime' => $this->_get_dtt_attached_tickets_row( 'DTTNUM', NULL, array(), array(), TRUE ),
			'default_datetime_display_row' => $this->_get_dtt_display_row( 'DTTNUM', NULL, TRUE ),
			'existing_available_datetime_tickets_list' => '',
			'existing_available_ticket_datetimes_list' => '',
			'new_available_datetime_ticket_list_item' => $this->_get_datetime_tickets_list_item( 'DTTNUM', 'TICKETNUM', NULL, NULL, array(), TRUE ),
			'new_available_ticket_datetime_list_item' => $this->_get_ticket_datetime_list_item( 'DTTNUM', 'TICKETNUM', NULL, NULL, array(), TRUE )
			);

		$tktrow = 1;
		foreach ( $all_tickets as $ticket ) {
			$template_args['existing_available_datetime_tickets_list'] .= $this->_get_datetime_tickets_list_item( 'DTTNUM', $tktrow, NULL, $ticket, array(), TRUE );
			$tktrow++;
		}
		

		$dttrow = 1;
		foreach ( $all_dtts as $dtt ) {
			$template_args['existing_available_ticket_datetimes_list'] .= $this->_get_ticket_datetime_list_item( $dttrow, 'TICKETNUM', $dtt, NULL, array(), TRUE );
			$dttrow++;
		}

		$default_prices = EE_Registry::instance()->load_model('Price')->get_all_default_prices();
		$prcrow = 1;
		foreach ( $default_prices as $price ) {
			$show_trash = ( count( $default_prices ) > 1 && $prcrow === 1 ) || count( $default_prices ) === 1  ? FALSE : TRUE;
			$show_create = count( $default_prices ) > 1 && count( $default_prices ) !== $prcrow ? FALSE : TRUE;
			$template_args['default_price_rows'] .= $this->_get_ticket_price_row( 'TICKETNUM', $prcrow, $price, TRUE, NULL, $show_trash, $show_create );
			$prcrow++;
		}


		$template = PRICING_TEMPLATE_PATH . 'event_tickets_datetime_ticket_js_structure.template.php';
		return EEH_Template::display_template( $template, $template_args, TRUE );
	}


	/** experiemental box
	public function pricing_metabox() {
		$template = EVENTS_TEMPLATE_PATH . 'new_price_layout.template.php';
		EEH_Template::display_template($template);
	} /**/



} //end class espresso_events_Pricing_Hooks