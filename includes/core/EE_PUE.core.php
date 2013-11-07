<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
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
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * EE_PUE
 *
 * @package			Event Espresso
 * @subpackage	includes/core/
 * @author				Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class EE_PUE {

	/**
	 * 	EE_Registry Object
	 *	@var 	object	
	 * 	@access 	protected
	 */
	protected $EE = NULL;

	/**
	 * This property is used to hold an array of EE_default_term objects assigned to a custom post type when the post for that post type is published with no terms set for the taxonomy.
	  *
	 * @var array of EE_Default_Term objects
	 */
	protected $_default_terms = array();


	
	

	/**
	 *	class constructor
	 * 
	 *	@access public
	 *	@return void
	 */	
	public function __construct() {
//		throw new EE_Error('error');
		$this->EE = EE_Registry::instance();
		do_action('AHEE_log', __FILE__, __FUNCTION__, '' );

		$ueip_optin = get_option('ee_ueip_optin');
		$ueip_has_notified = isset($_POST['ueip_optin']) ? TRUE : get_option('ee_ueip_has_notified');

		//has optin been selected for datacollection?
		$espresso_data_optin = !empty($ueip_optin) ? $ueip_optin : NULL;

		if ( empty($ueip_has_notified) ) {
			add_action('admin_notices', array( $this, 'espresso_data_collection_optin_notice' ), 10 );
			add_action('admin_enqueue_scripts', array( $this, 'espresso_data_collection_enqueue_scripts' ), 10 );
			add_action('wp_ajax_espresso_data_optin', array( $this, 'espresso_data_optin_ajax_handler' ), 10 );
			update_option('ee_ueip_optin', 'yes');
			$espresso_data_optin = 'yes';
		}

		//let's prepare extra stats
		$extra_stats = array();

		//only collect extra stats if the plugin user has opted in.
		if ( !empty($espresso_data_optin) && $espresso_data_optin == 'yes' ) {
			//let's only setup extra data if transient has expired
			if ( false === ( $transient = get_transient('ee_extra_data') ) ) {
				//active gateways
				$active_gateways = get_option('event_espresso_active_gateways');
				if ( !empty($active_gateways ) ) {
					foreach ( (array) $active_gateways as $gateway => $ignore ) {
						$extra_stats[$gateway . '_gateway_active'] = 1;
					}
				}

				//set transient
				set_transient( 'ee_extra_data', $extra_stats, WEEK_IN_SECONDS );
			}
		}



		// PUE Auto Upgrades stuff
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'libraries/pue/pue-client.php')) { //include the file 
			require(EVENT_ESPRESSO_PLUGINFULLPATH . 'libraries/pue/pue-client.php' );

			$api_key = isset( $this->EE->CFG->site_license_key ) ? $this->EE->CFG->site_license_key : '';
			$host_server_url = 'http://eventespresso.com'; //this needs to be the host server where plugin update engine is installed. Note, if you leave this blank then it is assumed the WordPress repo will be used and we'll just check there.

			//Note: PUE uses a simple preg_match to determine what type is currently installed based on version number.  So it's important that you use a key for the version type that is unique and not found in another key.
			//For example:
			//$plugin_slug['premium']['p'] = 'some-premium-slug';
			//$plugin_slug['prerelease']['pr'] = 'some-pre-release-slug';
			//The above would not work because "p" is found in both keys for the version type. ( i.e 1.0.p vs 1.0.pr ) so doing something like:
			//$plugin_slug['premium']['p'] = 'some-premium-slug';
			//$plugin_slug['prerelease']['b'] = 'some-pre-release-slug';
			//..WOULD work!
			$plugin_slug = array(
				'free' => array( 'l' => 'event-espresso-core-decaf' ),
				'premium' => array( 'p' => 'event-espresso-core-caf' ),
				'prerelease' => array( 'b' => 'event-espresso-core-pr' )
				);


			//$options needs to be an array with the included keys as listed.
			$options = array(
			//	'optionName' => '', //(optional) - used as the reference for saving update information in the clients options table.  Will be automatically set if left blank.
				'apikey' => $api_key, //(required), you will need to obtain the apikey that the client gets from your site and then saves in their sites options table (see 'getting an api-key' below)
				'lang_domain' => 'event_espresso', //(optional) - put here whatever reference you are using for the localization of your plugin (if it's localized).  That way strings in this file will be included in the translation for your plugin.
				'checkPeriod' => '24', //(optional) - use this parameter to indicate how often you want the client's install to ping your server for update checks.  The integer indicates hours.  If you don't include this parameter it will default to 12 hours.
				'option_key' => 'site_license_key', //this is what is used to reference the api_key in your plugin options.  PUE uses this to trigger updating your information message whenever this option_key is modified.
				'options_page_slug' => 'event_espresso',
				'plugin_basename' => plugin_basename(EVENT_ESPRESSO_PLUGINPATH),
				'use_wp_update' => FALSE, //if TRUE then you want FREE versions of the plugin to be updated from WP
				'extra_stats' => $extra_stats
			);
			$check_for_updates = new PluginUpdateEngineChecker($host_server_url, $plugin_slug, $options); //initiate the class and start the plugin update engine!
		}
	}
	//add_action('AHEE_pue_update', 'espresso_site_license');



	/**
	 * The purpose of this function is to display information about Event Espresso data collection and a optin selection for extra data collecting by users.
	 * @return string html.
	 */
	 public static function espresso_data_collection_optin_text( $extra = TRUE ) {
		 echo '<h4>'.__('User eXperience Improvement Program (UXIP)', 'event_espresso').'</h4>';
		 $settings_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'your_organization_settings'), admin_url( 'admin.php?page=espresso_general_settings') );
		 $settings_url = $settings_url . '#UXIP_settings';
		 echo sprintf( __('%sPlease help us make Event Espresso better and vote for your favorite features.%s The %sUser eXperience Improvement Program (UXIP)%s, has been created so when you use Event Espresso you are voting for the features and settings that are important to you. The UXIP helps us understand how you use our products and services, track problems and in what context. If you opt-out of the UXIP you essentially elect for us to disregard how you use Event Espresso as we build new features and make changes. Participation in the program is completely voluntary but it is enabled by default. The end results of the UXIP are software improvements to better meet your needs. The data we collect will never be sold, traded, or misused in any way. %sPlease see our %sPrivacy Policy%s for more information.', 'event_espresso'), '<p><em>', '</em></p>','<a href="http://eventespresso.com/about/user-experience-improvement-program-uxip/" target="_blank">','</a>','<br><br>','<a href="http://eventespresso.com/about/privacy-policy/" target="_blank">','</a>' );
		 if ( $extra ) {
		 	echo sprintf( __( 'You can choose to not be part of the solution and opt-out of this program by changing the %sEvent Espresso > General Settings > Your Organization > UXIP Settings%s within your WordPress Admin', 'event_espresso' ), '<a href="' . $settings_url . '">','</a>' );
		}
	}




	function espresso_data_collection_optin_notice() {
		$ueip_has_notified = get_option('ee_ueip_has_notified');
		$settings_url = EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'your_organization_settings'), admin_url( 'admin.php?page=espresso_general_settings') );
		$settings_url = $settings_url . '#UXIP_settings';
		?>
		<div class="updated data-collect-optin" id="espresso-data-collect-optin-container">
			<p><?php echo EE_PUE::espresso_data_collection_optin_text(); ?></p>
			<div id="data-collect-optin-options-container">
				<span style="display: none" id="data-optin-nonce"><?php echo wp_create_nonce('ee-data-optin'); ?></span>
				<?php
				if ( empty($ueip_has_notified) ) {
					echo '<a href="' . $settings_url . '">'.__('Opt-out now?', 'event_espresso').'</a>';
				}
				?>
				<button class="button-secondary data-optin-button" value="no"><?php _e('Dismiss', 'event_espresso'); ?></button>
				<!--<button class="button-primary data-optin-button" value="yes"><?php _e('Yes! I\'m In', 'event_espresso'); ?></button>-->
				<div style="clear:both"></div>
			</div>
		</div>
		<?php
	}



	/**
	 * enqueue scripts/styles needed for data collection optin
	 * @return void
	 */
	function espresso_data_collection_enqueue_scripts() {
		wp_register_script( 'ee-data-optin-js', EVENT_ESPRESSO_PLUGINFULLURL . 'scripts/ee-data-optin.js', array('jquery'), EVENT_ESPRESSO_VERSION, TRUE );
		wp_register_style( 'ee-data-optin-css', EVENT_ESPRESSO_PLUGINFULLURL . 'css/ee-data-optin.css', array(), EVENT_ESPRESSO_VERSION );

		wp_enqueue_script('ee-data-optin-js');
		wp_enqueue_style('ee-data-optin-css');
	}



	/**
	 * This just handles the setting of the selected option for data optin via ajax
	 * @return void
	 */
	function espresso_data_optin_ajax_handler() {

		//verify nonce
		if ( isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'ee-data-optin') ) exit();

		//made it here so let's save the selection
		$ueip_optin = isset( $_POST['selection'] ) ? $_POST['selection'] : 'no';

		//update_option('ee_ueip_optin', $ueip_optin);
		update_option('ee_ueip_has_notified', 1);
		exit();
	}



}
// End of file EE_PUE.core.php
// Location: ./core/EE_PUE.core.php