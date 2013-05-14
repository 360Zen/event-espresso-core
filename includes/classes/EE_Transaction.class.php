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
require_once('EE_Base_Class.class.php');
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
    *	Transaction Details
	* 
    *	notes regarding the transaction
	*  
	*	@access	protected
    *	@var string	
    */
	protected $_TXN_details = NULL;	
	
	
	
    /**
    *	session data
	* 
    *	dump off the entire session object 
	* 
	*	@access	protected
    *	@var string	
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







	public static function new_instance( $props_n_values = array(), $timezone = NULL ) {
		$classname = __CLASS__;
		$has_object = parent::_check_for_object( $props_n_values, $classname );
		return $has_object ? $has_object : new self( $props_n_values, FALSE, $timezone );
	}




	public static function new_instance_from_db ( $props_n_values = array() ) {
		return new self( $props_n_values, TRUE );
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
	*		Set transaction total
	* 
	* 		@access		public		
	*		@param		string		$details 		notes regarding the transaction
	*/	
	public function set_details( $details = FALSE ) {
		return $this->set('TXN_details',$details);
	}






	/**
	*		Set session data within the TXN object
	* 
	* 		@access		public		
	*		@param		string		$details 		dump off the entire session object 
	*/	
	public function set_txn_session_data( $session_data = FALSE ) {	
		return	$this->set('TXN_session_data',$session_data);
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
	*/	
	public function total() {
		return $this->get('TXN_total');
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
	*		get Transaction Details
	* 		@access		public
	*/	
	public function details() {
		return $this->get('TXN_details');
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
	public function primary_registration( $return_obj = FALSE ){
		require_once('EEM_Registration.model.php');
		$result = $this->get_first_related('Registration', array(array('REG_count'=>  EEM_Registration::PRIMARY_REGISTRANT_COUNT)));
		return $result;//$return_obj ? array_shift($result) : $result;
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
		require_once('EEM_Payment.model.php');
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
			case EEM_Transaction::pending_status_code:
				return __('Pending Payment','event_espresso');
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
		if($this->status_ID() == EEM_Transaction::pending_status_code){
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
	 * This returns the url for the invoice of this transaction
	 *
	 * @access public
	 * @return string
	 */
	public function invoice_url() {
		$REG = $this->primary_registration();
		if ( empty( $REG ) ) return false;
		return $REG->invoice_url();
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



}



/* End of file EE_Transaction.class.php */
/* Location: includes/classes/EE_Transaction.class.php */		