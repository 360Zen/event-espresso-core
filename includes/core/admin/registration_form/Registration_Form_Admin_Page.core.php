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
 * Registration_Form_Admin_Page
 *
 * This contains the logic for setting up the Custom Forms related pages.  Any methods without phpdoc comments have inline docs with parent class. 
 *
 * NOTE:  TODO: This is a straight conversion from the legacy 3.1 questions and question groups related pages.  It is NOT optimized and will need modification to fully use the new system (and also will need adjusted when Questions and Questions groups model is implemented)
 *
 * @package		Registration_Form_Admin_Page
 * @subpackage	includes/core/admin/Registration_Form_Admin_Page.core.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class Registration_Form_Admin_Page extends EE_Admin_Page {

	/**
	 * _question
	 * holds the specific question object for the question details screen
	 * @var object
	 */
	protected $_question;

	/**
	 * _question_group
	 * holds the specific question group object for the question group details screen
	 * @var object
	 */
	protected $_question_group;
	
	/**
	 *_question_model EEM_Question model instance (for queries)
	 * @var EEM_Question
	 */
	protected $_question_model;
	
	/**
	 * _question_group_model EEM_QUestion_group instance (for queries)
	 * @var EEM_Question_Group 
	 */
	protected $_question_group_model;



	public function __construct( $routing = TRUE ) {
		require_once('EEM_Question.model.php');
		require_once('EEM_Question_Group.model.php');
		$this->_question_model=  EEM_Question::instance();
		$this->_question_group_model=EEM_Question_Group::instance();
		parent::__construct( $routing );
	}



	protected function _init_page_props() {
		$this->page_slug = REGISTRATION_FORM_PG_SLUG;
		$this->page_label = __('Registration Form', 'event_espresso');
	}




	protected function _ajax_hooks() {
		add_action('wp_ajax_espresso_update_question_order', array( $this, 'update_question_order' ));
	}





	protected function _define_page_props() {
		$this->_admin_base_url = EE_FORMS_ADMIN_URL;
		$this->_admin_page_title = __('Registration Form', 'event_espresso');
		$this->_labels = array(
			'buttons' => array(
				'edit_question' => __('Edit Question', 'event_espresso')
			)
		);
	}




	protected function _set_page_routes() {
		$this->_page_routes = array(
		
			'default' => '_questions_overview_list_table',

			'edit_question' => array(
				'func' => '_edit_question',
				'args' => array('edit')
				),

			'question_groups' => '_questions_groups_preview',

			'update_question' => array(
				'func' => '_insert_or_update_question',
				'args' => array('new_question' => FALSE ),
				'noheader' => TRUE,
				),

			'espresso_update_question_order' => array(
				'func' => 'update_question_order',
				'noheader' => TRUE
				),	
			);
	}





	protected function _set_page_config() {
		$this->_page_config = array(

			'default' => array(
				'nav' => array(
					'label' => __('Questions'),
					'order' => 10
					),
				'list_table' => 'Registration_Form_Questions_Admin_List_Table',
				'metaboxes' => array('_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box')
				),

			'question_groups' => array(
				'nav' => array(
					'label' => __('Question Groups'),
					'order' => 20
					)
				),

			'edit_question' => array(
				'nav' => array(
					'label' => __('Edit Question', 'event_espresso'),
					'order' => 5,
					'persistent' => FALSE,
					'url' => isset($this->_req_data['question_id']) ? add_query_arg(array('question_id' => $this->_req_data['question_id'] ), $this->_current_page_view_url )  : $this->_admin_base_url
					),
				'metaboxes' => array('_publish_post_box','_espresso_news_post_box', '_espresso_links_post_box', '_espresso_sponsors_post_box' )
				),
			);
	}



	protected function _add_screen_options() {
		//todo
	}

	protected function _add_screen_options_default() {
		$this->_per_page_screen_option();
	}

	protected function _add_screen_options_question_groups() {
		$this->_per_page_screen_option();
	}

	//none of the below group are currently used for Event Categories
	protected function _add_feature_pointers() {}
	public function load_scripts_styles() {
		wp_register_style( 'espresso_registration', REGISTRATION_FORM_ASSETS_URL . 'espresso_registration_form_admin.css', array(), EVENT_ESPRESSO_VERSION );		
		wp_enqueue_style('espresso_registration');		
	}
	public function admin_init() {}
	public function admin_notices() {}
	public function admin_footer_scripts() {}



	public function load_scripts_styles_default() {
		wp_enqueue_script( 'espresso_ajax_table_sorting' );	
	}




	protected function _set_list_table_views_default() {
		$this->_views = array(
			'all' => array(
				'slug' => 'all',
				'label' => __('All', 'event_espresso'),
				'count' => 0,
				)
		);
	}



	/**
	 * This just previews the question groups tab that comes in caffeinated.
	 * @return string html
	 */
	protected function _questions_groups_preview() {
		$this->_template_args['preview_img'] = '<img src="' . REGISTRATION_FORM_ASSETS_URL . 'caf_reg_form_preview.png" alt="Preview Question Groups Overview List Table screenshot" />';
		$this->_template_args['preview_text'] = __( 'Question Groups is a feature only availble in the Caffeinated version of Event Espresso.  With the Question Groups feature you are able to: create new question groups, edit existing question groups, and also create and edit new questions and add them to question groups', 'event_espresso' );
		$this->display_admin_caf_preview_page();
	}



	/**
	 * Extracts the question field's values from the POST request to update or insert them
	 * @return array where each key is the name of a model's field/db column, and each value is its value.
	 */
	protected function _set_column_values_for(EEM_Base $model){
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$set_column_values=array();
		foreach($model->field_settings() as $fieldName=>$settings){
			// basically if QSG_identifier is empty or not set
			if ( $fieldName == 'QSG_identifier' && ( isset( $this->_req_data['QSG_identifier'] ) && empty( $this->_req_data['QSG_identifier'] ) || ! isset( $this->_req_data['QSG_identifier'] ) )) {
				$QSG_name = isset( $this->_req_data['QSG_name'] ) ? $this->_req_data['QSG_name'] : '' ;
				$this->_req_data['QSG_identifier'] = strtolower( str_replace( ' ', '-', $QSG_name )) . '-' . uniqid();
			}
			$set_column_values[$fieldName]=array_key_exists($fieldName,$this->_req_data)?$this->_req_data[$fieldName]:null;
		}
		return $set_column_values;//validation fo this data to be performed by the model before insertion.
	}




	protected function _questions_overview_list_table() {
		$this->display_admin_list_table_page_with_sidebar();
	}





	/**
	 * method for performing updates to question order
	 * @return array results array
	 */	
	public function update_question_order() {

		$success = __( 'Question order was updated successfully.', 'event_espresso' );
		
		// grab our row IDs
		$row_ids = isset( $this->_req_data['row_ids'] ) && ! empty( $this->_req_data['row_ids'] ) ? explode( ',', wp_strip_all_tags( $this->_req_data['row_ids'] )) : FALSE;

		if ( is_array( $row_ids )) {
			global $wpdb;
			for ( $i = 0; $i < count( $row_ids ); $i++ ) {
				//Update the questions when re-ordering
				if ( ! EEM_Question::instance()->update ( array( 'QST_order' => $i+1 ), array(array( 'QST_ID' => $row_ids[$i] ) ))) {
					$success = FALSE;
				} 
			}
		} else {
			$success = FALSE;
		}
		
		$errors = ! $success ? __( 'An error occured. The question order was not updated.', 'event_espresso' ) : FALSE;
		
		echo json_encode( array( 'return_data' => FALSE, 'success' => $success, 'errors' => $errors ));
		die();
		
	}


	
	protected function _edit_question( $action= 'add' ) {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$ID=isset( $this->_req_data['QST_ID'] ) && ! empty( $this->_req_data['QST_ID'] ) ? absint( $this->_req_data['QST_ID'] ) : FALSE;
		
		$this->_admin_page_title = ucwords( str_replace( '_', ' ', $this->_req_action ));
		// add PRC_ID to title if editing 
		$this->_admin_page_title = $ID ? $this->_admin_page_title . ' # ' . $ID : $this->_admin_page_title;
		if($ID){
			$question=$this->_question_model->get_one_by_ID($ID);
			$additional_hidden_fields=array('QST_ID'=>array('type'=>'hidden','value'=>$ID));
			$this->_set_add_edit_form_tags('update_question', $additional_hidden_fields);
		}else{
			$question= EE_Question::new_instance();
			$this->_set_add_edit_form_tags('insert_question');
		}
		$questionTypes=array();
		$count=0;
		foreach($this->_question_model->allowed_question_types() as $db_name => $nice_text){
			$questionTypes[$count]=array('id'=>$db_name,'text'=>$nice_text);
			$count++;
		}
		$this->_template_args['QST_ID']=$ID;
		$this->_template_args['question']=$question;
		$this->_template_args['question_types']=$questionTypes;
		
		$this->_set_publish_post_box_vars( 'id', $ID );
		$this->_template_args['admin_page_content'] = espresso_display_template( REGISTRATION_FORM_TEMPLATE_PATH . 'questions_main_meta_box.template.php', $this->_template_args, TRUE );

		// the details template wrapper
		$this->display_admin_page_with_sidebar();	
	}



	
	protected function _trash_question(){
		$success=$this->_question_model->delete_by_ID(intval($this->_req_data['QST_ID']));
		$query_args=array('action'=>'default','status'=>'all');
		$this->_redirect_after_action($success, $this->_question_model->item_name($success), 'trashed', $query_args);
	}



	protected function _trash_or_restore_questions($trash=TRUE){
		$this->_trash_or_restore_items( $this->_question_model, $trash );
	}


	protected function _delete_question(){
		$success=$this->_question_model->delete_permanently_by_ID(intval($this->_req_data['QST_ID']));
		$query_args=array('action'=>'default','status'=>'all');
		$this->_redirect_after_action($success, $this->_question_model->item_name($success), 'deleted', $query_args);
	}



	protected function _delete_questions() {
		$this->_delete_items($this->_question_model);
	}



	private function _delete_items(EEM_TempBase $model){
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		if (!empty($this->_req_data['checkbox']) && is_array($this->_req_data['checkbox'])) {			
			// if array has more than one element than success message should be plural
			$success = count( $this->_req_data['checkbox'] ) > 1 ? 2 : 1;
			// cycle thru bulk action checkboxes
			while (list( $ID, $value ) = each($this->_req_data['checkbox'])) {

				if (!$model->delete_permanently_by_ID(absint($ID))) {
					$success = 0;
				}
			}
	
		}
		$this->_redirect_after_action( $success, $model->item_name($success), 'deleted permanently', array( 'action'=>'default', 'status'=>'all' ));
	}



	protected function _insert_or_update_question($new_question = TRUE) {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$success=0;
		$set_column_values=$this->_set_column_values_for($this->_question_model);
		require_once('EE_Question.class.php');
		if($new_question){
			$new_id=$this->_question_model->insert($set_column_values);
			if($new_id){
				$success=1;
				$ID=$new_id;
			}else{
				$success=0;
				$ID=false;
			}
			$action_desc='created';
		}else{
			$ID=absint($this->_req_data['QST_ID']);
			$pk=$this->_question_model->primary_key_name();
			$wheres=array($pk=>$ID);
			unset($set_column_values[$pk]);
			$success= $this->_question_model->update($set_column_values,array($wheres));
			$action_desc='updated';
		}
		if ($ID){
			//save the related options
			//trash removed options, save old ones
				//get list of all options
			$question=$this->_question_model->get_one_by_ID($ID);
			$options=$question->options();
			if(!empty($options)){
				foreach($options as $option_ID=>$option){
					$option_req_index=$this->_get_option_req_data_index($option_ID);
					if($option_req_index!==FALSE){
						$option->save($this->_req_data['question_options'][$option_req_index]);
					}else{
						//not found, remove it
						$option->delete();
					}
				}
			}
			//save new related options
			foreach($this->_req_data['question_options'] as $index=>$option_req_data){
				if(empty($option_req_data['QSO_ID']) && (!empty($option_req_data['QSO_name']) || !empty($option_req_data['QSO_value']))){//no ID! save it!
					if(empty($option_req_data['QSO_value'])){
						$option_req_data['QSO_value']=$option_req_data['QSO_name'];
					}
					if(empty($option_req_data['QSO_name'])){
						$option_req_data['QSO_name']=$option_req_data['QSO_value'];
					}
					$new_option=EE_Question_Option::new_instance( array( 'QSO_name' => $option_req_data['QSO_name'], 'QSO_value' => $option_req_data['QSO_value'], 'QST_ID' => $question->ID()));
					$new_option->save();
				}
			}
		}
		$query_args=array('action'=>'edit_question','QST_ID'=>$ID);
		$this->_redirect_after_action($success, $this->_question_model->item_name($success), $action_desc, $query_args);
	}



	
	/**
	 * Upon saving a question, there should be an array of 'question_options'. This array is index numerically, but not by ID 
	 * (this is done because new question optiosn don't have an ID, but we may want to add multiple simultaneously).
	 * So, this function gets the index in that request data array called question_options. Returns FALSE if not found.
	 * @param int $ID of the question option to find
	 * @return int indexin in question_options array if successful, FALSE if unsuccessful
	 */
	protected function _get_option_req_data_index($ID){
		$req_data_for_question_options=$this->_req_data['question_options'];
		foreach($req_data_for_question_options as $num=>$option_data){
			if(array_key_exists('QSO_ID',$option_data) && intval($option_data['QSO_ID'])==$ID){
				return $num;
			}
		}
		return FALSE;
	}



	/******************************    QUESTION GROUPS    ******************************/




	protected function _edit_question_group( $type = 'add' ) {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$ID=isset( $this->_req_data['QSG_ID'] ) && ! empty( $this->_req_data['QSG_ID'] ) ? absint( $this->_req_data['QSG_ID'] ) : FALSE;
		$this->_admin_page_title = ucwords( str_replace( '_', ' ', $this->_req_action ));
		// add ID to title if editing 
		$this->_admin_page_title = $ID ? $this->_admin_page_title . ' # ' . $ID : $this->_admin_page_title;
		if($ID){
			$questionGroup=$this->_question_group_model->get_one_by_ID($ID);
			$additional_hidden_fields=array('QSG_ID'=>array('type'=>'hidden','value'=>$ID));
			$this->_set_add_edit_form_tags('update_question_group', $additional_hidden_fields);
		}else{
			$questionGroup=new EE_Question_Group();
			$this->_set_add_edit_form_tags('insert_question_group');
		}
		$this->_template_args['values'] = $this->_yes_no_values;
		$this->_template_args['all_questions']=$this->_question_model->get_all();
		$this->_template_args['QSG_ID']=$ID ? $ID : TRUE;
		$this->_template_args['question_group']=$questionGroup;
		
		$redirect_URL = add_query_arg( array( 'action' => 'question_groups'), $this->_admin_base_url );
		$this->_set_publish_post_box_vars( 'id', $ID, FALSE, $redirect_URL  );
		$this->_template_args['admin_page_content'] = espresso_display_template( REGISTRATION_FORM_TEMPLATE_PATH . 'question_groups_main_meta_box.template.php', $this->_template_args, TRUE );

		// the details template wrapper
		$this->display_admin_page_with_sidebar();	
	}




	protected function _delete_question_groups() {}



	protected function _insert_or_update_question_group( $new_question_group = TRUE) {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$success=0;
		$set_column_values=$this->_set_column_values_for($this->_question_group_model);
		require_once('EE_Question_Group.class.php');
		if($new_question_group){
			$results=$this->_question_group_model->insert($set_column_values);
			if($results){
				$success=1;
				$ID=$results['new-ID'];
			}else{
				$success=0;
				$ID=false;
			}
			$action_desc='created';
		}else{
			$ID=absint($this->_req_data['QSG_ID']);
			$pk=$this->_question_group_model->primary_key_name();
			$wheres=array($pk=>$ID);
			unset($set_column_values[$pk]);
			$success= $this->_question_group_model->update($set_column_values,array($wheres));
			$action_desc='updated';
		}
		//save the related questions
		$question_group=$this->_question_group_model->get_one_by_ID($ID);
		$questions=$question_group->questions();
		foreach(array_keys($questions) as $question_ID){
			
			if(array_key_exists('questions',$this->_req_data) && array_key_exists($question_ID,$this->_req_data['questions'])){
				$question_group->add_question($question_ID);
			}else{
				//not found, remove it
				$question_group->remove_question($question_ID);
			}
		}
		//save new related questions
		if(array_key_exists('questions',$this->_req_data)){
			foreach(array_keys($this->_req_data['questions']) as $question_ID){
				$question_group->add_question($question_ID);
			}
		}
		$query_args=array('action'=>'edit_question_group','QSG_ID'=>$ID);
		$this->_redirect_after_action($success, $this->_question_group_model->item_name($success), $action_desc, $query_args);
		
	}



	protected function _trash_or_restore_question_groups($trash = TRUE) {
		return $this->_trash_or_restore_items( $this->_question_group_model, $trash );
	}



	/**
	 * Interally used to delete or restore items, using the request data. Meant to be 
	 * flexible between question or question gruops
	 * @param EEM_TempBase $model
	 * @param boolean $trash wehter to trash or restore
	 */
	private function _trash_or_restore_items( EEM_TempBase $model, $trash = TRUE ) {
		
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		
		$success = 1;
		//Checkboxes
		//echo "trash $trash";
		//var_dump($this->_req_data['checkbox']);die;
		if ( isset( $this->_req_data['checkbox'] )) {
			if ( isset( $this->_req_data['checkbox'] ) && ! empty( $this->_req_data['checkbox'] ) && is_array( $this->_req_data['checkbox'] )) {			
				// if array has more than one element than success message should be plural
				$success = count( $this->_req_data['checkbox'] ) > 1 ? 2 : 1;
				// cycle thru bulk action checkboxes
				while (list( $ID, $value ) = each($this->_req_data['checkbox'])) {
					if ( ! $model->delete_or_restore_by_ID($trash,absint($ID))) {
						$success = 0;
					}				
				}
		
			} else {
				// grab single id and delete
				$ID = absint($this->_req_data['checkbox']);
				if ( ! $model->delete_or_restore_by_ID($trash,$ID)) {
					$success = 0;
				}			
			}
		
		} else {
			// delete via trash link
			// grab single id and delete
			$ID = absint($this->_req_data[ $model->primary_key_name() ]);
			if ( ! $model->delete_or_restore_by_ID($trash,$ID)) {
				$success = 0;
			}	
				
		}
		

		$action = strtolower( $model->item_name() . 's' );
		$action = 'questions' ? 'default' : $action;
		$action_desc = $trash?'trashed':'restored';
		
		$this->_redirect_after_action( $success, $model->item_name($success), $action_desc, array( 'action' => $action, 'status'=>'all' ) );
	}




	/***********/
	/* QUERIES */


	
	/**
	 * For internal use in getting all the query parameters (because it's pretty well the same between question, question groups, and
	 * for both when searchign for trahsed and untrahse dones)
	 * @param EEM_Base $model either EEM_Question or EEM_Question_Group
	 * @return array lik EEM_Base::get_all
	 */
	protected function get_query_params($model, $per_page=10,$current_page=10){
		$query_params = array();
		$offset=($current_page-1)*$per_page;
		$query_params['limit']=array($offset,$per_page);
		$order = ( isset( $this->_req_data['order'] ) && ! empty( $this->_req_data['order'] )) ? $this->_req_data['order'] : 'ASC';
		$field_to_order_by = empty($this->_req_data['orderby']) ? $model->get_primary_key_field()->get_name() : $this->_req_data['orderby'];	
		$query_params['order_by']=array( $field_to_order_by => $order );
		$search_string = array_key_exists('s',$this->_req_data) ? $this->_req_data['s'] : null;
		if(! empty($search_string)){
			if($model instanceof EEM_Question_Group){
				$query_params[0]=array(
					'OR'=>array(
						'QSG_name'=>array('LIKE',"%$search_string%"),
						'QSG_desc'=>array('LIKE',"%$search_string%"))
					);
			}else{
				$query_params[0]=array(
					'QST_display_text'=>array('LIKE',"%$search_string%"));
			}
		}
		
		return $query_params;
		
	}



	public function get_questions( $per_page=10,$current_page = 1, $count = FALSE ) {
		$QST = EEM_Question::instance();
		$query_params = $this->get_query_params($QST, $per_page, $current_page);
		if ($count){
			$results = $QST->count($query_params);
		}else{
			$results = $QST->get_all($query_params);
		}
		return $results;
		
	}



	public function get_trashed_questions( $per_page,$current_page = 1, $count = FALSE ) {
		list($order,$limit,$output,$searchString)=$this->get_query_params(EEM_Question::instance(),$per_page,$current_page,$count);
		$orderby = empty($this->_req_data['orderby']) ? 'QST_order' : $this->_req_data['orderby'];		
		if(!empty($searchString)){
			//note: this a subclass of EEM_Soft_Delete_Base, so thsi is actually only getting nontrashed items
			$questions=EEM_Question::instance()->get_all_where_deleted(array('QST_display_text'=>'%'.$searchString.'%'), $orderby, $order, 'LIKE', $limit,$output);
		}else{
			//note: this a subclass of EEM_Soft_Delete_Base, so thsi is actually only getting nontrashed items
			$questions=EEM_Question::instance()->get_all_where_deleted(null, $orderby, $order, '=', $limit,$output);
		}
		return $questions;
	}



	public function get_question_groups( $per_page,$current_page = 1, $count = FALSE ) {
		$questionGroupModel=EEM_Question_Group::instance();
		list($order,$limit,$output,$searchString)=$this->get_query_params($questionGroupModel,$per_page,$current_page,$count);
		$orderby = empty($this->_req_data['orderby']) ? 'QSG_order' : $this->_req_data['orderby'];		
		if(!empty($searchString)){
			$questionGroups=$questionGroupModel->get_all_where(array('QSG_name'=>'%'.$searchString.'%'), $orderby, $order, 'LIKE', $limit,$output);//note: this a subclass of EEM_Soft_Delete_Base, so thsi is actually only getting nontrashed items
		}else{
			$questionGroups=$questionGroupModel->get_all_where(null, $orderby, $order, '=', $limit,$output);//note: this a subclass of EEM_Soft_Delete_Base, so thsi is actually only getting nontrashed items
		}
		

		return $questionGroups;
	}



	public function get_trashed_question_groups( $per_page,$current_page = 1, $count = FALSE ) {
		$questionGroupModel=EEM_Question_Group::instance();
		list($order,$limit,$output,$searchString)=$this->get_query_params($questionGroupModel,$per_page,$current_page,$count);
		$orderby = empty($this->_req_data['orderby']) ? 'QSG_order' : $this->_req_data['orderby'];		
		if(!empty($searchString)){
			$questionGroups=$questionGroupModel->get_all_where_deleted(array('QSG_name'=>'%'.$searchString.'%'), $orderby, $order, 'LIKE', $limit,$output);//note: this a subclass of EEM_Soft_Delete_Base, so thsi is actually only getting nontrashed items
		}else{
			$questionGroups=$questionGroupModel->get_all_where_deleted(null, $orderby, $order, '=', $limit,$output);//note: this a subclass of EEM_Soft_Delete_Base, so thsi is actually only getting nontrashed items
		}
		

		return $questionGroups;
	}


	/**
	 * method for performing updates to question order
	 * @return array results array
	 */	
	public function update_question_group_order() {

		$success = __( 'Question group order was updated successfully.', 'event_espresso' );
		
		// grab our row IDs
		$row_ids = isset( $this->_req_data['row_ids'] ) && ! empty( $this->_req_data['row_ids'] ) ? explode( ',', wp_strip_all_tags( $this->_req_data['row_ids'] )) : FALSE;

		if ( is_array( $row_ids )) {
			global $wpdb;
			for ( $i = 0; $i < count( $row_ids ); $i++ ) {
				//Update the questions when re-ordering
				if ( ! EEM_Question_Group::instance()->update ( array( 'QSG_order' => $i+1 ), array( 'QSG_ID' => $row_ids[$i] ) )) {
					$success = FALSE;
				} 
			}
		} else {
			$success = FALSE;
		}
		
		$errors = ! $success ? __( 'An error occured. The question group order was not updated.', 'event_espresso' ) : FALSE;
		
		echo json_encode( array( 'return_data' => FALSE, 'success' => $success, 'errors' => $errors ));
		die();
		
	}





	/***************************************		REGISTRATION SETTINGS 		***************************************/





	protected function _reg_form_settings() {
	
		global $org_options;
		$this->_template_args['values'] = $this->_yes_no_values;
		
		$this->_template_args['use_captcha'] = isset( $org_options['use_captcha'] ) ? absint( $org_options['use_captcha'] ) : FALSE;
		$this->_template_args['show_captcha_settings'] = $this->_template_args['use_captcha'] ? 'style="display:table-row;"': ''; 
		
		$this->_template_args['recaptcha_publickey'] = isset( $org_options['recaptcha_publickey'] ) ? stripslashes( $org_options['recaptcha_publickey'] ) : '';
		$this->_template_args['recaptcha_privatekey'] = isset( $org_options['recaptcha_privatekey'] ) ? stripslashes( $org_options['recaptcha_privatekey'] ) : '';
		$this->_template_args['recaptcha_width'] = isset( $org_options['recaptcha_width'] ) ? absint( $org_options['recaptcha_width'] ) : 500;
		
		$this->_template_args['recaptcha_theme_options'] = array(
				array('id'  => 'red','text'=> __('Red', 'event_espresso')),
				array('id'  => 'white','text'=> __('White', 'event_espresso')),
				array('id'  => 'blackglass','text'=> __('Blackglass', 'event_espresso')),
				array('id'  => 'clean','text'=> __('Clean', 'event_espresso'))
			);
		$this->_template_args['recaptcha_theme'] = isset( $org_options['recaptcha_theme'] ) ? $this->_display_nice( $org_options['recaptcha_theme'] ) : 'clean';
	
		$this->_template_args['recaptcha_language_options'] = array(
				array('id'  => 'en','text'=> __('English', 'event_espresso')),
				array('id'  => 'es','text'=> __('Spanish', 'event_espresso')),
				array('id'  => 'nl','text'=> __('Dutch', 'event_espresso')),
				array('id'  => 'fr','text'=> __('French', 'event_espresso')),
				array('id'  => 'de','text'=> __('German', 'event_espresso')),
				array('id'  => 'pt','text'=> __('Portuguese', 'event_espresso')),
				array('id'  => 'ru','text'=> __('Russian', 'event_espresso')),
				array('id'  => 'tr','text'=> __('Turkish', 'event_espresso'))
			);		
		$this->_template_args['recaptcha_language'] = isset( $org_options['recaptcha_language'] ) ? $this->_display_nice( $org_options['recaptcha_language'] ) : 'en';

		$this->_set_add_edit_form_tags( 'update_reg_form_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args['admin_page_content'] = espresso_display_template( REGISTRATION_FORM_TEMPLATE_PATH . 'reg_form_settings.template.php', $this->_template_args, TRUE );
		$this->display_admin_page_with_sidebar();	
	}

	protected function _update_reg_form_settings() {
		
		$data = array();

		$data['use_captcha'] = isset( $this->_req_data['use_captcha'] ) ? absint( $this->_req_data['use_captcha'] ) : FALSE;
		$data['recaptcha_publickey'] = isset( $this->_req_data['recaptcha_publickey'] ) ? sanitize_text_field( $this->_req_data['recaptcha_publickey'] ) : NULL;
		$data['recaptcha_privatekey'] = isset( $this->_req_data['recaptcha_privatekey'] ) ? sanitize_text_field( $this->_req_data['recaptcha_privatekey'] ) : NULL;
		$data['recaptcha_width'] = isset( $this->_req_data['recaptcha_width'] ) ? absint( $this->_req_data['recaptcha_width'] ) : 500;
		$data['recaptcha_theme'] = isset( $this->_req_data['recaptcha_theme'] ) ? sanitize_text_field( $this->_req_data['recaptcha_theme'] ) : 'clean';
		$data['recaptcha_language'] = isset( $this->_req_data['recaptcha_language'] ) ? sanitize_text_field( $this->_req_data['recaptcha_language'] ) : 'en';
		
		$data = apply_filters('FHEE_reg_form_settings_save', $data);	
		
		$what = 'Registration Options';
		$success = $this->_update_organization_settings( $what, $data, __FILE__, __FUNCTION__, __LINE__ );
		$this->_redirect_after_action( $success, $what, 'updated', array( 'action' => 'view_reg_form_settings' ) );
		
	}






} //ends Registration_Form_Admin_Page class
