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
 * Question Model
 *
 * @package			Event Espresso
 * @subpackage		includes/models/
 * @author				Michael Nelson
 *
 * ------------------------------------------------------------------------
 */
require_once ( EVENT_ESPRESSO_INCLUDES_DIR . 'models/EEM_Soft_Delete_Base.model.php' );

class EEM_Question extends EEM_Soft_Delete_Base {

  	// private instance of the Attendee object
	private static $_instance = NULL;

	/**
	 *		This funtion is a singleton method used to instantiate the EEM_Attendee object
	 *
	 *		@access public
	 *		@return EEM_Question instance
	 */	
	public static function instance(){
	
		// check if instance of EEM_Attendee already exists
		if ( self::$_instance === NULL ) {
			// instantiate Espresso_model 
			self::$_instance = new self();
		}
		// EEM_Attendee object
		return self::$_instance;
	}
	/**
	 * lists all the question types which should be allowed. Ideally, this will be extensible.
	 * @access private
	 * @var array of strings 
	 */
	private $_allowed_question_types;
	/**
	 * Returns the list of allowed question types, which are normally: 'TEXT','TEXTAREA','SINGLE','DROPDOWN','MULTIPLE','DATE'
	 * but they can be extended
	 * @return string[]
	 */
	public function allowed_question_types(){
		return $this->_allowed_question_types;
	}
	protected function __construct(){
		$this->singular_item = __('Question','event_espresso');
		$this->plural_item = __('Questions','event_espresso');
		$this->_allowed_question_types=apply_filters('FHEE_allowed_question_types',
					array(
						'TEXT'=>__('Text','event_espresso'),
						'TEXTAREA'=>__('Textarea','event_espresso'),
						'SINGLE'=>__('Single','event_espresso'),
						'DROPDOWN'=>__('Dropdown','event_espresso'),
						'MULTIPLE'=>__('Multiple Choice','event_espresso'),
						'DATE'=>__('Date','event_espresso')));
//		$this->_fields_settings=array(
//				'QST_ID'=>new EE_Model_Field( __('Question ID','event_espresso'), 'primary_key', false, null, null, null),
//				'QST_display_text'=>new EE_Model_Field( __('Question Text','event_espresso'), 'simplehtml', false, '', null, null),
//				'QST_admin_label'=>new EE_Model_Field( __('Admin Label','event_espresso'), 'simplehtml', false, '', null, null),
//				'QST_system_ID'=>new EE_Model_Field( __('System ID','event_espresso'), 'int', false, 0, null, null),
//				'QST_type'=>new EE_Model_Field( __('Question Type','event_espresso'), 'enum', false, 'TEXT', $this->allowed_question_types(), null),
//				'QST_required'=>new EE_Model_Field( __('Required','event_espresso'),'bool',false,false,null,null),
//				'QST_required_text'=>new EE_Model_Field( __('Required Text','event_espresso'), 'simplehtml', true,  __('This field is required','event_espresso'), null, null),
//				'QST_order'=>new EE_Model_Field( __('Order','event_espresso'), 'int', false, 0, null, null),
//				'QST_admin_only'=>new EE_Model_Field( __('For Admins Only','event_espresso'),'bool',false,false,null,null),
//				'QST_wp_user'=>new EE_Model_Field( __('WP User ID','event_espresso'), 'foreign_key', true, 1, null, 'WP_User'),
//				'QST_deleted'=>new EE_Model_Field( __('Deleted','event_espresso'),'deleted_flag',false,false,null,null)
//			);
//		$this->_related_models=array(
//				'Answers'=>new EE_Model_Relation('hasMany', 'Answer', 'QST_ID'),
//				'Question_Groups'=>new EE_Model_Relation('hasAndBelongsToMany', 'Question_Group', 'QSG_ID','question_group_question',
//						array(
//							'QGQ_ID'=>new EE_Model_Field('Question Group-Question ID', 'primary_key', false, null, null, null),
//							'QSG_ID'=>new EE_Model_Field('Foreign Key to Question Groups', 'foreign_key', false, null, null, 'Question_Group'),
//							'QST_ID'=>new EE_Model_Field('Foreign Key to Questions','foreign_key',false,null,null,'Question'))
//						),
//				'Question_Options'=>new EE_Model_Relation('hasMany', 'Question_Option', 'QST_ID', null, null)
//			);
		$this->_tables = array(
			'Question'=>new EE_Primary_Table('esp_question','QST_ID')
		);
		$this->_fields = array(
			'Question'=>array(
				'QST_ID'=>new EE_Primary_Key_Int_Field('QST_ID', __('Question ID','event_espresso'), false, 0),
				'QST_display_text'=>new EE_Full_HTML_Field('QST_display_text', __('Question Text','event_espresso'), true, ''),
				'QST_admin_label'=>new EE_Simple_HTML_Field('QST_admin_label', __('Questino Label (admin-only)','event_espresso'), true, ''),
				'QST_system'=>new EE_Integer_Field('QST_system', __('Internal string ID for question','event_espresso'), true, 0),
				'QST_type'=>new EE_Enum_Field('QST_type', __('Question Type','event_espresso'),false, 'TEXT',$this->_allowed_question_types),
				'QST_required'=>new EE_Boolean_Field('QST_required', __('Required Question?','event_espresso'), false, false),
				'QST_required_text'=>new EE_Simple_HTML_Field('QST_required_text', __('Text to Display if Not Provided','event_espresso'), true, ''),
				'QST_order'=>new EE_Integer_Field('QST_order', __('Question Order','event_espresso'), false, 0),
				'QST_admin_only'=>new EE_Boolean_Field('QST_admin_only', __('Admin-Only Question?','event_espresso'), false, false),
				'QST_wp_user'=>new EE_Integer_Field('QST_wp_user', __('Wp User ID who created question','event_espresso'), false, 1),
				'QST_deleted'=>new EE_Trashed_Flag_Field('QST_deleted', __('Flag Indicating question was deleted','event_espresso'), false, false)
			)
		);
		$this->_model_relations = array(
			'Question_Group'=>new EE_HABTM_Relation('Question_Group_Question'),
			'Question_Option'=>new EE_Has_Many_Relation(),
			'Answer'=>new EE_Has_Many_Relation()
		);
		
		parent::__construct();
	}
	
	/**
	 * Gets an array for converting between QST_system and QST_IDs for system questions. Eg, if you want to know 
	 * which system question QST_ID corresponds to the QST_system 'city', use EEM_Question::instance()->get_Question_ID_from_system_string('city');
	 * @return int of QST_ID for the question that corresponds to that QST_system
	 */
	public function get_Question_ID_from_system_string($QST_system){
		 $conversion_array = array(
			'fname'=> EEM_Attendee::fname_question_id,
			'lname'=> EEM_Attendee::lname_question_id,
			'email'=> EEM_Attendee::email_question_id,
			'address'=> EEM_Attendee::address_question_id,
			'address2'=> EEM_Attendee::address2_question_id,
			'city'=> EEM_Attendee::city_question_id,
			'state'=> EEM_Attendee::state_question_id,
			'country'=> EEM_Attendee::country_question_id,
			'zip'=> EEM_Attendee::zip_question_id,
			'phone'=> EEM_Attendee::phone_question_id
		);
		 if(array_key_exists($QST_system, $conversion_array)){
			return $conversion_array[$QST_system];
		 }else{
			 return null;
		 }
	}
	
	
	//function get_system_questions





}
// End of file EEM_Question.model.php
// Location: /includes/models/EEM_Question.model.php
