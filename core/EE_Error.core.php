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
 * Error Handling Class
 *
 * @package			Event Espresso
 * @subpackage	includes/classes/EE_Exceptions.class.php
 * @author				Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
class EE_Error extends Exception {


	/**
	* 	name of the file to log exceptions to
	* 	@access	private
    *	@var string	
	*/
	private static $_exception_log_file = 'espresso_error_log.txt';

	/**
	* 	the exception  
	* 	@access	private
    *	@var object	
	*/
	private $_exception;

	/**
	* 	stores details for all exception
	* 	@access	private
    *	@var array	
	*/
	private static $_all_exceptions = array();

	/**
	* 	tracks number of errors
	* 	@access	private
    *	@var int	
	*/
	private static $_error_count = 0;

	/**
	* 	has JS been loaded ?
	* 	@access	private
    *	@var boolean	
	*/
	private static $_js_loaded = FALSE;

	/**
	* 	has shutdown action been added ?
	* 	@access	private
    *	@var boolean	
	*/
	private static $_action_added = FALSE;

	/**
	* 	has shutdown action been added ?
	* 	@access	private
    *	@var boolean	
	*/
	private static $_espresso_notices = array( 'success' => FALSE, 'errors' => FALSE, 'attention' => FALSE );





	/**
	*	@override default exception handling
	*	@access public
	*	@echo string
	*/
	function __construct( $message, $code = 0, Exception $previous = NULL ) {

		wp_enqueue_script( 'ee_error_js' );
		wp_localize_script( 'ee_error_js','ee_settings', array( 'wp_debug'=>WP_DEBUG ));
	
		if (version_compare(phpversion(), '5.3.0', '<')) {
			parent::__construct( $message, $code );
		} else {
			parent::__construct( $message, $code, $previous );
		}
		
	}






	/**
	*	_add_actions
	*	@access public
	*	@return void
	*/
    public function get_error() {
  		
		// get separate user and developer messages if they exist		
		$msg = explode( '||', $this->getMessage() );
		$user_msg = $msg[0];
		$dev_msg = isset( $msg[1] ) ? $msg[1] : $msg[0];
		$msg = WP_DEBUG ? $dev_msg : $user_msg;
		
		// add details to _all_exceptions array
		$x_time = time();
		self::$_all_exceptions[ $x_time ]['name'] 	= get_class( $this );
		self::$_all_exceptions[ $x_time ]['file'] 		= $this->getFile();
		self::$_all_exceptions[ $x_time ]['line'] 		= $this->getLine();
		self::$_all_exceptions[ $x_time ]['msg'] 	= $msg;
		self::$_all_exceptions[ $x_time ]['code'] 	= $this->getCode();
		self::$_all_exceptions[ $x_time ]['trace'] 	= $this->getTrace();
		self::$_all_exceptions[ $x_time ]['string'] 	= $this->getTraceAsString();
		self::$_error_count++;
		
		//add_action( 'shutdown', array( $this, 'display_errors' ));
		$this->display_errors();
		
	}



	/**
	*	has_error
	*	@access public
	*	@return boolean
	*/
    public static function has_error(){
		return self::$_error_count ? TRUE : FALSE;
	}



	/**
	*	display_errors
	*	@access public
	*	@echo string
	*/
    public function display_errors(){		
		
		$trace_details = '';

		$ouput = '
<style type="text/css">
	#error-page {
		max-width:90% !important;	
	}
</style>		
<div id="ee-error-message" class="error">';

		if ( ! WP_DEBUG ) {
			$ouput .= '
	<p>';
		}
		
		// cycle thru errors
		foreach ( self::$_all_exceptions as $time => $ex ) {
		
			// process trace info
			if ( empty( $ex['trace'] )) {
		 
	            $trace_details .= __( 'Sorry, but no trace information was available for this exception.', 'event_espresso' );
			 
			} else {

				$trace_details .= '
			<div style="padding:3px; margin:0 0 1em; border:1px solid #666; background:#fff; border-radius:3px;">
			<table width="100%" border="0" cellpadding="5" cellspacing="0" style="border:1px solid #666; border-bottom:none; background:#f9f9f9;">
				<tr>
					<th scope="col" align="center" style="width:1.5%; background:#eee; border-bottom:1px solid #666;">#</th>
					<th scope="col" align="center" style="width:3.5%; background:#eee; border-bottom:1px solid #666;">Line</th>
					<th scope="col" align="left" style="width:40%; background:#eee; border-bottom:1px solid #666;">File</th>
					<th scope="col" align="left" style="background:#eee; border-bottom:1px solid #666;">Class</th>
					<th scope="col" align="left" style="background:#eee; border-bottom:1px solid #666;">Method( arguments )</th>
				</tr>';
			
				$last_on_stack = count( $ex['trace'] ) - 1;
				// reverse array so that stack is in proper chronological order
				$sorted_trace = array_reverse( $ex['trace'] );

				foreach ( $sorted_trace as $nmbr => $trace ) {
					
					$file = isset( $trace['file'] ) ? $trace['file'] : '' ;
					$class = isset( $trace['class'] ) ? $trace['class'] : '';
					$type = isset( $trace['type'] ) ? $trace['type'] : '';
					$function = isset( $trace['function'] ) ? $trace['function'] : '';
					$args = isset( $trace['args'] ) ? $this->_convert_args_to_string( $trace['args'] ) : '';
					$line = isset( $trace['line'] ) ? $trace['line'] : '';
					$zebra = $nmbr % 2 ? ' background:#f3f3f3;' : '';
					
					if ( empty( $file ) && ! empty( $class )) {
						$a = new ReflectionClass( $class );
						$file = $a->getFileName();
						if ( empty( $line ) && ! empty( $function )) {
							$b = new ReflectionMethod( $class, $function );
							$line = $b->getStartLine();
						}
					}
					
					if ( $nmbr == $last_on_stack ) {
						$file = $ex['file'] != '' ? $ex['file'] : $file;
						$line = $ex['line'] != '' ? $ex['line'] : $line;
						$error_code = self::generate_error_code ( $file, $trace['function'], $line );
					}
					
					$nmbr_dsply = ! empty( $nmbr ) ? $nmbr : '&nbsp;';
					$line_dsply = ! empty( $line ) ? $line : '&nbsp;';
					$file_dsply = ! empty( $file ) ? $file : '&nbsp;';
					$class_dsply = ! empty( $class ) ? $class : '&nbsp;';
					$type_dsply = ! empty( $type ) ? $type : '&nbsp;';
					$function_dsply = ! empty( $function ) ? $function : '&nbsp;';
					$args_dsply = ! empty( $args ) ? '( ' . $args . ' )' : '';
								 
		              $trace_details .= '
					<tr>
						<td align="center" style="border-bottom:1px solid #666;' . $zebra . '">' . $nmbr_dsply . '</td>
						<td align="center" style="border-bottom:1px solid #666;' . $zebra . '">' . $line_dsply . '</td>
						<td align="left" style="border-bottom:1px solid #666;' . $zebra . '">' . $file_dsply . '</td>
						<td align="left" style="border-bottom:1px solid #666;' . $zebra . '">' . $class_dsply . '</td>
						<td align="left" style="border-bottom:1px solid #666;' . $zebra . '">' . $type_dsply . $function_dsply . $args_dsply . '</td>
					</tr>';

					
				}
				 
	            $trace_details .= '
			 </table>
			</div>';
			
			}
			
			$ex['code'] = $ex['code'] ? $ex['code'] : $error_code;
			
			// add generic non-identifying messages for non-privledged uesrs
			if ( ! WP_DEBUG ) {
			
				$ouput .= '<span class="ee-error-user-msg-spn">' . trim( $ex['msg'] )  . '</span> &nbsp; <sup>' . $ex['code'] . '</sup><br />';
				
			} else {

				// or helpful developer messages if debugging is on
				$ouput .= '
		<div class="ee-error-dev-msg-dv">
			<p class="ee-error-dev-msg-pg">
				<strong class="ee-error-dev-msg-str">An ' . $ex['name'] . ' exception was thrown!</strong>  &nbsp; <span>code: ' . $ex['code'] . '</span><br />
				<span class="big-text">"' . trim( $ex['msg'] ) . '"</span>  &nbsp; <a class="display-ee-error-trace-lnk" rel="ee-error-trace-' . self::$_error_count . $time . '">' . __( 'click to view backtrace and class/method details', 'event_espresso' ) . '</a><br />
				'.$ex['file'].' &nbsp; ( line no: '.$ex['line'].' )
			</p>
			<div id="ee-error-trace-' . self::$_error_count . $time . '" class="ee-error-trace-dv hidden">
				' . $trace_details;
				
				if ( ! empty( $class )) {
					$ouput .= '
				<div style="padding:3px; margin:0 0 1em; border:1px solid #666; background:#fff; border-radius:3px;">
					<div style="padding:1em 2em; border:1px solid #666; background:#f9f9f9;">
						<h3>Class Details</h3>';
						$a = new ReflectionClass( $class );
						$ouput .= '
						<pre>' . $a . '</pre>
					</div>
				</div>';
				}
				
				$ouput .= '				
			</div>
		</div>
		<br />';		
		
			}
			
			$this->write_to_error_log( $time, $ex );
		
		}
		
		// remove last linebreak
		$ouput = substr( $ouput, 0, ( count( $ouput ) - 7 ));
		
		if ( ! WP_DEBUG ) {
			$ouput .= '
	</p>';
		}
				
		$ouput .= '
</div>';

		if ( defined('DOING_AJAX') ) {
			echo json_encode( array('error' => $ouput) );
			exit();
		}

		echo $ouput;

//		$template = 'error_msg.php';
//		$path_to_template = EE_TEMPLATES_PATH;
//		$template_vars = array( 'error_msg' => $ouput );
//		EEH_Template::display_template( $template . $path_to_template, $template_args );
		
	}





	/**
	*	generate string from exception trace args
	* 
	*	@access private
	*	@ param array $arguments
	*	@ return string
	*/
	private function _convert_args_to_string ( $arguments = array(), $array = FALSE ) {		
	
		$arg_string = '';
		if ( ! empty( $arguments )) {
		
			$args = array();
			
			foreach ( $arguments as $arg ) {
			
				if ( ! empty( $arg )) {
				
					if ( is_string( $arg )) {
						$args[] = " '" . $arg . "'";
					} elseif ( is_array( $arg )) {
						$args[] = 'ARRAY(' . $this->_convert_args_to_string( $arg, TRUE );
					} elseif ( is_null( $arg )) {
						$args[] = ' NULL';
					} elseif ( is_bool( $arg )) {
						$args[] = ( $arg ) ? ' TRUE' : ' FALSE';
					} elseif ( is_object( $arg )) {
						$args[] = ' OBJECT ' . get_class( $arg );
					} elseif ( is_resource( $arg )) {
						$args[] = get_resource_type( $arg );
					} else {
						$args[] = $arg;
					}   
					
				}
				
			}   
			$arg_string = implode( ', ', $args );
		}
		if ( $array ) {
			$arg_string .= ' )';
		}
		return $arg_string;
	}





	/**
	* 	add error message
	*
	*	@access public
	* 	@param		string		$msg	the message to display to users or developers - adding a double pipe || (OR) creates separate messages for user || dev
	* 	@param		string		$file		the file that the error occured in - just use __FILE__
	* 	@param		string		$func	the function/method that the error occured in - just use __FUNCTION__
	* 	@param		string		$line	the line number where the error occured - just use __LINE__
	* 	@return 		void
	*/
	public static function add_error( $msg = NULL, $file = NULL, $func = NULL, $line = NULL ) {
		self::_add_notice ( 'errors', $msg, $file, $func, $line );
	}





	/**
	* 	add success message
	*
	*	@access public
	* 	@param		string		$msg	the message to display to users or developers - adding a double pipe || (OR) creates separate messages for user || dev
	* 	@param		string		$file		the file that the error occured in - just use __FILE__
	* 	@param		string		$func	the function/method that the error occured in - just use __FUNCTION__
	* 	@param		string		$line	the line number where the error occured - just use __LINE__
	* 	@return 		void
	*/
	public static function add_success( $msg = NULL, $file = NULL, $func = NULL, $line = NULL ) {
		self::_add_notice ( 'success', $msg, $file, $func, $line );
	}





	/**
	* 	add attention message
	*
	*	@access public
	* 	@param		string		$msg	the message to display to users or developers - adding a double pipe || (OR) creates separate messages for user || dev
	* 	@param		string		$file		the file that the error occured in - just use __FILE__
	* 	@param		string		$func	the function/method that the error occured in - just use __FUNCTION__
	* 	@param		string		$line	the line number where the error occured - just use __LINE__
	* 	@return 		void
	*/
	public static function add_attention( $msg = NULL, $file = NULL, $func = NULL, $line = NULL ) {
		self::_add_notice ( 'attention', $msg, $file, $func, $line );
	}





	/**
	* 	add success message
	*
	*	@access public
	* 	@param		string		$type	whether the message is for a success or error notification
	* 	@param		string		$msg	the message to display to users or developers - adding a double pipe || (OR) creates separate messages for user || dev
	* 	@param		string		$file		the file that the error occured in - just use __FILE__
	* 	@param		string		$func	the function/method that the error occured in - just use __FUNCTION__
	* 	@param		string		$line	the line number where the error occured - just use __LINE__
	* 	@return 		void
	*/
	private static function _add_notice( $type = 'success', $msg = NULL, $file = NULL, $func = NULL, $line = NULL ) {
		// get error code only on error
		$error_code = $type == 'errors' ? EE_Error::generate_error_code ( $file, $func, $line ) : '';
		// get separate user and developer messages if they exist		
		$msg = explode( '||', $msg );
		$user_msg = $msg[0];
		$dev_msg = isset( $msg[1] ) ? $msg[1] : $msg[0];
		$msg = WP_DEBUG ? $dev_msg : $user_msg;
		// add notice if message exists
		if ( ! empty( $msg )) {
			self::$_espresso_notices[ $type ][] = $msg . $error_code;
			add_action( 'wp_footer', array( 'EE_Error', 'enqueue_error_scripts' ), 1 );
		}
		
	}





	/**
	* 	in some case it may be necessary to overwrite the existing success messages
	*
	*	@access public
	* 	@return 		void
	*/
	public static function overwrite_success() {
		self::$_espresso_notices['success'] = FALSE;
	}





	/**
	* 	in some case it may be necessary to overwrite the existing attention messages
	*
	*	@access public
	* 	@return 		void
	*/
	public static function overwrite_attention() {
		self::$_espresso_notices['attention'] = FALSE;
	}





	/**
	* 	in some case it may be necessary to overwrite the existing error messages
	*
	*	@access public
	* 	@return 		void
	*/
	public static function overwrite_errors() {
		self::$_espresso_notices['errors'] = FALSE;
	}



	/**
	*	has_errors
	*	@access public
	*	@return int
	*/
    public static function has_notices(){
		$has_notices = 0;
		// check for success messages
		$has_notices = self::$_espresso_notices['success'] && ! empty(  self::$_espresso_notices['success'] ) ? 3 : $has_notices;
		// check for attention messages
		$has_notices = self::$_espresso_notices['attention'] && ! empty(  self::$_espresso_notices['attention'] ) ? 2 : $has_notices;
		// check for error messages
		$has_notices = self::$_espresso_notices['errors'] && ! empty(  self::$_espresso_notices['errors'] ) ? 1 : $has_notices;
		return $has_notices;
	}



	/**
	* 	compile all error or success messages into one string
	*
	*	@access public
	* 	@param		boolean		$format_output		whether or not to format the messages for display in the WP admin
	* 	@param		boolean		$save_to_transient	whether or not to save notices to the db for retreival on next request - ONLY do this just before redirecting
	* 	@param		boolean		$remove_empty		whether or not to unset empty messages
	* 	@return 		array
	*/
	public static function get_notices( $format_output = TRUE, $save_to_transient = FALSE, $remove_empty = TRUE ) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');

		$success_messages = '';
		$attention_messages = '';
		$error_messages = '';
		$print_scripts = FALSE;

		// printr( self::$_espresso_notices, 'espresso_notices  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		
		// either save notices to the db
		if ( $save_to_transient ) {
			update_option( 'espresso_notices', self::$_espresso_notices );
			return;
		} 
		// grab any notices that have been previously saved
		if ( $notices = get_option( 'espresso_notices', FALSE )) {
			foreach ( $notices as $type => $notice ) {
				if ( is_array( $notice ) && ! empty( $notice )) {
					// make sure that existsing notice type is an array
					self::$_espresso_notices[ $type ] =  is_array( self::$_espresso_notices[ $type ] ) && ! empty( self::$_espresso_notices[ $type ] ) ? self::$_espresso_notices[ $type ] : array();
					// merge stored notices with any newly created ones
					self::$_espresso_notices[ $type ] = array_merge( self::$_espresso_notices[ $type ], $notice );
					$print_scripts = TRUE;
				}
			}
			// now clear any stored notices
			update_option( 'espresso_notices', FALSE );
		}

		// check for success messages
		if ( self::$_espresso_notices['success'] && ! empty(  self::$_espresso_notices['success'] )) {
			// combine messages
			$success_messages .= implode( self::$_espresso_notices['success'], '<br />' );
			$print_scripts = TRUE;
		}

		// check for attention messages
		if ( self::$_espresso_notices['attention'] && ! empty(  self::$_espresso_notices['attention'] ) ) {
			// combine messages
			$attention_messages .= implode( self::$_espresso_notices['attention'], '<br />' );
			$print_scripts = TRUE;
		}

		// check for error messages
		if ( self::$_espresso_notices['errors'] && ! empty(  self::$_espresso_notices['errors'] ) ) {
			$error_messages .= count( self::$_espresso_notices['errors'] ) > 1 ? __( 'The following errors have occured:<br />', 'event_espresso' ) : __( 'An error has occured:<br />', 'event_espresso' );
			// combine messages
			$error_messages .= implode( self::$_espresso_notices['errors'], '<br />' );
			$print_scripts = TRUE;
		}

		if ($format_output) {

			$notices = '<div id="espresso-notices">';
			
			$close = is_admin() ? '' : '<a class="close-espresso-notice">&times;</a>';

			if ($success_messages != '') {
				$css_id = is_admin() ? 'message' : 'espresso-notices-success';
				$css_class = is_admin() ? 'updated fade' : 'success fade-away';
				//showMessage( $success_messages );
				$notices .= '<div id="' . $css_id . '" class="espresso-notices ' . $css_class . '"><p>' . $success_messages . '</p>' . $close . '</div>';
			}

			if ($attention_messages != '') {
				$css_id = is_admin() ? 'message' : 'espresso-notices-attention';
				$css_class = is_admin() ? 'updated fade' : 'attention fade-away';
				//showMessage( $error_messages, TRUE );
				$notices .= '<div id="' . $css_id . '" class="espresso-notices ' . $css_class . '"><p>' . $attention_messages . '</p>' . $close . '</div>';
			}
			
			if ($error_messages != '') {
				$css_id = is_admin() ? 'message' : 'espresso-notices-error';
				$css_class = is_admin() ? 'error fade' : 'error fade-away';
				//showMessage( $error_messages, TRUE );
				$notices .= '<div id="' . $css_id . '" class="espresso-notices ' . $css_class . '"><p>' . $error_messages . '</p>' . $close . '</div>';
			}
			
			$notices .= '</div>';
			
		} else {

			$notices = array(
					'success' => $success_messages,
					'attention' => $attention_messages,
					'errors' => $error_messages
			);
			
			if ( $remove_empty ) {
				// remove empty notices						
				foreach ($notices as $type => $notice) {
					if (empty($notice)) {
						unset($notices[$type]);
					}
				}
			}
		}
		
		if ( $print_scripts ) {			
			wp_enqueue_script( 'ee_error_js' );
			wp_localize_script( 'ee_error_js','ee_settings', array( 'wp_debug'=>WP_DEBUG ));
		}
		
		return $notices;
	}






	/**
	* 	enqueue_error_scripts
	*
	*	@access public
	* 	@return 		void
	*/
	public function enqueue_error_scripts() {
		add_filter( 'FHEE_load_css', '__return_true' );
		add_filter( 'FHEE_load_js', '__return_true' );
		wp_enqueue_script( 'ee_error_js' );
		wp_localize_script( 'ee_error_js','ee_settings', array( 'wp_debug'=>WP_DEBUG ));
	}



	/**
	*	create error code from filepath, function name,
	*	and line number where exception or error was thrown
	* 
	*	@access public
	*	@ param string $file
	*	@ param string $func
	*	@ param string $line
	*	@ return string
	*/
	public static function generate_error_code ( $file = '', $func = '', $line = '' ) {

	//echo '<h4>$file : ' . $file . '  <br /><span style="font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span></h4>';
	//echo '<h4>$func : ' . $func . '  <br /><span style="font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span></h4>';
	//echo '<h4>$line : ' . $line . '  <br /><span style="font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span></h4>';

		do_action('AHEE_log', __FILE__, __FUNCTION__, '');

		$error_code = '';
		$code_bits = array( 'file' => $file, 'func' => $func, 'line' => $line );

		foreach ( $code_bits as $key => $code_bit ) {
			switch ( $key ) {

				case 'file':
					$code_bit = str_replace( '\\', '/', $code_bit );
					// break filepath up by the /
					$code_bit = explode ( '/', $code_bit );
					// filename is the last segment
					$file = isset( $code_bit[ count($code_bit)-1 ] ) ? $code_bit[ count($code_bit)-1 ] : '';
					// folder is the second to the last segment
					$folder = isset( $code_bit[ count($code_bit)-2 ] ) ? $code_bit[ count($code_bit)-2 ] : '';
					//change all dashes to underscores
					$folder = str_replace ( '-', '_', $folder );
					//strip vowels
					$folder = str_replace ( array( 'a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U' ), '', $folder );
					// break it up by the _
					$folder_bits = explode( '_', $folder);
					$folder = '';
					foreach ( $folder_bits as $folder_bit ) {
						// grab the first 2 characters from each word
						$folder .= substr($folder_bit, 0, 3);
					}
					$error_code .= $folder != '' ? $folder . '-' : '';

					// break filename by the dots - to get at the first bit
					$code_bit = explode('.', $file);
					// remove EE_ from the filename
					$code_bit = str_replace ( 'EE_', '', $code_bit[0] );
					// and EEM_ 
					$code_bit = str_replace ( 'EEM_', '', $code_bit );
					// remove all non-alpha characters
					$code_bit = preg_replace( '[A-Za-z]', '', $code_bit );
					//change all dashes to underscores
					$file = str_replace ( '-', '_', $code_bit );
					//strip vowels
					$file = str_replace ( array( 'a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U' ), '', $file );
					// break it up by the _
					$file_bits = explode( '_', $file);
					$file = '';
					foreach ( $file_bits as $file_bit ) {
						// grab the first 2 characters from each word
						$file .= substr($file_bit, 0, 3);
					}
					$error_code .= $file != '' ? $file . '-' : '';

				break;

				case 'func':
					//change all dashes to underscores
					$code_bit = str_replace ( '-', '_', $code_bit );
					// break function name by the underscore if there are any
					$func_bits = explode('_', $code_bit);
					// split camelCase
					// preg_match_all('/((?:^|[A-Z])[a-z]+)/',$str,$matches);
					$func = '';
					$x = 0;
					foreach ( $func_bits as $func_bit ) {
						$func .= substr($func_bit, 0, 3);
					}
					// convert to uppercase
					$error_code .= $func != '' ? $func . '-' :  '';
				break;

				case 'line':
					// i can't figure this one out
					$error_code .= $code_bit;
				break;

			}
		}
		$error_code = ' ' . rtrim( strtoupper( $error_code ), '-' );
		return $error_code;
	}





	/**
	*	write exception details to log file
	* 
	*	@access public
	*	@ param timestamp $time
	*	@ param object $ex
	*	@ return void
	*/
	public function write_to_error_log ( $time = FALSE, $ex = FALSE, $clear = FALSE ) {
		
		if ( ! $ex ) {
			return;
		}
		
		if ( ! $time ) {
			$time = time();
		}
		
		$exception_log = '----------------------------------------------------------------------------------------' . PHP_EOL;
		$exception_log .= '[' . date( 'Y-m-d H:i:s', $time ) . ']  Exception Details' . PHP_EOL;
		$exception_log .= 'Message: ' . $ex['msg'] . PHP_EOL;
		$exception_log .= 'Code: '. $ex['code'] . PHP_EOL;
		$exception_log .= 'File: '. $ex['file'] . PHP_EOL;
		$exception_log .= 'Line No: ' . $ex['line'] . PHP_EOL;
		$exception_log .= 'Stack trace: ' . PHP_EOL;
		$exception_log .= $ex['string'] . PHP_EOL;
		$exception_log .= '----------------------------------------------------------------------------------------' . PHP_EOL;
		
		$exception_log_file = str_replace( '\\', '/', EVENT_ESPRESSO_UPLOAD_DIR . 'logs/' ) . self::$_exception_log_file;
		
		if ( is_writable( EVENT_ESPRESSO_UPLOAD_DIR.'logs' ) && ! file_exists( $exception_log_file )) {
			touch( $exception_log_file );
		}
		
		if ( is_writable( $exception_log_file )) {
			$fh = fopen( $exception_log_file, 'a' ) or die( 'Cannot open ' . $exception_log_file . ' file!' );
			fwrite( $fh, $exception_log );
			fclose( $fh );
		} else {
			echo '<div class="error"><p>'. sprintf( __( 'Your log file is not writable. Check if your server is able to write to %s.', 'event_espresso' ), $exception_log_file ) . '</p></div>';
		}
		
	}




}
// end of Class EE_Exceptions



/**
 * 	espresso_error_enqueue_scripts
 *
 *  @access 	public
 *  @return 	void
 */
function espresso_error_enqueue_scripts() {
	// js for error handling
	wp_register_script( 'ee_error_js', EE_PLUGIN_DIR_URL . 'scripts/EE_Error.js', array('espresso_core'), EVENT_ESPRESSO_VERSION, FALSE );
}
add_action( 'wp_enqueue_scripts', 'espresso_error_enqueue_scripts', 2 );



/* End of file EE_Exceptions.class.php */
/* Location: includes/classes/EE_Exceptions.class.php */	