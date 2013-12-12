<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
do_action('AHEE_log', __FILE__, ' FILE LOADED', '' );

abstract class EE_Onsite_Gateway extends EE_Gateway {

	// list of fields required for capturing the billing address 
	protected $_billing_info_address_fields = array();
	// list of fields required for capturing the credit card information
	protected $_billing_info_credit_card_fields = array();
	// list of fields required for capturing other information
	protected $_billing_info_other_fields = array();
	
	abstract public function espresso_reg_page_billing_inputs();

	protected function __construct(EEM_Gateways &$model) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		// this filter allows whatever function is processing the registration page to know what inputs to expect
		//add_filter('FHEE_reg_page_billing_inputs', array(&$this, 'espresso_reg_page_billing_inputs'));
		parent::__construct($model);
	}
	
	protected function _set_default_properties() {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		parent::_set_default_properties();
		// list of fields required for capturing the billing address 
		$this->_billing_info_address_fields = array(
				'reg-page-billing-fname-' . $this->_gateway_name,
				'reg-page-billing-lname-' . $this->_gateway_name,
				'reg-page-billing-email-' . $this->_gateway_name,
				'reg-page-billing-phone-' . $this->_gateway_name,
				'reg-page-billing-address-' . $this->_gateway_name,
				'reg-page-billing-city-' . $this->_gateway_name,
				'reg-page-billing-state-' . $this->_gateway_name,
				'reg-page-billing-country-' . $this->_gateway_name,
				'reg-page-billing-zip-' . $this->_gateway_name
		);

		// list of fields required for capturing the credit card information
		$this->_billing_info_credit_card_fields = array(
				'reg-page-billing-card-type-' . $this->_gateway_name,
				'reg-page-billing-card-nmbr-' . $this->_gateway_name,
				'reg-page-billing-card-exp-date-mnth-' . $this->_gateway_name,
				'reg-page-billing-card-exp-date-year-' . $this->_gateway_name,
				'reg-page-billing-card-ccv-code-' . $this->_gateway_name
		);
	}

	/**
	 * 		generate s HTML for the billing info form during registration
	 * 		@access 		protected
	 * 		@param		array	$billing_inputs - array of input field details
	 * 		@param		array	$section - what part of the billing info form, "address", "credit_card", or "other"
	 * 		@return 		string
	 */
	protected function _generate_billing_info_form_fields($billing_inputs = array(), $section = FALSE) {

		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		if (empty($billing_inputs) || !$section) {
			return;
		}
		global $css_class;
		// fill out section name
		$section = '_billing_info_' . $section . '_fields';
		// if you don't behave - this is what you're gonna get !!!
		$output = '';
		// cycle thru billing inputs
		foreach ($billing_inputs as $input_key => $billing_input) {
			// is the billing input in the requested section	?
			if (in_array($input_key, $this->$section)) {
				// required fields get a * 
				$required = $billing_input['required'] ? '&nbsp;<em>*</em>' : '';
				// and the css class "required"
				$styles = $billing_input['required'] ? 'required ' . $css_class : $css_class;

				// start with a p tag, unless this is the credit card year field
				if ( $input_key != 'reg-page-billing-card-exp-date-year-' . $this->_gateway_name ) {
					$output .= "\n\t\t" . '<p class="event_form_field">';
				}

				// what type of input are we dealing with ?
				switch ($billing_input['input']) {

					// text inputs
					case 'text' :

						$output .= "\n\t\t\t" . '<label for="' . $input_key . '">' . $billing_input['label'] . $required . '</label>';
						$output .= "\n\t\t\t" . '<input id="' . $input_key . '" class="' . $styles . '" type="text" value="' . $billing_input['value'] . '" name="' . $input_key . '">';
						break;

					// dropdowns
					case 'select' :

						if ( $input_key == 'reg-page-billing-card-exp-date-mnth-' . $this->_gateway_name ) {

							$output .= "\n\t\t\t" . '<label>' . __('Expiry Date', 'event_espresso') . '&nbsp;<em>*</em></label>';
							$output .= "\n\t\t\t" . '<select id="reg-page-billing-card-exp-date-mnth-' . $this->_gateway_name . '" class="' . $styles . ' small-txt" name="reg-page-billing-card-exp-date-mnth-' . $this->_gateway_name . '">';
							for ($x = 1; $x <= 12; $x++) {
								$value = $x < 10 ? '0' . $x : $x;
								$output .= "\n\t\t\t\t" . '<option value="' . $value . '">' . $value . '</option>';
							}
							$output .= "\n\t\t\t" . '</select>';
							$output .= "\n\t\t\t" . '&nbsp;/&nbsp;';
						} elseif ( $input_key == 'reg-page-billing-card-exp-date-year-' . $this->_gateway_name ) {

							$output .= "\n\t\t\t" . '<select id="reg-page-billing-card-exp-date-year-' . $this->_gateway_name . '" class="' . $styles . ' small-txt" name="reg-page-billing-card-exp-date-year-' . $this->_gateway_name . '">';
							$current_year = date('y');
							$next_decade = $current_year + 10;
							for ($x = $current_year; $x <= $next_decade; $x++) {
								$value = $x < 10 ? '0' . $x : $x;
								$output .= "\n\t\t\t\t" . '<option value="' . $value . '">' . $value . '</option>';
							}
							$output .= "\n\t\t\t" . '</select>';
							$output .= "\n\t\t\t" . '<span class="small-text lt-grey-text">' . __('(mm/yy)', 'event_espresso') . '</span>';
						} else {

							$output .= "\n\t\t\t" . '<label for="' . $input_key . '">' . $billing_input['label'] . $required . '</label>';
							$output .= "\n\t\t\t" . '<select id="' . $input_key . '" class="' . $styles . ' small-txt" name="' . $input_key . '">';

							if (is_array($billing_input['options'])) {
								$options = $billing_input['options'];
							} else {
								$options = explode(',', $billing_input['options']);
							}

							foreach ($options as $key => $value) {
								//$key = str_replace( ' ', '_', sanitize_key( $value ));
								$output .= "\n\t\t\t\t" . '<option value="' . $key . '">' . $value . '</option>';
							}
							$output .= "\n\t\t\t" . '</select>';
						}

						break;
				} // end switch
				// end with a p tag, unless this is the credit card month field
				if ( $input_key != 'reg-page-billing-card-exp-date-mnth-' . $this->_gateway_name ) {
					$output .= "\n\t\t" . '</p>';
				}
			} // end if ( in_array( $input_key, $this->$section ))
		} // end foreach( $billing_inputs as $input_key => $billing_input ) 

		return $output;
	}



	/**
	 * 		process_gateway_selection()
	 * 		@access public
	 * 		@return 	mixed	array on success or FALSE on fail
	 */
	public function process_gateway_selection() {
	
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		// set  billing inputs in the individual gateways plz
		$reg_page_billing_inputs = array();
		// allow others to edit post input array
		$reg_page_billing_inputs = $this->espresso_reg_page_billing_inputs();
		$reg_page_billing_inputs = apply_filters( 'FHEE_reg_page_billing_inputs', $reg_page_billing_inputs );
		// if EE_Validate_and_Sanitize is not instantiated
		if ( ! defined( 'EE_Validate_and_Sanitize' )) {
			require_once(EE_CLASSES . 'EE_Validate_and_Sanitize.class.php');
			$EE_VnS = EE_Validate_and_Sanitize::instance();
		}
//printr( $reg_page_billing_inputs, '$reg_page_billing_inputs  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

		// validate and sanitize	post data
		$reg_page_billing_inputs = $EE_VnS->validate_and_sanitize_post_inputs($reg_page_billing_inputs);
		if ($reg_page_billing_inputs) {
			// add billing info to the session
			if (EE_Registry::instance()->SSN->set_session_data( array( 'billing_info' => $reg_page_billing_inputs ))) {
				$msg = __( 'Billing information submitted successfully.', 'event_espresso' );
				EE_Error::add_success( $msg, __FILE__, __FUNCTION__, __LINE__ );
			} 
		}


	}



	/**
	 * 		set_billing_info_for_confirmation
	 * 		@access public
	 * 		@param array	$billing_info
	 * 		@return array
	 */
	public function set_billing_info_for_confirmation( $billing_info ) {
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');
		$confirm_inputs = array(
				'first name'=>'fname-' . $this->_gateway_name,
				'last name'=>'lname-' . $this->_gateway_name,
				'email address'=>'email-' . $this->_gateway_name,
				'address'=>'address-' . $this->_gateway_name,
				'city'=>'city-' . $this->_gateway_name,
				'state'=>'state-' . $this->_gateway_name,
				'country'=>'country-' . $this->_gateway_name,
				'zip'=>'zip-' . $this->_gateway_name,
				'ccv code'=>'ccv-code-' . $this->_gateway_name
				);
		$confirm_data = array();
		foreach ( $confirm_inputs as $confirm_name=>$billing_name ) {
			if ( ! empty( $billing_info['reg-page-billing-'.$billing_name]['value'] )) {
				$confirm_data[$confirm_name] = $billing_info['reg-page-billing-'.$billing_name]['value'];
			}
		}
		$confirm_data['credit card #'] = $this->_EEM_Gateways->FormatCreditCard( $billing_info[ 'reg-page-billing-card-nmbr-' . $this->_gateway_name ]['value'] );
		$confirm_data['expiry date'] = $billing_info[ 'reg-page-billing-card-exp-date-mnth-' . $this->_gateway_name ]['value'] . '&nbsp;/&nbsp;';
		$confirm_data['expiry date'] .= $billing_info[ 'reg-page-billing-card-exp-date-year-' . $this->_gateway_name ]['value'];
		return $confirm_data;
	}
	
	/**
	 * Saves the cleaned billing info to the trasnaction's primary registration's attendee.
	 * @param array $billing_info where keys are keys in teh espresso_reg_page_billing_inputs()'s array, values are their
	 * cleaned values.
	 * @param EE_Transaction $transaction
	 * @return boolean
	 */
	protected function _save_billing_info_to_attendee($billing_info,$transaction){
		if( ! $transaction || ! $transaction instanceof EE_Transaction){
			EE_Error::add_error(__("Cannot save billing info because no transaction was specified", "event_espresso"), __FILE__, __FUNCTION__, __LINE__);
			return false;
		}
		$primary_reg = $transaction->primary_registration();
		if( ! $primary_reg ){
			EE_Error::add_error(__("Cannot save billing info because the transaction has no primary registration", "event_espresso"), __FILE__, __FUNCTION__, __LINE__);
			return false;
		}
		$attendee_id = $primary_reg->attendee_ID();
		if( ! $attendee_id ){
			EE_Error::add_error(__("Cannot save billing info because the transaction's primary registration has no attendee!", "event_espresso"), __FILE__, __FUNCTION__, __LINE__);
			return false;
		}
		$billing_input_field_settings = $this->espresso_reg_page_billing_inputs();
		$billing_info_ready_for_saving = array();
		foreach($billing_input_field_settings as $field_name => $settings){
			if(in_array($settings['sanitize'],array('ccv','ccard'))){
				//dont save ccv or credit card data
				continue;
			}
			$cleaned_value = isset( $billing_info[$field_name]) && isset($billing_info[$field_name]['value']) ? $billing_info[$field_name]['value'] : null;
			$billing_info_ready_for_saving[$field_name] = $cleaned_value;
		}
		
		$success = update_post_meta($attendee_id, 'billing_info_'.$this->_gateway_name, $billing_info_ready_for_saving);
		return $success;
	}

}