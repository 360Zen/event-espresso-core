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
 * EE_Transaction class
 *
 * @package			Event Espresso
 * @subpackage		includes/classes/EE_Transaction.class.php
 * @author				Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
require_once( EE_CLASSES . 'EE_Base_Class.class.php');
class EE_Transaction extends EE_Base_Class{
	
    /**
    *	Transaction ID
	* 
	* 	primary key
	*	
	* 	@access	protected
    *	@var int	
    */
	protected $_TXN_ID = FALSE;

	
	
	
    /**
    *	Timestamp
	* 
	*	date / time
	*  
	*	@access	protected
    *	@var timestamp	
    */
	protected $_TXN_timestamp = NULL;
	
	
	
    /**
    *	Total Cost for Transaction
	* 
	* 	note: always use Decimal(10,2) as SQL type for money
	*
	*	@access	protected
    *	@var float	
    */
	protected $_TXN_total = 0;	
	
	
	
    /**
    *	Total Amount Paid to Date
	* 
	* 	note: always use Decimal(10,2) as SQL type for money
	*
	*	@access	protected
    *	@var float	
    */
	protected $_TXN_paid = 0;	
	
	
    /**
    *	Transaction Status
	*
	*	foreign key from status type table - 3 character string
	* 
	*	@access	protected
    *	@var string	
    */
	protected $_STS_ID = NULL;
	
	
	
	
	/**
	*	session data
	* 
	*	EE_Sesssion session_data array from when this txn was generated
	* 
	*	@access	protected
	*	@var array	
	*/
	protected $_TXN_session_data = NULL;	
	
	
	
    /**
    *	Hash Salt
	* 
    *	required for some payment gateways
	* 
	*	@access	protected
    *	@var string	
    */
	protected $_TXN_hash_salt = NULL;		



    /**
    *	Tax Data
	* 
    *	information regarding taxes
	* 
	*	@access	protected
    *	@var array	
    */
	protected $_TXN_tax_data = NULL;	



    /**
    *	datetime format
	* 
    *	pattern or format for displaying dates and times
	* 
	*	@access	private
    *	@var string	
    */
	private $dt_frmt = 'F j, Y g:i a';	

	
	/**
	 * Registrations on this transaction
	 * @var EE_Registration[]
	 */
	protected $_Registration = NULL;
	
	
	
	/**
	 * Payments for this transaction
	 * @var EE_Payment[]
	 */
	protected $_Payment = NULL;

	
	/**
	 *
	 * @var EE_Status
	 */
	protected $_Status = NULL;

	
	/**
	 * @var EE_Promotion_Object relation to teh join table between promotions and whatevers
	 */
	protected $_Promotion_Object = NULL;
	
	/**
	 * All the line items associated with this transaction. Note: the line item of type 'total'
	 * is the most important one, and should eb the parent of all other line items 
	 * (meaning it is the result of all the other line items)
	 * @var EE_Line_Item[]
	 */
	protected $_Line_Item = NULL;


	/**
	 * 
	 * $var EE_Extra_Meta[]
	 */
	protected $_Extra_Meta = NULL;

	/**
	 * 
	 * @param type $props_n_values
	 * @param type $timezone
	 * @return EE_Transaction
	 */
	public static function new_instance( $props_n_values = array(), $timezone = NULL ) {
		$classname = __CLASS__;
		$has_object = parent::_check_for_object( $props_n_values, $classname );
		return $has_object ? $has_object : new self( $props_n_values, FALSE, $timezone );
	}



	/**
	 * 
	 * @param type $props_n_values
	 * @param type $timezone
	 * @return EE_Transaction
	 */
	public static function new_instance_from_db ( $props_n_values = array(), $timezone = NULL ) {
		return new self( $props_n_values, TRUE, $timezone );
	}





	/**
	*		Set transaction total
	* 
	* 		@access		public		
	*		@param		float		$total 		total value of transaction 
	*/	
	public function set_total( $total = FALSE ) {
		$this->set('TXN_total',$total);
	}





	/**
	*		Set Total Amount Paid to Date
	* 
	* 		@access		public		
	*		@param		float		$total_paid 		total amount paid to date (sum of all payments)
	*/	
	public function set_paid( $total_paid = FALSE ) {
		$this->set('TXN_paid',$total_paid);
	}





	/**
	*		Set transaction status
	* 
	* 		@access		public		
	*		@param		string		$status 		whether the transaction is open, declined, accepted, or any number of custom values that can be set
	*/	
	public function set_status( $status = FALSE ) {
		$this->set('STS_ID',$status);
	}








	/**
	*		Set session data within the TXN object
	* 
	* 		@access		public		
	*		@param		NULL  $session_data 
	*/	
	public function set_txn_session_data( $session_data ) {
		if ( $session_data instanceof EE_Session ) {
			return $this->set( 'TXN_session_data', $session_data->get_session_data() );
		} else {
			return $this->set( 'TXN_session_data', $session_data );
		}
		return FALSE;			
	}





	/**
	*		Set hash salt
	* 
	* 		@access		public		
	*		@param		string		$hash_salt 		required for some payment gateways
	*/	
	public function set_hash_salt( $hash_salt = FALSE ) {
		$this->set('TXN_hash_salt',$hash_salt);
	}






	/**
	*		Set tax data
	* 
	* 		@access		public		
	*		@param		string		$tax_data 		information regarding taxes
	*/	
	public function set_tax_data( $tax_data = FALSE ) {
		$this->set('TXN_tax_data');
	}


	/**
	*		get Transaction Total
	* 		@access		public
	 * @return float
	*/	
	public function total() {
		return $this->get('TXN_total');
	}

	/**
	 * 
	 * @return string of transaction's total cost, with currency symbol and decimal
	 */
	public function pretty_total(){
		return $this->get_pretty('TXN_total');
	}




	/**
	*		get Total Amount Paid to Date
	* 		@access		public
	*		@return float
	*/	
	public function paid() {
		return $this->get('TXN_paid');
	}
	
	/**
	 * Gets the amount paid in a pretty string (formatted and with currency symbol)
	 * @return string
	 */
	public function pretty_paid(){
		return $this->get_pretty('TXN_paid');
	}



	/**
	 * calculate the amount remaining for this transaction and return;
	 *
	 * @access public
	 * @return float amount remaining
	 */
	public function remaining() {
		return $this->total() - $this->paid();
	}




	/**
	*		get Transaction Status 
	* 		@access		public
	*/	
	public function status_ID() {
		return $this->get('STS_ID');
	}




	/**
	*		get Transaction session data
	* 		@access		public
	*/	
	public function session_data() {
		return $this->get('TXN_session_data' );
	}




	/**
	*		get Transaction hash salt
	* 		@access		public
	*/	
	public function hash_salt_() {
		return $this->get('TXN_hash_salt');
	}




	/**
	*		get Transaction tax data
	* 		@access		public
	*/	
	public function tax() {
		return $this->get('TXN_tax_data');
	}




	/**
	*		get Transaction datetime
	* 		@param 		boolean		$format - whether to format date  - defaults to FALSE (return timestamp)
	* 		@param 		string			$dt_frmt - defaults to 'F j, Y g:i a'
	* 		@access		public
	*/	
	public function datetime( $format = FALSE, $dt_frmt = FALSE ) {
		return $this->get('TXN_timestamp');
	}	
	
	
	
	
	/**
	 * Gets registrations on this transaction
	 * @return EE_Registration[]
	 */
	public function registrations(){
		return $this->get_many_related('Registration');
	}
	
	/**
	 * Gets all the attendees for this transaction (handy for use with EE_Attendee's get_registrations_for_event function
	 * for getting attendees and how many registrations they each have for an event)
	 * @param string $output like 'OBJECT_K' or 'COUNT', like EEM_Base's select_all_where's $output parameter
	 * @return mixed EE_Attendee[] by default, int if $output is set to 'COUNT'
	 */
	public function attendees(){
		return $this->get_many_related('Attendee', array(array('Registration.Transaction.TXN_ID'=>$this->ID())));
	}
	
	/**
	 * Gets teh primary registration only
	 * @return EE_Registration
	 */
	public function primary_registration(){
		return $this->get_first_related('Registration', array(array( 'REG_count'=>  EEM_Registration::PRIMARY_REGISTRANT_COUNT )));
	}
	
	
	
	/**
	 * Gets payments for this transaction. Unlike other such functions, order by 'DESC' by default
	 * @param array $query_params like EEM_Base::get_all
	 * @return EE_Payment[]
	 */
	public function payments($query_params = array() ){
		return $this->get_many_related('Payment',$query_params);
	}
	
	
	/**
	 * gets only approved payments for this transaction
	 * @return EE_Payment[]
	 */
	public function approved_payments(){
		EE_Registry::instance()->load_model( 'Payment' );
		return $this->get_many_related('Payment', array(array('STS_ID'=>  EEM_Payment::status_id_approved), 'order_by'=>array('PAY_timestamp' =>'DESC')));
	}
	
	
	/**
	 * returns a pretty version of the status, good for displayign to users
	 * @return string
	 */
	public function pretty_status(){
		switch($this->status_ID()){
			case EEM_Transaction::complete_status_code:
				return __("Complete",'event_espresso');
			case EEM_Transaction::incomplete_status_code:
				return __('Incomplete','event_espresso');
			case EEM_Transaction::open_status_code:
				return __('Pending Payment','event_espresso');
			case EEM_Transaction::overpaid_status_code:
				return __('Overpaid','event_espresso');
			default:
				return __('Unknown','event_espresso');
		}
	}
	
	
	/**
	 * echoes $this->pretty_status()
	 * @return void
	 */
	public function e_pretty_status(){
		echo $this->pretty_status();
	}
	
	
	
	/**
	 * Returns whether this transaction is complete
	 * Useful in templates and other logic for deciding if we should ask for another payment...
	 * @return boolean
	 */
	public function is_completed(){
		if($this->status_ID()==EEM_Transaction::complete_status_code){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	/**
	 * Returns whether this transaction is pending
	 * Useful in templates and other logic for deciding if we should ask for another payment...
	 * @return boolean
	 */
	public function is_pending(){
		if($this->status_ID() == EEM_Transaction::open_status_code){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	
	/**
	 * Returns whether this transaction is incomplete
	 * Useful in templates and other logic for deciding if we should ask for another payment...
	 * @return boolean
	 */
	public function is_incomplete(){
		if($this->status_ID() == EEM_Transaction::incomplete_status_code){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Returns whether this transaction is overpaid
	 * Useful in templates and other logic for deciding if we should ask for another payment...
	 * @return boolean
	 */
	public function is_overpaid(){
		if($this->status_ID() == EEM_Transaction::overpaid_status_code){
			return true;
		}else{
			return false;
		}
	}



	/**
	 * This returns the url for the invoice of this transaction
	 * @param string $type  'download','launch', or 'html' (default is 'launch')
	 * @access public
	 * @return string
	 */
	public function invoice_url($type = 'launch') {
		$REG = $this->primary_registration();
		if ( empty( $REG ) ) return false;
		return $REG->invoice_url($type);
	}
	/**
	 * Gets the URL for viewing the 
	 * @param string $type  'download','launch', or 'html' (default is 'launch')
	 * @return string
	 */
	public function receipt_url($type = 'launch'){
		$REG = $this->primary_registration();
		if ( empty( $REG ) ) return false;
		return $REG->receipt_url($type);
	}



	/**
	 * Gets the URL of the thank you page with this registraiton REG_url_link added as
	 * a query parameter
	 *
	 * @access public
	 * @return string
	 */
	public function payment_overview_url() {
		$REG = $this->primary_registration();
		if ( empty($REG) ) return false;
		return $REG->payment_overview_url();
	}
	
	
	
	
	/**
	 * Updates the transaction's status and total_paid based on all the payments
	 * taht apply to it
	 * @return boolean success of the application
	 */
	public function update_based_on_payments(){
		return $this->get_model()->update_based_on_payments($this);
	}



	public function selected_gateway() {
		return $this->get_extra_meta('gateway', true);
	}



	public function gateway_response_on_transaction() {
		$payment = $this->get_first_related('Payment');
		$details = !empty( $payment ) ? $this->get_first_related('Payment')->details() : array();

		return isset($details['response_msg']) ? $details['response_msg'] : '';
	}



	/**
	 * Get the status object of this object
	 * @return EE_Status
	 */
	public function status_obj(){
		return $this->get_first_related('Status');
	}
	
	/**
	 * Gets all the extra meta info on this payment
	 * @param array $query_params like EEM_Base::get_all
	 * @return EE_Extra_Meta
	 */
	public function extra_meta($query_params = array()){
		return $this->get_many_related('Extra_Meta', $query_params);
	}

	/**
	 * Wrapper for _add_relation_to
	 * @param type $registration
	 * @return EE_Base_Class the relation was added to
	 */
	public function add_registration($registration){
		return $this->_add_relation_to($registration, 'Registration');
	}
	/**
	 * Removes the given registration from being related (even before saving thist ransaction).
	 * If an ID/index is provided and this transaction isn't saved yet, removes it from list of cached relations
	 * @param type $registration_or_id
	 * @return EE_Base_Class that was removed from being related
	 */
	public function remove_registration_with_id($registration_or_id){
		return $this->_remove_relation_to($registration_or_id, 'Registration');
	}
	
	/**
	 * Gets ALL the line items related to this transaction (unstructured)
	 * @param type $query_params
	 * @return EE_Line_Item[]
	 */
	public function line_items($query_params){
		return $this->get_many_related('Line_Item', $query_params);
	}
	/**
	 * Gets all the line items which are for ACTUAL items
	 * @return EE_Line_Item[]
	 */
	public function items_purchased(){
		return $this->line_items(array(array('LIN_type'=>  EEM_Line_Item::type_line_item)));
	}
	
	/**
	 * Gets all the line items which are taxes on the total
	 * @return EE_Line_Item[]
	 */
	public function tax_items(){
		return $this->line_items(array(array('LIN_type'=>  EEM_Line_Item::type_tax)));
	}

	/**
	 * Gets the total line item (which is a parent of all other related line items,
	 * meaning it takes them all intou account on its total)
	 * @return EE_Line_Item
	 */
	public function total_line_item(){
		return $this->get_first_related('Line_Item', array(array('LIN_type'=>  EEM_Line_Item::type_total)));
	}



	/**
	 * cycles thru related registrations and calls finalize_registration() on each
	 * @return void
	 */
	public function finalize(){
		//$registrations = $this->get_many_related('Registration');
		foreach ( $this->get_many_related('Registration') as $registration ) {
			$registration->finalize();
		}
		if (( ! is_admin() || EE_Registry::instance()->REQ->is_set( 'ee_front_ajax' ) && EE_Registry::instance()->REQ->get( 'ee_front_ajax' )) && ! EE_Registry::instance()->REQ->is_set( 'e_reg_url_link' )) {
//			//remove the session from the transaction before saving it to the db to minimize recursive relationships
			$this->set_txn_session_data( NULL );
//			// save this transaction and it's registrations to the session
			EE_Registry::instance()->SSN->set_session_data( array( 'transaction' => $this ));
			// save the session (with it's sessionless transaction) back to this transaction... we need to go deeper!
			$this->set_txn_session_data( EE_Registry::instance()->SSN );
			// save the transaction to the db
			$this->save();
		}
	}
	
	/**
	 * Gets the last payment made
	 * @return EE_Payment
	 */
	public function last_payment(){
		return $this->get_first_related('Payment',array('order_by'=>array('PAY_ID'=>'desc')));
	}

	/**
	 *  Gets the array of billing info for the gateway and for this transaction's primary registration's attendee.
	 * @param string $gateway_name the gateway class' _gateway_name property
	 * @return array exactly like EE_Onsite_Gateway->espresso_reg_page_billing_inputs(),
	 * where keys are names of fields, and values are an array of settings (the most important keys being
	 * 'label' and 'value)
	 */
	public function billing_info(){
		$gateway_name = $this->get_extra_meta('gateway',true);
		if ( ! $gateway_name){
			$last_payment = $this->last_payment();
			if( $last_payment){
				$gateway_name = $last_payment->gateway();
			}
		}
		if ( !$gateway_name){
			EE_Error::add_error(__("Could not find billing info for transaction because no gateway has been used for it yet", "event_espresso"), __FILE__, __FUNCTION__, __LINE__);
			return false;
		}
		$primary_reg = $this->primary_registration();
		if( ! $primary_reg ){
			EE_Error::add_error(__("Cannot get billing info for gateway %s on transaction because no primary registration exists", "event_espresso"), __FILE__, __FUNCTION__, __LINE__);
			return false;
		}
		$attendee = $primary_reg->attendee();
		if ( ! $attendee){
			EE_Error::add_error(__("Cannot get billing info for gateway %s on transaction because teh primary registration has no attendee exists", "event_espresso"), __FILE__, __FUNCTION__, __LINE__);
			return false;
		}
		return $attendee->billing_info_for_gateway($gateway_name);
	}



	
	/**
	 * process EE_Transaction object prior to serialization
	 * @return array
	 */
	public function __sleep() {
		// the transaction stores a record of the session_data array, and the session_data array has a copy of the transaction
		if ( isset( $this->_TXN_session_data['transaction'] ) && $this->_TXN_session_data['transaction'] instanceof EE_Transaction ) {
			// but we don't want that copy of the transaction
			$this->_TXN_session_data['transaction'] = NULL;
		}		
		
		$properties_to_serialize = array(
			'_TXN_ID',
			'_TXN_timestamp',
			'_TXN_total',
			'_TXN_paid',
			'_STS_ID',
			'_TXN_session_data',
			'_TXN_hash_salt',
			'_TXN_tax_data',
			'dt_frmt',
			'_Registration',
			'_Payment',
			'_Status',
			'_Promotion_Object',
			'_Line_Item',
			'_Extra_Meta'
		);

		return $properties_to_serialize;
	}



}/* End of file EE_Transaction.class.php */
/* Location: includes/classes/EE_Transaction.class.php */