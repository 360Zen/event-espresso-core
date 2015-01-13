<?php

class EE_No_Layout extends EE_Form_Section_Layout_Base{

	/**
	 * opening div tag for a form
	 * @return string
	 */
	public function layout_form_begin() {
		return EEH_HTML::nl(1);
	}


	/**
	 * Lays out the row for the input, including label and errors
	 * @param EE_Form_Input_Base $input
	 * @return string
	 */
	public function layout_input( $input ) {
		$html = '';
		if ( $input instanceof EE_Hidden_Input  ) {
			$html .= EEH_HTML::nl() . $input->get_html_for_input();
		} else if ( $input instanceof EE_Submit_Input  ) {
			$html .= EEH_HTML::nl(1) . $input->get_html_for_input();
		} else if ( $input instanceof EE_Select_Input  ) {
			$html .= EEH_HTML::nl(1) . $input->get_html_for_label();
			$html .= EEH_HTML::nl() . $input->get_html_for_errors();
			$html .= EEH_HTML::nl() . $input->get_html_for_input();
			$html .= EEH_HTML::nl() . $input->get_html_for_help();
		} else if ( $input instanceof EE_Form_Input_With_Options_Base  ) {
			$html .= EEH_HTML::nl(1) . $input->get_html_for_errors();
			$html .= EEH_HTML::nl() . $input->get_html_for_input();
			$html .= EEH_HTML::nl() . $input->get_html_for_help();
		} else {
			$html .= EEH_HTML::nl(1) . $input->get_html_for_label();
			$html .= EEH_HTML::nl() . $input->get_html_for_errors();
			$html .= EEH_HTML::nl() . $input->get_html_for_input();
			$html .= EEH_HTML::nl() . $input->get_html_for_help();
		}
		$html .= EEH_HTML::nl(-1);
		return $html;
	}



	/**
	 * Lays out a row for the subsection
	 * @param EE_Form_Section_Proper $form_section
	 * @return string
	 */
	public function layout_subsection( $form_section ){
//		d( $form_section );
		return EEH_HTML::nl(1) . $form_section->get_html_and_js() . EEH_HTML::nl(-1);
	}


	/**
	 * closing div tag for a form
	 * @return string
	 */
	public function layout_form_end(){
		return EEH_HTML::nl(-1);
	}
}