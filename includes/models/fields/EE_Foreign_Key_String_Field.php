<?php
require_once('fields/EE_Foreign_Key_Field_Base.php');
class EE_Foreign_Key_String_Field extends EE_Foreign_Key_Field_Base{
	function get_wpdb_data_type(){
		return '%s';
	}
	/**
	 * removes all tags when setting
	 * @param string $value_inputted_for_field_on_model_object
	 * @return string
	 */
	function prepare_for_set($value_inputted_for_field_on_model_object) {
		return strtoupper(htmlentities(wp_strip_all_tags($value_inputted_for_field_on_model_object)));
	}
}