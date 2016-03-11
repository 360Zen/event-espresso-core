<?php
namespace EventEspresso\core\libraries\rest_api;
use EventEspresso\core\libraries\rest_api\Rest_Exception;
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * Capabilities
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class Capabilities {

	/**
	 * The current user can see at least SOME of these entities.
	 * @param \EEM_Base $model
	 * @param string $model_context one of the return values from EEM_Base::valid_cap_contexts()
	 * @return boolean
	 */
	public static function current_user_has_partial_access_to( $model, $model_context = \EEM_Base::caps_read ) {
		if( apply_filters( 'FHEE__Capabilities__current_user_has_partial_access_to__override_begin', false, $model, $model ) ) {
			return true;
		}
		foreach( $model->caps_missing( $model_context ) as $capability_name => $restriction_obj ) {
			if( $restriction_obj instanceof \EE_Return_None_Where_Conditions ){
				return false;
			}
		}
		if( apply_filters( 'FHEE__Capabilities__current_user_has_partial_access_to__override_end', false, $model, $model ) ) {
			return false;
		}
		return true;
	}
	/**
	 * Gets an array of all the capabilities the current user is missing that affected
	 * the query
	 *
	 * @param \EEM_Base $model
	 * @param string $request_type one of the constants on WP_JSON_Server
	 * @return array
	 */
	public static function get_missing_permissions( $model, $request_type = \EEM_Base::caps_read ) {
		return $model->caps_missing( $request_type );
	}
	/**
	 * Gets a string of all the capabilities the current user is missing that affected
	 * the query
	 *
	 * @param \EEM_Base $model
	 * @param string $model_context one of the return values from EEM_Base::valid_cap_contexts()
	 * @return string
	 */
	public static function get_missing_permissions_string( $model, $model_context = \EEM_Base::caps_read ) {
		return implode(',', array_keys( self::get_missing_permissions( $model, $model_context ) ) );
	}

	/**
	 * Takes a entity that's ready to be returned and removes fields which the user shouldn't be able to access.
	 * @param array $entity
	 * @param \EEM_Base $model
	 * @param string $request_type one of the return values from EEM_Base::valid_cap_contexts()
	 * @param Model_Version_Info $model_version_info
	 * @return array ready for converting into json
	 */
	public static function filter_out_inaccessible_entity_fields( $entity,  $model, $request_type, $model_version_info ) {
		//we only care to do this for frontend reads and when the user can't edit the item
		if(  $request_type !== \EEM_Base::caps_read ||
				$model->exists( array(
					array( $model->primary_key_name() => $entity[ $model->primary_key_name() ] ),
					'default_where_conditions' => 'none',
					'caps' => \EEM_Base::caps_edit ) ) ) {
			return $entity;
		}
		foreach( $model->field_settings() as $field_name => $field_obj ){
			if( $model_version_info->field_has_rendered_format( $field_obj )
				&& isset( $entity[ $field_name ][ 'raw' ] )
			) {
				unset( $entity[ $field_name ][ 'raw' ] );
			}
		}
		//theoretically we may want to filter out specific fields for specific models

		return apply_filters( 'FHEE__Capabilities__filter_out_inaccessible_entity_fields', $entity, $model, $request_type );
	}

	/**
	 * Verifies the current user has at least partial access to do this action on this model.
	 * If not, throws an exception (so we can define the code that sets up this error object
	 * once)
	 * @param type $model
	 * @param type $model_action_context
	 * @param type $action_name
	 * @return void
	 * @throw Rest_Exception
	 */
	public static function verify_at_least_partial_access_to( $model, $model_action_context, $action_name = 'list'  ) {
		if( ! Capabilities::current_user_has_partial_access_to( $model, $model_action_context ) ) {
			$model_name_plural = \EEH_Inflector::pluralize_and_lower( $model->get_this_model_name() );
			throw new Rest_Exception(
				sprintf( 'rest_%s_cannot_' . strtolower( $action_name ), $model_name_plural ),
				sprintf(
					__( 'Sorry, you are not allowed to %1$s %2$s. Missing permissions: %2$s', 'event_espresso' ),
					$action_name,
					$model_name_plural,
					Capabilities::get_missing_permissions_string( $model, $model_action_context )
				),
				array( 'status' => 403 )
			);
		}
	}
}

// End of file Capabilities.php