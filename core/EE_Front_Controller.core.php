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
 * EE_Front_Controller
 *
 * @package			Event Espresso
 * @subpackage	core/
 * @author				Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
final class EE_Front_Controller {

	/**
	 * 	system registry
	 *	@var 	EE_Registry		$EE
	 * 	@access 	private
	 */
	private $EE;

	/**
	 * 	$_view_template
	 *	@var 	string		$_view_template
	 * 	@access 	public
	 */
	private $_view_template = NULL;


	/**
	 * static copy of registry that modules can use until they get instantiated
	 *	@var 	EE_Registry	$registry
	 * 	@access 	public
	 */
	public static $registry;



	/**
	 * 	class constructor
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function __construct() {
		// early init
		add_action( 'init', array( $this, 'init' ), 5 );
		// determine how to integrate WP_Query with the EE models
		add_action( 'init', array( $this, 'employ_CPT_Strategy' ), 10 );
		// action hook EE
		do_action('AHEE__EE_Front_Controller__construct__done',$this);
	}







	/*********************************************** 		INIT ACTION HOOK		 ***********************************************/





	/**
	 * 	init - should fire after shortcode, module, addon, or other plugin's default priority init phases have run
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function init(){
//		EEM_Ticket::instance()->update_tickets_sold(EEM_Ticket::instance()->get_all());
//		$t = EE_Ticket::new_instance(array('TKT_name'=>'prog1',));
//		$p = EEM_Price::instance()->get_one();
//		$d = EEM_Datetime::instance()->get_one();
//		$t->save();
//		$t->_add_relation_to($p, 'Price');
//		$t->_add_relation_to($d, 'Datetime');
//		d($t);
//		d($t->datetimes());
//		d($t->prices());
//		$t->_remove_relations('Datetime');
//		$t->delete_related_permanently('Price');
//		$t->delete_permanently();
//		$em = EEM_Event::instance();
//		$event = $em->get_one(array(array('Term_Taxonomy.term_taxonomy_id'=>array('IS NOT NULL'))));
//		d($event);
//		$taxes = $event->term_taxonomies();
//		d($taxes);
//		$a_tax = array_shift($taxes);
//		$event->remove_relation_to_term_taxonomy($a_tax);
//		$event->clear_cache('Term_Taxonomy');
//		d($event->term_taxonomies());
		////		$ticket = EEM_Ticket::instance()->get_one();
//		$li = EE_Line_Item::new_instance(array(
//			'LIN_code'=>'wefaeefaw',
//			'LIN_name'=>'monkey',
//			'OBJ_ID'=>$ticket->ID(),
//			'OBJ_type'=>'Ticket'
//		));
//		d($li);
////		$li->save();
//		d($li->object());
				
		//create basic transactionand payment meta
//		$pay = EE_Payment::new_instance(array('PAY_amount'=>100));
//		$pay->save();
//		$meta = EE_Extra_Meta::new_instance(array('OBJ_ID'=>$pay->ID(),'EXM_type'=>'Payment','EXM_key'=>'numMonkeys','EXM_value'=>'19'));
//		$meta->save();
//		echo 'echodump of $meta';
//		var_dump($meta);
//		echo 'echodump of $pay->extra_meta()';
//		var_dump($pay->extra_meta());
		
		
		//create basic promotion
//		$pro =  EE_Promotion::new_instance(array('PRO_scope'=>'Event'));				
//		$pro->save();
//		$ticket = EEM_Ticket::instance()->get_one();
//		$pro_ticket = EE_Promotion_Object::new_instance(array(
//			'PRO_ID'=>$pro->ID(),
//			'OBJ_ID'=>$ticket->ID(),
//			'POB_type'=>'Ticket',
//			));
//		$pro_ticket->save();
//		$pro_ticket = EEM_Promotion_Object::instance()->get_one(array(array('POB_type'=>'Ticket')));
//		echo 'echodump of $pro_ticket';
//		var_dump($pro_ticket);
//		$obj = $pro_ticket->object();
//		echo 'echodump of $obj';
//		var_dump($obj);
//		$promo = EEM_Promotion_Object::instance()->get_one(array(array('Ticket.TKT_ID'=>1)));
//		echo 'echodump of $promo';
//		var_dump($promo);
//CREATE A BASIC REG, ATTENDEE, AND TRANSACTION
		//
//		$txn = EE_Transaction::new_instance(array('TXN_total'=>34.02,'TXN_paid'=>34.02, 'STS_ID'=>'TIN'));
//////		$txn->save();
//		$att = EE_Attendee::new_instance(array('ATT_fname'=>'monkey2','ATT_lname'=>'nelson'));
//////		$att->save();
//////		$evt = EEM_Event::instance()->get_one();
////		$reg = EE_Registration::new_instance(array('TKT_ID'=>1,'STS_ID'=>'RCN'));
//////		$reg->save();
////		d($txn);
////		$txn->add_registration($reg);
////		d($txn);
//		$reg2 = EE_Registration::new_instance(array('TKT_ID'=>2,'STS_ID'=>'RCN'));
//		/* @var $reg2 EE_Registration */
//		$reg2->_add_relation_to($att, 'Attendee');
//		d($att);
//		d($reg2);
//		$txn->add_registration($reg2);
//		d($txn);
//		$txn->save_new_cached_related_model_objs();
//		d($txn);
//		d($txn);
//		d($txn->registrations());
//		$regs = EEM_Registration::instance()->get_all(array(array('Status.STS_open'=>true)));
//		echo 'echodump of $regs';
//		var_dump($regs);
		
//		$status = EEM_Status::instance()->get_one(array(array('STS_ID'=>'RCN')));
//		$regs = $status->get_many_related('Registration');
//		echo 'echodump of $regs';
//		var_dump($regs);
//		global $wpdb;
//		$result = $wpdb->get_var("SELECT option_value from wp_options where option_name='espresso_data_migrations'");
//		echo $result;die;
//		$dms = EE_Data_Migration_Manager::instance()->check_for_applicable_data_migration_scripts();
//		echo 'echodump of $dms';
//		var_dump($dms);
//		EE_Data_Migration_Manager::instance()->response_to_migration_ajax_request();
		
//		var_dump(EEM_Registration::instance()->get_registrations_per_day_report());
//		$results = EEM_Registration::instance()->get_registrations_per_event_report('-5 months');
//		$regs = EEM_Registration::instance()->get_all(array('limit'=>4));
//		$qg = EEM_Question_Group::instance()->get_one();
//		echo 'echodump of $qg';
//		var_dump($qg);
//		$qs = $qg->questions();
//		$qg->delete_related_permanently('Question', array(array('QST_ID'=>2)));
//		$r = EEM_Registration::instance()->get_one();
		
//		$related_answers = $r->answers();
//		echo 'echodump of $related_answers';
//		var_dump($related_answers);
//		$r->delete_related('Answer');
//		$related_answers = $r->answers();
//		echo '<hr><hr>echodump of $related_answers';
//		var_dump($related_answers);
		
		/* @var $r EE_Registration */
//		$qs = EEM_Question::instance()->get_all();
//		foreach($qs as $q){
//			/* @var $q EE_Question */
//			$ans1 = EE_Answer::new_instance(array('QST_ID'=>$q->ID(),'ANS_value'=>time(),'REG_ID'=>$r->ID()));
//			$ans1->save();
//		}
//		$answers_for_r = $r->answers();
//		echo 'echodump of $answers_for_r';
//		var_dump($answers_for_r);
		
		// additional hooks get added in the init phase
		// load other resources and begin to actually run shortcodes and modules
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 5 );
		// analyse the incoming WP request
		add_action( 'parse_request', array( $this, 'get_request' ), 1 );
		// process any content shortcodes
		add_action( 'parse_request', array( $this, '_initialize_shortcodes' ), 5 );
		// process request with module factory
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		// before headers sent
		add_action( 'wp', array( $this, 'wp' ), 5 );
		// load css and js
		add_action('wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 5 );
		// header
		add_action('wp_head', array( $this, 'header_meta_tag' ), 5 );
		// the content
		add_filter( 'the_content', array( $this, 'the_content' ), 5, 1 );
		// display errors
		add_action('wp_footer', array( $this, 'display_errors' ), 2 );			
		add_action('wp_footer', array( $this, 'display_registration_footer' ), 10 );

		//exclude EE critical pages from wp_list_pages
		add_filter('wp_list_pages_excludes', array( $this, 'remove_pages_from_wp_list_pages'), 10 );			
	

			//random debug code added by mike.
//			EE_Registry::instance()->load_class('Attendee',false,false,false);
//			$att = EE_Attendee::new_instance(array('ATT_lname'=>'nelson','ATT_ID'=>15));
//			echo 'echodump of $att';
//			var_dump($att);
//			
//			$att2 = EE_Attendee::new_instance(array('ATT_lname'=>'wilson'));
//			$att2->set_city('moscow');
//			$att2->set('ATT_ID',15);
//			echo 'echodump of $att2';
//			var_dump($att2);

		// NOTICES TEST
//		EE_Error::add_success('WOOT! This is a success message. Now we can dances!!!');
//		EE_Error::add_error('This is an error message. It means you done bad bad things!!!');
//		EE_Error::add_attention('This is an attention message. You should prolly read this... but... meh... do whatcha want!');
//		EE_Error::add_error('Ermehgerd!!! MOAR ERRORS!!! Seff n Garf aw gonna be maaaaad at you!!!');

//		$regs = EEM_Datetime::instance()->get_all( array(
//			array( 'DTT_reg_limit' => INF ),
//			'limit'=>1,
//			'order_by'=>array( 
//				'DTT_EVT_start'=>'ASC',		
//				'DTT_is_primary'=>'DESC'
//			)
//		));
//		$reg = reset( $regs );
//		d( $reg );		
//		$reg->save();
//		d( $reg );
		
//		$d = EE_Datetime::new_instance(array('DTT_reg_limit'=>-1));
//		d($d);
//		$d->save();
//		d($d);
		
	}


	/**
	 * simply hooks into "wp_list_pages_exclude" filter (for wp_list_pages method) and makes sure EE critical pages are never returned with the function.
	 *
	 *
	 * @param  array  $exclude_array any existing pages being excluded are in this array.
	 * @return array
	 */
	public function remove_pages_from_wp_list_pages( $exclude_array ) {
		return  array_merge( $exclude_array, EE_Registry::instance()->CFG->core->get_critical_pages_array() );
	}





	/**
	 * 	employ_CPT_Strategy
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function employ_CPT_Strategy() {
		if ( apply_filters('FHEE__EE_Front_Controller__employ_CPT_Strategy',true)){
			EE_Registry::instance()->load_core( 'CPT_Strategy' );
		}
	}






	/*********************************************** 		WP_LOADED ACTION HOOK		 ***********************************************/





	/**
	 * 	wp_loaded - should fire after shortcode, module, addon, or other plugin's have been registered and their default priority init phases have run
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function wp_loaded() {
		// messages loading is turned OFF by default, but prior to the wp_loaded hook, can be turned back on again via: add_filter( 'FHEE_load_EE_messages', '__return_true' );
		if ( apply_filters( 'FHEE_load_EE_messages', FALSE )) {
			EE_Registry::instance()->load_lib( 'Messages_Init' );
		}

	}





	/*********************************************** 		PARSE_REQUEST HOOK		 ***********************************************/




	/**
	 *	_get_request
	 * 
	 *	@access public
	 *	@return void
	 */
	public function get_request( WP $WP ) {
//		d( $WP );
		do_action( 'AHEE__EE_ront_Controller__get_request__before_Request_Handler_loaded' );
		EE_Registry::instance()->load_core( 'Request_Handler', $WP );	
		do_action( 'AHEE__EE_Front_Controller__get_request__after_Request_Handler_loaded' );
	}




	/**
	 * 	_initialize_shortcodes - calls init method on shortcodes that have been determined to be in the_content for the currently requested page
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function _initialize_shortcodes( WP $WP ) {
		do_action( 'AHEE__EE_Front_Controller__initialize_shortcodes__begin', $WP, $this );
		//d( $this->EE->REQ );
		// grab post_name from request
		$current_post = apply_filters('FHEE__EE_Front_Controller__initialize_shortcodes__current_post_name',EE_Registry::instance()->REQ->get( 'post_name' ));
//		d( $current_post );
		// if it's not set, then check if frontpage is blog
		if ( empty( $current_post ) && get_option( 'show_on_front' ) == 'posts' ) {
			// yup.. this is the posts page, prepare to load all shortcode modules
			$current_post = 'posts';
		} else if ( empty( $current_post ) && get_option( 'show_on_front' ) == 'page' ) {
			// some other page is set as the homepage
			if ( $page_on_front = get_option( 'page_on_front' )) {
				// k now we need to find the slug for this page
				global $wpdb;
				$SQL = 'SELECT post_name from ' . $wpdb->posts . ' WHERE post_type="page" AND post_status="publish" AND ID=%d';
				if( $post_slug = $wpdb->get_var( $wpdb->prepare( $SQL, $page_on_front ))) {
					// set the current post slug to what it actually is
					$current_post = $post_slug;
//					d( $current_post );
				}					
			}
		} else if ( get_option( 'show_on_front' ) == 'page' ) {
			// we're not on the homepage, but some "other" page is set as the posts page... 
			if ( $page_for_posts = get_option( 'page_for_posts' )) {
				// better get the ID for the current post
				global $wpdb;
				$SQL = 'SELECT ID from ' . $wpdb->posts . ' WHERE post_type="posts" OR post_type="page" AND post_status="publish" AND post_name=%s';
				$current_post_id = $wpdb->get_var( $wpdb->prepare( $SQL, $current_post ));
				// is the current post the "page_for_posts" ???
				if ( $current_post_id === $page_for_posts ) {
					$current_post = 'posts';
//					d( $current_post );
				}					
			}
		} 
		// are we on a category page?
		$term_exists = is_array( term_exists( $current_post, 'category' ));
//		d( $term_exists );
		// make sure shortcodes are set
		if ( isset( EE_Registry::instance()->CFG->core->post_shortcodes )) {
			// cycle thru all posts with shortcodes set
			foreach ( EE_Registry::instance()->CFG->core->post_shortcodes as $post_name => $post_shortcodes ) {
				// are we on this page ?
				if ( $current_post == $post_name || $term_exists ) {
//					d( $post_name );
					// filter shortcodes so 
					$post_shortcodes = apply_filters( 'FHEE__Front_Controller__initialize_shortcodes__post_shortcodes', $post_shortcodes );
//					d( $post_shortcodes );
					// now cycle thru shortcodes
					foreach ( $post_shortcodes as $shortcode_class => $post_id ) {
						// verify shortcode is in list of registered shortcodes
						if ( ! isset( EE_Registry::instance()->shortcodes[ $shortcode_class ] )) {
							$msg = sprintf( __( 'The %s shortcode has not been properly registered', 'event_espresso' ), $shortcode_class );
							EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
							add_filter( 'FHEE_run_EE_the_content', '__return_true' );
							break;
						}
						//is this : a shortcodes set exclusively for this post, or for the home page, or a category, or a taxonomy ?
						if ( isset( EE_Registry::instance()->CFG->core->post_shortcodes[ $current_post ] ) || $term_exists ) {
							// let's pause to reflect on this...
							$sc_reflector = new ReflectionClass( 'EES_' . $shortcode_class );
							// ensure that class is actually a shortcode
							if ( ! $sc_reflector->isSubclassOf( 'EES_Shortcode' )) {
								$msg = sprintf( __( 'The requested %s shortcode is not of the class "EES_Shortcode".', 'event_espresso' ), $shortcode_class );
								EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
								add_filter( 'FHEE_run_EE_the_content', '__return_true' );
								break;
							}
							// and pass the request object to the run method
							$shortcode = $sc_reflector->newInstance( EE_Registry::instance() );
							// fire the shortcode class's run method, so that it can activate resources
							$shortcode->run( $WP );
//								d( $shortcode );
						}
					}
				}
			}
		}

		do_action('AHEE__EE_Front_Controller__initialize_shortcodes__end',$this);
//		printr( EE_Registry::instance()->shortcodes, 'EE_Registry::instance()->shortcodes  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
	}




	/**
	 * 	pre_get_posts - basically a module factory for instantiating modules and selecting the final view template
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function pre_get_posts( $WP_Query ) {
		// load module request router
		$Module_Request_Router = EE_Registry::instance()->load_core( 'Module_Request_Router' );
		// cycle thru module routes
		while ( $route = $Module_Request_Router->get_route( $WP_Query ) ) {
//			d( $route );
			// determine module and method for route
			$module = $Module_Request_Router->resolve_route( $route );
			// get registered view for route
			$this->_view_template = $Module_Request_Router->get_view( $route );
			// map the routes to the module objects
			EE_Registry::instance()->modules[ $route ] = $module;
		}
		// if a view was registered for the last called route, then hook into template_include
		if ( ! empty( $this->_view_template )) {
			add_filter( 'template_include', array( $this, 'template_include' ), 1 );
		}

	}





	/*********************************************** 		WP HOOK		 ***********************************************/






	/**
	 * 	wp - basically last chance to do stuff before headers sent
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function wp() {
		EE_Registry::instance()->load_helper( 'Template' );
	}



	/*********************************************** 		WP_ENQUEUE_SCRIPTS && WP_HEAD HOOK		 ***********************************************/



	/**
	 * 	wp_enqueue_scripts
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function wp_enqueue_scripts() {
		
		// css is turned ON by default, but prior to the wp_enqueue_scripts hook, can be turned OFF  via:  add_filter( 'FHEE_load_css', '__return_false' );
		if ( apply_filters( 'FHEE_load_css', TRUE )) {
			
			EE_Registry::instance()->CFG->template_settings->enable_default_style = TRUE;
			//Load the ThemeRoller styles if enabled
			if ( isset( EE_Registry::instance()->CFG->template_settings->enable_default_style ) && EE_Registry::instance()->CFG->template_settings->enable_default_style ) {

				add_filter( 'FHEE_enable_default_espresso_css', '__return_true' );

				//Load custom style sheet if available
				if ( isset( EE_Registry::instance()->CFG->style_settings['css_name'] )) {
					wp_register_style('espresso_custom_css', EVENT_ESPRESSO_UPLOAD_URL . 'css/' . EE_Registry::instance()->CFG->style_settings['css_name']);
					wp_enqueue_style('espresso_custom_css');
				}
				
				if ( file_exists( EVENT_ESPRESSO_UPLOAD_DIR . 'css/espresso_default.css' )) {
					wp_register_style( 'espresso_default', EVENT_ESPRESSO_UPLOAD_DIR . 'css/espresso_default.css' );
				} else {
					wp_register_style( 'espresso_default', EE_GLOBAL_ASSETS_URL . 'css/espresso_default.css' );
				}
				wp_enqueue_style('espresso_default');
				
			}

		}

		// js is turned ON by default, but prior to the wp_enqueue_scripts hook, can be turned OFF  via:  add_filter( 'FHEE_load_js', '__return_false' );
		if ( apply_filters( 'FHEE_load_js', TRUE )) {

			wp_enqueue_script( 'jquery' );
			//let's make sure that all required scripts have been setup
			if ( function_exists( 'wp_script_is' )) {
				if ( ! wp_script_is( 'jquery' )) {
					$msg = sprintf( 
						__( '%sJquery is not loaded!%sEvent Espresso is unable to load Jquery due to a conflict with your theme or another plugin.', 'event_espresso' ),
						'<em><br />',
						'</em>'
					);
					EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
				}
			}
			// load core js
			wp_register_script( 'espresso_core', EE_GLOBAL_ASSETS_URL . 'scripts/espresso_core.js', array('jquery'), EVENT_ESPRESSO_VERSION, TRUE );
			wp_enqueue_script( 'espresso_core' );
			
			if ( ! function_exists( 'wp_head' )) {
				$msg = sprintf( 
					__( '%sMissing wp_head() function.%sThe WordPress function wp_head() seems to be missing in your theme. Please contact the theme developer to make sure this is fixed before using Event Espresso.', 'event_espresso' ),
					'<em><br />',
					'</em>'
				);
				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
			}
			if ( ! function_exists( 'wp_footer' )) {
				$msg = sprintf( 
					__( '%sMissing wp_footer() function.%sThe WordPress function wp_footer() seems to be missing in your theme. Please contact the theme developer to make sure this is fixed before using Event Espresso.', 'event_espresso' ),
					'<em><br />',
					'</em>'
				);
				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
			}
		}


		//qtip is turned OFF by default, but prior to the wp_enqueue_scripts hook, can be turned back on again via: add_filter('FHEE_load_qtips', '__return_true' );
		if ( apply_filters( 'FHEE_load_qtip', FALSE ) ) {
			EE_Registry::instance()->load_helper('Qtip');
			EEH_Qtip::register_and_enqueue();
		}

	}



	/**
	 * 	header_meta_tag
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function header_meta_tag() {
		print( apply_filters("FHEE__EE_Front_Controller__header_meta_tag","<meta name='generator' content='Event Espresso Version " . EVENT_ESPRESSO_VERSION . "' />"));
	}




	/*********************************************** 		THE_CONTENT FILTER HOOK		 ***********************************************/



	/**
	 * 	the_content
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function the_content( $the_content ) {
//		EE_Registry::instance()->load_model('Attendee', false, false, false);
//		EEM_Attendee::instance()->show_next_x_db_queries(1);
//		$attendees = EEM_Attendee::instance()->get_all(array(array('Registration.Event.EVT_ID'=>1),'default_where_conditions'=>'all'));
//		echo 'echodump of $answers';
//		var_dump($attendees);
		
		
//		
//		EE_Registry::instance()->load_class('Attendee', false, false, false);
//		$a1 = EE_Attendee::new_instance(array('ATT_fname'=>'mike','ATT_address'=>'monkey town'));
//		$a1->save();
//		
//		EE_Registry::instance()->load_class('Registration',false,false,false);
//		$r1 = EE_Registration::new_instance(array('ATT_ID'=>$a1->ID(),'PRC_ID'=>1,'DTT_ID'=>1));
//		$r1->save();
//		
//		$r_result = $r1->delete();
//		echo 'echodump of registration $r_result';
//		var_dump($r_result);
//		$result = $a1->delete();
//		
//		echo 'echodump of attendee $result';
//		var_dump($result);
//		var_dump( EE_Error::get_notices());
		
		
		
//		EE_Registry::instance()->load_model('Question',false,false,false);
//		EEM_Question::instance()->show_next_x_db_queries(1);
//		$q  = EEM_Question::instance()->get_one_deleted_or_undeleted();
//		echo 'echodump of $q';
//		var_dump($q);
		/* @var $q1 EE_Question  */
//		$q1 = EEM_Question::instance()->get_one_deleted_or_undeleted();
//		
//		echo 'echodump of $q1';
//		var_dump($q1);
//		$q1->set_required(false);
//		$q1->save();
//		$groups =$q1->question_groups();
//		$result = $q1->delete();
//		echo 'echodump of $result';
//		var_dump($result);
//		var_dump(EE_Error::get_notices());
		
		// nothing gets loaded at this point unless other systems turn this hookpoint on by using:  add_filter( 'FHEE_run_EE_the_content', '__return_true' );
		if ( apply_filters( 'FHEE_run_EE_the_content', FALSE )) {
		}

		return $the_content;
	}







	/*********************************************** 		WP_FOOTER		 ***********************************************/





	/**
	 * 	display_registration_footer
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function display_registration_footer() {
		$url = apply_filters( 'FHEE__EE_Front_Controller__registration_footer__url', 'http://eventespresso.com/' );
		if ( apply_filters('FHEE__EE_Front__Controller__show_reg_footer',EE_Registry::instance()->CFG->admin->show_reg_footer )) {
			return apply_filters('FHEE__EE_Front_Controller__display_registration_footer','<p style="font-size: 12px;"><a href="' . $url . '" title="Event Registration Powered by Event Espresso">Event Registration and Ticketing</a> Powered by <a href="' . $url . '" title="Event Espresso - Event Registration and Management System for WordPress">Event Espresso</a></p>');
		}
	}




	/**
	 * 	display_errors
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function display_errors() {
		do_action('AHEE__EE_Front_Controller__display_errors__begin');
		if(apply_filters('FHEE__EE_Front_Controller__display_errors',true)){
			echo EE_Error::get_notices();
			EE_Registry::instance()->load_helper( 'Template' );
			EEH_Template::display_template( EE_TEMPLATES . 'espresso-ajax-notices.template.php' );
		}
		do_action('AHEE__EE_Front_Controller__display_errors__end');
	}





	/*********************************************** 		UTILITIES		 ***********************************************/




	/**
	 * 	template_include
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function template_include( $template_path = NULL ) {
		// check if the template file exists in the theme first by calling locate_template()
		if ( ! empty( $this->_view_template ) && ! $template_path = locate_template( array( basename( $this->_view_template )))) {
			// otherwise get it from 
			$template_path = $this->_view_template;
		}
		return $template_path;
	}





	
	


}
// End of file EE_Front_Controller.core.php
// Location: /core/EE_Front_Controller.core.php
