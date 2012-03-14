<?php

function espresso_transactions_aim_get_attendee_id($attendee_id) {
	global $EE_Session;
	return $EE_Session->id();
}
add_filter('filter_hook_espresso_transactions_get_attendee_id', 'espresso_transactions_aim_get_attendee_id');

function espresso_process_aim( $EE_Session ) {
	global $wpdb, $org_options, $payment_settings, $EE_Session;

	require_once 'lib/AuthorizeNet.php';

	$authnet_aim_settings = $payment_settings['aim'];
	$authnet_aim_login_id = $authnet_aim_settings['authnet_aim_login_id'];
	$authnet_aim_transaction_key = $authnet_aim_settings['authnet_aim_transaction_key'];

// Enable test mode if needed
//4007000000027  <-- test successful visa
//4222222222222  <-- test failure card number
	if ($authnet_aim_settings['use_sandbox']) {
		define("AUTHORIZENET_SANDBOX", true);
		define("AUTHORIZENET_LOG_FILE", true);
	} else {
		define("AUTHORIZENET_SANDBOX", false);
	}

	$billing_info = $EE_Session->get_session_data( FALSE, 'billing_info' );
	$reg_info = $EE_Session->get_session_data( 'REG', 'cart' );
	$primary_attendee = $EE_Session->get_session_data( FALSE, 'primary_attendee' );
	
//start transaction
	$transaction = new AuthorizeNetAIM($authnet_aim_login_id, $authnet_aim_transaction_key);
	echo '<!--Event Espresso Authorize.net AIM Gateway Version ' . $transaction->gateway_version . '-->';
	 
	$transaction->amount = $reg_info['sub_total'];
	$transaction->card_num = $billing_info['card_num'];
	$transaction->exp_date = $billing_info['exp_date'];
	$transaction->card_code = $billing_info['ccv_code'];
	$transaction->first_name = $billing_info['first_name'];
	$transaction->last_name = $billing_info['last_name'];
	$transaction->email = $billing_info['email'];
	$transaction->address = $billing_info['address'];
	$transaction->city = $billing_info['city'];
	$transaction->state = $billing_info['state'];
	$transaction->zip = $billing_info['zip'];
	$transaction->cust_id = $primary_attendee['registration_id'];
	$transaction->invoice_num = $EE_Session->id(); // <<<<<<<<<<<<<<<<<<<<<<< This actually should NOT be generated YET !!! right?? or is it ? and if so from where ?
	
	if ($authnet_aim_settings['test_transactions']) {
		$transaction->test_request = "true";
	}
	
	// create an object to hold payment data
	$payment_data = new stdClass;

	$payment_data->txn_type = 'authorize.net AIM';
	$payment_data->payment_status = 'Incomplete';
	$payment_data->txn_id = 0;
	$payment_data->txn_details = 'No response from authorize.net';
	$payment_data = apply_filters('filter_hook_espresso_prepare_event_link', $payment_data);
	$payment_data = apply_filters('filter_hook_espresso_get_total_cost', $payment_data);
//Capture response
	$response = $transaction->authorizeAndCapture();


	if (!empty($response)) {
		if ($authnet_aim_settings['use_sandbox']) {
			$payment_data->txn_id = $response->invoice_number;
		} else {
			$payment_data->txn_id = $response->transaction_id;
		}
		$payment_data->txn_details = serialize($response);
		if ($response->approved) {
			$payment_data->payment_status = 'Completed';
			?>
			<h2><?php _e('Thank You!', 'event_espresso'); ?></h2>
			<p><?php _e('Your transaction has been processed.', 'event_espresso'); ?></p>
			<p><?php __('Transaction ID:', 'event_espresso') . $response->transaction_id; ?></p>
			<?php
		} else {
			print $response->error_message;
			$payment_data->payment_status = 'Payment Declined';
		}
	} else {
		?>
		<p><?php _e('There was no response from Authorize.net.', 'event_espresso'); ?></p>
		<?php
	}
	
	$EE_Session->set_session_data(  array( 'payment_data' => $payment_data ), $section = 'session_data' );
	
	add_action('action_hook_espresso_email_after_payment', 'espresso_email_after_payment');  //<-- Should this be here ? or in the successful txn bit above ( around line 79 ? ) or does this send failed txn info as well /
 	
	// return $payment_data;  <<<<-------  do we need to return success or flase or anything ?
}

add_action('action_hook_espresso_thank_you_get_payment_data', 'espresso_process_aim');