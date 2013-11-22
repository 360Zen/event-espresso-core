<?php
if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for Wordpress
 *
 * @package		Event Espresso
 * @author		Seth Shoultes
 * @copyright	(c)2009-2012 Event Espresso All Rights Reserved.
 * @license		http://eventespresso.com/support/terms-conditions/  ** see Plugin Licensing **
 * @link		http://www.eventespresso.com
 * @version		4.0
 *
 * ------------------------------------------------------------------------
 *
 * Price_Types_Default_Help_Tour
 *
 * This is the help tour object for the Default Price Types page
 *
 *
 * @package		Price_Types_Default_Help_Tour
 * @subpackage	caffeinated/admin/new/pricing/help_tours/Price_Types_Default_Help_Tour.core.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class Price_Types_Default_Help_Tour extends EE_Help_Tour {

	protected function _set_tour_properties() {
		$this->_label = __('Price Types Tour', 'event_espresso');
		$this->_slug = 'default-price-types-joyride';
	}


	protected function _set_tour_stops() {
		$this->_stops = array(
			10 => array(
				'content' => $this->_start(),
				),
			20 => array(
				'id' => 'event-espresso_page_pricing-search-input',
				'content' => $this->_search_stop(),
				'options' => array(
					'tipLocation' => 'left',
					'tipAdjustmentY' => -50,
					'tipAdjustmentX' => -15
					)
				),
			30 => array(
				'id' => 'name',
				'content' => $this->_name_column_stop(),
				'options' => array(
					'tipLocation' => 'top',
					'tipAdjustmentY' => -20
					)
				),
			40 => array(
				'id' => 'base_type',
				'content' => $this->_base_type_stop(),
				'options' => array(
					'tipLocation' => 'top',
					'tipAdjustmentY' => -20,
					'tipAdjustmentX' => 100
					)
				),
			50 => array(
				'id' => 'percent',
				'content' => $this->_percent_column_stop(),
				'options' => array(
					'tipLocation' => 'top',
					'tipAdjustmentY' => -20,
					'tipAdjustmentX' => 100
					)
				),
			60 => array(
				'id' => 'order',
				'content' => $this->_order_column_stop(),
				'options' => array(
					'tipLocation' => 'left',
					'tipAdjustmentY' => -30,
					'tipAdjustmentX' => 100,
					)
				)
			);
	}


	protected function _start() {
		$content = '<h3>' . __('Price Types', 'event_espresso') . '</h3>';
		$content .= '<p>' . __('This section allows you to create new discounts and surcharges to add to your events.', 'event_espresso') . '</p>';
		return $content;
	}

	

	protected function _search_stop() {
		return '<p>' . __('Fields that will be searched with the value from the search are: Price Type name', 'event_espresso') . '</p>';
	}


	protected function _name_column_stop() {
		return '<p>' . __('This column shows you the name of the price type. It is visible to your customers.', 'event_espresso') . '</p>';
	}


	protected function _base_type_stop() {
		return '<p>' . __('Here you can see whether the price type is a discount or a surcharge.', 'event_espresso') . '</p>';
	}


	protected function _member_column_stop() {
		return '<p>' . __('Here you can see if the discount/surcharge is percentage based or a flat monetary amount.', 'event_espresso') . '</p>';
	}


	protected function _percent_column_stop() {
		return '<p>' . __('Here you can see if the discount/surcharge is percentage based or a flat monetary amount.', 'event_espresso') . '</p>';
	}


	protected function _order_column_stop() {
		return '<p>' . __('This column shows you the order in which each discount or surcharge will be applied to the base ticket cost. Zero (0) means it will be applied first. Any number can be set here.', 'event_espreso') . '</p>';
	}

}