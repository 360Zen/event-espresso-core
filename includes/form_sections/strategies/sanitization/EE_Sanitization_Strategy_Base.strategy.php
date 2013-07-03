<?php

/*
 * Performs initial sanitization and normalization on the field's input before the input 
 * gets saved onto the form field object. 
 * 
 */
abstract class EE_Sanitization_Strategy_Base extends EE_Form_Input_Strategy_Base{
	/**
	 * Finds the request data that corresponds to the field being sanitized, and 
	 * sanitizes it, and returns it. This sanitized HTML should be safe, but may not necessarily be valid.
	 * @param array $raw_req_data
	 * @return string
	 */
	public function sanitize($raw_req_data){
		$raw_req_data_for_this_field = isset($raw_req_data[$this->_input->html_name()]) ? $raw_req_data[$this->_input->html_name()] : null;
		return $this->_sanitize($raw_req_data_for_this_field);
	}
	/**
	 * implement this function to perform sanitization on the field.
	 * @param string $raw_req_data_for_this_field
	 * @return string of sanitized
	 */
	abstract function _sanitize($raw_req_data_for_this_field);
	
	/**
	 * uses the sanitized value on the field to get the normalized value, and return it.
	 * Eg, if the input is meant to hold integers, and the field's sanitized value is '123', this should return 123.
	 * If, however, the field's sanitized value is 'jabberwacky', this function should return 0, and also add a validation error
	 * @return mixed
	 */
	abstract function normalize();
}