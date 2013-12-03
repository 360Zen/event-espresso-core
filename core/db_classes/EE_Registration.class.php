<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			{@link http://eventespresso.com/support/terms-conditions/}   * see Plugin Licensing *
 * @ link					{@link http://www.eventespresso.com}
 * @ since		 		4.0
 *
 * ------------------------------------------------------------------------
 *
 * EE_Registration class
 *
 * @package			Event Espresso
 * @subpackage		includes/classes/EE_Registration.class.php
 * @author				Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
require_once ( EE_CLASSES . 'EE_Soft_Delete_Base_Class.class.php' );
class EE_Registration extends EE_Soft_Delete_Base_Class {
	
    /**
    *	Registration ID
	* 
	* 	primary key
	*	
	* 	@access	protected
    *	@var int	
    */
	protected $_REG_ID = FALSE;

	
	
	
    /**
    *	Event ID
	* 
	*	foreign key from event table
	*  
	*	@access	protected
    *	@var int	
    */
	protected $_EVT_ID = NULL;
	
	
	
    /**
    *	Attendee ID
	* 
	* 	foreign key from attendee table
	*
	*	@access	protected
    *	@var int	
    */
	protected $_ATT_ID = NULL;	
	
	
    /**
    *	Transaction ID
	*
	*	foreign key from transaction table
	* 
	*	@access	protected
    *	@var int	
    */
	protected $_TXN_ID = NULL;
	
	
    /**
    *	Ticket ID
	* 
    *	foreign key from Ticket table
	* 
	*	@access	protected
    *	@var int	
    */
	protected $_TKT_ID = NULL;	
	
	
	
	
    /**
    *	Status ID
	* 
    *	registration status code - Pending, Complete, Incomplete
	* 
	*	@access	protected
    *	@var string	
    */
	protected $_STS_ID = NULL;	
	
	
    /**
    *	Registration Date
	* 
    *	Unix timestamp
	* 
	*	@access	protected
    *	@var int	
    */
	protected $_REG_date = NULL;	
	
	
    /**
    *	Final Price
	* 
    *	Final Price for ticket after all modifications
	* 
	*	@access	protected
    *	@var float	
    */
	protected $_REG_final_price = NULL;	
	
	
	
    /**
    *	PHP Session ID
	*  
	*	@access	protected
    *	@var string	
    */
	protected $_REG_session = NULL;	
	
	
	
    /**
    *	Registration Code
	* 
    *	a unique string for public identification ( = existing registration_id )
	* 
	*	@access	protected
    *	@var string	
    */
	protected $_REG_code = NULL;	
	
	
	
    /**
    *	Registration URL Link
	* 
    *	a unique string for use in email links, etc
	* 
	*	@access	protected
    *	@var string	
    */
	protected $_REG_url_link = NULL;	
	
	
	
    /**
    *	Attendee Number
	* 
    *	Simple attendee counter where the Primary Registrant is always #1
	* 
	*	@access	protected
    *	@var int	
    */
	protected $_REG_count = 1;		
	
	
    /**
    *	Group Size
	* 
    *	total number of registrations that were performed in the same session
	* 
	*	@access	protected
    *	@var int	
    */
	protected $_REG_group_size = 1;	
	
	
    /**
    *	Attendee Is Going
	* 
    *	whether or not the attendee has confirmed they will be going to the event
	* 
	*	@access	protected
    *	@var boolean	
    */
	protected $_REG_att_is_going = 0;




	/**
	 * This is the soft delete field for Registrations
	 *
	 * @access protected
	 * @var integer
	 */
	protected $_REG_deleted = 0;
	
	

	
	/**
	 * Event for which this registration is for
	 * 
	 * @access protected
	 * @var EE_Event $_Event
	 */
	protected $_Event = NULL;
	
	
	/**
	 * Attendee data for this registration
	 * 
	 * @access protected
	 * @var EE_Attendee $_Attendee
	 */
	protected $_Attendee = NULL;
	
	
	/**
	 * Transaction of this Registration
	 * @access protected
	 * @var EE_Tranaction $_Transaction
	 */
	protected $_Transaction = NULL;
	
	
	/**
	 * Ticket object of the Event Datetime(s) this registration is for
	 * @access protected
	 * @var EE_Ticket $_Ticket
	 */
	protected $_Ticket = NULL;
		
	/**
	 * Status of the registration
	 * @access protected
	 * @var EE_Status $_Status
	 */
	protected $_Status = NULL;
	
	/**
	 * Answers made to questions for this registration
	 * @access protected 
	 * @var EE_Answer $_Answer
	 */
	protected $_Answer = NULL;
	
	/**
	 * Check-in
	 * @access protected 
	 * @var EE_Checkin $_Checkin
	 */
	protected $_Checkin = NULL;






	public static function new_instance( $props_n_values = array(), $timezone = NULL ) {
		$classname = __CLASS__;
		$has_object = parent::_check_for_object( $props_n_values, $classname );
		return $has_object ? $has_object : new self( $props_n_values, FALSE, $timezone );
	}




	public static function new_instance_from_db ( $props_n_values = array(), $timezone = NULL ) {
		return new self( $props_n_values, TRUE, $timezone );
	}





	/**
	*		Set Event ID
	* 
	* 		@access		public		
	*		@param		int		$EVT_ID 		Event ID
	*/	
	public function set_event( $EVT_ID = FALSE ) {		
		$this->set('EVT_ID',$EVT_ID);
	}



	/**
	*		Set Attendee ID
	* 
	* 		@access		public		
	*		@param		int		$ATT_ID 		Attendee ID
	*/	
	public function set_attendee_id( $ATT_ID = FALSE ) {		
		$this->set('ATT_ID',$ATT_ID);
	}



	/**
	*		Set Transaction ID
	* 
	* 		@access		public		
	*		@param		int		$TXN_ID 		Transaction ID
	*/	
	public function set_transaction_id( $TXN_ID = FALSE ) {		
		$this->set('TXN_ID',$TXN_ID);
	}



	/**
	*		Set Session 
	* 
	* 		@access		public		
	*		@param		string		$REG_session 		PHP Session ID
	*/	
	public function set_session( $REG_session = FALSE ) {		
		$this->set('REG_session',$REG_session);
	}



	/**
	*		Set Registration Code 
	* 
	* 		@access		public		
	*		@param		string		$REG_code 		Registration Code
	*/	
	public function set_reg_code( $REG_code = FALSE ) {		
		$this->set('REG_code',$REG_code);
	}



	/**
	*		Set Registration URL Link 
	* 
	* 		@access		public		
	*		@param		string		$REG_url_link 		Registration URL Link 
	*/	
	public function set_reg_url_link( $REG_url_link = FALSE ) {		
		$this->set('REG_url_link',$REG_url_link);
	}



	/**
	*		Set Attendee Counter
	* 
	* 		@access		public		
	*		@param		boolean		$REG_count 		Primary Attendee
	*/	
	public function set_count( $REG_count = FALSE ) {		
		$this->set('REG_count',$REG_count);
	}



	/**
	*		Set Group Size
	* 
	* 		@access		public		
	*		@param		boolean		$REG_group_size 		Group Registration
	*/	
	public function set_group_size( $REG_group_size = FALSE ) {		
		$this->set('REG_group_size',$REG_group_size);
	}



	/**
	*		Set Status ID
	* 
	* 		@access		public		
	*		@param		int		$STS_ID 		Status ID
	*/	
	public function set_status( $STS_ID = FALSE ) {	
		//make sure related TXN is set
		$this->get_first_related('Transaction');
		// if status is ANYTHING other than approved, OR if it IS approved AND the TXN is paid in full (or free)
		if ( $STS_ID != EEM_Registration::status_id_approved || ( $STS_ID == EEM_Registration::status_id_approved && $this->_Transaction->is_completed() )) {
			$this->set('STS_ID',$STS_ID);
			return TRUE;
		} else {
			//@tod: this looks awfully like controller or business logic, not model code, imho; Mike
			$txn_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'view_transaction', 'TXN_ID'=>$this->_TXN_ID ), TXN_ADMIN_URL );
			$txn_link = '
			<a id="reg-admin-sts-error-txn-lnk" href="' . $txn_url . '" title="' . __( 'View transaction #', 'event_espresso' ) . $this->_TXN_ID . '">
				' . __( 'View the Transaction for this Registration', 'event_espresso' ) . '
			</a>';			
			$msg =  __( 'Registrations can only be approved if the corresponding transaction is completed and has no monies owing.', 'event_espresso' ) . $txn_link;
			EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
			return FALSE;
		}	
		
	}



	/**
	*		Set Registration Date
	* 
	* 		@access		public		
	*		@param		mixed ( int or string )		$REG_date 		Registration Date - Unix timestamp or string representation of Date
	*/	
	public function set_reg_date( $REG_date = FALSE ) {		
		$this->set('REG_date',$REG_date);
	}



	/**
	*		Set final Price Paid for ticket after all modifications
	* 
	* 		@access		public		
	*		@param		float		$REG_final_price 		Price Paid
	*/	
	public function set_price_paid( $REG_final_price = FALSE ) {		
		$this->set('REG_final_price',$REG_final_price);
	}
	
	/**
	 * @return string of price, with correct decimal places and currency symbol
	 */
	public function pretty_price_paid(){
		return $this->get_pretty('REG_final_price');
	}





	/**
	*		Attendee Is Going
	* 
	* 		@access		public		
	*		@param		boolean		$REG_att_is_going 		Attendee Is Going
	*/	
	public function set_att_is_going( $REG_att_is_going = NULL ) {		
		$this->set('REG_att_is_going',$REG_att_is_going);
	}



	
	/**
	 * Returns the related EE_Transaction to this registration
	 * @return EE_Transaction	 
	 */
	public function transaction(){
		return $this->get_first_related('Transaction');
	}
	
	
	/**
	 * Gets the reltaed attendee
	 * @return EE_Attendee
	 */
	public function attendee(){
		return $this->get_first_related('Attendee');
	}




	/**
	*		get Event ID
	* 		@access		public
	*/	
	public function event_ID() {
		return $this->get('EVT_ID');
	}



	/**
	*		get Event ID
	* 		@access		public
	*/	
	public function event_name() {
		$event = $this->event_obj();
		if($event){
			return $event->name();
		}else{
			return '';
		}
	}


	/**
	 * Fetches teh event this registration is for
	 * @return EE_Event
	 */
	public function event_obj(){
		return $this->get_first_related('Event');
	}


	/**
	*		get Attendee ID
	* 		@access		public
	*/	
	public function attendee_ID() {
		return $this->get('ATT_ID');
	}



	/**
	*		get Transaction ID
	* 		@access		public
	*/	
	public function transaction_ID() {
		return $this->get('TXN_ID');
	}



	/**
	*		get PHP Session ID
	* 		@access		public
	*/	
	public function session_ID() {
		return $this->get('REG_session');
	}



	/**
	*		get Registration Code
	* 		@access		public
	*/	
	public function reg_code() {
		return $this->get('REG_code');
	}



	/**
	*		get Registration URL Link
	* 		@access		public
	*/	
	public function reg_url_link() {
		return $this->get('REG_url_link');
	}
	
	/**
	 * Gets the string which represents the URL for the invoice PDF for this registration (according to EED_Invoice)
	 * Dependant on code in ee/includes/functions/init espresso_export_invoice
	 * @param string $type 'download','launch', or 'html' (default is 'launch')
	 * @return string
	 */
	public function invoice_url($type = 'launch'){
		if($type == 'download'){
			$route = 'download_invoice';
		}else{
			$route = 'launch_invoice';
		}
		$query_args = array('ee'=>$route,'id'=>$this->reg_url_link());
		if($type=='html'){
			$query_args['html'] = true;
		}
		return add_query_arg($query_args,get_permalink(EE_Registry::instance()->CFG->core->thank_you_page_id));
	}
	/**
	 * Gets the string which represents the URL for the 'receipt' PDF, which is currently 
	 * just a variant of the invoice
	 * @param string $type  'download','launch', or 'html' (default is 'launch')
	 * @return string
	 */
	public function receipt_url($type = 'launch'){
		return add_query_arg(array('receipt'=>'true'),$this->invoice_url($type));
	}
	
	
	
	
	
	
	/**
	 * Echoes out invoice_url()
	 * @param string $type  'download','launch', or 'html' (default is 'launch')
	 * @return void
	 */
	public function e_invoice_url($type = 'launch'){
		echo $this->invoice_url($type);
	}
	
	
	
	
	

	
	
	
	
	
	/**
	 * Echoes out payment_overview_url
	 */
	public function e_payment_overview_url(){
		echo $this->payment_overview_url();
	}
	
	
	
	
	
	/**
	 * Gets the URL of the thank you page with this registraiton REG_url_link added as
	 * a query parameter
	 * @return string
	 */
	public function payment_overview_url(){
		return add_query_arg( array('e_reg_url_link'=>$this->reg_url_link() ), get_permalink( EE_Registry::instance()->CFG->core->reg_page_id ));
	}


	
	
	/**
	*		get  Attendee Number
	* 		@access		public
	*/	
	public function count() {
		return $this->get('REG_count');
	}



	/**
	*		get Group Size
	* 		@access		public
	*/	
	public function group_size() {
		return $this->get('REG_group_size');
	}



	/**
	*		get Status ID
	* 		@access		public
	*/	
	public function status_ID() {
		return $this->get('STS_ID');
	}



	/**
	*		get Registration Date
	* 		@access		public
	*/	
	public function date() {
		return $this->get('REG_date');
	}
	
	/**
	 * gets a pretty date
	 * @param string $date_format
	 * @param string $time_format
	 * @return string
	 */
	public function pretty_date($date_format = null, $time_format = null){
		return $this->get_datetime('REG_date', $date_format, $time_format);
	}
	
	

	/**
	 * Gets the event this registration is for
	 * @return EE_Event
	 */
	public function event(){
		return $this->get_first_related('Event');
	}	
	

	/**
	 * Gets the ticket this registration is for
	 * @return EE_Ticket
	 */
	public function ticket($query_params = array()){
		//we're going to assume that when this method is called we always want to receive the attached ticket EVEN if that ticket is archived.  This can be overridden via the incoming $query_params argument
		$remove_defaults = array('default_where_conditions' => 'none');
		$query_params = array_merge($remove_defaults, $query_params);

		return $this->get_first_related('Ticket', $query_params);
	}
	


	/**
	*		get Price Paid
	* 		@access		public
	*/	
	public function price_paid() {
		return $this->get('REG_final_price');
	}
	
	
	
	
	/**
	 * Returns a nice version of the status for displaying to customers
	 * @return string
	 */
	public function pretty_status(){
		require_once( EE_MODELS . 'EEM_Registration.model.php');
		switch($this->status_ID()){
			case EEM_Registration::status_id_approved:
				return __("Approved",'event_espresso');
			case EEM_Registration::status_id_not_approved:
				return __("Not Approved",'event_espresso');
			case EEM_Registration::status_id_pending:
				return __("Pending Approval",'event_espresso');
			case EEM_Registration::status_id_cancelled:
				return __("Cancelled",'event_espresso');
			default:
				return __("Unknown",'event_espresso');
		}
	}

	
	
	/**
	 * Prints out the return value of $this->pretty_status()
	 * @return void
	 */
	public function e_pretty_status(){
		echo $this->pretty_status();
	}





	/**
	*		get Attendee Is Going
	* 		@access		public
	*/	
	public function att_is_going() {
		return $this->get('REG_att_is_going');
	}



	
	/**
	 * Gets related answers
	 * @param array $query_params like EEM_Base::get_all
	 * @return EE_Answer[]
	 */
	public function answers($query_params = null){
		return $this->get_many_related('Answer',$query_params);
	}
	
	/**
	 * Returns the registration date in the 'standard' string format
	 * (function may be improved in the future to allow for different formats and timezones)
	 * @return string
	 */
	public function reg_date(){
		return $this->get_datetime('REG_date');
	}
	
	/**
	 * Gets the datetime-ticket for this registration (ie, it can be used to isolate
	 * the ticket this registration purchased, or the datetime they have registered
	 * to attend)
	 * @return EE_Datetime_Ticket
	 */
	public function datetime_ticket(){
		return $this->get_first_related('Datetime_Ticket');
	}
	
	/**
	 * Sets the registration's datetime_ticket.
	 * @param EE_Datetime_Ticket $datetime_ticket
	 * @return EE_Datetime_Ticket
	 */
	public function set_datetime_ticket($datetime_ticket){
		return$this->_add_relation_to($datetime_ticket, 'Datetime_Ticket');
	}
	
	
	/**
	 * Get the status object of this object
	 * @return EE_Status
	 */
	public function status_obj(){
		return $this->get_first_related('Status');
	}


	/**
	 * Gets the primary datetime related to this registration via the related Event to this registration
	 * @return EE_Datetime 
	 */
	public function get_related_primary_datetime() {
		return $this->get_first_related('Event')->primary_datetime();
	}
	
	/**
	 * Returns the number of times this registration has checked into any of the datetimes
	 * its available for
	 * @return int
	 */
	public function count_checkins(){
		return $this->get_model()->count_related($this,'Checkin');
	}


	/**
	 * Returns the number of current Check-ins this registration is checked into for any of the datetimes the registration is for.  Note, this is ONLY checked in (does not include checkedout)
	 * @return int
	 */
	public function count_checkins_not_checkedout() {
		return $this->get_model()->count_related($this, 'Checkin', array( array('CHK_in' => 1 ) ) );
	}


	/**
	 * This method simply returns the check in status for this registration and the given datetime.
	 * @param  int         $DTT_ID  The ID of the datetime we're checking against (if empty we'll get the primary datetime for this registration (via event) and use it's ID);
	 * @param 	EE_Checkin $checkin If present, we use the given checkin object rather than the dtt_id.
	 * @return int            Integer representing Check-in status.
	 */
	public function check_in_status_for_datetime( $DTT_ID = 0, $checkin = NULL ) {

		if ( empty( $DTT_ID ) && empty( $checkin ) ) {
			$datetime = $this->get_related_primary_datetime();
			$DTT_ID = $datetime->ID();
		}

		if ( empty( $checkin ) )
			//get checkin object (if exists)
			$checkin = $this->get_first_related( 'Checkin', array( array( 'DTT_ID' => $DTT_ID ), 'order_by' => array('CHK_timestamp' => 'DESC' ) ) );
		if ( empty( $checkin ) ) {
			return 0; //never been checked in
		} else if ( $checkin->get('CHK_in' ) ) {
			return 1; //checked in
		} else {
			return 2; //had checked in but is now checked out.
		}
	}




	/**
	 * toggle Check-in status for this registration
	 *
	 * Check-ins are toggled in the followin order:
	 * never checked in -> checkedin
	 * checked in -> checked out
	 * checked out -> never checked in
	 * @param  int    $DTT_ID include specific datetime to toggle Check-in for.  If not included or null, then it is assumed primary datetime is being toggled.
	 * @return int|BOOL            the chk_in status toggled to OR false if nothing got changed.
	 */
	public function toggle_checkin_status( $DTT_ID = NULL ) {
		if ( empty( $DTT_ID ) ) {
			$datetime = $this->get_related_primary_datetime();
			$DTT_ID = $datetime->ID();
		}

		$updated = 0;

		$status_paths = array(
			0 => 1,
			1 => 2,
			2 => 1
			);

		//start by getting the current status so we know what status we'll be changing to.
		$checkin = $this->get_first_related( 'Checkin', array( array( 'DTT_ID' => $DTT_ID ), 'order_by' => array('CHK_timestamp' => 'DESC') ) );
		$cur_status = $this->check_in_status_for_datetime( NULL, $checkin );
		$status_to = $status_paths[$cur_status];

		//add relation - note Check-ins are always creating new rows because we are keeping track of Check-ins over time.  Eventually we'll probably want to show a list table for the individual Check-ins so that can be managed.
		
		
		$new_status = $status_to == 2 ? 0 : $status_to;
		$chk_data = array(
			'REG_ID' => $this->ID(),
			'DTT_ID' => $DTT_ID,
			'CHK_in' => $new_status
			);
		$checkin = EE_Checkin::new_instance( $chk_data );
		$updated = $checkin->save();
		

		if ( $updated == 0 )
			$status_to = FALSE;

		return $status_to;
	}




	/**
	 * This method returns a localized message for the toggled Check-in message.
	 * @param  int    $DTT_ID include specific datetime to get the correct Check-in message.  If not included or null, then it is assumed Check-in for primary datetime was toggled.
	 * @param bool $error This just flags that you want an error message returned. This is put in so that the error message can be customized with the attendee name.
	 * @return string         internationalized message
	 */
	public function get_checkin_msg( $DTT_ID, $error = FALSE ) {
		if ( empty( $DTT_ID ) ) {
			$datetime = $this->get_related_primary_datetime();
			$DTT_ID = $datetime->ID();
		}

		$cur_status = $this->check_in_status_for_datetime($DTT_ID);

		//let's get the attendee as well so we can include the name of the attendee
		$attendee = $this->get_first_related('Attendee');

		if ( $error ) {
			return sprintf( __("%s's check in status was not changed.", "event_espresso"), $attendee->full_name() );
		}

		//what is the status message going to be?
		switch ( $cur_status ) {
			case 0 :
				$msg = sprintf( __("%s has been removed from Check-in records", "event_espresso"), $attendee->full_name()  );
				break;
			case 1 :
				$msg = sprintf( __('%s has been checked in', 'event_espresso'), $attendee->full_name() );
				break;
			case 2 :
				$msg = sprintf( __('%s has been checked out', 'event_espresso'), $attendee->full_name() );
				break;
		}
		return $msg;
	}



	
	/**
	 * genrerates reg code
	 * @return boolean
	 */
	private function _generate_new_reg_code() {
		// generate a reg code ?
		if ( empty( $this->_REG_code )) {			
			// figure out where to start parsing the reg code
			$chars = strpos( $this->_REG_url_link, '-' ) + 4;
			$new_reg_code = array(
				$this->_TXN_ID,
				$this->_TKT_ID,
				substr( $this->_REG_url_link, 0, $chars ) . substr( $this->_REG_url_link, -3 ),
				$this->get_first_related('Transaction')->is_completed() ? 1 : 0
			);
			$new_reg_code = implode( '-', $new_reg_code );
			$new_reg_code = apply_filters( 'FHEE_new_registration_code', $new_reg_code, $this );	
			$this->set( 'REG_code', $new_reg_code );
			return TRUE;
		}
		return FALSE;
	}



	
	/**
	 * increments this registration's related ticket sold and corresponding datetime sold values
	 * @return void
	 */
	public function reserve_registration_space() {
		$this->ticket()->increase_sold();
		$this->ticket()->save();
		$datetimes = $this->ticket()->datetimes();
		foreach ( $datetimes as $datetime ) {
			$datetime->increase_sold();
			$datetime->save();
		}
		// possibly set event status to sold out
		$this->get_first_related( 'Event' )->perform_sold_out_status_check();
	}


	
	/**
	 * decrements (subtracts) this registration's related ticket sold and corresponding datetime sold values
	 * @return void
	 */
	public function release_registration_space() {
		$this->ticket()->decrease_sold();
		$this->ticket()->save();
		$datetimes = $this->ticket()->datetimes();
		foreach ( $datetimes as $datetime ) {
			$datetime->decrease_sold();
			$datetime->save();
		}	
	}



	
	/**
	 * genrerates reg code if that has yet to been done, 
	 * sets reg status based on transaction status and event pre-approval setting
	 * @return void
	 */
	public function finalize() {
		$update_reg = $this->_generate_new_reg_code();
		// update reg status if no monies are owing and event does NOT require pre-approval
		if (( $this->transaction()->is_completed() || $this->transaction()->is_overpaid() ) && ! $this->get_first_related('Event')->require_pre_approval() ) {
			$this->set( 'STS_ID', EEM_Registration::status_id_approved );
			$update_reg = TRUE;
		}		
		// does this registration count towards the reg limits ?
		if ( EE_Registry::instance()->CFG->registration->pending_counts_reg_limit  || $this->transaction()->is_completed() || $this->transaction()->is_overpaid() ) {
			$this->reserve_registration_space();
		}
			
		if ( $update_reg ) { 
			$this->save();
		}
			
	}




}
/* End of file EE_Registration.class.php */
/* Location: includes/classes/EE_Registration.class.php */