<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Seth Shoultes
 * @ copyright	(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		4.0
 *
 * ------------------------------------------------------------------------
 *
 * Ticket Selector  class
 *
 * @package		Event Espresso
 * @subpackage	includes/classes/EE_Ticket_Selector.class.php
 * @author			Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Ticket_Selector extends  EED_Module {

	/**
	* event that ticket selector is being generated for
	*
	* @access protected
	* @var array
	*/
	protected $_event = NULL;



	/**
	* whether the registration is being added by an admin
	*
	* @access protected
	* @var boolean
	*/
	protected $_added_by_admin = NULL;





	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
	}

	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
	}



	/**
	* 	gets the ball rolling
	*
	*	@access 	public
	* 	@param	object 			$WP  
	* 	@return 	void	
	*/
	public function run( $WP ) {	
	
		$this->EE->load_class( 'Cost_Calculator' );
		add_action( 'AHEE_display_ticket_selector', array( $this, 'load_tckt_slctr_js' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_tckt_slctr_js' ), 10 );		

	}





	/**
	* 	creates buttons for selecting number of attendees for an event
	*
	*	@access public
	* 	@param	object 			$event  
	* 	@param	boolean 		$added_by_admin  whether the registration is being added by an admin
	* 	@return 	string	
	*/
	public function display_ticket_selector( $event = FALSE, $added_by_admin = FALSE ) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');		

		if ( ! $event ) {
			$user_msg = __( 'No Event was not supplied.', 'event_espresso' );
			$dev_msg = $user_msg . __( 'In order to generate a ticket selector, please ensure you are passing an event object to the EE_Ticket_Selector class constructor.', 'event_espresso' );
			EE_Error::add_error( $user_msg . '||' . $dev_msg, __FILE__, __FUNCTION__, __LINE__ );	
			return FALSE;
		}

		$this->_event = $event;
		$this->_added_by_admin = $added_by_admin;

		$template_args = array();
		//printr( $this->_event, '$this->_event  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		
		if ( $this->_event->allow_multiple ) {
			// make sure additional_limit is set
			if ( ! isset( $this->_event->additional_limit ) or $this->_event->additional_limit == '' ) {
				$this->_event->additional_limit = $this->_event->reg_limit;
			}
			// then make it at least 1
			$this->_event->additional_limit = ( $this->_event->additional_limit == 0 ) ? 1 : $this->_event->additional_limit;
			// let's make the max amount of attendees somebody can select a little more reasonable
			$template_args['max_atndz'] = $this->_event->additional_limit > 16 ? 16 : $this->_event->additional_limit;	
		} else {
			$template_args['max_atndz'] = 1;
		}
		

		
		$template_args['event_id'] = $this->_event->id;
		$template_args['event_name'] = $this->_event->event_name;
		$template_args['require_pre_approval'] = $this->_event->require_pre_approval;

		$template_args['dates'] = $this->_event->datetimes;
		$template_args['dates'] = $this->_format_date($template_args['dates']);
		//printr( $template_args['dates'], 'dates <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

		$template_args['times'] = $this->_process_event_times($this->_event->datetimes);
		$template_args['datetimes'] = $this->_process_event_datetimes($this->_event->datetimes);
		$template_args['multiple_time_options'] = count($template_args['times']) > 1 ? TRUE : FALSE;
		//echo printr( $template_args['times'], 'times <span style="margin:0 0 0 3em;font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span>', 'auto' );
		//echo printr( $this->_event->datetimes, 'event->datetimes <span style="margin:0 0 0 3em;font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span>', 'auto' );

		$template_args['prices'] = $this->_process_event_prices( $this->_event->prices, $this->_event->currency_symbol );
		$template_args['multiple_price_options'] = count($template_args['prices']) > 1 ? TRUE : FALSE;
		//echo printr($this->_event->prices, 'event->prices <span style="margin:0 0 0 3em;font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span>', 'auto' );

		$template_args['event_meta'] = EE_Ticket_Prices::obfuscate( empty( $this->_event->meta ) ? array() : $this->_event->meta );
//		$template_args['event_meta'] = base64_encode( serialize( empty( $this->_event->meta ) ? array() : $this->_event->meta ));
//		printr( $template_args['event_meta'], 'event_meta  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

		$template_args['currency_symbol'] = $this->_event->currency_symbol;
		$template_args = apply_filters('FHEE__EE_Ticket_Selector__display_ticket_selector__template_args',$template_args,$this->_event);
		
		$templates['ticket_selector'] =  apply_filters('FHEE__EE_Ticket_Selector__display_ticket_selector__template_path',
							EVENT_ESPRESSO_PLUGINFULLPATH . 'templates/ticket_selector/ticket_selector_chart.template.php',$this->_event);
	//	$templates['ticket_selector'] =  EVENT_ESPRESSO_PLUGINFULLPATH . 'templates/ticket_selector/ticket_selector_multi_selects.template.php';
	//	$templates['ticket_selector'] =  EVENT_ESPRESSO_PLUGINFULLPATH . 'templates/ticket_selector/ticket_selector_threaded_chart.template.php';
		espresso_display_template($templates['ticket_selector'], $template_args);

	}





	/**
	* 	format date for display
	*
	*	@access private
	* 	@param  mixed 		$dates
	* 	@return 	string
	*/
	private function _format_date( $datetimes ) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		//printr( $datetimes, '$datetimes  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		// start with an empty array
		$dates = array();
		foreach ( $datetimes as $DTT_ID => $date ) {
			$frmtd = $date->start_date('D M jS');
			$dates[ $DTT_ID ] = str_replace( ' ', '&nbsp;', $frmtd );
		}
		// flip it once
		$dates = array_flip( $dates );
		// flip it twice - and the duplicates magically dissappear
		$dates = array_flip( $dates );
		return $dates;
	}





	/**
	* 	process event times
	*
	*	@access private
	* 	@param array  	$times
	* 	@return array
	* 	@return string
	*/
	private function _process_event_times($times) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		// start with an empty array
		$time_options = array();
		$tm_frmt = 'g:ia';
		foreach ($times as $DTT_ID => $time) {		
			$time_options[ $DTT_ID ] = array(
					'id' => $DTT_ID,
					'event_id' => $time->event_ID(),
					'start_time' => $time->start(),
					'formatted' => $time->end_time() ? $time->start_time($tm_frmt) . ' - ' . $time->end_time($tm_frmt) : $time->start_time($tm_frmt),
					'date' => str_replace( ' ', '&nbsp;', $time->start_date('D M jS'))	
			);
		}
		//echo printr($time_options);
		return $time_options;
	}






	/**
	* 	process event date times
	*
	*	@access private
	* 	@param array  	$times
	* 	@return array
	* 	@return string
	*/
	private function _process_event_datetimes($datetimes) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		// start with an empty array
		$datetime_options = array();
		$tm_frmt = 'g:ia';
		foreach ($datetimes as $DTT_ID => $datetime) {		
			$datetime_options[ $DTT_ID ] = array(
					'id' => $DTT_ID,
					'event_id' => $datetime->event_ID(),
					'start_date' => str_replace( ' ', '&nbsp;', $datetime->start_date('D M jS') ),
					'start_time' => $datetime->start(),
					'formatted' => $datetime->end_time() ? $datetime->start_time($tm_frmt) . ' - ' . $datetime->end_time($tm_frmt) : $datetime->start_time($tm_frmt)
			);
		}
		//echo printr( $datetime_options,'$datetime_options'  );

		return $datetime_options;
	}





	/**
	* 	process event prices for display
	*
	*	@access private
	* 	@param array  	$times
	* 	@param string  	$currency_symbol
	* 	@return array
	* 	@return string
	*/
	private function _process_event_prices( $prices = array(), $currency_symbol = '$', $surcharge_type = 'included' ) {

		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		//printr( $prices, '$prices  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

		// start with an empty array
		$price_options = array();

		if ( ! empty( $prices )) {
			foreach ( $prices as $price ) {			
				if ( $price instanceof EE_Ticket_Price ) {
					//printr( $price, '$price  <span style="margin:0 0 0 3em;font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span>', 'auto' );		
					// add price 
					$price_option = $price->name() . ': ';
					// format ticket price
					$price_option .= $price == '0.00' ? '<span class="price-is-free">free</span>' : $currency_symbol . $price->price();
					// add this price option to the array of options
					$price_options[ implode( ',', $price->ID_list() ) ] = array( 'raw' => $price->price(), 'option' => $price_option, 'obj' => $price->obfuscate() );				
				} else {
					$error_msg = __( 'An error occured. A supplied ticket price was not of the correct type.', 'event_espresso' );
					EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );					
				}
			}		
		}
		//printr( $price_options, '$price_options  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

		return $price_options;
	}





	
	/**
	* 	process_ticket_selections
	* 
	*	@access public
	* 	@access 		public
	* 	@return		array  or FALSE
	*/	
	public static function process_ticket_selections( $registration_url = FALSE, $return = FALSE ) {
		//we should really only have 1 registration in the works now (ie, no MER)
		//so clear any previosu items in the cart. When MER happens this will probably need to be tweaked, 
		//possibly wrappe din a conditional checking for some constant defined in MER etc.
		espresso_clear_session();
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		
		//printr( $_POST, '$_POST  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		
		// do we have an event id?
		if ( isset($_POST['tkt-slctr-event-id'] )) {
		
			// validate/sanitize data
			$valid = self::_validate_post_data('add_event_to_cart');
			//printr( $valid, '$valid  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		
			//check total tickets oredered vs max number of attendees that can register
			if ($valid['total_tickets'] > $valid['atndz']) {
		
				// ordering too many tickets !!!
				$singular = 'You have attempted to purchase %s ticket.';
				$plural = 'You have attempted to purchase %s tickets.';
				$limit_error_1 = sprintf(_n($singular, $plural, $valid['total_tickets'], 'event_espresso'), $valid['total_tickets'], $valid['total_tickets']);
		
				$singular = 'The registration limit for this event is %s ticket per registration, therefore the total number of tickets you may purchase at a time can not exceed %s.';
				$plural = 'The registration limit for this event is %s tickets per registration, therefore the total number of tickets you may purchase at a time can not exceed %s.';
				$limit_error_2 = sprintf(_n($singular, $plural, $valid['atndz'], 'event_espresso'), $valid['atndz'], $valid['atndz']);
				$error_msg = $limit_error_1 . '<br/>' . $limit_error_2;
				EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
				
			} else {
				
				$tckts_slctd = FALSE;
				$success = FALSE;
				// all data appears to be valid
				// cycle thru the number of data rows sent from the event listsing
				for ( $x = 0; $x < $valid['rows']; $x++ ) {
		
					// does this row actually contain a ticket quantity?
					if ( isset( $valid['qty'][$x] ) && $valid['qty'][$x] > 0 ) {		
						// YES we have a ticket quantity				
						$tckts_slctd = TRUE;
						// throw valid data into a new array
						$event_to_add = array(
								'id' => $valid['id'],
								'name' => $valid['name'],
								'price' => $valid['price'][$x],
								'price_id' => $valid['price_id'][$x],
								'price_obj' => $valid['price_obj'][$x],
								'qty' => $valid['qty'][$x],
//								'meta_keys' => $valid['meta_keys'],
//								'meta_values' => $valid['meta_values'],
								
								'options' => array(
										'date' => $valid['date'][$x],
										'time' => $valid['time'][$x],
										'dtt_id' => $valid['dtt_id'][$x],
										'price_desc' => $valid['price_desc'][$x],
										'event_meta' => $valid['event_meta'],
										'pre_approval' => $valid['pre_approval']
								)
						);
						//printr( $event_to_add, '$event_to_add  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
						
						// then add event
						if ( self::_add_event_to_cart( $event_to_add )) {
							$success = TRUE;
						}
					} 
				}
				
//$cart = EE_Cart::instance()->whats_in_the_cart();
//printr( $cart, '$cart  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//die();
				if ( $tckts_slctd ) {				
					if ( $success ) {
						if ( $return ) {
							return TRUE;
						} else {
							if ( ! $registration_url ) {
								$registration_url = add_query_arg( array( 'e_reg'=>'register', 'step' => 1 ), espresso_get_reg_page_full_url() );
							}
//echo '<h4>$registration_url : ' . $registration_url . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//$cart = EE_Cart::instance()->whats_in_the_cart();
//printr( $cart, '$cart  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//printr( EE_Session::instance()->get_session_data(), '$EE_Session  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//
//die();
							wp_safe_redirect($registration_url);
							exit();
						}
					} else {
						// nothing added to cart
						$error_msg = __( 'An error occured. No tickets were added for the event.<br/>Please click the back button on your browser and try again.', 'event_espresso' );
						EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
					}

				} else {
					// no ticket quantities were selected
					$error_msg = __( 'You need to select a ticket quantity before you can proceed.<br/>Please click the back button on your browser and try again.', 'event_espresso' );
					EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
				}				
			}
//die();
			if ( isset( $_POST['tkt-slctr-return-url-'.$valid['id']] )) {
				$return_url = add_query_arg( EE_Error::get_notices( FALSE, TRUE ), $_POST['tkt-slctr-return-url-'.$valid['id']] );
				wp_safe_redirect( $return_url );
				exit();
			} elseif ( isset( $_SERVER['HTTP_REFERER'] )) {
				$return_url = add_query_arg( EE_Error::get_notices( FALSE, TRUE ), $_SERVER['HTTP_REFERER'] );
				wp_safe_redirect( $return_url );
				exit(); 
			} else {
				echo EE_Error::get_notices();			
			}
			
			
		} /*else {
			// $_POST['tkt-slctr-event-id'] was not set ?!?!?!?
			$error_msg = __( 'An error occured. An event id was not provided or was not received.<br/>Please click the back button on your browser and try again.', 'event_espresso' );
			EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			
		}	

		if ( isset( $_SERVER['HTTP_REFERER'] )) {
			$return_url = add_query_arg(  EE_Error::get_notices( FALSE, TRUE ), $_SERVER['HTTP_REFERER'] );
			wp_safe_redirect( $return_url );
			exit();
		}	*/

	}






	/**
	* 	validate_post_data
	* 
	* 	@access 		private
	*  	@param 		string 		$stage - where we are in the registration process
	* 	@return		array  or FALSE
	*/
	private static function _validate_post_data() {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		
		// start with an empty array()
		$valid_data = array();

		//if event id is valid
		if ( $id = absint( $_POST['tkt-slctr-event-id'] )) {

			$valid_data['id'] = $id;
			// grab and sanatize return-url
			$return_url = esc_url_raw($_POST['tkt-slctr-return-url-' . $id]);
			// array of other form names
			$inputs_to_clean = array(
					'name' => 'tkt-slctr-event-name-',
					'atndz' => 'tkt-slctr-max-atndz-',
					'rows' => 'tkt-slctr-rows-',
					'qty' => 'tkt-slctr-qty-',
					'price' => 'tkt-slctr-price-',
					'price_id' => 'tkt-slctr-price-id-',
					'date' => 'tkt-slctr-date-',
					'dtt_id' => 'tkt-slctr-dtt-id-',
					'time' => 'tkt-slctr-time-',
					'price_desc' => 'tkt-slctr-price-desc-',
					'price_obj' => 'tkt-slctr-price-obj-',
					'event_meta' => 'tkt-slctr-event-meta-',
//					'meta_keys' => 'tkt-slctr-meta-keys-',
//					'meta_values' => 'tkt-slctr-meta-values-',
					'pre_approval' => 'tkt-slctr-pre-approval-'
			);
			// let's track the total number of tickets ordered.'
			$valid_data['total_tickets'] = 0;
			// cycle through $inputs_to_clean array
			foreach ($inputs_to_clean as $what => $input_to_clean) {

//				echo '<h4>what : ' . $what . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//				printr( $_POST[ $input_to_clean . $id ], '$input_to_clean  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

				switch ($what) {

					// integers
					case 'atndz':
					case 'rows':
					case 'pre_approval':
						$valid_data[$what] = absint($_POST[$input_to_clean . $id]);
						break;

					// arrays of integers
					case 'qty':
						// grab the array
						$row_qty =$_POST[$input_to_clean . $id];						
//						$ints = is_array( $_POST[$input_to_clean . $id] ) ? $_POST[$input_to_clean . $id] : array( $_POST[$input_to_clean . $id] );						
						// if qty is coming from a radio button input, then we need to assemble an array of rows
						if( ! is_array( $_POST[$input_to_clean . $id] )) {
							// get number of rows
							$rows = isset( $_POST['tkt-slctr-rows-' . $id] ) ? absint( $_POST['tkt-slctr-rows-' . $id] ) : 1;
							//echo '<h4>$rows : ' . $rows . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
							// explode ints by the dash
							$row_qty = explode( '-', $row_qty );
							$row = isset( $row_qty[0] ) ? ( absint( $row_qty[0] )+1 ) : 1;
							$qty = isset( $row_qty[1] ) ? absint( $row_qty[1] ) : 0;
							$row_qty = array( $row => $qty );
							//printr( $row_qty, '$row_qty  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
							for( $x = 1; $x <= $rows; $x++ ) {
								if ( ! isset( $row_qty[$x] )) {
									$row_qty[$x] = 0;
								}
							}
						}
						ksort( $row_qty );
						//printr( $row_qty, '$row_qty  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
						// cycle thru values
						foreach ($row_qty as $qty) {
							// sanitize as integers
							$valid_data[$what][] = absint($qty);
							$valid_data['total_tickets'] = $valid_data['total_tickets'] + absint($qty);
						}
						break;
						
					case 'dtt_id':
					case 'time':
						// grab the array
						$ints =$_POST[$input_to_clean . $id];						
						// cycle thru values
						foreach ($ints as $int) {
							switch ($what ) {
																	
								case 'time' :
									$time = absint($int);
									$valid_data[$what][] = date( 'g:i a', $time);
									break;
									
								default :
								// sanitize as integers
								$valid_data[$what][] = absint($int);								
							}
						}
						break;

					// floats
					case 'price':
						// grab the array
						$floats = $_POST[$input_to_clean . $id];
						// cycle thru values
						foreach ($floats as $float) {
							// sanitize as float
							$valid_var = trim(preg_replace('/[^0-9.+$]/', '', $float));
							$valid_data[$what][] = $valid_var = number_format((float) $valid_var, 2, '.', '');
						}
						break;

					// string
					case 'date':
						// grab the array
						$dates = $_POST[$input_to_clean . $id];
						// cycle thru values
						foreach ($dates as $date) {
							// allow only numbers, letters,  spaces, commas and dashes
							$valid_var = trim(preg_replace('/[^a-zA-Z0-9,-\s\s++$]/', '', $date));
							// can it convert to a date?
							if ($valid_var = date('Y-m-d', strtotime($date))) {
								$valid_data[$what][] = $date;
							}
						}
						break;

					// string
					case 'name':
						// allow only numbers, letters,  spaces, commas and dashes
						$valid_data[$what] = sanitize_text_field( $_POST[$input_to_clean . $id] );
						break;
					case 'event_meta':
						$valid_data[$what] = $_POST[$input_to_clean . $id];
						break;

					// arrays of string
					case 'price_id':
					case 'meta_keys':
					case 'meta_values':
						$value_array = array();
						// grab the array
						$values = $_POST[$input_to_clean . $id];
						// cycle thru values
						foreach ($values as $key=>$value) {
							// allow only numbers, letters,  spaces, commas and dashes
							$value_array[$key] = wp_strip_all_tags($value);
						}
						$valid_data[$what] = $value_array;
						break;
						
					case 'price_desc':
						// grab the array
						$descs = maybe_unserialize($_POST[$input_to_clean . $id]);
						// cycle thru values
						foreach ($descs as $desc) {
							// allow safe html
							$valid_data[ $what ][] = wp_kses_data( $desc );
						}
						break;
						
					case 'price_obj':
						// grab the array
						$values = $_POST[$input_to_clean . $id];
						// cycle thru values
						foreach ($values as $key=>$value) {
							// allow only numbers, letters,  spaces, commas and dashes
							$valid_data[$what][] = $value;
						}
						break;
						
					case 'return-url' :
						break;
						
				} 	// end switch $what
			} 	// end foreach $inputs_to_clean 
			
		} else {
			$error_msg = 'An error occured. The event id provided was not valid';
			EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}

		//printr( $valid_data, '$valid_data  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' ); die();
		return $valid_data;
	}





	/**
	* 	adds an event to the cart
	* 	@access private
	* 	@param string - which_cart
	* 	@param array - items
	* 	@return TRUE on success, FALSE on fail
	*/
	private static function _add_event_to_cart( $event = FALSE, $qty = 1, $which_cart = 'REG' ) {
	
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		
		global $EE_Cart; 
		// check that an event has been passed
		if (!$event or !is_array($event) or empty($event)) {
			$error_msg = 'An error occured. No event details were submitted. Could not add to cart';
			EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}
		// make sure cart is loaded
		if (!defined('ESPRESSO_CART')) {
			require_once(EE_CLASSES . 'EE_Cart.class.php');
			$EE_Cart = EE_Cart::instance();
		}

		$event['options'] = isset($event['options']) ? $event['options'] : '';

		$add_to_cart_args = array(
				'id' => $event['id'],
				'name' => $event['name'],
				'price' => $event['price'],
				'price_id' => $event['price_id'],
				'price_obj' => $event['price_obj'],
				'qty' => $event['qty'],
				'options' => $event['options'],
//				'meta_keys' => $event['meta_keys'],
//				'meta_values' => $event['meta_values']
//				'event_meta' => $event['event_meta']
		);

		// get the number of spaces left for this event
		$available_spaces = self::get_available_spaces($event['id']);

		// compare availalbe spaces against the number of tickets being purchased
		if ($available_spaces >= $event['qty']) {
			// add event to cart
			if ($EE_Cart->add_to_cart($which_cart, $add_to_cart_args)) {

				// retreive event id list
				//$events_in_cart = $this->session->data('events_in_cart');
				//echo $EE_Cart->session->pre_r($EE_Cart); die();
				// add this event to list
				$EE_Cart->set_events_in_cart_list($event['id']);
				// send event id list back to session
				//$EE_Cart->session->set_session_data( $EE_Cart->get_events_in_cart_list(), 'events_in_cart' );
				// add event id to list of events in cart within individual cart
				$EE_Cart->add_to_cart_event_id_list($which_cart, $event['id']);

				return TRUE;
			} else {
				// error adding to cart
				return FALSE;
			}
		} else {

			// event is full
			if ($available_spaces > 0) {
				// add error messaging - we're using the _n function that will generate the appropriate singular or plural message based on the number of $available_spaces
				$error_msg = sprintf(_n(
												'We\'re sorry, but there is only %s available space left for this event. Please go back and select a different number of tickets.', 'We\'re sorry, but there are only %s available spaces left for this event. Please go back and select a different number of tickets.', $available_spaces, 'event_espresso'
								), $available_spaces
				);
				EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			} else {
				$error_msg = __('We\'re sorry, but there are no available spaces left for this event', 'event_espresso');
				EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
			}
			return FALSE;
		}
	}




	
	/**
	* 	get number of available spaces for event
	*
	*	@access 		public
	*	@param 		string 		$event_id
	* 	@return 		int
	*/
	public static function get_available_spaces($event_id) {

		global $wpdb, $org_options;

		$nmbr_attendees = 0;
		$available_spaces = 0;

		// first get the number of attendees already registered
		$SQL = 'SELECT COUNT(REG_ID) quantity FROM ' . $wpdb->prefix . 'esp_registration ';
		$SQL .= 'WHERE EVT_ID=%d AND (STS_ID="RAP"';
		$SQL .= $org_options['pending_counts_reg_limit'] ? ' OR STS_ID="RPN")' : ')';		

		$wpdb->get_results($wpdb->prepare($SQL, $event_id));

		if ($wpdb->num_rows > 0 && $wpdb->last_result[0]->quantity != NULL) {
			$nmbr_attendees = $wpdb->last_result[0]->quantity;
		}
		// now get the reg limit for the event
		$SQL = "SELECT reg_limit FROM " . EVENTS_DETAIL_TABLE . " WHERE id=%d";

		$reg_limit = $wpdb->get_var($wpdb->prepare($SQL, $event_id));
		//return $reg_limit;  <<< check this

		// then determine how many spaces are left
		$available_spaces = max(( $reg_limit - $nmbr_attendees ), 0 );
		return $available_spaces;
	}





	/**
	* 	load js
	*
	*	@access 		public
	* 	@access 		public
	* 	@return 		void
	*/
	public function load_tckt_slctr_js() {
		// add some style
		wp_register_style('ticket_selector', EVENT_ESPRESSO_PLUGINFULLURL . 'templates/ticket_selector/ticket_selector.css');
		wp_enqueue_style('ticket_selector');
		// make it dance
		wp_register_script('ticket_selector', EVENT_ESPRESSO_PLUGINFULLURL . 'scripts/ticket_selector.js', array('jquery'), '', TRUE);
		wp_enqueue_script('ticket_selector');
		// loco grande 
		wp_localize_script( 'ticket_selector', 'eei18n', EE_Registry::$i18n_js_strings );
	}





}



// End of file EE_Ticket_Selector.class.php
// Location: /includes/classes/EE_Ticket_Selector.class.php