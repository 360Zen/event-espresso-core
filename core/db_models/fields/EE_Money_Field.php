<?php

use EventEspresso\core\domain\values\currency\Money;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidIdentifierException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\currency\MoneyFactory;
use EventEspresso\core\services\loaders\LoaderFactory;

defined('EVENT_ESPRESSO_VERSION') || exit;

/**
 * EE_Money_Field
 * Model field for dealing with money amounts. Originally this accepted and returns float
 * values, but now it also deals in Money entities.
 */
class EE_Money_Field extends EE_Float_Field
{

    /**
     * @var $money_factory MoneyFactory
     */
    protected $money_factory;


    /**
     * @param string       $table_column
     * @param string       $nicename
     * @param bool         $nullable
     * @param null         $default_value
     * @param MoneyFactory $factory
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public function __construct(
        $table_column,
        $nicename,
        $nullable,
        $default_value = null,
        MoneyFactory $factory = null
    ) {
        if (! $factory instanceof MoneyFactory) {
            $factory = LoaderFactory::getLoader()->getShared('EventEspresso\core\services\currency\MoneyFactory');
        }
        $this->money_factory = $factory;
        parent::__construct($table_column, $nicename, $nullable, $default_value);
        $this->setSchemaType('object');
    }


    /**
     * Formats the value for pretty output, according to $schema.
     * Schemas (use the admin-editable currency data from the database):
     *    'localized_float': "3,023.00"
     *    'no_currency_code': "$3,023.00"
     *    null: "$3,023.00<span>USD</span>"
     *
     * @param string|Money $value_on_field_to_be_outputted
     * @param string       $schema
     * @return string
     * @throws InvalidIdentifierException
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function prepare_for_pretty_echoing($value_on_field_to_be_outputted, $schema = null)
    {
        $value_on_field_to_be_outputted = $this->ensureNotMoney($value_on_field_to_be_outputted);
        $pretty_float = parent::prepare_for_pretty_echoing($value_on_field_to_be_outputted);

        if ($schema === 'localized_float') {
            return $pretty_float;
        }
        $display_code = true;
        if ($schema === 'no_currency_code') {
            //          echo "schema no currency!";
            $display_code = false;
        }

        //we don't use the $pretty_float because format_currency will take care of it.
        return EEH_Money::format_currency($value_on_field_to_be_outputted, false, $display_code);
    }


    /**
     * Make sure this value is a money object
     *
     * @param string|float|int|Money $value
     * @return Money
     * @throws InvalidIdentifierException
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    private function ensureMoney($value)
    {
        if (! $value instanceof Money) {
            return $this->money_factory->createForSite($value);
        }
        return $value;
    }



    /**
     * Ensures we're dealing with something that isn't Money
     * (for passing off to legacy systems or the parent field)
     * @param  string|float|int|Money $value
     * @return string|float|int
     */
    private function ensureNotMoney($value)
    {
        if( $value instanceof Money) {
            return $value->amount();
        }
        return $value;
    }


    /**
     * If provided with a string, strips out money-related formatting to turn it into a proper float.
     * Rounds the float to the correct number of decimal places for this country's currency.
     * Also, interprets periods and commas according to the country's currency settings.
     * So if you want to pass in a string that NEEDS to interpret periods as decimal marks,
     * type cast it to a float first.
     *
     * @param string|float|int|Money $value_inputted_for_field_on_model_object
     * @return Money
     * @throws InvalidInterfaceException
     * @throws InvalidIdentifierException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     * @throws InvalidArgumentException
     */
    public function prepare_for_set($value_inputted_for_field_on_model_object)
    {
        if ($value_inputted_for_field_on_model_object instanceof Money) {
            return $value_inputted_for_field_on_model_object;
        }
        //now it's a float-style string or number
        return $this->ensureMoney(
            parent::prepare_for_set($value_inputted_for_field_on_model_object)
        );
    }



    /**
     * @param string|float|int|Money $value_of_field_on_model_object
     * @return float
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public function prepare_for_get($value_of_field_on_model_object)
    {
        $value_of_field_on_model_object = $this->ensureNotMoney($value_of_field_on_model_object);
        $c = EE_Registry::instance()->CFG->currency;
        return round(parent::prepare_for_get($value_of_field_on_model_object), $c->dec_plc);
    }


    /**
     * Takes the incoming float and create a money entity for the model object
     *
     * @param string|float|int $value_found_in_db_for_model_object
     * @return Money
     * @throws InvalidIdentifierException
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    public function prepare_for_set_from_db($value_found_in_db_for_model_object)
    {
        return $this->money_factory->createForSite($value_found_in_db_for_model_object);
    }



    /**
     * Prepares a value for use in the DB
     * @param string|float|int|Money $value_of_field_on_model_object
     * @return float
     */
    public function prepare_for_use_in_db($value_of_field_on_model_object)
    {
        $value_of_field_on_model_object = $this->ensureNotMoney($value_of_field_on_model_object);
        return parent::prepare_for_use_in_db($value_of_field_on_model_object);
    }



    public function getSchemaProperties()
    {
        return array(
            'raw' => array(
                'description' =>  sprintf(
                    __('%s - the raw value as it exists in the database as a simple float.', 'event_espresso'),
                    $this->get_nicename()
                ),
                'type' => 'number'
            ),
            'pretty' => array(
                'description' =>  sprintf(
                    __('%s - formatted for display in the set currency and decimal places.', 'event_espresso'),
                    $this->get_nicename()
                ),
                'type' => 'string'
            )
        );
    }
}
