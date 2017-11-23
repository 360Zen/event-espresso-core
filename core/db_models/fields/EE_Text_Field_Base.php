<?php

/**
 * Text_Fields is a base class for any fields which are have text value. (Exception: foreign and private key fields.
 * Wish PHP had multiple-inheritance for this...)
 */
abstract class EE_Text_Field_Base extends EE_Model_Field_Base
{

    function prepare_for_get($value_of_field_on_model_object)
    {
        return is_string($value_of_field_on_model_object) ? stripslashes($value_of_field_on_model_object) : $value_of_field_on_model_object;
    }

    /**
     * Accepts schema of 'form_input' which formats the string for echoing in form input's value.
     *
     * @param string $value_on_field_to_be_outputted
     * @param string $schema
     * @return string
     */
    function prepare_for_pretty_echoing($value_on_field_to_be_outputted, $schema = null)
    {
        if ($schema == 'form_input') {
            $value_on_field_to_be_outputted = esc_attr($value_on_field_to_be_outputted);
        }
        return parent::prepare_for_pretty_echoing($value_on_field_to_be_outputted, $schema);
    }

    /**
     * Data received from the user should be exactly as they hope to save it in the DB, with the exception that
     * quotes need to have slashes added to it. (We used to call html_entity_decode on the value here,
     * because we called htmlentities when in EE_Text_Field_Base::prepare_for_pretty_echoing, but that's not necessary
     * because web browsers always decode HTML entities once. So if we do it again here, we'll be removing
     * HTML entities the user intended to have.)
     *
     * @param string $value_inputted_for_field_on_model_object
     * @return string
     */
    function prepare_for_set($value_inputted_for_field_on_model_object)
    {
        return stripslashes(parent::prepare_for_set($value_inputted_for_field_on_model_object));
    }
}