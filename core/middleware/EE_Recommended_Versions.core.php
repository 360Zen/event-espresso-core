<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/**
 * Class EE_Recommended_Versions
 *
 * checks required and recommended versions for both WP and PHP
 * terminates the request if minimum required versions are not met
 *
 * @package 			Event Espresso
 * @subpackage 	core
 * @author 				Brent Christensen
 * @since 				$VID:$
 *
 */

class EE_Recommended_Versions extends EE_Middleware implements EEI_Request_Decorator {


	/**
	 * converts a Request to a Response
	 * @param 	EE_Request $request
	 * @return 	EE_Response
	 */
	public function handle( EE_Request $request ) {
		//$this->response->add_output( "\n<br />" . __CLASS__ );
		//$this->response->set_notice( 1, 'hey look at this' );
		// check required WP version
		if ( ! $this->_minimum_wp_version_required() ) {
			$request->un_set( 'activate', true );
			add_action( 'admin_notices', array( $this, 'minimum_wp_version_error' ), 1 );
			//$this->response->add_output( "\n<br />" . 'minimum_wp_version_error' );
			$this->response->set_terminate_request();
		}
		// check required PHP version
		if ( ! $this->_minimum_php_version_required() ) {
			$request->un_set( 'activate', true );
			add_action( 'admin_notices', array( $this, 'minimum_php_version_error' ), 1 );
			//$this->response->add_output( "\n<br />" . 'minimum_php_version_error' );
			$this->response->set_terminate_request();
		}
		// check recommended WP version
		if ( ! $this->_minimum_wp_version_recommended() ) {
			$this->_display_minimum_recommended_wp_version_notice();
		}
		// check recommended PHP version
		if ( ! $this->_minimum_php_version_recommended() ) {
			$this->_display_minimum_recommended_php_version_notice();
		}

		$response = $this->process_request_stack( $request );
		//$this->response->add_output( "\n<br />out " . __CLASS__ );
		return $response;
	}



	/**
	 *    _check_wp_version
	 *
	 * @access private
	 * @param string $min_version
	 * @return boolean
	 */
	private function _check_wp_version( $min_version = EE_MIN_WP_VER_REQUIRED ) {
		global $wp_version;
		return version_compare( $wp_version, $min_version, '>=' ) ? true : false;
	}



	/**
	 *    _minimum_wp_version_required
	 *
	 * @access private
	 * @return boolean
	 */
	private function _minimum_wp_version_required() {
		return $this->_check_wp_version( EE_MIN_WP_VER_REQUIRED );
	}



	/**
	 *    _minimum_wp_version_recommended
	 *
	 * @access private
	 * @return boolean
	 */
	private function _minimum_wp_version_recommended() {
		return $this->_check_wp_version( EE_MIN_WP_VER_RECOMMENDED );
	}



	/**
	 *    _check_php_version
	 *
	 * @access private
	 * @param string $min_version
	 * @return boolean
	 */
	private function _check_php_version( $min_version = EE_MIN_PHP_VER_RECOMMENDED ) {
		return version_compare( PHP_VERSION, $min_version, '>=' ) ? true : false;
	}



	/**
	 *    _minimum_php_version_required
	 *
	 * @access private
	 * @return boolean
	 */
	private function _minimum_php_version_required() {
		return $this->_check_php_version( EE_MIN_PHP_VER_REQUIRED );
	}



	/**
	 *    _minimum_php_version_recommended
	 *
	 * @access private
	 * @return boolean
	 */
	private function _minimum_php_version_recommended() {
		return $this->_check_php_version( EE_MIN_PHP_VER_RECOMMENDED );
	}



	/**
	 *    minimum_wp_version_error
	 *
	 * @return void
	 */
	public function minimum_wp_version_error() {
		global $wp_version;
		?>
		<div class="error">
			<p>
				<?php
				printf(
					__( 'We\'re sorry, but Event Espresso requires WordPress version %1$s or greater in order to operate. You are currently running version %2$s.%3$sFor information on how to update your version of WordPress, please go to %4$s.', 'event_espresso' ),
					EE_MIN_WP_VER_REQUIRED,
					$wp_version,
					'<br/>',
					'<a href="http://codex.wordpress.org/Updating_WordPress">http://codex.wordpress.org/Updating_WordPress</a>'
				);
				?>
			</p>
		</div>
		<?php
		espresso_deactivate_plugin( EE_PLUGIN_BASENAME );
	}



	/**
	 *    minimum_php_version_error
	 *
	 * @return void
	 */
	public function minimum_php_version_error() {
		?>
		<div class="error">
			<p>
				<?php
				printf(
					__( 'We\'re sorry, but Event Espresso requires PHP version %1$s or greater in order to operate. You are currently running version %2$s.%3$sIn order to update your version of PHP, you will need to contact your current hosting provider.%3$sFor information on stable PHP versions, please go to %4$s.', 'event_espresso' ),
					EE_MIN_PHP_VER_REQUIRED,
					PHP_VERSION,
					'<br/>',
					'<a href="http://php.net/downloads.php">http://php.net/downloads.php</a>'
				);
				?>
			</p>
		</div>
		<?php
		deactivate_plugins( EE_PLUGIN_BASENAME );
	}



	/**
	 *    _display_minimum_recommended_wp_version_notice
	 *
	 * @access private
	 * @return void
	 */
	private function _display_minimum_recommended_wp_version_notice() {
		global $wp_version;
		EE_Error::add_persistent_admin_notice(
			'wp_version_' . str_replace( '.', '-', EE_MIN_WP_VER_RECOMMENDED ) . '_recommended',
			sprintf(
				__( 'Event Espresso recommends WordPress version %1$s or greater in order for everything to operate properly. You are currently running version %2$s.%3$sFor information on how to update your version of WordPress, please go to %4$s.', 'event_espresso' ),
				EE_MIN_WP_VER_RECOMMENDED,
				$wp_version,
				'<br/>',
				'<a href="http://codex.wordpress.org/Updating_WordPress">http://codex.wordpress.org/Updating_WordPress</a>'
			)
		);
	}



	/**
	 *    _display_minimum_recommended_php_version_notice
	 *
	 * @access private
	 * @return void
	 */
	private function _display_minimum_recommended_php_version_notice() {
		EE_Error::add_persistent_admin_notice(
			'php_version_' . str_replace( '.', '-', EE_MIN_PHP_VER_RECOMMENDED ) . '_recommended',
			sprintf(
				__( 'Event Espresso recommends PHP version %1$s or greater for optimal performance. You are currently running version %2$s.%3$sIn order to update your version of PHP, you will need to contact your current hosting provider.%3$sFor information on stable PHP versions, please go to %4$s.', 'event_espresso' ),
				EE_MIN_PHP_VER_RECOMMENDED,
				PHP_VERSION,
				'<br/>',
				'<a href="http://php.net/downloads.php">http://php.net/downloads.php</a>'
			)
		);
	}

}



// End of file EE_Recommended_Versions.core.php
// Location: /EE_Recommended_Versions.core.php