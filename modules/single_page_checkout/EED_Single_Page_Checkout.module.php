<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * Single Page Checkout (SPCO)
 *
 * @package			Event Espresso
 * @subpackage	/modules/single_page_checkout/
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Single_Page_Checkout  extends EED_Module {

	/**
	 * 	$checkout - EE_Checkout object for handling the properties of the current checkout process
	 * 	@access public
	 *	@type EE_Checkout $checkout
	 */
	public $checkout = NULL;

	/**
	 * the action being performed on the current step
	 * @type string
	 */
	private $_action = '';




	/**
	 * @return EED_Single_Page_Checkout
	 */
	public static function instance() {
		return parent::instance( __CLASS__ );
	}





	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
		EED_Single_Page_Checkout::set_definitions();
		// set routing
		EE_Config::register_route( '_register', 'EED_Single_Page_Checkout', 'run' );
//		EE_Config::register_route( 'process_reg_step', 'EED_Single_Page_Checkout', 'process_reg_step' );
//		EE_Config::register_route( 'finalize_registration', 'EED_Single_Page_Checkout', 'finalize_registration' );
		// add powered by EE msg
		add_action( 'AHEE__SPCO__reg_form_footer', array( 'EED_Single_Page_Checkout', 'display_registration_footer' ));
	}



	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
		EED_Single_Page_Checkout::set_definitions();
		EE_Config::register_route( '_register', 'EED_Single_Page_Checkout', 'run' );
//		EE_Config::register_route( 'process_reg_step', 'EED_Single_Page_Checkout', 'process_reg_step' );
//		EE_Config::register_route( 'finalize_registration', 'EED_Single_Page_Checkout', 'finalize_registration' );
		// set ajax hooks
		add_action( 'wp_ajax_espresso_process_reg_step', array( 'EED_Single_Page_Checkout', 'process_reg_step' ));
		add_action( 'wp_ajax_nopriv_espresso_process_reg_step', array( 'EED_Single_Page_Checkout', 'process_reg_step' ));
		add_action( 'wp_ajax_espresso_finalize_registration', array( 'EED_Single_Page_Checkout', 'finalize_registration' ));
		add_action( 'wp_ajax_nopriv_espresso_finalize_registration', array( 'EED_Single_Page_Checkout', 'finalize_registration' ));
	}



	/**
	 * 	set_definitions
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_definitions() {
		define( 'SPCO_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets' . DS );
		define( 'SPCO_BASE_PATH', rtrim( str_replace( array( '\\', '/' ), DS, plugin_dir_path( __FILE__ )), DS ) . DS );
		define( 'SPCO_REG_STEPS_PATH', SPCO_BASE_PATH . 'reg_steps' . DS );
		define( 'SPCO_TEMPLATES_PATH', SPCO_BASE_PATH . 'templates' . DS );
	}



	/**
	 * 	load_reg_steps
	 * loads and instantiates each reg step based on the EE_Registry::instance()->CFG->registration->reg_steps array
	 *
	 *  @access 	private
	 *  @return 	array
	 */
	private function load_reg_steps() {
		// instantiate EE_Checkout object for handling the properties of the current checkout process
		$this->checkout = EE_Registry::instance()->load_file( SPCO_BASE_PATH, 'EE_Checkout', 'class', array(), FALSE  );
		// load EE_SPCO_Reg_Step base class
		EE_Registry::instance()->load_file( SPCO_REG_STEPS_PATH, 'EE_SPCO_Reg_Step', 'class'  );
		// filter list of reg_steps
		$reg_steps_to_load = apply_filters( 'AHEE__SPCO__load_reg_steps__reg_steps_to_load', $this->get_reg_steps() );
		// sort by key (order)
		ksort( $reg_steps_to_load );
		// loop through folders
		foreach ( $reg_steps_to_load as $order => $reg_step ) {
			// we need a
			if ( isset( $reg_step['file_path'] ) && isset( $reg_step['class_name'] ) && isset( $reg_step['slug'] )) {
				// instantiate step class using file path and class name
				$reg_step_obj = EE_Registry::instance()->load_file( $reg_step['file_path'], $reg_step['class_name'], 'class', $this->checkout, FALSE  );
				// did we gets the goods ?
				if ( $reg_step_obj instanceof EE_SPCO_Reg_Step ) {
					// set reg step order based on config
					$reg_step_obj->set_order( $order );
					// add instantiated reg step object to the master reg steps array
					$this->checkout->reg_steps[ $reg_step_obj->slug()  ] = $reg_step_obj;
				}
			}
		}
		EE_Registry::instance()->CFG->registration->skip_reg_confirmation = TRUE;
		EE_Registry::instance()->CFG->registration->reg_confirmation_last = TRUE;
		// skip the registration_confirmation page ?
		if ( EE_Registry::instance()->CFG->registration->skip_reg_confirmation ) {
			// just remove it from the reg steps array
			unset( $this->checkout->reg_steps['registration_confirmation'] );
		} else if ( EE_Registry::instance()->CFG->registration->reg_confirmation_last && isset( 	$this->checkout->reg_steps['registration_confirmation'] )) {
			// set the order to something big like 100
			$this->checkout->reg_steps['registration_confirmation']->set_order( 100 );
			// and resort based on the reg step class order properties
			$this->checkout->sort_reg_steps();
		}
		// finally, filter the array for good luck
		$this->checkout->reg_steps = apply_filters( 'FHEE__Single_Page_Checkout__load_reg_steps__reg_steps', $this->checkout->reg_steps );
	}



	/**
	 *    get_reg_steps
	 *
	 * @access 	public
	 * @return 	array
	 */
	public function get_reg_steps() {
		$reg_steps = EE_Registry::instance()->CFG->registration->reg_steps;
		if ( empty( $reg_steps )) {
			$reg_steps = array(
				10 => array(
					'file_path' => EE_MODULES . DS . 'single_page_checkout' . DS . 'reg_steps' . DS,
					'class_name' => 'EE_SPCO_Reg_Step_Attendee_Information',
					'slug' => 'attendee_information'
				),
				20 => array(
					'file_path' => EE_MODULES . DS . 'single_page_checkout' . DS . 'reg_steps' . DS,
					'class_name' => 'EE_SPCO_Reg_Step_Registration_Confirmation',
					'slug' => 'registration_confirmation'
				),
				30 => array(
					'file_path' => EE_MODULES . DS . 'single_page_checkout' . DS . 'reg_steps' . DS,
					'class_name' => 'EE_SPCO_Reg_Step_Payment_Options',
					'slug' => 'payment_options'
				)
			);
		}
		return $reg_steps;
	}



	/**
	 * 	_set_current_step
	 *
	 *  @access 	private
	 *  @return 	void
	 */
	private function _set_current_step() {
		// grab what step we're on
		$current_step = EE_Registry::instance()->REQ->get( 'step', '' );
		$this->checkout->current_step = isset( $this->checkout->reg_steps[ $current_step ] ) ? $this->checkout->reg_steps[ $current_step ] : reset( $this->checkout->reg_steps );
		$this->checkout->current_step->set_is_current_step( TRUE );
	}



	/**
	 * 	_set_next_step
	 * advances the reg_steps array pointer and sets the next step, then reverses pointer back to the current step
	 *
	 *  @access 	private
	 *  @return 	void
	 */
	private function _set_next_step() {
		// set pointer to start of array
		reset( $this->checkout->reg_steps );
		// if there is more than one step
		if ( count( $this->checkout->reg_steps ) > 1 && $this->checkout->current_step->slug() != 'finalize_registration' ) {
			// advance to the current step and set pointer
			while ( key( $this->checkout->reg_steps ) != $this->checkout->current_step->slug() && key( $this->checkout->reg_steps ) != '' ) {
				next( $this->checkout->reg_steps );
			}
		}
		// advance one more spot ( if it exists )
		$this->checkout->next_step = next( $this->checkout->reg_steps );
		// verify instance
		$this->checkout->next_step = $this->checkout->next_step instanceof EE_SPCO_Reg_Step ? $this->checkout->next_step  : 'finalize_registration';
		// then back to current step to reset
		prev( $this->checkout->reg_steps );
	}



	/**
	 * 	_get_next_reg_step
	 * 	this simply returns the next step from reg_steps array
	 *
	 *  @access 	private
	 *  @return 	EE_SPCO_Reg_Step
	 */
	private function _get_next_reg_step() {
		$next = next( $this->checkout->reg_steps );
		prev( $this->checkout->reg_steps );
		return $next;
	}



	/**
	 * 	_initialize_reg_steps
	 * simply loops thru all of the active reg steps and calls the initialize_reg_step() method
	 *
	 *  @access 	private
	 *  @return 	void
	 */
	private function _initialize_reg_steps() {
		foreach ( $this->checkout->reg_steps as $reg_step ) {
			$reg_step->initialize_reg_step();
		}
	}



	/**
	 * 	ajax_process_registration_steps
	 */
	public static function process_reg_step() {
		EED_Single_Page_Checkout::instance()->_initialize();
	}



	/**
	 *    run
	 *
	 * @access    public
	 *
	 * @param WP $WP
	 *
	 * @return    void
	 */
	public function run( $WP ) {
		$this->_initialize();
	}



	/**
	 *    _initialize - initial module setup
	 *
	 * @access    private
	 * @throws EE_Error
	 * @return    void
	 */
	private function _initialize() {
		// load classes
		if ( ! isset( EE_Registry::instance()->REQ )) {
			EE_Registry::instance()->load_core( 'Request_Handler' );
		}
		// load the reg steps array
		$this->load_reg_steps();
		// set the current step
		$this->_set_current_step();
		// and the next step
		$this->_set_next_step();
		// and what we're doing on the current step
		$this->_action = EE_Registry::instance()->REQ->get( 'action', 'display_reg_step' );
		// returning from the thank you page ?
		$this->checkout->reg_url_link = EE_Registry::instance()->REQ->get( 'e_reg_url_link', FALSE );
		// get transaction from db or session
		$this->checkout->transaction = $this->checkout->reg_url_link && ! is_admin() ? $this->_get_transaction_and_cart_for_previous_visit() : $this->_get_transaction_and_cart_for_current_session();
		// and the registrations for the transaction
		$this->_get_registrations( $this->checkout->transaction );
		// and template paths
		$this->set_templates();
		// verify transaction one last time
		if ( $this->checkout->transaction instanceof EE_Transaction ) {
			// load reg form for the current step
//			$this->checkout->current_step->reg_form = EE_Registry::instance()->load_file(
//				SPCO_REG_STEPS_PATH,
//				$this->checkout->current_step->reg_form_name(),
//				'form',
//				$this->checkout->current_step,
//				FALSE
//			);
			try {
				$this->checkout->current_step->generate_reg_form();
//				d( $this->checkout->current_step->reg_form );
				// check for form submission
				if ( $this->checkout->current_step->reg_form->was_submitted() ) {
//					echo '<h2 style="color:#E76700;">$this->checkout->current_step->reg_form->was_submitted()<br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h2>';
					// capture form data
					$this->checkout->current_step->reg_form->receive_form_submission();
					// validate form data
					if ( $this->checkout->current_step->reg_form->is_valid() ) {
//						echo '<h2 style="color:#E76700;">$this->checkout->current_step->reg_form->is_valid()<br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h2>';
						// good registrant, you get to proceed
						EE_Error::add_success( $this->checkout->current_step->reg_form->submission_success_message() );
						d( $this->checkout->current_step->reg_form->input_values() );
						// advance to the next step! If you pass GO, collect $200
						next( $this->checkout->reg_steps );
						// and advance the next step as well
						$this->_set_next_step();
					} else {
						// bad, bad, bad registrant
						EE_Error::add_error( $this->checkout->current_step->reg_form->submission_error_message(), __FILE__, __FUNCTION__, __LINE__ );
						$this->_action = 'display_reg_step';
					}
				}
			} catch( EE_Error $e ) {
				$e->get_error();
			}
			// initialize each reg step, which gives them the chance to potentially alter the process
			$this->_initialize_reg_steps();
			// IT'S THE FI-NAL COUNT-DOWN... err.. I mean... final reg step
			if ( $this->checkout->current_step == 'finalize_registration' ) {
				do_action( "AHEE__Single_Page_Checkout__before_finalize_registration", $this->checkout->current_step );
				$this->_process_finalize_registration();
				do_action( "AHEE__Single_Page_Checkout__after_finalize_registration", $this->checkout->current_step );
			} else {
//				echo '<h5 style="color:#2EA2CC;">$this->_action : <span style="color:#E76700">' . $this->_action . '</span><br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h5>';
				// meh... do one of those other steps first
				if ( method_exists( $this->checkout->current_step, $this->_action )) {
					do_action( "AHEE__Single_Page_Checkout__before_{$this->checkout->current_step->slug()}_{$this->_action}", $this->checkout->current_step );
					call_user_func( array( $this->checkout->current_step, $this->_action ));
					do_action( "AHEE__Single_Page_Checkout__after_{$this->checkout->current_step->slug()}_{$this->_action}", $this->checkout->current_step );
				}
			}

		}
//		d( $this->checkout );
		// add some style and make it dance
		add_action( 'wp_enqueue_scripts', array( $this, 'load_css' ), 10 );
//		add_action( 'wp_enqueue_scripts', array( $this, 'load_js' ), 10 );

		$this->registration_checkout();

	}



	/**
	 * _get_transaction_and_cart_for_previous_visit
	 *
	 * @access private
	 * 	@return mixed EE_Transaction|NULL
	 */
	private function _get_transaction_and_cart_for_previous_visit() {
		// because the reg_url_link is present in the request, this is a return visit to SPCO, so we'll get the transaction data from the db
		$transaction = EE_Registry::instance()->load_model( 'Transaction' )->get_transaction_from_reg_url_link( $this->checkout->reg_url_link );
		// verify transaction
		if ( $transaction instanceof EE_Transaction ) {
			// and get the cart that was used for that transaction
			$this->checkout->cart = $this->_get_cart_for_transaction( $transaction );
			return $transaction;
		} else {
			EE_Error::add_error( __( 'Your Registration and Transaction information could not be retrieved from the db.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__);
			return NULL;
		}
	}



	/**
	 * _get_cart_for_transaction
	 *
	 * @access private
	 * @param EE_Transaction $transaction
	 * @param bool           $from_session
	 * @return EE_Cart
	 */
	private function _get_cart_for_transaction( EE_Transaction $transaction, $from_session = FALSE ) {
		$cart = $from_session ? $transaction->get_cart_session() : EE_Cart::get_cart_from_txn( $transaction );
		// verify cart
		if ( ! $cart instanceof EE_Cart ) {
			$cart = EE_Registry::instance()->load_core( 'Cart' );
		}
		return $cart;
	}



	/**
	 * _get_transaction_and_cart_for_current_session
	 * 	generates a new EE_Transaction object and adds it to the $_transaction property.
	 *
	 * 	@access private
	 * 	@return EE_Transaction
	 */
	private function _get_transaction_and_cart_for_current_session() {
		// first check in the session
		$transaction = EE_Registry::instance()->SSN->get_session_data( 'transaction' );
		// verify transaction
		if ( $transaction instanceof EE_Transaction ) {
			// check if the TXN has an ID, which means it has already been saved to the db
			$TXN_ID = $transaction->ID();
			if ( $TXN_ID ) {
				// so this transaction has already been saved to the db earlier in the same session (ie: it's not a revisit)... so let's pull that
				$transaction = EEM_Transaction::instance()->get_one_by_ID( $TXN_ID );
				// verify transaction
				if ( ! $transaction instanceof EE_Transaction ) {
					EE_Error::add_error( __( 'The Transaction could not be retrieved from the db when attempting to process your registration information', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__);
				}
			}
			// and get the cart that was used for that transaction
			$this->checkout->cart = $this->_get_cart_for_transaction( $transaction, TRUE );
		} else {
			//  if there's no transaction, then this is the FIRST visit to SPCO
			// so load up the cart
			$this->checkout->cart = EE_Registry::instance()->load_core( 'Cart' );
			// and then create a new transaction
			$transaction = $this->_initialize_transaction();
		}
		return $transaction;
	}



	/**
	 * 	generates a new EE_Transaction object and adds it to the $_transaction property.
	 *
	 * 	@access private
	 * 	@return mixed EE_Transaction|NULL
	 */
	private function _initialize_transaction() {
		try {
			// create new TXN
			return EE_Transaction::new_instance( array(
				'TXN_timestamp' => current_time('mysql'),
				'TXN_total' => $this->checkout->cart->get_cart_grand_total(),
				'TXN_paid' => 0,
				'STS_ID' => EEM_Transaction::failed_status_code,
			));
		} catch( Exception $e ) {
			EE_Error::add_error( $e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
		}
		return NULL;
	}



	/**
	 * _get_registrations
	 *
	 * @access private
	 * @param EE_Transaction $transaction
	 * @return EE_Cart
	 */
	private function _get_registrations( EE_Transaction $transaction ) {
		// first step: grab the registrants  { : o
		$registrations = $transaction->registrations( array(), TRUE );
		// verify registrations have been set
		if ( $registrations == NULL ) {
			// if no cached registrations, then check the db
			if ( $transaction->registrations() == NULL ) {
				// still nothing? then start from scratch
				$this->_initialize_registrations( $transaction );
			}
		}
		// sort by their original registration order
		usort( $registrations, array( 'EED_Single_Page_Checkout', 'sort_registrations_by_REG_count' ));
		// then loop thru the array
		foreach ( $registrations as $registration ) {
			// verify each registration
			if ( $registration instanceof EE_Registration ) {
				// we display all attendee info for the primary registrant
				if ( $this->checkout->reg_url_link == $registration->reg_url_link() && $registration->is_primary_registrant() ) {
					$this->checkout->primary_revisit = TRUE;
					break;
				} else if ( $this->checkout->reg_url_link != $registration->reg_url_link() ) {
					// but hide info if it doesn't belong to you
					$transaction->clear_cache( 'Registration', $registration->ID() );
				}
			}
		}
	}



	/**
	 *    adds related EE_Registration objects for each ticket in the cart to the current EE_Transaction object
	 *
	 * @access private
	 * @param EE_Transaction $transaction
	 * @return    void
	 */
	private function _initialize_registrations( EE_Transaction $transaction ) {
		//d($this->checkout->cart->all_ticket_quantity_count());
		if ( $transaction instanceof EE_Transaction ) {
			$att_nmbr = 0;
			$total_items = $this->checkout->cart->all_ticket_quantity_count();
			// now let's add the cart items to the $transaction
			foreach ( $this->checkout->cart->get_tickets() as $item ) {
				// grab the related ticket object for this line_item
				$ticket = $item->ticket();
				if ( ! $ticket instanceof EE_Ticket ){
					EE_Error::add_error(sprintf(__("Line item %s did not contain a valid ticket", "event_espresso"),$item->ID()), __FILE__, __FUNCTION__, __LINE__);
					break;
				}
				$first_datetime = $ticket->get_first_related( 'Datetime' );
				if ( ! $first_datetime instanceof EE_Datetime ){
					EE_Error::add_error(sprintf(__("The ticket (%s) is not associated with any valid datetimes.", "event_espresso"),$ticket->name()), __FILE__, __FUNCTION__, __LINE__ );
					continue;
				}
				$event = $first_datetime->get_first_related( 'Event' );
				if ( ! $event instanceof EE_Event ){
					EE_Error::add_error(sprintf(__("The ticket (%s) is not associated with a valid event.", "event_espresso"),$ticket->name()),__FILE__,__FUNCTION__,__LINE__);
					continue;
				}
				//do the following for each ticket of this type they selected
				for ( $x = 1; $x <= $item->quantity(); $x++ ) {
					$att_nmbr++;
					$reg_url_link = $att_nmbr . '-' . $item->code();

					$event_default_registration_status = $event->default_registration_status();
					$STS_ID = ! empty( $event_default_registration_status ) ? $event_default_registration_status : EE_Registry::instance()->CFG->registration->default_STS_ID;
					// now create a new registration for the ticket
					$registration = EE_Registration::new_instance( array(
						'EVT_ID' => $event->ID(),
						'TXN_ID' => $transaction->ID(),
						'TKT_ID' => $ticket->ID(),
						'STS_ID' => $STS_ID,
						'REG_date' => $transaction->datetime(),
						'REG_final_price' => $ticket->price(),
						'REG_session' => EE_Registry::instance()->SSN->id(),
						'REG_count' => $att_nmbr,
						'REG_group_size' => $total_items,
						'REG_url_link'	=> $reg_url_link
					));
					$registration->_add_relation_to( $event, 'Event', array(), $event->ID() );
					$registration->_add_relation_to( $item->ticket(), 'Ticket', array(), $item->ticket()->ID() );
					$transaction->_add_relation_to( $registration, 'Registration', array(), $reg_url_link );

				}
			}
			EE_Registry::instance()->SSN->set_session_data( array( 'transaction' => $transaction ));
			EE_Registry::instance()->SSN->update();

			//			echo '<h3>'. __CLASS__ . '->' . __FUNCTION__ . ' <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h3>';
		}
	}



	/**
	 * sorts registrations by REG_count
	 *
	 * @access public
	 * @param EE_Registration $reg_A
	 * @param EE_Registration $reg_B
	 * @return array()
	 */
	public static function sort_registrations_by_REG_count( EE_Registration $reg_A, EE_Registration $reg_B ) {
		// this shouldn't ever happen within the same TXN, but oh well
		if ( $reg_A->count() == $reg_B->count() ) {
			return 0;
		}
		return ( $reg_A->count() > $reg_B->count() ) ? 1 : -1;
	}



	/**
	 * 		set templates
	 *
	 * 		@access 		public
	 * 		@return 		void
	 */
	public function set_templates() {
//		$this->_templates['registration_page_wrapper'] = SPCO_TEMPLATES_PATH . 'registration_page_wrapper.template.php';
//		$this->_templates['registration_page_attendee_information'] = SPCO_TEMPLATES_PATH . 'attendee_info_main.template.php';
//		$this->_templates['registration_page_payment_options'] = SPCO_TEMPLATES_PATH . 'registration_page_payment_options.template.php';
//		$this->_templates['registration_page_confirmation'] = SPCO_TEMPLATES_PATH . 'registration_page_confirmation.template.php';
//		$this->_templates['confirmation_page'] = SPCO_TEMPLATES_PATH . 'confirmation_page.template.php';
//		 EE_Config::register_view( 'single_page_checkout', 0, $this->_templates['registration_page_wrapper'] );
	}



	/**
	 * 		translate_js_strings
	 *
	 * 		@access 		public
	 * 		@return 		void
	 */
	public static function translate_js_strings() {
		EE_Registry::$i18n_js_strings['invalid_coupon'] = __('We\'re sorry but that coupon code does not appear to be valid. If this is incorrect, please contact the site administrator.', 'event_espresso');
		EE_Registry::$i18n_js_strings['required_field'] = __(' is a required question.', 'event_espresso');
		EE_Registry::$i18n_js_strings['required_multi_field'] = __(' is a required question. Please enter a value for at least one of the options.', 'event_espresso');
		EE_Registry::$i18n_js_strings['reg_step_error'] = __('This registration step could not be completed. Please refresh the page and try again.', 'event_espresso');
		EE_Registry::$i18n_js_strings['answer_required_questions'] = __('Please answer all required questions before proceeding.', 'event_espresso');
		EE_Registry::$i18n_js_strings['attendee_info_copied'] = __('The attendee information was successfully copied.<br/>Please ensure the rest of the registration form is completed before proceeding.', 'event_espresso');
		EE_Registry::$i18n_js_strings['enter_valid_email'] = __('You must enter a valid email address.', 'event_espresso');
		EE_Registry::$i18n_js_strings['valid_email_and_questions'] = __('You must enter a valid email address and answer all other required questions before you can proceed.', 'event_espresso');
		EE_Registry::$i18n_js_strings['no_payment_method'] = __( 'Please select a method of payment in order to continue.', 'event_espresso' );
		EE_Registry::$i18n_js_strings['process_registration'] = __( 'Please wait while we process your registration.<br />Do not refresh the page or navigate away while this is happening.<br/> Thank you for your patience.', 'event_espresso' );
		EE_Registry::$i18n_js_strings['language'] = get_bloginfo( 'language' );
		EE_Registry::$i18n_js_strings['EESID'] = EE_Registry::instance()->SSN->id();
	}



	/**
	 * 	load_css
	 *
	 * 	@access 		public
	 * 	@return 		void
	 */
	public function load_css() {
		wp_register_style( 'single_page_checkout', SPCO_ASSETS_URL . 'single_page_checkout.css', array(), EVENT_ESPRESSO_VERSION );
		wp_enqueue_style( 'single_page_checkout' );
	}


	/**
	 * 	load_js
	 *
	 * 	@access 		public
	 * 	@return 		void
	 */
	public function load_js() {
		EED_Single_Page_Checkout::translate_js_strings();
		wp_enqueue_script( 'underscore' );
		wp_register_script( 'single_page_checkout', SPCO_ASSETS_URL . 'single_page_checkout.js', array('espresso_core', 'underscore'), EVENT_ESPRESSO_VERSION, TRUE );
		wp_enqueue_script( 'single_page_checkout' );
		wp_localize_script( 'single_page_checkout', 'eei18n', EE_Registry::$i18n_js_strings );
	}



	/**
	 * 	display the Registration Single Page Checkout
	 *
	 * @access 	public
	 * @return 	string
	 */
	public function registration_checkout() {
		// form details
		$form_args = array(
			'name' 	=> 'single-page-checkout',
			'html_id' 	=> 'ee-single-page-checkout-dv',
			//template files
			'layout_strategy' => is_admin() ?
				new EE_Div_Per_Section_Layout() :
				new EE_Template_Layout( array(
					'layout_template_file' 			=> SPCO_TEMPLATES_PATH . 'registration_page_wrapper.template.php',
					'begin_template_file' 			=> NULL,
					'input_template_file' 				=> NULL,
					'subsection_template_file' 	=> NULL,
					'end_template_file' 				=> NULL,
					'template_args' => array(
							'empty_cart' 		=> count( $this->checkout->transaction->registrations() ) < 1 ? TRUE : FALSE,
							'revisit' 				=> $this->checkout->revisit,
							'reg_steps' 			=> $this->checkout->reg_steps,
							'empty_msg' 		=> apply_filters(
								'FHEE__Single_Page_Checkout__registration_checkout__empty_msg',
								sprintf(
									__( 'You need to %sselect at least one event%s before you can proceed with the registration process.', 'event_espresso' ),
									'<a href="'. add_query_arg( array( 'post_type' => 'espresso_events' ), site_url() ) . '" title="' . __( 'Return to Events list', 'event_espresso' ) . '">',
									'</a>'
								)
							)
						)
				)
			)
		);
//		d( $this->checkout );
		$this->checkout->registration_form = new EE_Form_Section_Proper( $form_args );
		// load template and add to output sent that gets filtered into the_content()
		EE_Registry::instance()->REQ->add_output( $this->checkout->registration_form->get_html_and_js() );
	}



	/**
	 *        load and display the Registration Single Page Checkout
	 *
	 * @access 	public
	 * @param 	bool $from_admin
	 * @return 	mixed void | string
	 */
	public function _registration_checkout( $from_admin = FALSE ) {
		echo '<br/><h5 style="color:#2EA2CC;">' . __CLASS__ . '<span style="font-weight:normal;color:#0074A2"> -> </span>' . __FUNCTION__ . '() <br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h5>';

		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		//d($this->checkout->cart);
		EE_Registry::instance()->load_helper( 'Form_Fields' );
		EE_Registry::instance()->load_helper( 'Template' );
		EE_Registry::instance()->load_class( 'Question_Form_Input', array(), FALSE, FALSE, TRUE );

		$event_queue = array();
		$total_items = 0;
		$ticket_count = array();
		$payment_required = FALSE;

		$sold_out_events = array();
		$events_requiring_pre_approval = array();
		$additional_event_attendees = array();
//		$events_that_use_coupon_codes = array();
//		$events_that_use_groupon_codes = array();

		$template_args = array(
			'css_class' => '',
			'confirmation_data' => '',
			'reg_page_discounts_dv_class' => 'hidden',
			'additional_attendee_reg_info' => NULL,
			'whats_in_the_cart' => '',
			'prmy_att_input_name' => NULL
		);

		$event_queue['title'] = __('Registrations', 'event_espresso');
		$additional_attendee_forms = FALSE;

		$registrations  =  $this->_transaction->registrations( array(), TRUE );
		// grab the saved registrations from the transaction
		if ( $this->_transaction instanceof EE_Transaction && $registrations !== NULL ) {

			//d( $this->_transaction );
			$event_queue['has_items'] = TRUE;
			$prev_event = NULL;

			foreach ( $registrations as $registration ) {

				if ( $registration->event()->is_sold_out() || $registration->event()->is_sold_out( TRUE )) {
					// add event to list of events that are sold out
					$sold_out_events[ $registration->event()->ID() ] = '<li><span class="dashicons dashicons-marker ee-icon-size-16 pink-text"></span>' . $registration->event()->name() . '</li>';
				}
				$payment_required  = $registration->status_ID() == EEM_Registration::status_id_pending_payment || $registration->status_ID() == EEM_Registration::status_id_approved ? TRUE : $payment_required;
				if ( ! $payment_required ) {
					// add event to list of events with pre-approval reg status
					$events_requiring_pre_approval[ $registration->event()->ID() ] = '<li><span class="dashicons dashicons-marker ee-icon-size-16 orange-text"></span>' . $registration->event()->name() . '</li>';
				}

				$line_item_ID = $registration->reg_url_link();
				$event_queue['items'][ $line_item_ID ]['ticket'] = $registration->ticket();
				$event_queue['items'][ $line_item_ID ]['event'] = $registration->event();
				$event_queue['items'][ $line_item_ID ]['reg_count'] = $registration->count();
				$total_items ++;
				$ticket_count[ $registration->ticket()->ID() ] = isset( $ticket_count[ $registration->ticket()->ID() ] ) ? $ticket_count[ $registration->ticket()->ID() ] + 1 : 1;

				$question_meta = array(
					'EVT_ID' => $registration->event()->ID(),
					'att_nmbr' => $registration->count(),
					'ticket_id' => $registration->ticket()->ID(),
					'input_name' =>  '[' . $line_item_ID . ']',
					'input_id' => $line_item_ID,
					'input_class' => 'ee-reg-page-questions' . $template_args['css_class']
				);

				$Question_Groups = EE_Registry::instance()->load_model( 'Question_Group' )->get_all( array(
					array(
						'Event.EVT_ID' => $registration->event()->ID(),
						'Event_Question_Group.EQG_primary' => $registration->count() == 1 ? TRUE : FALSE
					),
					'order_by'=>array( 'QSG_order'=>'ASC' )
				));

				foreach ( $Question_Groups as $QSG_ID => $Question_Group ) {
					$where = array( 'QST_deleted' => 0 );
					if ( ! $from_admin ) {
						$where['QST_admin_only'] = 0;
					}
					$Questions = $Question_Group->get_many_related( 'Question', array( $where, 'order_by'=>array( 'Question_Group_Question.QGQ_order' =>'ASC' )));
					foreach ( $Questions as $Question ) {
						if( $Question instanceof EE_Question ){
							// if this question was for an attendee detail, then check for that answer
							$answer_value = EEM_Answer::instance()->get_attendee_property_answer_value( $registration, $Question->ID() );
							$answer = $this->checkout->reg_url_link || ! $answer_value ? EEM_Answer::instance()->get_one( array( array( 'QST_ID'=>$Question->ID(), 'REG_ID'=>$registration->ID() ))) : NULL;
							// if NOT returning to edit an existing registration OR if this question is for an attendee property OR we still don't have an EE_Answer object
							if( ! $this->checkout->reg_url_link || $answer_value || ! $answer instanceof EE_Answer ) {
								// create an EE_Answer object for storing everything in
								$answer = EE_Answer::new_instance ( array(
									'QST_ID'=> $Question->ID(),
									'REG_ID'=> $registration->ID()
								 ));
							}
							// verify instance
							if( $answer instanceof EE_Answer ){
								if ( ! empty( $answer_value )) {
									$answer->set( 'ANS_value', $answer_value );
								}
								$question_meta['attendee'][ $Question->is_system_question() ? $Question->system_ID() : $Question->ID() ] = $answer->value();
								$answer->cache( 'Question', $Question );
								$answer_cache_id =$Question->system_ID() != NULL ? $Question->system_ID() . '-' . $line_item_ID : $Question->ID() . '-' . $line_item_ID;
	//								echo '<h4>$answer_cache_id : ' . $answer_cache_id . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
								$registration->cache( 'Answer', $answer, $answer_cache_id );
							}
							$Question_Groups[ $QSG_ID ]->cache( 'Question', $Question );
						}
					}
				}
//					printr( $registration, '$registration  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

				add_filter( 'FHEE__EEH_Form_Fields__label_html', array( 'EED_Single_Page_Checkout', 'reg_form_form_field_label_wrap' ), 10, 2 );
				add_filter( 'FHEE__EEH_Form_Fields__input_html', array( 'EED_Single_Page_Checkout', 'reg_form_form_field_input__wrap' ), 10, 2 );
				$attendee_questions = EEH_Form_Fields::generate_question_groups_html2( $Question_Groups, $question_meta, $from_admin, 'div' );

				// show this attendee form?
				if ( empty( $attendee_questions )) {
					$event_queue['items'][ $line_item_ID ]['additional_attendee_reg_info'] = '
	<input type="hidden" id="' . $line_item_ID . '-additional_attendee_reg_info" name="qstn[' . $line_item_ID . '][additional_attendee_reg_info]" value="0" />' . "\n";
				} else {
					$additional_attendee_forms = $registration->count() == 1 ? FALSE : TRUE;
					$event_queue['items'][ $line_item_ID ]['additional_attendee_reg_info'] = '';
				}
				$event_queue['items'][ $line_item_ID ]['attendee_questions'] = $attendee_questions;



				// is this the primarary registrant ?
				if ( $registration->count() == 1 ) {
					// grab line item from primary attendee
					$template_args['prmy_att_input_name'] =  $line_item_ID;
				} else {

					// for all  attendees other than the primary attendee
					$additional_event_attendees[ $registration->ticket()->ID() ][ $line_item_ID ] = array(
							'ticket' => $registration->ticket()->name(),
							'att_nmbr' => $registration->count(),
							'input_id' => $line_item_ID,
							'input_name' =>  '[' . $line_item_ID . ']'
					);

					$item_name = $registration->ticket()->name();
					$item_name .= $registration->ticket()->description() != '' ? ' - ' . $registration->ticket()->description() : '';

					// if this is a new ticket OR if this is the very first additional attendee after the primary attendee
					if ( $registration->ticket()->ID() != $prev_event || $registration->count() == 2 ) {
						$additional_event_attendees[ $registration->ticket()->ID() ][ $line_item_ID ]['event_hdr'] = $item_name;
						$prev_event = $registration->ticket()->ID();
					} else {
						// no heading
						$additional_event_attendees[ $registration->ticket()->ID() ][ $line_item_ID ]['event_hdr'] = FALSE;
					}
				}



			}

			if ( ! $this->checkout->reg_url_link ) {
				EE_Registry::instance()->SSN->set_session_data( array( 'transaction' => $this->_transaction ));
			}
//				echo '<h3>'. __CLASS__ . '->' . __FUNCTION__ . ' <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h3>';
//				EE_Registry::instance()->SSN->update();
//				d( $this->_transaction );
//				d( $this->checkout->cart );

		} else {
			// empty
			$event_queue['has_items'] = FALSE;
		}
		// sold_out_events
		$template_args['sold_out_events'] = implode( $sold_out_events );
		$template_args['sold_out_events_msg'] = apply_filters( 'FHEE__Single_Page_Checkout__registration_checkout__sold_out_events_msg', __('It appears that the event you were about to make a payment for has sold out since you first registered. If you have already made a partial payment towards this event, please contact the event administrator for a refund.', 'event_espresso') );
		// events_requiring_pre_approval
		$template_args['events_requiring_pre_approval'] = implode( $events_requiring_pre_approval );
		$template_args['events_requiring_pre_approval_msg'] = apply_filters( 'FHEE__Single_Page_Checkout__registration_checkout__sold_out_events_msg', __('The following events do not require payment at this time and will not be billed during this transaction. Billing will only occur after the attendee has been approved by the event organizer. You will be notified when your registration has been processed. If this is a free event, then no billing will occur.', 'event_espresso') );

		//  GOT COUPONS ?
		$template_args['events_that_use_coupon_codes'] = '';
		$template_args['use_coupon_codes'] = FALSE;

		// Groupons ?
		$template_args['events_that_use_groupon_codes'] = '';
		$template_args['use_groupon_codes'] = FALSE;


		$template_args['spco_reg_page_ajax_coupons_url'] = add_query_arg( array( 'ee' => 'apply_coupon' ), $this->_reg_page_base_url );
//		$template_args['print_copy_info'] = $additional_attendee_forms || $total_items > 2 ? TRUE : FALSE;
		$template_args['total_items'] = $total_items;
		$template_args['ticket_count'] = $ticket_count;
		$template_args['print_copy_info'] = $additional_attendee_forms;

//		d($additional_event_attendees);
		$template_args['additional_event_attendees'] = $additional_event_attendees;
		// total monies paid to date
		$total_payments = 0;
		// the original total
		$cart_total_before_tax = $this->checkout->cart->get_cart_total_before_tax();
		// get cart total
		$grand_total = $this->checkout->cart->get_cart_grand_total();
		$template_args['grand_total'] = apply_filters( 'FHEE__EED_Single_Page_Checkout__registration_checkout__grand_total', $grand_total );
		// check if monies are potentially owing
		$template_args['payment_required'] = $cart_total_before_tax > 0 ? $payment_required : FALSE;
		// not a free event?
		if ( $template_args['payment_required'] ) {
			//check for any previous payments
			if ( $template_args['payments'] = $this->_transaction->approved_payments() ) {
				foreach ( $template_args['payments'] as $payment ) {
					if ( $payment instanceof EE_Payment ) {
						// increment total payments
						$total_payments += $payment->amount();
					}
				}
				$template_args['pay_date_frmt'] = get_option('date_format') . ' ' . get_option('time_format');
			}
		} else {
			//unset( $this->checkout->reg_steps['payment_options'] );
			EE_Registry::instance()->SSN->set_session_data( array( 'billing_info' => 'no payment required' ));
			$template_args['payments'] = array();
		}

		$template_args['sub_total'] = $cart_total_before_tax;
		$template_args['taxes'] = $this->checkout->cart->get_taxes_line_item()->children();

		// what's left to pay?
		$amount_owing = $grand_total - $total_payments;
		$template_args['amount_owing'] = apply_filters( 'FHEE__EED_Single_Page_Checkout__registration_checkout__amount_owing', $amount_owing );

		//$template_args['grand_total'] = $template_args['amount_owing'] !== FALSE ? $amount_owing : $grand_total;

		$template_args['total_items'] = $event_queue['total_items'] = $total_items;
//	d( $event_queue );
		$template_args['event_queue'] = $event_queue;
		$template_args['images_dir_url'] = EE_GLOBAL_ASSETS_URL . 'images/';
		$template_args['reg_url_link'] = $this->checkout->reg_url_link;

		$template_args['return_url'] = add_query_arg( array('ee' => 'event_queue'), $this->_reg_page_base_url );
		$template_args['update_url'] = add_query_arg( array('ee' => 'update_event_queue'), $this->_reg_page_base_url );
		$template_args['register_url'] = add_query_arg( array('ee' => '_register'), $this->_reg_page_base_url );
		$template_args['event_queue_url'] = add_query_arg( array('ee' => 'event_queue'), $this->_reg_page_base_url );

		$template_args['confirmation_data'] = $this->checkout->current_step == 'registration_confirmation' ? $this->_registration_confirmation() : '';

		$step_or_revisit = __('Step ', 'event_espresso');

		if ( $this->_revisit && $this->checkout->current_step == 'attendee_information' ) {
			// Update Registration Details
			$confirmation_btn_text = sprintf( __('Update%1$sRegistration%1$sDetails', 'event_espresso'), '&nbsp;' );
			$confirmation_btn_text = apply_filters( 'FHEE__EED_Single_Page_Checkout__registration_checkout__button_text__update_registration_details', $confirmation_btn_text );
			$step_or_revisit = __('Edit', 'event_espresso');
		} else if ( $this->_revisit && $this->checkout->current_step == 'payment_options' ) {
			// Process Payment
			$confirmation_btn_text = sprintf( __('Process%1$sPayment', 'event_espresso'), '&nbsp;' );
			$confirmation_btn_text = apply_filters( 'FHEE__EED_Single_Page_Checkout__registration_checkout__button_text__process_payment', $confirmation_btn_text );
			$step_or_revisit = '';
		} else {
			// Finalize Registration
			$confirmation_btn_text = sprintf( __('Finalize%1$sRegistration', 'event_espresso'), '&nbsp;' );
			$confirmation_btn_text = apply_filters( 'FHEE__EED_Single_Page_Checkout__registration_checkout__button_text__finalize_registration', $confirmation_btn_text );
		}
		// grand total less than paid but greater than zero ?
		if ( $grand_total < $this->_transaction->paid() && $grand_total > 0 && $this->checkout->next_step == 'payment_options' ) {
			// owing money
			$proceed_to_payment_btn_text = sprintf(
				// & Proceed to Payment
				__('%1$s%2$s%1$sProceed%1$sto%1$sPayment', 'event_espresso'),
				'&nbsp;',  // %1$s
				'&amp;'	// %2$s
			);
			$confirmation_btn_text .=  apply_filters( 'FHEE__EED_Single_Page_Checkout__registration_checkout__button_text__and_proceed_to_payment', $proceed_to_payment_btn_text );
		}
		add_action( 'AHEE__SPCO_after_reg_step_form', array( $this, 'add_extra_finalize_registration_inputs' ), 10, 2 );

		$template_args['from_admin'] = $from_admin;

		//if in admin we exit at this point and display the questions template
		if ( $from_admin ) {
			//some custom template args
			$template_args['step_dv_class'] = '';
			$template_args['revisit'] =$this->_revisit;
			return EEH_Template::display_template( $this->_templates['registration_page_attendee_information'], $template_args, TRUE );
		}

		$proceed_to_btn_text = sprintf( __('Proceed%1$sto%1$s', 'event_espresso'), '&nbsp;' );
		$proceed_to_btn_text = apply_filters( 'FHEE__EED_Single_Page_Checkout__registration_checkout__button_text__proceed_to', $proceed_to_btn_text );

		$registration_steps = '';
		$step_nmbr = 1;
		// set pointer to first step
		reset( $this->checkout->reg_steps );
		// loop through steps
		while ( $reg_step_details = current( $this->checkout->reg_steps )) {
			$reg_step = key( $this->checkout->reg_steps );
//			echo '<br/><h4>$reg_step : ' . $reg_step . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//			echo '<h4>$this->checkout->current_step : ' . $this->checkout->current_step . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
			$edit_lnk_class = $this->checkout->current_step == $reg_step ? ' hidden' : '';
			$edit_lnk_url = add_query_arg( array( 'ee' => '_register', 'step' => $reg_step_details['display_func'] ), $this->_reg_page_base_url );
			$step_dv_class = $this->checkout->current_step == $reg_step ? '' : ' hidden';
			$reg_step_form_url = add_query_arg( array( 'ee' => '_register', 'step' => $reg_step_details['process_func'] ), $this->_reg_page_base_url );
			$next = $this->_get_next_reg_step();
			//d( $next );
			$next_step = $next ? $next['display_func'] : 'finalize_registration';
			$next_step_text = $next ? $proceed_to_btn_text . $next['name'] : $confirmation_btn_text;

			$step_args = array_merge(
				$template_args,
				array(
					'step' => $reg_step,
					'step_nmbr' => $this->_revisit !== FALSE ? $step_or_revisit : $step_or_revisit . $step_nmbr . ' - ',
					'edit_lnk_class' => $edit_lnk_class,
					'edit_lnk_url' => add_query_arg( array( 'ee' => '_register', 'step' => $reg_step_details['display_func'] ), $this->_reg_page_base_url ),
					'step_dv_class' => $step_dv_class,
					'reg_step_form_url' => $reg_step_form_url,
					'reg_step_ajax_action' => $reg_step_details['process_func'],
					'next_step' => $next_step,
					'next_step_text' => $next_step_text,
					'revisit' => $this->_revisit
				)
			);

			if ( $reg_step == 'payment_options' ) {
				EE_Registry::instance()->load_model( 'Payment_Method' );
				// has method_of_payment been set by no-js user?
				if ( $this->_selected_method_of_payment = $this->_get_selected_method_of_payment() ) {
					// get EE_Payment_Method object
					$this->_payment_method = $this->_get_payment_method_for_selected_method_of_payment( $this->_selected_method_of_payment );
					if ( $this->_payment_method ) {
						$this->_get_billing_form();
					}
				}
				$step_args['selected_method_of_payment'] = $this->_selected_method_of_payment;
				$available_payment_methods = EE_Registry::instance()->LIB->EEM_Payment_Method->get_all_for_transaction($this->_transaction, EEM_Payment_Method::scope_cart);
				$available_pm = array();
				foreach( $available_payment_methods as $pm ) {
//					d( $pm );
					if ( $pm instanceof EE_Payment_Method ) {
						$available_pm[ $pm->slug() ]['button_html'] = $pm->button_html( $pm->button_url() );
						$pm_css_class = $pm->open_by_default() ? '' : 'hidden';
						$available_pm[ $pm->slug() ]['divo'] = '<div id="reg-page-billing-info-' . $pm->slug() . '-dv" class="reg-page-billing-info-dv ' . $pm_css_class . '">';
						$available_pm[ $pm->slug() ]['name'] = apply_filters(
							'FHEE__Single_Page_Checkout__registration_checkout__selected_payment_method',
							sprintf( __('You have selected "%s" as your method of payment', 'event_espresso' ), $pm->name() )
						);
						$available_pm[ $pm->slug() ]['description'] = $pm->description();
						if ( $billing_form = $pm->type_obj()->billing_form() ) {
							$available_pm[ $pm->slug() ]['billing_form'] = $billing_form->get_html_and_js();
						} else {
							$available_pm[ $pm->slug() ]['billing_form'] = '';
						}
						$available_pm[ $pm->slug() ]['divx'] = '</div>';
					}
				}
				$step_args['available_payment_methods'] = $available_pm;
			}

//			printr( $step_args, '$step_args  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

//			d( $step_args );
			$registration_steps .= EEH_Template::locate_template( $this->_templates[ $reg_step_details[ 'template' ] ], $step_args, TRUE, TRUE );
			// pass step info to js
			EE_Registry::$i18n_js_strings[ 'reg_steps' ][] = $reg_step_details['display_func'];
			next( $this->checkout->reg_steps );
			$step_nmbr++;

		}

		EE_Registry::$i18n_js_strings[ 'reg_steps' ][] = 'finalize_registration';

		$wrapper_args = array(
			'step' => $this->checkout->current_step,
			'empty_cart' => $total_items < 1 ? TRUE : FALSE,
			'reg_steps' => $this->checkout->reg_steps,
			'registration_steps' => $registration_steps,
			'revisit' => $this->_revisit,
			'empty_msg' => apply_filters( 'FHEE__Single_Page_Checkout__registration_checkout__empty_msg', __( 'You need to select at least one event before you can proceed with the registration process.', 'event_espresso' ))
		);
//		d( $wrapper_args );
		EE_Registry::instance()->REQ->add_output( EEH_Template::locate_template( $this->_templates[ 'registration_page_wrapper' ], $wrapper_args, TRUE, TRUE ));

		return '';

	}



	/**
	 * 	_generate_available_payment_methods
	 *
	 * 	@access 		public
	 * 	@param 		boolean 	$required - whether to throw an error if the "selected_method_of_payment" is not found in the incoming request
	 * 	@return 		string
	 */
	private function _get_selected_method_of_payment( $required = FALSE ) {
		// is selected_method_of_payment set in the request ?
		if ( EE_Registry::instance()->REQ->is_set( 'selected_method_of_payment' )) {
			// grab it and sanitize it
			$this->_selected_method_of_payment = sanitize_text_field( EE_Registry::instance()->REQ->get( 'selected_method_of_payment' ));
			// store it in the session so that it's availalbe for all subsequent requests including AJAX
			EE_Registry::instance()->SSN->set_session_data( array( 'selected_method_of_payment' => $this->_selected_method_of_payment ));
			return $this->_selected_method_of_payment;
		// or is is set in the session ?
		} elseif ( $this->_selected_method_of_payment = EE_Registry::instance()->SSN->get_session_data( 'selected_method_of_payment' )) {
 			return $this->_selected_method_of_payment;
		// do ya really really gotsta have it?
		} elseif ( $required ) {
			EE_Error::add_error(
				sprintf(
					__( 'The selected method of payment could not be determined.%sPlease ensure that you have selected one before proceeding.%sIf you continue to experience difficulties, then refresh your browser and try again, or contact %s for assistance.', 'event_espresso' ),
					'<br/>',
					'<br/>',
					EE_Registry::instance()->CFG->organization->email
				),
				__FILE__, __FUNCTION__, __LINE__
			);
		}
		return FALSE;
	}



	/**
	 * _get_payment_method_for_selected_method_of_payment
	 * retreives a valid payment method
	 *
	 * @access public
	 * @param null $selected_method_of_payment
	 * @return \EE_Payment_Method
	 */
	private function _get_payment_method_for_selected_method_of_payment( $selected_method_of_payment = NULL ) {
		// if not passed, then get selected method of payment
		$this->_selected_method_of_payment = ! empty( $selected_method_of_payment ) ? $selected_method_of_payment : $this->_get_selected_method_of_payment();
		// get EE_Payment_Method object
		$this->_payment_method = EE_Registry::instance()->load_model( 'Payment_Method' )->get_one_by_slug( $this->_selected_method_of_payment );
		// verify $payment_method
		if ( ! $this->_payment_method instanceof EE_Payment_Method ) {
			// not a payment
			EE_Error::add_error(
				sprintf(
					__( 'The selected method of payment could not be determined due to a technical issue.%sPlease try again or contact %s for assistance.', 'event_espresso' ),
					'<br/>',
					EE_Registry::instance()->CFG->organization->email
				), __FILE__, __FUNCTION__, __LINE__
			);
			return FALSE;
		}
		// and verify it has a valid Payment_Method Type object
		if ( ! $this->_payment_method->type_obj() instanceof EE_PMT_Base ) {
			// not a payment
			EE_Error::add_error(
				sprintf(
					__( 'A valid payment method could not be determined due to a technical issue.%sPlease try again or contact %s for assistance.', 'event_espresso' ),
					'<br/>',
					EE_Registry::instance()->CFG->organization->email
				), __FILE__, __FUNCTION__, __LINE__
			);
			return FALSE;
		}
		return $this->_payment_method;
	}




	/**
	 * _get_billing_form
	 *
	 * @access private
	 * @return bool
	 */
	private function _get_billing_form() {
		// get billing form for the selected payment method
		$this->_billing_form = $this->_payment_method->type_obj()->billing_form();
		if ( $this->_billing_form instanceof EE_Billing_Info_Form ) {
			if ( $this->_billing_form->was_submitted() ) {
				$this->_billing_form->receive_form_submission();
				if ( ! $this->_billing_form->is_valid() ) {
					EE_Error::add_error( __( 'One or more billing form inputs are invalid and require correction before proceeding.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
					return FALSE;
				}
			}
		}
		return TRUE;
	}




	/**
	 * this generates the output for the registration form for manual registrations via the admin
	 *
	 * @access public
	 * @return string
	 */
	public function registration_checkout_for_admin() {

		$this->checkout->reg_url_link = FALSE;
		$this->_revisit = FALSE;
		$this->init_for_admin();

		//do native registration_checkout
		$this->registration_checkout( TRUE );
	}



	/**
	 *    add_extra_finalize_registration_inputs
	 *
	 * @access    public
	 * @param $current_step
	 * @param $next_step
	 * @internal  param string $label
	 * @return        string
	 */
	public function add_extra_finalize_registration_inputs( $current_step, $next_step ) {
		if ( $next_step == 'finalize_registration' ) {
			echo '<div id="spco-extra-finalize_registration-inputs-dv"></div>';
		}
	}





	/**
	 * 	reg_form_form_field_label_wrap
	 *
	 * 	@access 	public
	 * 	@param 	string 	$label
	 * 	@return 	string
	 */
	public static function reg_form_form_field_label_wrap( $label ) {
		return '<div class="reg-page-form-field-wrap-pg">' . $label;
	}




	/**
	 * 	reg_form_form_field_input__wrap
	 *
	 * 	@access 	public
	 * 	@param 	string 	$input
	 * 	@param 	string 	$label
	 * 	@return 	string
	 */
	public static function reg_form_form_field_input__wrap( $input, $label ) {
		return $input . '</div>';
	}



	/**
	 * 	_process_attendee_information
	 *
	 * 	@access 	private
	 * @return mixed
	 */
	private function _process_attendee_information() {

		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$success = TRUE;
		// empty container
		$valid_data = array();

		if ( EE_Registry::instance()->REQ->is_set( 'qstn' )) {
			$valid_data = apply_filters( 'FHEE__EE_Single_Page_Checkout__process_attendee_information__REQ', EE_Registry::instance()->REQ->get( 'qstn' ) );
			// loop through post data and sanitize all elements
			array_walk_recursive( $valid_data, array(  EE_Registry::instance()->REQ, 'sanitize_text_field_for_array_walk' ));
		}
		// if we don't have any $valid_data then something went TERRIBLY WRONG !!! AHHHHHHHH!!!!!!!
		if ( ! empty( $valid_data )) {

			if ( isset( $valid_data['custom_questions'] )) {
				if ( ! $this->_reg_url_link ) {
					EE_Registry::instance()->SSN->set_session_data( array( 'custom_questions' =>$valid_data['custom_questions'] ));
				}
				unset( $valid_data['custom_questions'] );
			}

			$primary_attendee = array();
			$primary_attendee_obj = NULL;
			$primary_attendee['line_item_id'] = NULL;
			if ( isset( $valid_data['primary_attendee'] )) {
				$primary_attendee['line_item_id'] =  ! empty( $valid_data['primary_attendee'] ) ? $valid_data['primary_attendee'] : FALSE;
				unset( $valid_data['primary_attendee'] );
			}

//			printr( $valid_data, '$valid_data  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//			printr( $this->_transaction, '$this->_transaction  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

			// attendee counter
			$att_nmbr = 0;
			if ( $this->_continue_reg ) {
				if ( $this->_transaction instanceof EE_Transaction && $this->_continue_reg ) {
					$registrations = $this->_transaction->registrations( array(), TRUE );
					if ( ! empty( $registrations )) {
						EE_Registry::instance()->load_model( 'Attendee' );
						$this->_primary_attendee_obj = NULL;
						// grab the saved registrations from the transaction
						foreach ( $registrations  as $registration ) {
							// verify EE_Registration object
							if ( $registration instanceof EE_Registration ) {
								// EITHER a) first time thru SPCO so process ALL registrations
								// OR b) primary registrant is editing info, so process ALL registrations
								// OR b) another registrant is editing info, so ONLY process their registration
								if ( ! $this->_revisit || $this->_primary_revisit || ( $this->_revisit && $this->_reg_url_link == $registration->reg_url_link() )) {
									// reg_url_link / line item ID exists ?
									$line_item_id = $registration->reg_url_link();
									if ( $line_item_id ) {
//										echo '<h5 style="color:#2EA2CC;">$line_item_id : <span style="color:#E76700">' . $line_item_id . '</span><br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h5>';
										// Houston, we have a registration!
										$att_nmbr++;
										// grab related answer objects
										$answers = $registration->answers();
//										printr( $answers, '$answers  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
										$attendee_data = array();
										// do we need to copy basic info from primary attendee ?
										$copy_primary = isset( $valid_data[ $line_item_id ]['additional_attendee_reg_info'] ) && absint( $valid_data[ $line_item_id ]['additional_attendee_reg_info'] ) === 0 ? TRUE  : FALSE;
//										echo '<h5 style="color:#2EA2CC;">$copy_primary : <span style="color:#E76700">' . $copy_primary . '</span><br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h5>';
										unset( $valid_data[ $line_item_id ]['additional_attendee_reg_info'] );
										if ( isset( $valid_data[ $line_item_id ] )) {
											// filter form input data for this registration
											$valid_data[ $line_item_id ] = apply_filters( 'FHEE__EE_Single_Page_Checkout__process_attendee_information__valid_data_line_item', $valid_data[ $line_item_id ] );
	//									printr( $valid_data[ $line_item_id ], '$valid_data[ $line_item_id ]  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
											// now loop through our array of valid post data && process attendee reg forms
											foreach ( $valid_data[ $line_item_id ] as $form_input => $input_value ) {
												// check for critical inputs
												if ( empty( $input_value )) {

													switch( $form_input ) {
														case 'fname' :
															EE_Error::add_error( __( 'First Name is a required value.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
															$success = FALSE;
														break;
														case 'lname' :
															EE_Error::add_error( __( 'Last Name is a required value.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
															$success = FALSE;
														break;
														case 'email' :
															EE_Error::add_error( __( 'Email Address is a required value.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
															$success = FALSE;
														break;
													}

												} elseif ( $form_input == 'email' ) {
													// clean the email address
													$valid_email = sanitize_email( $input_value );
													// check if it matches
													if ( $input_value != $valid_email ) {
														// whoops!!!
														EE_Error::add_error( __( 'Please enter a valid email address.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
														$success = FALSE;
													}
												}

												// store a bit of data about the primary attendee
												if ( $att_nmbr == 1 && $line_item_id == $primary_attendee['line_item_id'] && ! empty( $input_value )) {
													$primary_attendee[ $form_input ] = $input_value;
												} else if ( $copy_primary && isset( $primary_attendee[ $form_input ] ) && $input_value == NULL ) {
													$input_value = $primary_attendee[ $form_input ];
												}

												// $answer_cache_id is the key used to find the EE_Answer we want
												$answer_cache_id = $this->_reg_url_link ? $form_input : $form_input . '-' . $line_item_id;
												$answer_is_obj = isset( $answers[ $answer_cache_id ] ) && $answers[ $answer_cache_id ] instanceof EE_Answer ? TRUE : FALSE;

												//rename a couple of form_inputs
												switch( $form_input ) {
													case 'state' :
														$form_input = 'STA_ID';
														$attendee_property = TRUE;
													break;
													case 'country' :
														$form_input = 'CNT_ISO';
														$attendee_property = TRUE;
													break;
													default :
														$attendee_property = EEM_Attendee::instance()->has_field('ATT_' . $form_input) ? TRUE : FALSE;
														$form_input = $attendee_property ? 'ATT_' . $form_input : $form_input;
												}

	//echo '<h4>$answer_cache_id : ' . $answer_cache_id . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
	//echo '<h4>attendee_property: ' . $attendee_property . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
	//echo '<h4>$answer_is_obj : ' . $answer_is_obj . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
	//echo '<h4>' . $form_input . ': ' . ( is_array( $input_value ) ? implode( ', ', $input_value ) : $input_value ) . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
												$saved = FALSE;
												// if this form input has a corresponding attendee property
												if ( $attendee_property ) {
													$attendee_data[ $form_input ] = $input_value;
													if (  $answer_is_obj ) {
														// and delete the corresponding answer since we won't be storing this data in that object
														$registration->_remove_relation_to( $answers[ $answer_cache_id ], 'Answer' );
													}
													$saved = TRUE;
												} elseif ( $answer_is_obj ) {
													// save this data to the answer object
													$answers[ $answer_cache_id ]->set_value( $input_value );
													$saved = TRUE;
												} else {
													foreach ( $answers as $answer ) {
														if ( $answer  instanceof EE_Answer && $answer->question_ID() == $answer_cache_id ) {
															$answer->set_value( $input_value );
															$saved = TRUE;
														}
													}

												}
												if ( ! $saved )  {
													EE_Error::add_error( sprintf( __( 'Unable to save registration form data for the form input: %s', 'event_espresso' ), $form_input ), __FILE__, __FUNCTION__, __LINE__ );
													$success = FALSE;
												}

											}  // end of foreach ( $valid_data[ $line_item_id ] as $form_input => $input_value )

										} /*else {
											EE_Error::add_error( sprintf( __( 'It appears that no form data, or invalid data, for attendee #%s was received while attempting to process the registration form.', 'event_espresso' ), $att_nmbr ), __FILE__, __FUNCTION__, __LINE__ );
											$success = FALSE;
										}*/
	//									printr( $attendee_data, '$attendee_data  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

										// this registration does not require additional attendee information ?
										if ( $copy_primary && $att_nmbr > 1 && $this->_primary_attendee_obj instanceof EE_Attendee ) {
	//										echo '<h1>$copy_primary && $att_nmbr > 1  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h1>';
											// add relation to new attendee
											$registration->_add_relation_to( $this->_primary_attendee_obj,
											                                 'Attendee' );
											$registration->set_attendee_id( $this->_primary_attendee_obj->ID() );
											$registration->update_cache_after_object_save( 'Attendee', $this->_primary_attendee_obj );
	//										echo '$copy_primary attendee: '. $this->_primary_attendee_obj->ID() . '<br/>';
										} else {
											// does this attendee already exist in the db ? we're searching using a combination of first name, last name, AND email address
											$ATT_fname = isset( $attendee_data['ATT_fname'] ) && ! empty( $attendee_data['ATT_fname'] ) ? $attendee_data['ATT_fname'] : '';
											$ATT_lname = isset( $attendee_data['ATT_lname'] ) && ! empty( $attendee_data['ATT_lname'] ) ? $attendee_data['ATT_lname'] : '';
											$ATT_email = isset( $attendee_data['ATT_email'] ) && ! empty( $attendee_data['ATT_email'] ) ? $attendee_data['ATT_email'] : '';
											// but only if those have values
											if ( $ATT_fname && $ATT_lname && $ATT_email ) {
												$existing_attendee = EE_Registry::instance()->LIB->EEM_Attendee->find_existing_attendee( array(
													'ATT_fname' => $ATT_fname,
													'ATT_lname' => $ATT_lname,
													'ATT_email' => $ATT_email
												));
											} else {
												$existing_attendee = NULL;
											}
	//										printr( $existing_attendee, '$existing_attendee  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
											$existing_attendee = apply_filters( 'FHEE_EE_Single_Page_Checkout__save_registration_items__find_existing_attendee', $existing_attendee, $registration );
											// did we find an already existing record for this attendee ?
											if ( $existing_attendee instanceof EE_Attendee ) {
//												echo '<h1>$existing_attendee instanceof EE_Attendee  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h1>';
												// update attendee data in case it has changed since last time they registered for an event
												// first remove fname, lname, and email from attendee data
												unset( $attendee_data['ATT_fname'] );
												unset( $attendee_data['ATT_lname'] );
												unset( $attendee_data['ATT_email'] );
												// now loop thru what' sleft and add to attendee CPT
												foreach ( $attendee_data as $property_name => $property_value ) {
													if ( EEM_Attendee::instance()->has_field($property_name)) {
														$existing_attendee->set( $property_name, $property_value );
													}
												}
												// better save that now
												$existing_attendee->save();
												// add relation to existing attendee
												$registration->_add_relation_to( $existing_attendee, 'Attendee' );
												$registration->set_attendee_id( $existing_attendee->ID() );
												$registration->update_cache_after_object_save( 'Attendee', $existing_attendee );

										} else {
											// ensure critical details are set for additional attendees
											if ( $att_nmbr > 1 ) {
												$critical_attendee_details = array(
													'ATT_fname',
													'ATT_lname',
													'ATT_email'
//													'ATT_address',
//													'ATT_address2',
//													'ATT_city',
//													'STA_ID',
//													'CNT_ISO',
//													'ATT_zip',
//													'ATT_phone',
												);
												$critical_attendee_details = apply_filters( 'FHEE__EE_Single_Page_Checkout__process_attendee_information__critical_attendee_details', $critical_attendee_details );
												foreach ( $critical_attendee_details as $critical_attendee_detail ) {
													if ( ! isset( $attendee_data[ $critical_attendee_detail ] ) || empty( $attendee_data[ $critical_attendee_detail ] )) {
														$attendee_data[ $critical_attendee_detail ] = $primary_attendee_obj->get( $critical_attendee_detail );
													}
													}

												}
												// set author to event creator
												$attendee_data['ATT_author'] = $registration->event()->wp_user();
												// create new attendee object
												$new_attendee = EE_Attendee::new_instance( $attendee_data );
												$new_attendee->save();
//												echo '<h1>$new_attendee  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h1>';
												// add relation to new attendee
												$registration->_add_relation_to( $new_attendee, 'Attendee' );
												$registration->set_attendee_id( $new_attendee->ID() );
												$registration->update_cache_after_object_save( 'Attendee', $new_attendee );

											}

											// who's the man ?
											if ( $att_nmbr == 1 ) {
												$this->_primary_attendee_obj = $registration->get_first_related( 'Attendee' );
//												printr( $this->_primary_attendee_obj, '$this->_primary_attendee_obj  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
											}
										}


									} else {
										EE_Error::add_error( __( 'An invalid or missing line item ID was encountered while attempting to process the registration form.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
										$success = FALSE;
										// remove malformed data
										unset( $valid_data[ $line_item_id ] );
									}

									if ( ! $registration->attendee() instanceof EE_Attendee ) {
										EE_Error::add_error( sprintf( __( 'Registration %s has an invalid or missing Attendee object.', 'event_espresso' ), $line_item_id ), __FILE__, __FUNCTION__, __LINE__ );
										$success = FALSE;
									}

//									printr( $registration->attendee(), '$registration->attendee()  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

								} // end of if ( ! $this->_revisit || $this->_primary_revisit || ( $this->_revisit && $this->_reg_url_link == $registration->reg_url_link() )) {

							// end of if ( $registration instanceof EE_Registration )
							} else {
								EE_Error::add_error( __( 'An invalid Registration object was discovered when attempting to process your registration information.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__);
								$success = FALSE;
							}

//							printr( $registration, '$registration  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

						} // end of foreach ( $this->_transaction->registrations()  as $registration )

						if ( ! $this->_reg_url_link ) {
							EE_Registry::instance()->SSN->set_session_data( array( 'transaction' => $this->_transaction ));
						}

					} else {
						EE_Error::add_error( __( 'Your form data could not be applied to any valid registrations.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
						$success = FALSE;
					}

				} else {
					EE_Error::add_error( __( 'A valid transaction could not be initiated for processing your registrations.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
					$success = FALSE;
				}

			}

		} else {
			EE_Error::add_error( __('No valid question responses were received.', 'event_espresso'), __FILE__, __FUNCTION__, __LINE__ );
			$success = FALSE;
		}

		// grab any errors
		if ( $success ) {
			EE_Error::add_success( __('Attendee information submitted successfully.', 'event_espresso' ));
		}

		//this might be called while in admin and if it is then we don't want to do our normal steps.
		if ( is_admin() && ! EE_Registry::instance()->REQ->front_ajax ) {
			return $success;
		}

		//do action in case a plugin wants to do something with the data submitted in step 1.
		//passes EE_Single_Page_Checkout, and it's posted data
		do_action( 'AHEE__EE_Single_Page_Checkout__process_attendee_information__end', $this, $valid_data );

		$this->go_to_next_step( __FUNCTION__ );
		return TRUE;
	}



	/**
	 * 	_save_all_registration_information
	 * 	simply loops through the current transaction and saves all data for each registration
	 *
	 * 	@access private
	 * 	@return 	bool
	 */
	private function _save_all_registration_information() {
//		echo '<br/><h5 style="color:#2EA2CC;">'. __CLASS__ . '<span style="font-weight:normal;color:#0074A2"> -> </span>' . __FUNCTION__ . '() <br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h5>';
		// verify the transaction
		if ( $this->_transaction instanceof EE_Transaction ) {
			$this->_transaction->save();
			// grab the saved registrations from the transaction
			foreach ( $this->_transaction->registrations( array(), TRUE ) as $line_item_id => $registration ) {  //
//				printr( $registration, '$registration  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
				// verify object
				if ( $registration instanceof EE_Registration ) {
					// EITHER a) first time thru SPCO so process ALL registrations
					// OR b) primary registrant is editing info, so process ALL registrations
					// OR b) another registrant is editing info, so ONLY process their registration
					if ( ! $this->_revisit || $this->_primary_revisit || ( $this->_revisit && $this->checkout->reg_url_link == $registration->reg_url_link() )) {
						//set TXN ID
						if ( ! $registration->transaction_ID() ) {
							$registration->set_transaction_id( $this->_transaction->ID() );
						}
						// verify and save the attendee
						$attendee = $registration->attendee();
						if ( $attendee ) {
							if ( $attendee instanceof EE_Attendee ) {
//								printr( $attendee, '$attendee  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
								$attendee->save();
								$registration->set_attendee_id( $attendee->ID() );
								if ( ! $registration->update_cache_after_object_save( 'Attendee', $attendee )) {
									EE_Error::add_error(
										__( 'The newly saved Attendee object could not be cached on the registration.', 'event_espresso' ),
										__FILE__, __FUNCTION__, __LINE__
									);
									return FALSE;
								}
							} else {
								EE_Error::add_error(
									__( 'An invalid Attendee object was discovered when attempting to save your registration information.', 'event_espresso' ),
									__FILE__, __FUNCTION__, __LINE__
								);
								return FALSE;
							}
						} else {
							EE_Error::add_error(
								__( 'No Attendee object was found when attempting to save your registration information.', 'event_espresso' ),
								__FILE__, __FUNCTION__, __LINE__
							);
							return FALSE;
						}
						// save so that REG has ID
						$registration->save();
						// now save the answers
						foreach ( $registration->answers() as $cache_key => $answer ) {
							// verify object
							if ( $answer instanceof EE_Answer ) {
//								printr( $answer, '$answer  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
								$answer->set_registration( $registration->ID() );
								$answer->save();
								if ( ! $registration->update_cache_after_object_save( 'Answer', $answer, $cache_key )) {
									EE_Error::add_error(
										 __( 'The newly saved Answer object could not be cached on the registration.', 'event_espresso' ),
										 __FILE__, __FUNCTION__, __LINE__
									);
									return FALSE;
								}
							} else {
								EE_Error::add_error(
									__( 'An invalid Answer object was discovered when attempting to save your registration information.', 'event_espresso' ),
									__FILE__, __FUNCTION__, __LINE__
								);
								return FALSE;
							}
						}
						if ( ! $this->_transaction->update_cache_after_object_save( 'Registration', $registration, $line_item_id )) {
							EE_Error::add_error( __( 'The newly saved Registration object could not be cached on the Transaction.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__);
							return FALSE;
						}
					}
				} else {
					EE_Error::add_error(
						__( 'An invalid Registration object was discovered when attempting to save your registration information.', 'event_espresso' ),
						__FILE__, __FUNCTION__, __LINE__
					);
					return FALSE;
				}
			}
		} else {
			EE_Error::add_error( __( 'A valid Transaction was not found when attempting to save your registration information.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__);
			return FALSE;
		}
		return TRUE;
	}




	/**
	 * This processes the registration form from the admin and returns either the true or false depending on the success of the process.
	 *
	 * Note that this method handles not only validating the registration form but also saving to the database all the data in the session.
	 *
	 * @access  public
	 * @return mixed bool|int (either false on fail OR TXN id on success)
	 */
	public function process_registration_from_admin() {
		//nonce check was done in admin so no need to do here.
		//first lets validate the registration form
		$this->init_for_admin();
		//if failure in processing attendee info then let's get out early
		if ( ! $this->_process_attendee_information() ) {
			return FALSE;
		}
		// same deal when saving everything
		if ( ! $this->_save_all_registration_information() ) {
			return FALSE;
		}
		//all is good so let's continue with finalizing the registration.
		EE_Registry::instance()->SSN->set_session_data( array( 'transaction', NULL ));
		$this->_transaction->set_txn_session_data( EE_Registry::instance()->SSN->get_session_data() );
		$this->checkout->cart->get_grand_total()->save_this_and_descendants_to_txn( $this->_transaction->ID() );
		//is this free event?
		if ( $this->checkout->cart->get_grand_total()->total() == EEH_Template::format_currency( 0, TRUE ) ) {
			$this->_transaction->set_status( EEM_Transaction::complete_status_code );
		} else {
			$this->_transaction->set_status( EEM_Transaction::incomplete_status_code );
		}
		$this->_transaction->finalize( TRUE );
		EE_Registry::instance()->SSN->clear_session( __CLASS__, __FUNCTION__ );
		return $this->_transaction->ID();
	}





	/**
	 * 	_process_finalize_registration
	 *
	 * 	@access private
	 * 	@return 	void
	 */
	private function _process_finalize_registration() {

		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );

		// save everything
		if ( $this->_continue_reg && $this->_save_all_registration_information() ) {
//			echo '<h2 style="color:#E76700;">_process_finalize_registration<br/><span style="font-size:9px;font-weight:normal;color:#666">' . __FILE__ . '</span>    <b style="font-size:10px;color:#333">  ' . __LINE__ . ' </b></h2>';
			// save TXN data to the cart
			$this->checkout->cart->get_grand_total()->save_this_and_descendants_to_txn( $this->_transaction->ID() );

			do_action( 'AHEE__EE_Single_Page_Checkout__process_finalize_registration__before_gateway', $this->_transaction );
			$this->_selected_method_of_payment = $this->_get_selected_method_of_payment();
			// if Default REG Status is set to REQUIRES APPROVAL... then payments are NOT allowed
			if ( $this->_selected_method_of_payment == 'payments_closed' ) {
				// set TXN Status to Open
				$this->_transaction->set_status( EEM_Transaction::incomplete_status_code );
				$this->_transaction->save();
				$this->_transaction->finalize();

			// Default REG Status is set to PENDING PAYMENT OR APPROVED, and payments are allowed
			} else if ( $this->_transaction->total() > 0 ) {
				// attempt payment via payment method
				$this->_process_payment();
			} else {
				// set TXN Status to Open
				$this->_transaction->set_status( EEM_Transaction::complete_status_code );
				$this->_transaction->save();
				$this->_transaction->finalize();
				$this->_redirect_to_thank_you_page = TRUE;
			}

		}
		$this->checkout->next_step = FALSE;
		$this->go_to_next_step( __FUNCTION__ );

	}





	/**
	 * 	display_registration_footer
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public static function display_registration_footer() {
		if ( apply_filters( 'FHEE__EE_Front__Controller__show_reg_footer', EE_Registry::instance()->CFG->admin->show_reg_footer ) ) {
			if ( ! empty( EE_Registry::instance()->CFG->admin->affiliate_id )) {
				$url = add_query_arg( array( 'ap_id' => EE_Registry::instance()->CFG->admin->affiliate_id ), 'http://eventespresso.com/' );
				$url = apply_filters( 'FHEE__EE_Front_Controller__registration_footer__url', $url );
				echo apply_filters( 'FHEE__EE_Front_Controller__display_registration_footer','<div id="espresso-registration-footer-dv"><a href="' . $url . '" title="Event Registration Powered by Event Espresso" target="_blank">Event Registration and Ticketing</a> Powered by <a href="' . $url . '" title="Event Espresso - Event Registration and Management System for WordPress" target="_blank">Event Espresso</a></div>' );
			}
		}
	}




	/**
	 *        _process_return_to_reg_step_query_args
	 *
	 * @access 	private
	 * @param 	$args
	 * @return 	array
	 */
	private function _process_return_to_reg_step_query_args( $args ) {
		$remove = array( 'ajax_action', 'espresso_ajax', 'noheader', 'spco-go-to-next-step-sbmt-btn' );
		foreach ( $_POST as $key => $value ) {
			if ( in_array( $key, $remove )) {
				unset( $_POST[ $key ] );
			}
		}
		return array_merge( $_POST, $args );
	}



	/**
	 *   handle ajax message responses and redirects
	 *
	 * @access public
	 * @param string $prev_step
	 * @param bool   $callback
	 * @param bool   $callback_param
	 * @return void
	 */
	public function go_to_next_step( $prev_step = '', $callback = FALSE, $callback_param = FALSE ) {

		$no_errors = TRUE;
		// where to boss?
		switch ( $this->checkout->next_step ) {
			case 'registration_confirmation' :
				$callback = '_go_to_registration_confirmation_ajax_response';
			break;
			case 'finalize_registration' :
				$callback = $this->_revisit ? '_finalize_' . $this->checkout->current_step : '_process_finalize_registration';
			break;
		}
		// check for valid callback function
		$valid_callback = $callback !== FALSE && $callback != '' && method_exists( $this, $callback ) ? TRUE : FALSE;
		// check for recursion
		if ( $prev_step == $callback ) {
			EE_Error::add_error( __('A recursive loop was detected and the registration process was halted.', 'event_espresso'), __FILE__, __FUNCTION__, __LINE__ );
			$valid_callback = FALSE;
		}
		// grab notices
		$notices = EE_Error::get_notices( FALSE );
		$success_msg = isset( $notices['success'] ) ? $notices['success'] : FALSE;
		$error_msg = isset( $notices['errors'] ) ? $notices['errors'] : FALSE;
		$attention_msg = isset( $notices['attention'] ) ? $notices['attention'] : FALSE;
		// setup URL for redirect
		if ( $this->_redirect_to_thank_you_page ) {
			$this->_thank_you_page_url = add_query_arg(
				array( 'e_reg_url_link' => $this->_transaction->primary_registration()->reg_url_link() ),
				$this->_thank_you_page_url
			);
			$this->_json_response['return_data'] = array( 'redirect-to-thank-you-page' => $this->_thank_you_page_url );
		}
		// set JSON response and merge in notices
		$this->_json_response = array_merge( $this->_json_response, $notices );

if ( $this->checkout->current_step != 'attendee_information' ) {
//		echo '<h4>$prev_step : ' . $prev_step . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$this->checkout->current_step : ' . $this->checkout->current_step . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$this->checkout->next_step : ' . $this->checkout->next_step . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$callback : ' . $callback . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$valid_callback : ' . $valid_callback . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$success_msg : ' . $success_msg . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$error_msg : ' . $error_msg . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$this->_revisit : ' . $this->_revisit . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>EE_Registry::instance()->REQ->ajax : ' . EE_Registry::instance()->REQ->ajax . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		printr( $this->_json_response, '$this->_json_response  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
}


		// all good ?
		if ( $success_msg && ! ( $error_msg || $attention_msg )) {
			unset( $this->_json_response['error'] );
			unset( $this->_json_response['attention'] );
			// if not ajax, then return TRUE to advance to next step
			$no_errors = TRUE;
		// DERP!!!
		} elseif ( $error_msg || $attention_msg ) {
			unset( $this->_json_response['success'] );
			// if not ajax, then return FALSE to repeat the current step while displaying the error notice
			$no_errors = FALSE;
		}
		// if this is an ajax request AND a callback function exists
		if ( EE_Registry::instance()->REQ->ajax  && $valid_callback ) {
			// send data through to the callback function
			$this->$callback( $callback_param, $success_msg );
		} else if ( EE_Registry::instance()->REQ->ajax ) {
			// just send the ajax
			$this->_json_response = apply_filters( 'FHEE__EE_Single_Page_Checkout__JSON_response', $this->_json_response );
			echo json_encode( $this->_json_response );
			die();
		}

		// store notices in a transient
		EE_Error::get_notices( FALSE, TRUE, TRUE );
		// no errors, means progress to next step, but if next step is empty, then redirect to thank you page. errors means return to page we came from
		$next_step = $no_errors ? $this->checkout->next_step : str_replace( 'process_', '', $this->checkout->current_step );
		if ( $next_step ) {
			$args = $this->_process_return_to_reg_step_query_args( array( 'ee' => '_register', 'step' => $next_step ));
			$redirect = add_query_arg( $args, $this->_reg_page_base_url );
		} else {
			$redirect = $this->_thank_you_page_url;
		}
//		echo '<h4>$no_errors : ' . $no_errors . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$next_step : ' . $next_step . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$redirect : ' . $redirect . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		printr( EE_Registry::instance()->REQ, 'EE_Registry::instance()->REQ  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		wp_safe_redirect( $redirect );
		exit();
	}

}
// End of file EED_Single_Page_Checkout.module.php
// Location: /modules/single_page_checkout/EED_Single_Page_Checkout.module.php
