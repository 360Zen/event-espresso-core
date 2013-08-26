<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * CSV Import Export class
 *
 * @package				Event Espresso
 * @subpackage		includes/functions
 * @author					Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
 class EE_CSV {

  // instance of the EE_CSV object
	private static $_instance = NULL;
 
	
	// multidimensional array to store update & error messages
//	var $_notices = array( 'updates' => array(), 'errors' => array() );


	private $_primary_keys;

	/**
	 *
	 * @var EE_Registry
	 */
	private $EE;
	/**
	 * string used for 1st cell in exports, which indicates that the following 2 rows will be metadata keys and values
	 */
	const metadata_header = 'Event Espresso Export Meta Data';
	/**
	 *		private constructor to prevent direct creation
	 *		@Constructor
	 *		@access private
	 *		@return void
	 */	
 	private function __construct() {
		
		$this->EE = EE_Registry::instance();
		global $wpdb;

		$this->_primary_keys = array(
				$wpdb->prefix . 'esp_answer' => array( 'ANS_ID' ),
				$wpdb->prefix . 'esp_attendee' => array( 'ATT_ID' ),
				$wpdb->prefix . 'esp_datetime'	=> array( 'DTT_ID' ),
				$wpdb->prefix . 'esp_event_question_group'	=> array( 'EQG_ID' ),
				$wpdb->prefix . 'esp_message_template'	=> array( 'MTP_ID' ),
				$wpdb->prefix . 'esp_payment'	=> array( 'PAY_ID' ),
				$wpdb->prefix . 'esp_price'	=> array( 'PRC_ID' ),
				$wpdb->prefix . 'esp_price_type'	=> array( 'PRT_ID' ),
				$wpdb->prefix . 'esp_question'	=> array( 'QST_ID' ),
				$wpdb->prefix . 'esp_question_group'	=> array( 'QSG_ID' ),
				$wpdb->prefix . 'esp_question_group_question'	=> array( 'QGQ_ID' ),
				$wpdb->prefix . 'esp_question_option'	=> array( 'QSO_ID' ),
				$wpdb->prefix . 'esp_registration'	=> array( 'REG_ID' ),
				$wpdb->prefix . 'esp_status'	=> array( 'STS_ID' ),
				$wpdb->prefix . 'esp_transaction'	=> array( 'TXN_ID' ),
				$wpdb->prefix . 'esp_transaction'	=> array( 'TXN_ID' ),
				$wpdb->prefix . 'events_detail'	=> array( 'id' ),
				$wpdb->prefix . 'events_category_detail'	=> array( 'id' ),
				$wpdb->prefix . 'events_category_rel'	=> array( 'id' ),
				$wpdb->prefix . 'events_venue'	=> array( 'id' ),
				$wpdb->prefix . 'events_venue_rel' =>  array( 'emeta_id' ),
				$wpdb->prefix . 'events_locale'	=> array( 'id' ),
				$wpdb->prefix . 'events_locale_rel'	=> array( 'id' ),
				$wpdb->prefix . 'events_personnel' =>  array( 'id' ),
				$wpdb->prefix . 'events_personnel_rel' =>  array( 'id' ),
			);

	}



	/**
	 *		@ singleton method used to instantiate class object
	 *		@ access public
	 *		@ return class instance
	 */	
	public static function instance ( ) {
		// check if class object is instantiated
		if ( self::$_instance === NULL  or ! is_object( self::$_instance ) or ! ( self::$_instance instanceof EE_CSV )) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	
	/**
	 * Generic CSV-functionality to turn an entire CSV file into a single array that's 
	 * NOT in a specific format to EE. It's just a 2-level array, with top-level arrays
	 * representing each row in the CSV file, and the second-level arrays being each column in that row
	 * @param string $path_to_file
	 * @return array of arrays. Top-level array has rows, second-level array has each item
	 */
	public function import_csv_to_multi_dimensional_array($path_to_file){
		// needed to deal with Mac line endings
		ini_set('auto_detect_line_endings',TRUE);

		// because fgetcsv does not correctly deal with backslashed quotes such as \"
		// we'll read the file into a string
		$file_contents = file_get_contents( $path_to_file );
		// replace backslashed quotes with CSV enclosures
		$file_contents = str_replace ( '\\"', '"""', $file_contents );
		// HEY YOU! PUT THAT FILE BACK!!!
		file_put_contents($path_to_file, $file_contents);
		
		if (($file_handle = fopen($path_to_file, "r")) !== FALSE) {
			# Set the parent multidimensional array key to 0.
			$nn = 0;
			$csvarray = array();
			
			// in PHP 5.3 fgetcsv accepts a 5th parameter, but the pre 5.3 versions of fgetcsv choke if passed more than 4 - is that crazy or what?
			if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {

				//  PHP 5.2- version

				// loop through each row of the file
				while(($data = fgetcsv($file_handle, 0, ',', '"' )) !== FALSE){
					$csvarray[]= $data;
				}
			}else{
				// loop through each row of the file
				while (( $data = fgetcsv( $file_handle, 0, ',', '"', '\\' )) !== FALSE ) {
					$csvarray[]=$data;
				}
			}
			# Close the File.
			fclose($file_handle);
			return $csvarray;
		}else{
			EE_Error::add_error( sprintf(__("An error occured - the file: %s could not opened.", "event_espresso"),$path_to_file));
			return false;
		}
	}

	
	/**
	 *			@Import contents of csv file and store values in an array to be manipulated by other functions
	 *		  @access public
	 *			@param string $path_to_file - the csv file to be imported including the path to it's location.
	 *			If $model_name is provided, assumes that each row in the CSV represents a model object for that model
	 *			If $model_name ISN'T provided, assumes that before model object data, there is a row where the first entry is simply
	 *			'MODEL', and next entry is the model's name, (untranslated) like Event, and then maybe a row of headers, and then the model data.
	 *			Eg. '<br>MODEL,Event,<br>EVT_ID,EVT_name,...<br>1,Monkey Party,...<br>2,Llamarama,...<br>MODEL,Venue,<br>VNU_ID,VNU_name<br>1,The Forest
	 *			@param string $model_name model name if we know what model we're importing
	 *			@param boolean $first_row_is_headers - whether the first row of data is headers or not - TRUE = headers, FALSE = data
	 *			@return mixed - array on success - multi dimensional with headers as keys (if headers exist) OR string on fail - error message
	 * like the following array('Event'=>array(
	 *								array('EVT_ID'=>1,'EVT_name'=>'bob party',...),
	 *								array('EVT_ID'=>2,'EVT_name'=>'llamarama',...),
	 *								...
	 *							)
	 *							'Venue'=>array(
	 *								array('VNU_ID'=>1,'VNU_name'=>'the shack',...),
	 *								array('VNU_ID'=>2,'VNU_name'=>'tree house',...),
	 *								...
	 *							)
	 *						...
	 *						)
	 */	
	public function import_csv_to_model_data_array( $path_to_file, $model_name = FALSE, $first_row_is_headers = TRUE ) {
		$multi_dimensional_array = $this->import_csv_to_multi_dimensional_array($path_to_file);
		if( ! $multi_dimensional_array ){
			return false;
		}
		// gotta start somewhere
		$row = 1;
		// array to store csv data in
		$ee_formatted_data = array();
		// array to store headers (column names)
		$headers = array();
		foreach($multi_dimensional_array as $data){
			// if first cell is MODEL, then second cell is the MODEL name
			if ( $data[0]	== 'MODEL' ) {
				$model_name = $data[1];
				//don't bother looking for model data in this row. The rest of this
				//row should be blank
				//AND pretend this is the first row again
				$row = 1;
				continue;
			}
			if($data[0] == EE_CSV::metadata_header){
				$model_name = EE_CSV::metadata_header;
				//store like model data, we just won't try importing it etc.
				$row = 1;
				continue;
			}
			

			// how many columns are there?
			$columns = count($data);

			$model_entry = array();
			// loop through each column
			for ( $i=0; $i < $columns; $i++ ) {
				
				//replace csv_enclosures with backslashed quotes 
				$data[$i] = str_replace ( '"""', '\\"', $data[$i] );
				// do we need to grab the column names?
				if ( $row === 1){
					if( $first_row_is_headers ) {
						// store the column names to use for keys
						$column_name = $data[$i];
						//check it's not blank... sometimes CSV editign programs adda bunch of empty columns onto the end...
						if(!$column_name){continue;}
						$matches = array();
						if($model_name == EE_CSV::metadata_header){
							$headers[$i] = $column_name;
						}else{
							//now get the db table name from it (the part between square brackets)
							$success = preg_match('~(.*)\[(.*)\]~', $column_name,$matches);
							if (!$success){
								EE_Error::add_error( sprintf(__("The column titled %s is invalid for importing. It must be be in the format of 'Nice Name[model_field_name]' in row %s", "event_espresso"),$column_name,implode(",",$data)));
								return false;
							}
							$headers[$i] = $matches[2];
						}
						
					}else{
						// no column names means our final array will just use counters for keys
						$model_entry[$headers[$i]] = $data[$i];
						$headers[$i] = $i;
					}
					// and we need to store csv data
				} else {
					// this column isn' ta header, store it if there is a header for it
					if(isset($headers[$i])){
						$model_entry[$headers[$i]] = $data[$i];
					}
				}
				
			}
			//save the row's data IF it's a non-header-row
			if( ! $first_row_is_headers || ($first_row_is_headers && $row > 1)){
				$ee_formatted_data[$model_name][] = $model_entry;
			}
			// advance to next row
			$row++;

		}

		// delete the uploaded file
		unlink($path_to_file);
//echo '<pre style="height:auto;border:2px solid lightblue;">' . print_r( $ee_formatted_data, TRUE ) . '</pre><br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>';
//die();

		// it's good to give back
		return $ee_formatted_data;
		
	}
	
	
	/**
	 *			Given an array of data (usually from a CSV import) attempts to save that data to teh db.
	 *			If $model_name ISN'T provided, assumes that this is a 3d array, with toplevel keys being model names,
	 *			next level being numeric indexes adn each value representing a model object, and teh last layer down
	 *			being keys of model fields and their proposed values.
	 *			If $model_name IS provided, assumes a 2d array of the bottom two layers previously mentioned.
	 *			Regarding primary and foreing keys: tries to intelligently decide whether to insert a new
	 *			model objects or not. If the model object's primary key is provided, checks if it's already in teh DB 
	 *			and if it is, then just updates that existing model object with the $csv_data_array's values. HOWEVER,
	 *			there is an exception: if a model object has previously been inserted from $csv_data_array that
	 *			a model object BELONGS TO, then we will also insert that model object, ignoring its primary key in $csv_data_array.
	 *			Eg, if we're decidign whether or not to insert a Datetime with DTT_ID=23, and EVT_ID=3, we check if we JUST
	 *			inserted an event with EVT_ID=3. If so, then this Datetime with DTT_ID=23 should also be new... after all, it depends
	 *			on Event with ID 3 which we just inserted. 
	 *		  @access public
	 *			@param array $csv_data_array - the array containing the csv data produced from EE_CSV::import_csv_to_model_data_array()
	 *			@param array $fields_to_save - an array containing the csv column names as keys with the corresponding db table fields they will be saved to
	 *			@return TRUE on success, FALSE on fail
	 */	
	public function save_csv_to_db( $csv_data_array, $model_name = FALSE ) {
		$total_inserts = 0;
		$total_updates = 0;
		$total_insert_errors = 0;
		$total_update_errors = 0;

		$success = FALSE;
		$error = FALSE;
		// first level of array is not table information but a table name was passed to the function
		// array is only two levels deep, so let's fix that by adding a level, else the next steps will fail
		if($model_name){
			$csv_data_array = array($csv_data_array);
		}
		// begin looking through the $csv_data_array, expecting the toplevel key to be the model's name...
		foreach ( $csv_data_array as $model_name_in_csv_data => $model_data_from_import ) {		
			//now check that assumption was correct. If
			if ( $this->EE->is_model_name($model_name_in_csv_data)) {
				$model_name = $model_name_in_csv_data;
			}elseif($model_name_in_csv_data == EE_CSV::metadata_header){
				//ok so its metadata, dont try to save it to ehte db obviously...
				continue;
			}else {
				// no table info in the array and no table name passed to the function?? FAIL
				EE_Error::add_error( __('No table information was specified and/or found, therefore the import could not be completed','event_espresso'));
				return FALSE;
			}
			$model = $this->EE->load_model($model_name);
		
			/**
			 * @var $key_in_csv_to_id_in_db_map 2d array: toplevel keys being model names, bottom-level keys being the original key, and 
			 * the value will be the newly-inserted ID
			 */
			$key_in_csv_to_id_in_db_map = array();
			//so without further ado, scanning all the data provided for primary keys and their inital values
			foreach ( $model_data_from_import as $model_object_data ) {		
				//before we do ANYTHING, make sure the csv row wasn't just completely blank
				$row_is_completely_empty = true;
				foreach($model_object_data as $field){
					if($field){
						$row_is_completely_empty = false;
					}
				}
				if($row_is_completely_empty){
					continue;
				}
				$id_in_csv = $model_object_data[$model->primary_key_name()];
				
				//now we need to decide if we're going to add a new model object given the $model_object_data,
				//or just update.
				//to decide that, first we want to know if this import has added
				//a related model object this one depends on (eg, if we're currently
				//deciding whether or not to add a Datetime, we ask did we JUST add
				//its event? Because if we just added a new event, and datetimes DEPEND 
				//on events for their existence, then we know we want to add a new datetime.
				//...because even if there IS a datetime with the same ID, it COULDN'T have
				//been for this same event, (because the event was JUST added), and therefore
				//it's only a COINCIDENCE that a datetime with teh same ID exists
				$models_it_belongs_to = $model->belongs_to_relations();
				$do_insert = false;
				foreach($models_it_belongs_to as $model_name_it_belongs_to => $relation_obj){
					$fk_field = $model->get_foreign_key_to($model_name_it_belongs_to);
					$fk_value = $model_object_data[$fk_field->get_name()];
					//now, is that value in the list of PKs that have been inserted?
					if(isset($key_in_csv_to_id_in_db_map[$model_name_it_belongs_to][$fk_value])){
						//INSERT because we inserted the thing it BELONGS to
						$do_insert = true;
					}else{
						$existing_model_object = $model->get_one_by_ID($id_in_csv);
						if($existing_model_object){
							//it already exists, so we'd rather just update it
							$do_insert = false;
						}else{
							//it doesn't already exist, so we OBVIOUSLY can't update it
							//so insert it
							$do_insert = true;
						}
					}
				}
				
				if($do_insert){
					unset($model_object_data[$model->primary_key_name()]);
					//the model takes care of validating the CSV's input
					try{
						$new_id = $model->insert($model_object_data);
						if($new_id){
							$key_in_csv_to_id_in_db_map[$model_name][$id_in_csv] = $new_id;
							$total_inserts++;
							EE_Error::add_success( sprintf(__("Successfully added new %s with csv data %s", "event_espresso"),$model_name,implode(",",$model_object_data)));
						}else{
							$total_insert_errors++;
							$model_object_data[$model->primary_key_name()] = $id_in_csv;
							EE_Error::add_error( sprintf(__("Could not insert new %s with the csv data: %s", "event_espresso"),$model_name,implode(",",$model_object_data)));
						}
					}catch(EE_Error $e){
						$total_insert_errors++;
						$model_object_data[$model->primary_key_name()] = $id_in_csv;
						EE_Error::add_error( sprintf(__("Could not insert new %s with the csv data: %s because %s", "event_espresso"),$model_name,implode(",",$model_object_data),$e->getMessage()));
					}
				}else{
					try{
//						$model->show_next_x_db_queries(1);
//						echo '<br><br>';
						$success = $model->update($model_object_data,array(array($model->primary_key_name() => $id_in_csv)));
						if($success){
							$total_updates++;
							EE_Error::add_success( sprintf(__("Successfully updated %s with csv data %s", "event_espresso"),$model_name,implode(",",$model_object_data)));
						}else{
							$matched_items = $model->get_all(array(array($model->primary_key_name() => $id_in_csv )));
							if( ! $matched_items){
								//no items were matched (so we shouldn't have updated)... but then we should have inserted? what the heck?
								$total_update_errors++;
								EE_Error::add_error( sprintf(__("Could not update %s with the csv data: '%s' for an unknown reason", "event_espresso"),$model_name,implode(",",$model_object_data)));
							}else{
								$total_updates++;
								EE_Error::add_success( sprintf(__("%s with csv data '%s' was found in the database and didn't need updating because all the data is identical.", "event_espresso"),$model_name,implode(",",$model_object_data)));
							}
						}
					}catch(EE_Error $e){
						$total_update_errors++;
						EE_Error::add_error( sprintf(__("Could not update %s with the csv data: %s because %s", "event_espresso"),$model_name,implode(",",$model_object_data),$e->getMessage()));
					}
				}
			}
		}

		if ( $total_updates > 0 ) {
			EE_Error::add_success( sprintf(__("%s existing records in the database were updated.", "event_espresso"),$total_updates));
			$success = true;
		}
		if ( $total_inserts > 0 ) {
			EE_Error::add_success(sprintf(__("$s new records were added to the database.", "event_espresso"),$total_inserts));
			$success = true;
		}

		if ( $total_update_errors > 0 ) {
			EE_Error::add_error(sprintf(__("'One or more errors occured, and a total of %s existing records in the database were <strong>not</strong> updated.'", "event_espresso"),$total_update_errors));
			$error = true;
		}
		if ( $total_insert_errors > 0 ) {
			EE_Error::add_error(sprintf(__("One or more errors occured, and a total of %s new records were <strong>not</strong> added to the database.'", "event_espresso"),$total_insert_errors));
			$error = true;
		}

		// if there was at least one success and absolutely no errors
		if ( $success && ! $error ) {
			return TRUE;
		} else {	
			return FALSE;
		}
			
	}
	
	/**
	 * Sends HTTP headers to indicate that the browser should download a file,
	 * and starts writing the file to PHP's output. Returns teh file handle so other functions can 
	 * also write to it
	 * @param string $new_filename the name of the file that the user will download
	 * @return resource, like the results of fopen(), which can be used for fwrite, fputcsv2, etc.
	 */
	public function begin_sending_csv($filename){
		// grab file extension
		$ext = substr(strrchr($filename, '.'), 1);
		if ( $ext == '.csv' or  $ext == '.xls' ) {
			str_replace( $ext, '', $filename );
		}
		$filename .= '.csv';
		
		//if somebody's been naughty and already started outputting stuff, trash it
		//and start writing our stuff.
		if (ob_get_length()) {
			@ob_flush();
			@flush();
			@ob_end_flush();
		}
		@ob_start();
		header("Pragma: public");
		header("Expires: 0");
		header("Pragma: no-cache");
		header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-disposition: attachment; filename='.$filename);
		header("Content-Type: text/csv");
		$fh = fopen('php://output', 'w');		
		return $fh;
	}
	
	/**
	 * Writes some meta data to the CSV as a bunch of columns. Initially we're only
	 * mentioning the version and timezone
	 * @param resource $filehandle
	 */
	public function write_metadata_to_csv($filehandle){
		$this->EE->load_helper('DTT_helper');
		$data_row = array(EE_CSV::metadata_header);//do NOT translate because this exact string is used when importing
		$this->fputcsv2($filehandle, $data_row);
		$meta_data = array( 0=> array(
			'version'=>espresso_version(),
			'timezone'=>  EEH_DTT_Helper::get_timezone()));
		$this->write_data_array_to_csv($filehandle, $meta_data);
	}
	
	
	
	/**
	 * Writes $data to the csv file open in $filehandle. uses the array indices of $data for column headers
	 * @param array $data 2D array, first numerically-indexed, and next-level-down preferably indexed by string 
	 * @param boolean $add_csv_column_names whether or not we should add the keys in the bottom-most array as a row for headers in teh CSV.
	 * Eg, if $data looked like array(0=>array('EVT_ID'=>1,'EVT_name'=>'monkey'...), 1=>array(...),...)) 
	 * then the first row we'd write to the CSV would be "EVT_ID,EVT_name,..."
	 * @return boolean if we successfully wrote to the CSV or not. If there's no $data, we consider that a success (because we wrote everything there was...nothing)
	 */
	public function write_data_array_to_csv($filehandle, $data){
		$this->EE->load_helper('Array');

		
		//determine if $data is actually a 2d array
		if ( $data && is_array($data) && is_array(EEH_Array::get_one_item_from_array($data))){
			//make sure top level is numerically indexed,
			
			if( EEH_Array::is_associative_array($data)){
				throw new EE_Error(sprintf(__("top-level array must be numerically indexed. Does these look like numbers to you? %s","event_espresso"),implode(",",array_keys($data))));
			}
			$item_in_top_level_array = EEH_Array::get_one_item_from_array($data);
			//now, is the last item in the top-level array of $data an associative or numeric array?
			if(EEH_Array::is_associative_array($item_in_top_level_array)){
				//its associative, so we want to output its keys as column headers
				$keys = array_keys($item_in_top_level_array);
				echo $this->fputcsv2($filehandle, $keys);
			}
			//start writing data
			foreach($data as $data_row){
				echo $this->fputcsv2($filehandle, $data_row);
			}
			return true;
		}else{
			//no data TO write... so we can assume that's a success
			return true;
		}
//		//if 2nd level is indexed by strings, use those as csv column headers (ie, the first row)
//		
//		
//		$no_table = TRUE;
//	
//		// loop through data and add each row to the file/stream as csv
//		foreach ( $data as $model_name => $model_data ) {
//			// test first row to see if it is data or a model name
//			$model = 	EE_System::instance()->get_registry()->load_model($model_name);
//			//if the model really exists, 
//			if ( $model ) {
//			
//				// we have a table name
//				$no_table = FALSE;
//	
//				// put the tablename into an array cuz that's how fputcsv rolls
//				$model_name_row = array( 'MODEL', $model_name );
//
//				// add table name to csv output
//				echo self::fputcsv2($filehandle, $model_name_row);
//	
//				// now get the rest of the data
//				foreach ( $model_data as $row ) {
//					// output the row
//					echo self::fputcsv2($filehandle, $row);
//				}
//				
//			}
//				
//			if ( $no_table ) {
//				// no table so just put the data
//				echo self::fputcsv2($filehandle, $model_data);
//			}
		
//		} 		//		END OF foreach ( $data )
	}
	/**
	 * Should be called after begin_sending_csv(), and one or more write_data_array_to_csv()s.
	 * Calls exit to prevent polluting the CSV file with other junk
	 * @param resource $fh filehandle where we're writing the CSV to 
	 */
	public function end_sending_csv($fh){
		fclose($fh);
		exit(0);
	}
	/**
	 * Given an open file, writes all teh model data to it in the format the importer expects.
	 * Usually preceded by begin_sending_csv($filename), and followed by end_sending_csv($filehandle).
	 * @param resource $filehandle
	 * @param array $model_data_array is assumed to be a 3d array: 1st layer has keys of model names (eg 'Event'),
	 * next layer is numerically indexed to represent each model object (eg, each individual event), and the last layer
	 * has all the attributes o fthat model object (eg, the event's id, name, etc)
	 * @return boolean success
	 */
	public function write_model_data_to_csv($filehandle,$model_data_array){
		$this->write_metadata_to_csv($filehandle);
		foreach($model_data_array as $model_name => $model_instance_arrays){
			//first: output a special row stating the model
			echo $this->fputcsv2($filehandle,array('MODEL',$model_name));
			//if we have items to put in the CSV, do it normally
			
			if( ! empty($model_instance_arrays) ){
				$this->write_data_array_to_csv($filehandle, $model_instance_arrays);
			}else{
//				echo "no data to write... so jsut write the headers";
				//so there's actually NO model objects for taht model.
				//probably still want to show the columns
				$model = $this->EE->load_model($model_name);
				$column_names = array();
				foreach($model->field_settings() as $field){
					$column_names[$field->get_nicename()."[".$field->get_name()."]"] = null ;
				}
				$this->write_data_array_to_csv($filehandle, array($column_names));
			}
		}
	}
	
	/**
	 * Writes the CSV file to the output buffer, with rows corresponding to $model_data_array,
	 * and dies (in order to avoid other plugins from messing up the csv output)
	 * @param string $filename the filename you want to give the file
	 * @param array $model_data_array 3d array, as described in EE_CSV::write_model_data_to_csv()
	 * @return void writes CSV file to output and dies
	 */
	public function export_multiple_model_data_to_csv($filename,$model_data_array){
		$filehandle = $this->begin_sending_csv($filename);
		$this->write_model_data_to_csv($filehandle, $model_data_array);
		$this->end_sending_csv($filehandle);
	}
	/**
	 *			@Export contents of an array to csv file
	 *		  @access public
	 *			@param array $data - the array of data to be converted to csv and exported 
	 *			@param string $filename - name for newly created csv file
	 *			@return TRUE on success, FALSE on fail
	 */	
	public function export_array_to_csv( $data = FALSE, $filename = FALSE  ) {
	
		// no data file?? get outta here
		if ( ! $data or ! is_array( $data ) or empty( $data ) ) {
			return FALSE;
		}
		
		// no filename?? get outta here
		if ( ! $filename ) {
			return FALSE;
		}
		
		
		
		// somebody told me i might need this ???
		global $wpdb;
		$prefix = $wpdb->prefix;
			
	
		$fh = $this->begin_sending_csv($filename);
		
		
		$this->end_sending_csv($fh);
		
	
	}
	
	
	/**
	 *			@Determine the maximum upload file size based on php.ini settings
	 *		  @access public
	 *			@param int $percent_of_max - desired percentage of the max upload_mb
	 *			@return int
	 */	
	public function get_max_upload_size ( $percent_of_max = FALSE ) {
	
		$max_upload = (int)(ini_get('upload_max_filesize'));
		$max_post = (int)(ini_get('post_max_size'));
		$memory_limit = (int)(ini_get('memory_limit'));
		
		// determine the smallest of the three values from above
		$upload_mb = min($max_upload, $max_post, $memory_limit);
		
		//convert MB to KB
		$upload_mb = $upload_mb * 1024;
		
		// don't want the full monty? then reduce the max uplaod size
		if ( $percent_of_max ) {
			// is percent_of_max like this -> 50 or like this -> 0.50 ?
			if ( $percent_of_max > 1 ) {
				// chnages 50 to 0.50
				$percent_of_max = $percent_of_max / 100;
			}
			// make upload_mb a percentage of the max upload_mb
			$upload_mb = $upload_mb * $percent_of_max;
		}
		
		return $upload_mb;
	}


	/**
	 *			@Drop in replacement for PHP's fputcsv function - but this one works!!!
	 *		  @access private
	 *			@param resource $fh - file handle - what we are writing to
	 *			@param array $row - individual row of csv data
	 *			@param string $delimiter - csv delimiter
	 *			@param string $enclosure - csv enclosure
	 *			@param string $mysql_null - allows php NULL to be overridden with MySQl's insertable NULL value
	 *			@return void
	 */	
	private function fputcsv2 ($fh, array $row, $delimiter = ',', $enclosure = '"', $mysql_null = FALSE) {
	
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');
		
		$output = array();
		foreach ($row as $field) {
			if ($field === null && $mysql_null) {
				$output[] = 'NULL';
				continue;
			}
			
			$output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ?
				( $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure ) : $field;
		}
		
		fwrite($fh, join($delimiter, $output) . PHP_EOL);
	} 
	




//	/**
//	 *			@CSV Import / Export messages 
//	 *		  @access public
//	 *			@return void
//	 */	
//	public function csv_admin_notices () {
//			
//		// We play both kinds of music here! Country AND Western! - err... I mean, cycle through both types of notices
//		foreach( array('updates', 'errors') as $type ) {
//		
//			// if particular notice type is not empty, then "You've got Mail"
//			if( ! empty( $this->_notices[$type] )) {
//			
//				// is it an update or an error ?
//				$msg_class = $type == 'updates' ? 'updated' : 'error';
//				echo '<div id="message" class="'. $msg_class .'">';
//				// display each notice, however many that may be
//				foreach($this->_notices[$type] as $message) {
//					echo '<p>'. $message .'</p>';
//				}
//				// wrap it up
//				echo '</div>';
//			}
//		}
//	}
	
	

}
/* End of file EE_CSV.class.php */
/* Location: //includes/classes/EE_CSV.class.php */
?>