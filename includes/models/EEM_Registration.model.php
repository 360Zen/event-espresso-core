<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * Registration Model
 *
 * @package			Event Espresso
 * @subpackage		includes/models/
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
require_once ( EE_MODELS . 'EEM_Base.model.php' );
require_once ( EE_CLASSES . 'EE_Registration.class.php' );


class EEM_Registration extends EEM_Base {

  	// private instance of the Registration object
	private static $_instance = NULL;

	/**
	 * Keys are the status IDs for registrations (eg, RAP, RCN, etc), and the values 
	 * are status codes (eg, approved, cancelled, etc)
	 * @var array 
	 */
	private static $_reg_status;

	/**
	 * The value of REG_count for a primary registrant
	 */
	const PRIMARY_REGISTRANT_COUNT = 1;

	
	
	/**
	 * Status ID (STS_ID on esp_status table) to indicate a pending registration
	 */
	const status_id_pending = 'RPN';
	
	
	/**
	 * Status ID (STS_ID on esp_status table) to indicate a cancelled registration
	 */
	const status_id_cancelled = 'RCN';
	
	
	/**
	 * Status ID (STS_ID on esp_status table) to indicate an approved registration
	 */
	const status_id_approved = 'RAP';
	
	
	/**
	 * Status ID (STS_ID on esp_status table) to indicate an unapproved registration
	 */
	const status_id_not_approved = 'RNA';
	/**
	 *		private constructor to prevent direct creation
	 *		@Constructor
	 *		@access protected
	 *		@param string $timezone string representing the timezone we want to set for returned Date Time Strings (and any incoming timezone data that gets saved).  Note this just sends the timezone info to the date time model field objects.  Default is NULL (and will be assumed using the set timezone in the 'timezone_string' wp option)
	 *		@return void
	 */
	protected function __construct( $timezone ) {
		$this->singular_item = __('Registration','event_espresso');
		$this->plural_item = __('Registrations','event_espresso');
		$this->_get_registration_status_array();
//		require_once(EE_CLASSES . 'EE_Registration.class.php');
		$this->_allowed_statuses=apply_filters('FHEE__EEM_Registration__allowed_statuses', self::$_reg_status );

		$this->_tables = array(
			'Registration'=>new EE_Primary_Table('esp_registration','REG_ID')
		);
		$this->_fields = array(
			'Registration'=>array(
				'REG_ID'=>new EE_Primary_Key_Int_Field('REG_ID', __('Registration ID','event_espresso'), false, 0),
				'EVT_ID'=>new EE_Foreign_Key_Int_Field('EVT_ID', __('Even tID','event_espresso'), false, 0, 'Event'),
				'ATT_ID'=>new EE_Foreign_Key_Int_Field('ATT_ID', __('Attendee ID','event_espresso'), false, 0, 'Attendee'),
				'TXN_ID'=>new EE_Foreign_Key_Int_Field('TXN_ID', __('Transaction ID','event_espresso'), false, 0, 'Transaction'),
				'DTT_ID'=>new EE_Foreign_Key_Int_Field('DTT_ID', __('Datetime ID','event_espresso'), false, 0, 'Datetime'),
				'PRC_ID'=>new EE_Foreign_Key_Int_Field('PRC_ID', __('Price ID','event_espresso'), false, 0, 'Price'),
				'STS_ID'=>new EE_Foreign_Key_String_Field('STS_ID', __('Status ID','event_espresso'), false, EEM_Registration::status_id_not_approved, 'Status'),
				'REG_date'=>new EE_Datetime_Field('REG_date', __('Time registration occured','event_espresso'), false, current_time('timestamp'), $timezone ),
				'REG_final_price'=>new EE_Money_Field('REG_final_price', __('Final Price of registration','event_espresso'), false, 0),
				'REG_session'=>new EE_Plain_Text_Field('REG_session', __('Session ID of registration','event_espresso'), false, ''),
				'REG_code'=>new EE_Plain_Text_Field('REG_code', __('Unique Code for this registration','event_espresso'), false, ''),
				'REG_url_link'=>new EE_Plain_Text_Field('REG_url_link', __('String to be used in URL for identifying registration','event_espresso'), false, ''),
				'REG_count'=>new EE_Integer_Field('REG_count', __('Count of this registration in the group registraion ','event_espresso'), true, 1),
				'REG_group_size'=>new EE_Integer_Field('REG_group_size', __('Number of registrations on this group','event_espresso'), false, 1),
				'REG_att_is_going'=>new EE_Boolean_Field('REG_att_is_going', __('Flag indicating the registrant plans on attending','event_espresso'), false, false),
				'REG_att_checked_in'=>new EE_Boolean_Field('REG_att_checked_in', __('Flag indicating the registrant has checked in','event_espresso'), false, false),
			)
		);
		$this->_model_relations = array(
			'Event'=>new EE_Belongs_To_Relation(),
			'Attendee'=>new EE_Belongs_To_Relation(),
			'Transaction'=>new EE_Belongs_To_Relation(),
			'Datetime'=>new EE_Belongs_To_Relation(),
			'Price'=>new EE_Belongs_To_Relation(),
			//'Status'=>new EE_Belongs_To_Relation(),
			'Answer'=>new EE_Has_Many_Relation()
		);
		
		parent::__construct( $timezone );
	}



	/**
	 * defines  table name as a constant
	 * @access public
	 */
	public static function define_table_name() {
		global $wpdb;
		define( 'EE_REGISTRATION_TABLE', $wpdb->prefix . 'esp_registration' );
	}



	/**
	 *		This funtion is a singleton method used to instantiate the Espresso_model object
	 *
	 *		@access public
	 *		@param string $timezone string representing the timezone we want to set for returned Date Time Strings (and any incoming timezone data that gets saved).  Note this just sends the timezone info to the date time model field objects.  Default is NULL (and will be assumed using the set timezone in the 'timezone_string' wp option)
	 *		@return EEM_Registration instance
	 */
	public static function instance( $timezone = NULL ){

		// check if instance of Espresso_model already exists
		if ( self::$_instance === NULL ) {
			// instantiate Espresso_model
			self::$_instance = new self( $timezone );
		}

		//we might have a timezone set, let set_timezone decide what to do with it
		self::$_instance->set_timezone( $timezone );
		
		// Espresso_model object
		return self::$_instance;
	}








	/**
	 * 		get list of registration statuses
	*		@access private
	*		@return void
	*/
	public static function reg_status_array() {
		call_user_func( array( __CLASS__, '_get_registration_status_array' ));
		return self::$_reg_status;
	}








	/**
	 * 		get list of registration statuses
	*		@access private
	*		@return void
	*/
	private function _get_registration_status_array() {

		global $wpdb;
		$SQL = 'SELECT STS_ID, STS_code FROM '. $wpdb->prefix . 'esp_status WHERE STS_type = "registration"';
		$results = $wpdb->get_results( $SQL );

		self::$_reg_status = array();
		foreach ( $results as $status ) {
			self::$_reg_status[ $status->STS_ID ] = $status->STS_code;
		}
	}




	/**
	*		retreive ALL registrations for a particular Attendee from db
	* 		@access		public
	* 		@param		int		$ATT_ID
	*		@return 	EE_Registration[]
	*/
	public function get_all_registrations_for_attendee( $ATT_ID = FALSE, $status_array = FALSE ) {

		if ( ! $ATT_ID ) {
			return FALSE;
		}
		if ( $registrations = $this->get_all( array( array( 'ATT_ID' => $ATT_ID )))) {
			return $registrations;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Gets a registration given their REG_url_link. Yes, this should usually 
	 * be passed via a GET parameter.
	 * @param string $REG_url_link
	 * @return EE_Registration
	 */
	public function get_registration_for_reg_url_link($REG_url_link){
		if(!$REG_url_link){
			return false;
		}
		return $this->get_one(array(array('REG_url_link'=>$REG_url_link)));
	}




	/**
	*		retreive registration for a specific transaction attendee from db
	* 
	* 		@access		public
	* 		@param		$TXN_ID
	* 		@param		$ATT_ID
	* 		@param		$att_nmbr 	in case the ATT_ID is the same for multiple registrations (same details used) then the attendee number is required
	*		@return 		mixed		array on success, FALSE on fail
	*/
	public function get_registration_for_transaction_attendee( $TXN_ID = FALSE, $ATT_ID = FALSE, $att_nmbr = FALSE ) {
		return $this->get_one(array(
			array(
				'TXN_ID'=>$TXN_ID,
				'ATT_ID'=>$ATT_ID),
			'limit'=>array($att_nmbr-1,1)
		));
	}




	
	/**
	*		return a list of attendees for a specific locale for the Registration Overview Admin page
	* 		@access		public
	*/
	public function get_registrations_for_admin_page( $EVT_ID = FALSE, $CAT_ID = FALSE, $reg_status = FALSE, $month_range = FALSE, $today_a = FALSE, $this_month_a = FALSE, $start_date = FALSE, $end_date = FALSE, $orderby = 'REG_date', $order = 'DESC', $limit = NULL, $count = FALSE ) {

		global $wpdb;

		//Dates
		$curdate = date('Y-m-d', current_time('timestamp'));
		$this_year_r = date('Y', current_time('timestamp'));
		$this_month_r = date('m', current_time('timestamp'));
		$days_this_month = date( 't', current_time('timestamp') );
		$time_start = ' 00:00:00';
		$time_end = ' 23:59:59';

		$sql_clause = ' WHERE ';
		$SQL = '(';

		// get list of attendees for regional managers locale
		if (function_exists('espresso_member_data') && espresso_member_data('role') == 'espresso_group_admin') {

			$locales = get_user_meta(espresso_member_data('id'), "espresso_group", true);

			if ( $locales != '' ) {
				$locales = unserialize($locales);

				$locales = implode(",", $locales);
			} else {
				$locales = FALSE;
			}

			$SQL .= $count ? "SELECT COUNT(reg.ATT_ID)" : "SELECT att.*, reg.*, dtt.*, reg.STS_ID REG_status, CONCAT(ATT_fname, ' ', ATT_lname) as REG_att_name, evt.id event_id, evt.event_name, evt.require_pre_approval, txn.TXN_ID, txn.TXN_timestamp, txn.TXN_total, txn.STS_ID AS txn_status, txn.TXN_details, txn.TXN_tax_data, txn.TXN_paid, PRC_amount, PRC_name";
			$SQL .= ' FROM ' . $wpdb->prefix . 'esp_attendee att';
			$SQL .= ' JOIN ' . $this->_get_main_table()->get_table_name() . ' reg ON reg.ATT_ID = att.ATT_ID';
			$SQL .= ' LEFT JOIN ' . EVENTS_DETAIL_TABLE . ' evt ON evt.id = reg.EVT_ID ';
			$SQL .= ' LEFT JOIN ' . $wpdb->prefix . 'esp_transaction txn ON txn.TXN_ID = reg.TXN_ID';
			$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_price prc ON prc.PRC_ID = reg.PRC_ID';
			$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_datetime dtt ON dtt.DTT_ID = reg.DTT_ID';

			if ( $CAT_ID ) {
				$SQL .= ' JOIN ' . EVENTS_CATEGORY_REL_TABLE . ' ect ON ect.event_id = evt.id ';
				$SQL .= ' JOIN ' . EVENTS_CATEGORY_TABLE . ' cat ON  cat.id = ect.cat_id ';
			}

			if ( $locales ) {
				$SQL .= ' JOIN ' . EVENTS_VENUE_REL_TABLE . ' evn ON evn.event_id = evt.id ';
				$SQL .= ' JOIN ' . EVENTS_LOCALE_REL_TABLE . ' loc ON  loc.venue_id = evn.venue_id ';
			}

			$sql_clause = ' WHERE ';

			if ( $CAT_ID ) {
				$SQL .= $sql_clause .' cat.id = "' . $CAT_ID . '"" ';
				$sql_clause = ' AND ';
			}

			if ( $reg_status ) {
				$SQL .= $sql_clause ." reg.STS_ID = '$reg_status'";
				$sql_clause = ' AND ';
			} else {
				$SQL .= $sql_clause ." reg.STS_ID != 'RCN'";
				$sql_clause = ' AND ';
			}


			if ( $month_range ) {
				$pieces = explode('-', $month_range, 3);
				$year_r = $pieces[0];
				$month_r = $pieces[1];

				$SQL .= $sql_clause .' reg.REG_date BETWEEN "' . strtotime( $month_r . ' 01 ' . $year_r . ' ' . $time_start ) . '" ';
				$SQL .= 'AND "' . strtotime( $month_r . ' ' . date( 't', strtotime( $year_r . ' ' . $month_r )) . ' ' . $year_r . ' ' . $time_end )  . '"';
				$sql_clause = ' AND ';
			}

			if ( $EVT_ID ) {
				$SQL .= $sql_clause .' reg.EVT_ID = "' . $EVT_ID  . '"';
				$sql_clause = ' AND ';
			}


			if ( $today_a ) {
				$SQL .= $sql_clause .' reg.REG_date BETWEEN "' . strtotime($curdate . $time_start) . '" AND "' . strtotime($curdate . $time_end)  . '"';
				$sql_clause = ' AND ';
			}

			if ( $this_month_a ) {
				$SQL .= $sql_clause .' reg.REG_date BETWEEN "' . strtotime( $this_month_r . ' 01 ' . $this_year_r . ' ' . $time_start ) . '" ';
				$SQL .= 'AND "' . strtotime( $this_month_r . ' ' . $days_this_month . ' ' . $this_year_r . ' ' . $time_end )  . '"';
				$sql_clause = ' AND ';
			}

			if ( $locales ) {
				$SQL .= $sql_clause . ' locale_id IN (' . $locales . ') ';
			}

			$SQL .= ' AND evt.event_status != "D" ';
			$SQL .= ') UNION (';

			// end if (function_exists('espresso_member_data')

		}

		$SQL .= $count ? "SELECT COUNT(reg.ATT_ID)" : "SELECT att.*, reg.*, dtt.*, reg.STS_ID REG_status, evt.id event_id, evt.event_name, CONCAT(ATT_fname, ' ', ATT_lname) as REG_att_name, evt.require_pre_approval, txn.TXN_ID, TXN_timestamp, TXN_total, txn.STS_ID AS txn_status, TXN_details, TXN_tax_data, txn.TXN_paid, PRC_amount, PRC_name";
		$SQL .= ' FROM ' . $wpdb->prefix . 'esp_attendee att';
		$SQL .= ' LEFT JOIN ' . $this->_get_main_table()->get_table_name() . ' reg ON reg.ATT_ID = att.ATT_ID';
		$SQL .= ' LEFT JOIN ' . EVENTS_DETAIL_TABLE . ' evt ON evt.id = reg.EVT_ID';
		$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_transaction txn ON txn.TXN_ID = reg.TXN_ID';
		$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_price prc ON prc.PRC_ID = reg.PRC_ID';
		$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_datetime dtt ON dtt.DTT_ID = reg.DTT_ID';

		if ( $CAT_ID ) {
			$SQL .= ' JOIN ' . EVENTS_CATEGORY_REL_TABLE . ' ect ON ect.event_id = evt.id';
			$SQL .= ' JOIN ' . EVENTS_CATEGORY_TABLE . ' cat ON  cat.id = ect.cat_id';
		}

		$sql_clause = ' WHERE ';

		if ( $CAT_ID ) {
			$SQL .= $sql_clause .'cat.id = "' . $CAT_ID . '"';
			$sql_clause = ' AND ';
		}

		if ( $reg_status ) {
			$SQL .= $sql_clause ." reg.STS_ID = '$reg_status'";
			$sql_clause = ' AND ';
		} else {
			$SQL .= $sql_clause ." reg.STS_ID != 'RCN'";
			$sql_clause = ' AND ';
		}

		if ( $EVT_ID ) {
			$SQL .= $sql_clause .' reg.EVT_ID = "' . $EVT_ID  . '"';
			$sql_clause = ' AND ';
		}

		if ( $month_range ) {
			$pieces = explode('-', $month_range, 3);
			$year_r = $pieces[0];
			$month_r = $pieces[1];

			$SQL .= $sql_clause .' reg.REG_date BETWEEN "' . strtotime( $month_r . ' 01 ' . $year_r . ' ' . $time_start ) . '" ';
			$SQL .= 'AND "' . strtotime( $month_r . ' ' . date( 't', strtotime( $year_r . ' ' . $month_r )) . ' ' . $year_r . ' ' . $time_end )  . '"';
			$sql_clause = ' AND ';
		}

		if ( $today_a ) {
			$SQL .= $sql_clause .' reg.REG_date BETWEEN "' . strtotime($curdate . $time_start) . '" AND "' . strtotime($curdate . $time_end)  . '"';
			$sql_clause = ' AND ';
		}

		if ( $this_month_a ) {
			$SQL .= $sql_clause .' reg.REG_date BETWEEN "' . strtotime( $this_year_r . '-' . $this_month_r . '-01' . $time_start ) . '" AND "' . strtotime( $this_year_r . '-' . $this_month_r . '-' . $days_this_month . $time_end )  . '"';
			$sql_clause = ' AND ';
		}

		if (function_exists('espresso_member_data') && ( espresso_member_data('role') == 'espresso_event_manager' || espresso_member_data('role') == 'espresso_group_admin')) {
			$SQL .= $sql_clause . ' evt.wp_user = "' . espresso_member_data('id')  . '"';
			$sql_clause = ' AND ';
		}

		$SQL .= ' AND evt.event_status != "D" ';



		//let's setup orderby
		switch ( $orderby ) {
			
			case 'REG_ID':
				$orderby = 'reg.REG_ID ';
				break;
				
			case 'STS_ID':
				$orderby = 'reg.REG_status ';
				break;
				
			case 'ATT_lname':
				$orderby = 'att.ATT_lname ';
				break;
				
			case 'event_name':
				$orderby = 'evt.event_name ';
				break;
				
			case 'DTT_EVT_start':
				$orderby = 'dtt.DTT_EVT_start ';
				break;
				
			default: //'REG_date'
				$orderby = 'reg.REG_date ';
		}

		//let's setup limit
		$limit = !empty($limit) ? 'LIMIT ' . implode(',', $limit) : '';
		$SQL .= $count ? ')' : ") ORDER BY $orderby $order $limit";

		$registrations = $count ? $wpdb->get_var( $SQL ) : $wpdb->get_results( $SQL );

		return $registrations;
	}





	/**
	*		return specific registration data for the Registration Admin page
	* 		@access		public
	*/
	public function get_registration_for_admin_page( $REG_ID = FALSE ) {

		if ( ! $REG_ID ) {
			return FALSE;
		}

		global $wpdb;

		$SQL = 'SELECT att.*, reg.*, evt.*, txn.*, prc.*, dtt.*, reg.STS_ID REG_status, txn.STS_ID txn_status';
		$SQL .= ' FROM ' . $wpdb->prefix . 'esp_attendee att';
		$SQL .= ' RIGHT JOIN ' . $this->_get_main_table()->get_table_name() . ' reg ON reg.ATT_ID = att.ATT_ID';
		$SQL .= ' LEFT JOIN ' . EVENTS_DETAIL_TABLE . ' evt ON evt.id = reg.EVT_ID';
		$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_transaction txn ON txn.TXN_ID = reg.TXN_ID';
		$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_price prc ON prc.PRC_ID = reg.PRC_ID';
		$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_datetime dtt ON dtt.DTT_ID = reg.DTT_ID';
		$SQL .= ' WHERE reg.REG_ID = %d';

		$attendee = $wpdb->get_row( $wpdb->prepare( $SQL, $REG_ID ));
		//printr( $attendee, 'attendee' ); die();
		
		return $attendee;
	}





	/**
	*		get the number of registrations per day  for the Registration Admin page Reports Tab
	* 		@access		public
	*/
	public function get_registrations_per_day_report( $period = '-1 month' ) {

		global $wpdb;
		$date_mod = strtotime( $period );

		$SQL = "SELECT DATE(FROM_UNIXTIME(reg.REG_date)) AS 'regDate', COUNT(REG_ID) AS total";
		$SQL .= ' FROM ' . $this->_get_main_table()->get_table_name() . ' reg';
		$SQL .= ' WHERE REG_date >= %d';
		$SQL .= ' GROUP BY `regDate`';
		$SQL .= ' ORDER BY REG_date DESC';
		
		return $wpdb->get_results( $wpdb->prepare( $SQL, $date_mod ));

	}





	/**
	*		get the number of registrations per event  for the Registration Admin page Reports Tab
	* 		@access		public
	*/
	public function get_registrations_per_event_report( $period = '-1 month' ) {

		global $wpdb;
		$date_mod = strtotime( $period );

		$SQL = "SELECT event_name, reg_limit, COUNT(REG_ID) AS total";
		$SQL .= ' FROM ' . $this->_get_main_table()->get_table_name() . ' reg';
		$SQL .= ' LEFT JOIN ' . EVENTS_DETAIL_TABLE . ' evt ON evt.id = reg.EVT_ID';
		$SQL .= ' JOIN ' . $wpdb->prefix . 'esp_datetime dtt ON dtt.DTT_ID = reg.DTT_ID';
		$SQL .= ' WHERE REG_date >= %d';
		$SQL .= ' AND DTT_is_primary = 1';
		$SQL .= ' GROUP BY event_name';
		$SQL .= ' ORDER BY event_name';  // DTT_EVT_start
		$SQL .= ' LIMIT 0, 24';
		
		return $wpdb->get_results( $wpdb->prepare( $SQL, $date_mod ));

	}


	/**
	 * Returns the EE_Registration of the primary attendee on the transaction id provided
	 * @param int $TXN_ID
	 * @return EE_Registration
	 */
	public function get_primary_registration_for_transaction_ID( $TXN_ID = FALSE){
		if( ! $TXN_ID ){
			return false;
		}
		return $this->get_one(array(array('TXN_ID'=>$TXN_ID,'REG_count'=>  EEM_Registration::PRIMARY_REGISTRANT_COUNT)));
	}

	/**
	*		get all registrations for a specific transaction, possibly excluding one (ie: get all OTHER registrations except this one )
	* 
	* 		@access		public
	* 		@param		int 			$TXN_ID
	* 		@param		int 			$REG_ID
	* 		@return		array
	*/
	public function get_registrations_for_transaction( $TXN_ID = FALSE, $REG_ID = FALSE ) {

		if ( ! $TXN_ID ) {
			return FALSE;
		}

		global $wpdb;

		$SQL = 'SELECT att.*, reg.*';
		$SQL .= ' FROM ' . $wpdb->prefix . 'esp_attendee att';
		$SQL .= ' RIGHT JOIN ' . $this->_get_main_table()->get_table_name() . ' reg ON reg.ATT_ID = att.ATT_ID';
		$SQL .= ' WHERE reg.TXN_ID = %d';
		
		if ( $REG_ID ) {
			$SQL .= ' AND reg.REG_ID != %d';
			$attendees = $wpdb->get_row( $wpdb->prepare( $SQL, $TXN_ID, $REG_ID ));
		} else {
			$attendees = $wpdb->get_row( $wpdb->prepare( $SQL, $TXN_ID ));
		}
		
		if ( ! is_array( $attendees )) {
			$attendees = array( $attendees );
		}
		//printr( $attendee, 'attendee' ); die();		
		return $attendees;
		
	}





	/**
	 *		get_event_registration_count
	 *
	 *		@access public
	 *		@param int $EVT_ID 
	 *		@param boolean $for_incomplete_payments
	 *		@return int
	 */
	public function get_event_registration_count ( $EVT_ID, $for_incomplete_payments = FALSE ) {		
		$query_params = array(
			array(
				'EVT_ID'=>$EVT_ID,
			)
		);
		if($for_incomplete_payments){
			$query_params[0]['Transaction.STS_ID']=array('!=',  EEM_Transaction::complete_status_code);
		}
		global $org_options;
		if($org_options['pending_counts_reg_limit']){
			$query_params[0]['STS_ID']=  EEM_Registration::status_id_pending;
		}
		return $this->count($query_params);

//		$SQL = 'SELECT COUNT(reg.EVT_ID) FROM ' . $this->_get_main_table()->get_table_name() . ' reg';
//		$SQL .= $for_incomplete_payments ? ' JOIN ' . $wpdb->prefix . 'esp_transaction txn ON txn.TXN_ID = reg.TXN_ID' : '';
//		$SQL .= ' WHERE reg.EVT_ID=%d AND ( reg.STS_ID="RAP"';
//		$SQL .= $org_options['pending_counts_reg_limit'] ? ' OR reg.STS_ID="RPN")' : ')';		
//		$SQL .= $for_incomplete_payments ? ' AND txn.STS_ID <> "TCM"' : '';
//
//		$reg_count = $wpdb->get_var( $wpdb->prepare( $SQL, $EVT_ID ));
//		
//		if ( $reg_count !== FALSE ) {
//			return $reg_count;
//		} else {
//			$user_msg = __('An error occurred. The event registration count could not be calculated because of a database error.', 'event_espresso');
//			EE_Error::add_error( $user_msg, __FILE__, __FUNCTION__, __LINE__ );
//			return FALSE;
//		}		
//			
	}
}
// End of file EEM_Registration.model.php
// Location: /includes/models/EEM_Registration.model.php
