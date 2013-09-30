<?php
if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

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
 * Payment Model
 *
 * @package			Event Espresso
 * @subpackage		gateways/
 * @author				Sidney Harrell
 *
 * ------------------------------------------------------------------------
 */
Class EE_Eway extends EE_Offsite_Gateway {

	private static $_instance = NULL;

	public static function instance(EEM_Gateways &$model) {
		// check if class object is instantiated
		if (self::$_instance === NULL or !is_object(self::$_instance) or ! ( self::$_instance instanceof  EE_Eway )) {
			self::$_instance = new self($model);
			//echo '<h3>'. __CLASS__ .'->'.__FUNCTION__.'  ( line no: ' . __LINE__ . ' )</h3>';
		}
		return self::$_instance;
	}

	protected function __construct(EEM_Gateways &$model) {
		$this->_gateway_name = 'Eway';
		$this->_button_base = 'eway-logo.png';
		$this->_path = str_replace('\\', '/', __FILE__);
		$this->_btn_img = file_exists( dirname( $this->_path ) . '/lib/' . $this->_button_base ) ? EVENT_ESPRESSO_PLUGINFULLURL . 'gateways/' . $this->_gateway_name . '/lib/' . $this->_button_base : '';
		parent::__construct($model);
	}

	protected function _default_settings() {
		$this->_payment_settings['eway_id'] = '';
		$this->_payment_settings['eway_username'] = '';
		$this->_payment_settings['image_url'] = '';
		$this->_payment_settings['currency_format'] = 'GBP';
		$this->_payment_settings['region'] = 'UK';
		$this->_payment_settings['use_sandbox'] = false;
		$this->_payment_settings['type'] = 'off-site';
		$this->_payment_settings['display_name'] = 'Eway';
		$this->_payment_settings['current_path'] = '';
		$this->_payment_settings['button_url'] = $this->_btn_img;
	}

	protected function _update_settings() {
		$this->_payment_settings['eway_id'] = $_POST['eway_id'];
		$this->_payment_settings['eway_username'] = $_POST['eway_username'];
		$this->_payment_settings['currency_format'] = $_POST['currency_format'];
		$this->_payment_settings['region'] = $_POST['region'];
		$this->_payment_settings['use_sandbox'] = empty($_POST['use_sandbox']) ? '' : $_POST['use_sandbox'];
		$this->_payment_settings['button_url'] = isset( $_POST['button_url'] ) ? esc_url_raw( $_POST['button_url'] ) : '';		$this->_payment_settings['image_url'] = $_POST['image_url'];
	}

	protected function _display_settings() {
		?>
		<tr>
			<th><label for="eway_id">
					<?php _e('eway ID', 'event_espresso'); ?>
				</label></th>
			<td><input class="regular-text" type="text" name="eway_id" size="35" value="<?php echo $this->_payment_settings['eway_id']; ?>">
				<br />
				<?php _e('(Typically 87654321)', 'event_espresso'); ?></td>
		</tr>
		
		<tr>
			<th><label for="eway_username">
					<?php _e('eway username', 'event_espresso'); ?>
				</label></th>
			<td><input class="regular-text" type="text" name="eway_username" size="35" value="<?php echo $this->_payment_settings['eway_username']; ?>">
				<br />
				<?php _e('(Typically TestAccount)', 'event_espresso'); ?></td>
		</tr>
		
		<tr>
			<th><label for="button_url">
					<?php _e('Button Image URL: ', 'event_espresso'); ?> <?php do_action('AHEE_help', 'eway_button_image'); ?>
				</label></th>
			<td>
				<?php $this->_payment_settings['button_url'] = empty( $this->_payment_settings['button_url'] ) ? $this->_btn_img : $this->_payment_settings['button_url']; ?>
				<input class="regular-text" type="text" name="button_url" size="34" value="<?php echo $this->_payment_settings['button_url']; ?>" />
				<a href="media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true&amp;width=640&amp;height=580&amp;rel=button_url" id="add_image" class="thickbox" title="Add an Image"><img src="images/media-button-image.gif" alt="Add an Image"></a>
			</td>
		</tr>
		
		<tr>
			<th><label for="image_url">
					<?php _e('Image URL (logo for payment page):', 'event_espresso'); ?> <?php do_action('AHEE_help', 'eway_image_url_info'); ?>
				</label></th>
			<td><input class="regular-text" type="text" name="image_url" size="35" value="<?php echo $this->_payment_settings['image_url']; ?>" />
				<a href="media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true&amp;width=640&amp;height=580&amp;rel=image_url" id="add_image" class="thickbox" title="Add an Image"><img src="images/media-button-image.gif" alt="Add an Image"></a> <br />
				<?php
				_e('(used for your business/personal logo on the eway page)', 'event_espresso');
				if ($this->_payment_settings['image_url'] != '')
					echo '<br /><img src="' . $this->_payment_settings['image_url'] . '" />';
				?>
			</td>
		</tr>
		
		<tr>
			<th><label for="currency_format">
					<?php _e('Select the currency for your country: ', 'event_espresso'); ?> <?php do_action('AHEE_help', 'eway_currency_info') ?>
				</label></th>
			<td><select name="currency_format" data-placeholder="Choose a currency..." class="chzn-select wide">
					<option value="<?php echo $this->_payment_settings['currency_format']; ?>"><?php echo $this->_payment_settings['currency_format']; ?>
					</option>
					<option value="AUD">
						<?php _e('Australian Dollars (A $)', 'event_espresso'); ?>
					</option>
					<option value="GBP">
						<?php _e('Pounds Sterling (&pound;)', 'event_espresso'); ?>
					</option>
					<option value="NZD">
						<?php _e('New Zealand Dollar ($)', 'event_espresso'); ?>
					</option>
				</select></td>
		</tr>
		
		<tr>
			<th><label for="region">
					<?php _e('Select the region where you want to use eWay:', 'event_espresso'); ?>
				</label></th>
			<td><select name="region" class="chzn-select" data-placeholder="Choose a region..." style="width:200px">
					<option value="<?php echo $this->_payment_settings['region']; ?>"><?php echo $this->_payment_settings['region']; ?></option>
					<option value="UK">
						<?php _e('United Kingdom', 'event_espresso'); ?>
					</option>
					<option value="AU">
						<?php _e('Australia', 'event_espresso'); ?>
					</option>
					<option value="NZ">
						<?php _e('New Zealand', 'event_espresso'); ?>
					</option>
				</select></td>
		</tr>
		
		<tr>
			<th><label for="use_sandbox">
					<?php _e('Use the Debugging Feature and the eway Sandbox?', 'event_espresso'); ?> <?php do_action('AHEE_help', 'eway_sandbox_info'); ?>
			</label></th>
		<td><?php
			echo EEH_Form_Fields::select_input('use_sandbox', $this->_yes_no_options, $this->_payment_settings['use_sandbox']);
					?></td>
		</tr>

		<tr>
			<th>
				<label for="<?php echo $this->_gateway_name; ?>_button_url">
					<?php _e('Button Image URL', 'event_espresso'); ?>
				</label>
			</th>
			<td>
				<?php $this->_payment_settings['button_url'] = empty( $this->_payment_settings['button_url'] ) ? $this->_btn_img : $this->_payment_settings['button_url']; ?>
				<input class="regular-text" type="text" name="button_url" id="<?php echo $this->_gateway_name; ?>_button_url" size="34" value="<?php echo $this->_payment_settings['button_url']; ?>" />
				<a href="media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true&amp;width=640&amp;height=580&amp;rel=button_url" id="add_image" class="thickbox" title="Add an Image"><img src="images/media-button-image.gif" alt="Add an Image"></a>
			</td>
		</tr>
		<?php
	}

	protected function _display_settings_help() {
		?>
		<div id="eway_sandbox_info" style="display:none">
			<h2><?php _e('eway Sandbox', 'event_espresso'); ?></h2>
			<p><?php _e('In addition to using the eway Sandbox fetaure. The debugging feature will also output the form varibales to the payment page, send an email to the admin that contains the all eway variables.', 'event_espresso'); ?></p>
			<hr />
			<p><?php _e('The eway Sandbox is a testing environment that is a duplicate of the live eway site, except that no real money changes hands. The Sandbox allows you to test your entire integration before submitting transactions to the live eway environment. Create and manage test accounts, and view emails and API credentials for those test accounts.', 'event_espresso'); ?></p>
		</div>
		<div id="eway_image_url_info" style="display:none">
			<h2>
				<?php _e('eway Image URL (logo for payment page)', 'event_espresso'); ?>
			</h2>
			<p>
				<?php _e('The URL of the 150x50-pixel image displayed as your logo in the upper left corner of the eway checkout pages.', 'event_espresso'); ?>
			</p>
			<p>
				<?php _e('Default - Your business name, if you have a Business account, or your email address, if you have Premier or Personal account.', 'event_espresso'); ?>
			</p>
		</div>
		<div id="eway_currency_info" style="display:none">
			<h2><?php _e('eway Currency', 'event_espresso'); ?></h2>
			<p><?php _e('eway uses 3-character ISO-4217 codes for specifying currencies in fields and variables. </p><p>The default currency code is British Pounds (GBP). If you want to require or accept payments in other currencies, select the currency you wish to use. The dropdown lists all currencies that eway (currently) supports.', 'event_espresso'); ?> </p>
		</div>
		<?php
	}

	public function process_reg_step_3() {

		$session_data = EE_Registry::instance()->SSN->get_session_data();

		$total = $session_data['_cart_grand_total_amount'];
		if (isset($session_data['tax_totals'])) {
			foreach ($session_data['tax_totals'] as $taxes) {
				$total = $total + $taxes;
			}
		}
		switch ($this->_payment_settings['region']) {
			case 'NZ':
				$this->_gatewayUrl = 'https://nz.ewaygateway.com/Request/';
				break;
			case 'AU':
				$this->_gatewayUrl = 'https://au.ewaygateway.com/Request/';
				break;
			case 'UK':
				$this->_gatewayUrl = 'https://payment.ewaygateway.com/Request/';
				break;
		}
		if ($this->_payment_settings['use_sandbox']) {
			// Enable test mode if needed
			$this->addField('CustomerID', '87654321');
			$this->addField('UserName', 'TestAccount');
			$total = "10.00";
		} else {
			$this->addField('CustomerID', $this->_payment_settings['eway_id']);
			$this->addField('UserName', $this->_payment_settings['eway_username']);
		}

		$this->addField('Amount', EEH_Template::format_currency( $total, TRUE ));
		$this->addField('Currency', $this->_payment_settings['currency_format']);
		$this->addField('PageTitle', '');
		$this->addField('PageDescription', '');
		$this->addField('PageFooter', '');
		$this->addField('Language', '');
		$this->addField('CompanyName', EE_Registry::instance()->CFG->organization->name );
		$registrations = $session_data['cart']['REG']['items'];
		$description = '';
		foreach ($registrations as $registration) {
			$description .= $registration['qty'] . ' ticket to ' . $registration['name'] . ', ';
		}
		$description = rtrim($description, ', ');
		$this->addField('InvoiceDescription', $description);
		$this->addField('CancelURL', get_permalink( EE_Registry::instance()->CFG->core->cancel_page_id ));
		$ReturnURL = add_query_arg( array( 'form_action' => 'payment', 'attendee_action' => 'post_payment', 'session_id' => $session_data['id'] ), get_permalink( EE_Registry::instance()->CFG->core->thank_you_page_id ));
		$this->addField('ReturnURL', $ReturnURL );
		$this->addField('CompanyLogo', $this->_payment_settings['image_url']);
		$this->addField('PageBanner', '');
		$this->addField('MerchantReference', '');
		$this->addField('MerchantInvoice', '');
		$this->addField('MerchantOption1', '');
		$this->addField('MerchantOption2', '');
		$this->addField('MerchantOption3', '');
		$this->addField('ModifiableCustomerDetails', 'false');
		do_action('AHEE_log', __FILE__, __FUNCTION__, serialize(get_object_vars($this)));
		$this->prepareSubmit();
		$this->_EEM_Gateways->set_off_site_form($this->submitPayment());
		$this->redirect_after_reg_step_3();
	}
	
	protected function prepareSubmit() {
		$ewayurl = "?";
		foreach ($this->fields as $name => $value) {
			$ewayurl .= $name . '=' . $value . '&';
		}
		$ewayurl = rtrim($ewayurl, "&");
		$spacereplace = str_replace(" ", "%20", $ewayurl);
		$posturl = $this->_gatewayUrl . $spacereplace;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $posturl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);

		$responsemode = $this->_fetch_data($response, '<result>', '</result>');
		$responseurl = $this->_fetch_data($response, '<uri>', '</uri>');

		if ($responsemode == "True") {
			$this->_gatewayUrl = $responseurl;
			$this->fields = array();
		} else {
			echo "ERROR";
		}
	}
	
	private function _fetch_data($string, $start_tag, $end_tag) {
			$position = stripos($string, $start_tag);
			$str = substr($string, $position);
			$str_second = substr($str, strlen($start_tag));
			$second_positon = stripos($str_second, $end_tag);
			$str_third = substr($str_second, 0, $second_positon);
			$fetch_data = trim($str_third);
			return $fetch_data;
		}



	public function thank_you_page() {

		$txn_details = array(
				'gateway' => $this->_payment_settings['display_name'],
				'approved' => FALSE,
				'response_msg' => __('You\'re registration has not been completed successfully.', 'event_espresso'),
				'status' => 'Incomplete',
				'raw_response' => serialize($_POST),
				'amount' => 0.00,
				'method' => 'CC',
				'auth_code' => '0',
				'md5_hash' => sanitize_text_field($_POST['AccessPaymentCode']),
				'invoice_number' => '0',
				'transaction_id' => '0'
		);
		switch ($this->_payment_settings['region']) {
			case 'NZ':
				$results_request = 'https://nz.ewaygateway.com/Result/';
				break;
			case 'AU':
				$results_request = 'https://au.ewaygateway.com/Result/';
				break;
			case 'UK':
				$results_request = 'https://payment.ewaygateway.com/Result/';
				break;
		}
		if ($this->_payment_settings['use_sandbox'] == 1) {
			$results_request .= "?CustomerID=" . '87654321';
			$results_request .= "&UserName=" . 'TestAccount';
		} else {
			$results_request .= "?CustomerID=" . $this->_payment_settings['eway_id'];
			$results_request .= "&UserName=" . $this->_payment_settings['eway_username'];
		}
		$results_request .= "&AccessPaymentCode=" . $_REQUEST['AccessPaymentCode'];
		$results_request = str_replace(" ", "%20", $results_request);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $results_request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		if (defined('CURL_PROXY_REQUIRED') && CURL_PROXY_REQUIRED == 'True') {
			$proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			curl_setopt($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
		}

		$response = curl_exec($ch);

		$response_array['authecode'] = $this->_fetch_data($response, '<authCode>', '</authCode>');
		$response_array['responsecode'] = $this->_fetch_data($response, '<responsecode>', '</responsecode>');
		$response_array['retrunamount'] = $this->_fetch_data($response, '<returnamount>', '</returnamount>');
		$response_array['txn_id'] = $this->_fetch_data($response, '<trxnnumber>', '</trxnnumber>');
		$response_array['trxnstatus'] = $this->_fetch_data($response, '<trxnstatus>', '</trxnstatus>');
		$response_array['trxnresponsemessage'] = $this->_fetch_data($response, '<trxnresponsemessage>', '</trxnresponsemessage>');

		$response_array['merchantoption1'] = $this->_fetch_data($response, '<merchantoption1>', '</merchantoption1>');
		$response_array['merchantoption2'] = $this->_fetch_data($response, '<merchantoption2>', '</merchantoption2>');
		$response_array['merchantoption3'] = $this->_fetch_data($response, '<merchantoption3>', '</merchantoption3>');
		$response_array['merchantreference'] = $this->_fetch_data($response, '<merchantreference>', '</merchantreference>');
		$response_array['merchantinvoice'] = $this->_fetch_data($response, '<merchantinvoice>', '</merchantinvoice>');
		if ($response_array['responsecode'] == '00' || $response_array['responsecode'] == '08') {
			$txn_details['approved'] = TRUE;
			$txn_details['response_msg'] = __('You\'re registration has been completed successfully.', 'event_espresso');
			$txn_details['status'] = 'Approved';
			$txn_details['raw_response'] = serialize($response_array);
			$txn_details['amount'] = $response_array['retrunamount'];
			$txn_details['auth_code'] = $response_array['authecode'];
			$txn_details['invoice_number'] = $response_array['txn_id'];
			$txn_details['transaction_id'] = $response_array['txn_id'];
		}
		EE_Registry::instance()->SSN->set_session_data(array('txn_results' => $txn_details), 'session_data');

		$success = $txn_details['approved'];

		do_action( 'AHEE_after_payment', EE_Registry::instance()->SSN, $success );

		if ($txn_details['approved'] && $this->_payment_settings['use_sandbox']) {
			do_action('AHEE_mail_successful_transaction_debugging_output');
		} else {
			do_action('AHEE_mail_failed_transaction_debugging_output');
		}
		parent::thank_you_page();
	}




	public function espresso_display_payment_gateways() {
		echo $this->_generate_payment_gateway_selection_button();
		?>
		

			<div id="reg-page-billing-info-<?php echo $this->_gateway_name;?>-dv" class="reg-page-billing-info-dv <?php echo $this->_css_class;?>">
				<?php _e('After confirming the details of your registration in Step 3, you will be transferred to the Eway.com website where your payment will be securely processed.', 'event_espresso'); ?>
			</div>

		<?php
	}

}