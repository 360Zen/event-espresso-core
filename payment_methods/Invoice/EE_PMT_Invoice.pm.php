<?php

if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

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
 * @ version		 	4.3
 *
 * ------------------------------------------------------------------------
 *
 * EE_PMT_Invoice
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
class EE_PMT_Invoice extends EE_PMT_Base{
	public function __construct($pm_instance = NULL) {
		$this->_pretty_name = __("Invoice", 'event_espresso');
		$this->_default_description = __( 'After clicking "Finalize Registration", you will be given instructions on how to access your invoice and complete your payment', 'event_espresso' );
		parent::__construct($pm_instance);
		$this->_default_button_url = $this->file_url().'lib'.DS.'invoice-logo.png';
	}
	public function generate_new_billing_form() {
		return NULL;
	}
	public function generate_new_settings_form() {
		if ( EE_Maintenance_Mode::instance()->level() != EE_Maintenance_Mode::level_2_complete_maintenance){
				$organization = EE_Registry::instance()->CFG->organization;
				$organization_name = $organization->name;
				$default_address = $organization->address_1 != '' ? $organization->address_1 . '<br />' : '';
				$default_address .= $organization->address_2 != '' ? $organization->address_2 . '<br />' : '';
				$default_address .= $organization->city != '' ? $organization->city : '';
				$default_address .= ( $organization->city != '' && $organization->STA_ID != '') ? ', ' : '<br />';
				$state = EE_Registry::instance()->load_model( 'State' )->get_one_by_ID( $organization->STA_ID );
				$country = EE_Registry::instance()->load_model( 'Country' )->get_one_by_ID( $organization->CNT_ISO ) ;
				$default_address .=  $state ? $state->name() . '<br />' : '';
				$default_address .= $country ? $country->name(). '<br />' : '';
				$default_address .= $organization->zip != '' ? $organization->zip : '';
			}else{
				$default_address = 'unknown';
				$organization_name = 'unknown';
			}
		$pdf_stylesheet_input_name = 'pdf_stylesheet';
		$page_title_input_name = 'page_title';
		$form =  new EE_Payment_Method_Form(array(
//				'payment_method_type' => $this,
				'extra_meta_inputs'=>array(
					$pdf_stylesheet_input_name=>new EE_Select_Input(array('simple.css'), array(
						'html_label_text'=>  sprintf(__("PDF Stylesheet %s", "event_espresso"),  $this->get_help_tab_link()),
						'html_help_text'=>  __("Load a custom/pre-made style sheet
	to change the look of your invoices.", 'event_espresso'),
					)),
					'pdf_instructions'=>new EE_Text_Area_Input(array(
						'html_label_text'=>  sprintf(__("PDF Instructions %s", "event_espresso"),  $this->get_help_tab_link()),
						'default'=>  __("Please send this invoice with payment attached to the address above, or use the payment link below. Payment must be received within 48 hours of event date.", 'event_espresso')
					)),
					'pdf_logo_image'=>new EE_Admin_File_Uploader_Input(array(
						'html_label_text'=>  sprintf(__("PDF Logo Image %s", "event_espresso"),  $this->get_help_tab_link()),
						'default'=>  EE_Config::instance()->organization->logo_url,
						'html_help_text'=>  __("(Logo for the top left of the invoice)", 'event_espresso'),
					)),
					$page_title_input_name =>new EE_Text_Input(array(
						'html_label_text'=>  sprintf(__("Payment Page Title %s", "event_espresso"),  $this->get_help_tab_link()),
						'default'=>  __("Invoice Payments", 'event_espresso')
					)),
					'page_instructions'=>new EE_Text_Area_Input(array(
						'html_label_text'=>  sprintf(__("Instructions %s", "event_espresso"),  $this->get_help_tab_link()),
						'default'=>  __("Please send Invoice to the address below. Payment must be received within 48 hours of event date.", 'event_espresso')
					)),
					'page_payable_to'=>new EE_Text_Input(array(
						'html_label_text'=>  sprintf(__("Payable To %s", "event_espresso"),  $this->get_help_tab_link()),
						'default'=> $organization_name
					)),
					'page_address_payable'=>new EE_Text_Area_Input(array(
						'html_label_text'=>  sprintf(__("Address Payable %s", "event_espresso"),  $this->get_help_tab_link()),
						'default'=> $default_address,
					)),
				),
				'include'=>array(
					'PMD_ID', 'PMD_name','PMD_desc','PMD_admin_name','PMD_admin_desc', 'PMD_type','PMD_slug', 'PMD_open_by_default','PMD_button_url','PMD_scope','Currency',
					'pdf_stylesheet','pdf_instructions','pdf_logo_image',
					'page_title','page_instructions','page_payable_to','page_address_payable'),
			));
		$form->add_subsections(array('header1'=>new EE_Form_Section_HTML_From_Template('payment_methods/Invoice/templates/invoice_settings_header_display.template.php')),$pdf_stylesheet_input_name);
		$form->add_subsections(array('header2'=>new EE_Form_Section_HTML_From_Template('payment_methods/Invoice/templates/invoice_settings_header_gateway.template.php')),$page_title_input_name);
		return $form;
	}



	/**
	 * Adds the help tab
	 * @see EE_PMT_Base::help_tabs_config()
	 * @return array
	 */
	public function help_tabs_config(){
		return array(
			$this->get_help_tab_name() => array(
						'title' => __('Invoice Settings', 'event_espresso'),
						'filename' => 'payment_methods_overview_invoice'
						),
		);
	}



 /**
	 * For adding any html output ab ove the payment overview.
	 * Many gateways won't want ot display anything, so this function just returns an empty string.
	 * Other gateways may want to override this, such as offline gateways.
	 * @return string
	 */
	public function payment_overview_content(EE_Payment $payment){
		EE_Registry::instance()->load_helper('Template');
		$extra_meta_for_payment_method = $this->_pm_instance->all_extra_meta_array();
		$transaction = $payment->transaction();
		$template_vars = array_merge(
						array(
							'payment_method'=>$this->_pm_instance,
							'payment'=>$payment,
							'page_title'=>'',
							'page_instructions'=>'',
							'page_payable_to'=>'',
							'page_address_payable'=>'',
							),
						$extra_meta_for_payment_method);
		return EEH_Template::display_template($this->_file_folder.'templates'.DS.'invoice_payment_details_content.template.php',
				$template_vars,
				true);
	}



}
// End of file EE_PMT_Invoice.pm.php