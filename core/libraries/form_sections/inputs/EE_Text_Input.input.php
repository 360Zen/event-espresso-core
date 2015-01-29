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
 * @ version		 	4.6
 *
 * ------------------------------------------------------------------------
 *
 * EE_Year_Input
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
class EE_Text_Input extends EE_Form_Input_Base{


	/**
	 * @param array $options
	 */
	function __construct($options = array()){
		$this->_set_display_strategy(new EE_Text_Input_Display_Strategy());
		$this->_set_normalization_strategy(new EE_Text_Normalization());
		parent::__construct($options);
	}



}