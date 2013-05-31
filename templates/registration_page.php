<?php
//As of version 3.0.17
//This is a logic file for displaying a registration form for an event on a page. This file will do all of the backend data retrieval functions.
//There should be a copy of this file in your wp-content/uploads/espresso/ folder.
//Note: This entire function can be overridden using the "Custom Files" addon
if (!function_exists('event_registration')) {

	function event_details_page($single_event_id = NULL, $event_id_sc = 0) {

		global $wpdb, $org_options;
		do_action('AHEE_log', __FILE__, __FUNCTION__, '');

		$event_slug = (get_query_var('event_slug')) ? get_query_var('event_slug') : FALSE;

		if ($event_id_sc != 0) {
			$SQL = 'SELECT slug  FROM ' . EVENTS_DETAIL_TABLE . ' WHERE id = %d';
			$event_slug = $wpdb->get_var($wpdb->prepare($SQL, $event_id_sc));
		}

		if (!empty($_REQUEST['event_id_time'])) {
			$pieces = explode('|', $_REQUEST['event_id_time'], 3);

			if (isset($pieces[0]) && $pieces[0] != '') {
				$SQL = 'SELECT slug  FROM ' . EVENTS_DETAIL_TABLE . ' WHERE id = %d';
				$event_slug = $wpdb->get_var($wpdb->prepare($SQL, $event_id_sc));
			}
		}

		if (isset($_REQUEST['ee']) && $_REQUEST['ee'] != '') {
			$ee_event_id = $_REQUEST['ee'];
		} else {
			$ee_event_id = FALSE;
		}

		//Build event queries
		$sql = "SELECT e.*";
		$org_options['use_venue_manager'] ? $sql .= ", v.name venue_name, v.address venue_address, v.address2 venue_address2, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
		$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";

		isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " r ON r.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = r.venue_id " : '';
		$sql.= " WHERE e.is_active=1 ";
		$sql.= " AND e.event_status != 'D' ";

		if ($single_event_id != NULL) {
			//Get the ID of a single event
			//If a single event needs to be displayed, get its ID
			$sql .= " AND event_identifier = '" . $single_event_id . "' ";
		} elseif ($ee_event_id) {
			$sql.= " AND e.id = '" . $ee_event_id . "' LIMIT 0,1";
		} else {
			$sql.= " AND e.slug = '" . $event_slug . "' LIMIT 0,1";
		}

		//Support for diarise
		if (!empty($_REQUEST['post_event_id'])) {
			$sql = "SELECT e.* FROM " . EVENTS_DETAIL_TABLE . ' e';
			$sql .= " WHERE post_id = '" . $_REQUEST['post_event_id'] . "' ";
			$sql .= " LIMIT 0,1";
		}

		$event = $wpdb->get_row($sql);
//		printr( $event, '$event  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		

		//Build the registration page
		//allow addons to override this part
		do_action('AHEE__registration_page__begin_display',$event);
		if(apply_filters('FHEE__registration_page__display_normal_reg_page',true,$event)){
			if (!empty($event)) {

				require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'models/EEM_Datetime.model.php');
				$DTM_MDL = EEM_Datetime::instance();

				// grab event times
				if ( $datetimes = $DTM_MDL->get_all_event_dates( $event->id )) {
					//printr( $datetimes, '$datetimes' );
					$single_event = count( $datetimes ) == 1 ? TRUE : FALSE;
					$first_event = current( $datetimes );

					if ( $single_event ) {
						$last_event = end( $datetimes );
						$start_date = $first_event->start_date_and_time( get_option('date_format') );
						$reg_start_date = $first_event->reg_start_date_and_time( get_option('date_format') );
						$end_date = $last_event->end_date_and_time( get_option('date_format'));
					}

					$event->registration_startT = $first_event->reg_start_time();
					$event->registration_start = $first_event->reg_start_date();
					$event->registration_endT = $first_event->reg_end_time();
					$event->registration_end = $first_event->reg_end_date();			

				}
				//These are the variables that can be used throughout the registration page
				//foreach ($events as $event) {
				global $this_event_id;
				$event_id = $event->id;
				$this_event_id = $event_id;

				$event_name = stripslashes($event->event_name);
				$event_desc = stripslashes($event->event_desc);
				$display_desc = $event->display_desc;
				$display_reg_form = $event->display_reg_form;
				$event_address = $event->address;
				$event_address2 = $event->address2;
				$event_city = $event->city;
				$event_state = $event->state;
				$event_zip = $event->zip;
				$event_country = $event->country;

				$member_only = $event->member_only;
				$reg_limit = $event->reg_limit;

				$allow_overflow = $event->allow_overflow;
				$overflow_event_id = $event->overflow_event_id;

				//Venue details
				$venue_title = $event->venue_title;
				$venue_address = '';
				$venue_address2 = '';
				$venue_city = '';
				$venue_state = '';
				$venue_zip = '';
				$venue_country = '';

				global $event_meta;
				$event_meta = unserialize($event->event_meta);

				//The following variables are used to get information about your organization
				if ( isset($org_options['map_settings']['ee_display_map_no_shortcodes']) && $org_options['map_settings']['ee_display_map_no_shortcodes']) {
					$show_ee_gmap_no_shortcode = true;
					require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'helpers/gmap_display.helper.php');
				} else {
					$show_ee_gmap_no_shortcode = false;
				}
				
				if (isset($org_options['map_settings']) && !empty($org_options['map_settings'])) {

					$gmap_address = ($event_address != '' ? $event_address : '') . ($event_address2 != '' ? ' ' . $event_address2 : '') . ($event_city != '' ? ', ' . $event_city : '') . ($event_state != '' ? ', ' . $event_state : '') . ($event_zip != '' ? ', ' . $event_zip : '') . ($event_country != '' ? ', ' . $event_country : '');
					// EE gmaps needs it's own org_options array populated on a per page basis to enable common queries in gmaps api function
					$ee_gmaps_opts = array(
							'ee_map_width' => empty($org_options['map_settings']['ee_map_width_single']) ? '' : $org_options['map_settings']['ee_map_width_single'],
							'ee_map_height' => empty($org_options['map_settings']['ee_map_height_single']) ? '' : $org_options['map_settings']['ee_map_height_single'],
							'ee_map_zoom' => empty($org_options['map_settings']['ee_map_zoom_single']) ? '' : $org_options['map_settings']['ee_map_zoom_single'],
							'ee_map_nav_display' => empty($org_options['map_settings']['ee_map_nav_display_single']) ? '' : $org_options['map_settings']['ee_map_nav_display_single'],
							'ee_map_nav_size' => empty($org_options['map_settings']['ee_map_nav_size_single']) ? '' : $org_options['map_settings']['ee_map_nav_size_single'],
							'ee_map_type_control' => empty($org_options['map_settings']['ee_map_type_control_single']) ? '' : $org_options['map_settings']['ee_map_type_control_single'],
							'ee_map_align' => empty($org_options['map_settings']['ee_map_align_single']) ? '' : $org_options['map_settings']['ee_map_align_single'],
							'ee_static_url' => empty($venue_meta['gmap_static']) ? '' : $venue_meta['gmap_static'],
							'ee_enable_for_gmap' => empty($event_meta['enable_for_gmap']) ? '' : $event_meta['enable_for_gmap'],
							'location' => $gmap_address,
							'event_id' => $event_id
					);
				}
				$show_google_map = $show_ee_gmap_no_shortcode && $event_meta['enable_for_gmap'] ? TRUE : FALSE;


				// display formatting
				$location = ($event_address != '' ? $event_address : '') . ($event_address2 != '' ? '<br />' . $event_address2 : '') . ($event_city != '' ? '<br />' . $event_city : '') . ($event_state != '' ? ', ' . $event_state : '') . ($event_zip != '' ? '<br />' . $event_zip : '') . ($event_country != '' ? '<br />' . $event_country : '');
			
				//Google map link creation
				require_once EE_HELPERS . 'EE_Maps.helper.php';
				$atts = array( 'address' => $event_address, 'city' => $event_city, 'state' => $event_state, 'zip' => $event_zip, 'country' => $event_country, 'text' => 'Map and Directions', 'type' => 'text' );
				$google_map_link = EE_Maps::google_map_link( $atts );

				$reg_limit = $event->reg_limit;
				//Set a default value for additional limit
				$additional_limit = ! empty( $event->additional_limit ) ? $event->additional_limit : '5';
				//Get the number of attendees
				$num_attendees = get_number_of_attendees_reg_limit($event_id, 'num_attendees'); 

				//Create all meta vars
				$more_meta = array(
						'event_name' => $event_name,
						'event_desc' => $event_desc,
						'event_address' => $event_address,
						'event_address2' => $event_address2,
						'event_city' => $event_state,
						'event_state' => $event_name,
						'event_zip' => $event_zip,
						'event_country' => $event->country,
						'venue_title' => '<span class="section-title">' . $venue_title . '</span>',
						'venue_address' => $venue_address,
						'venue_address2' => $venue_address2,
						'venue_city' => $venue_state,
						'venue_state' => $venue_state,
						'venue_country' => $venue_country,				
						'event_status' => $event->event_status,
						'is_active' => empty($event->is_active) ? '' : $event->is_active,
						'google_map_link' => $google_map_link,
						'price' => empty($event->event_cost) ? '' : $event->event_cost,
						'event_cost' => empty($event->event_cost) ? '' : $event->event_cost
				);

				$event->meta = is_array( $event_meta ) ? array_merge( $more_meta, $event_meta ) : $more_meta;

				$is_active = array();
				$is_active = event_espresso_get_is_active($event_id);



				//This is the start of the registration form. This is where you can start editing your display.
				//(Shows the regsitration form if enough spaces exist)
				if ($num_attendees >= $reg_limit) {
					?>
					<div class="espresso_event_full event-display-boxes" id="espresso_event_full-<?php echo $event_id; ?>">
						<h3 class="event_title"><?php echo stripslashes_deep($event_name) ?></h3>
						<div class="event-messages">
							<p class="event_full"><strong><?php _e('We are sorry but this event has reached the maximum number of attendees!', 'event_espresso'); ?></strong></p>
							<p class="event_full"><strong><?php _e('Please check back in the event someone cancels.', 'event_espresso'); ?></strong></p>
							<p class="num_attendees"><?php _e('Current Number of Attendees:', 'event_espresso'); ?> <?php echo $num_attendees ?></p>
						</div>
						<?php
						$num_attendees = get_number_of_attendees_reg_limit($event_id, 'num_attendees');
						if (($num_attendees >= $reg_limit) && ($allow_overflow && $overflow_event_id != 0)) {
							?>
							<p id="register_link-<?php echo $overflow_event_id ?>" class="register-link-footer"><a class="a_register_link" id="a_register_link-<?php echo $overflow_event_id ?>" href="<?php echo espresso_reg_url($overflow_event_id); ?>" title="<?php echo stripslashes_deep($event_name) ?>"><?php _e('Join Waiting List', 'event_espresso'); ?></a></p>
						<?php } ?>
					</div>

					<?php
				} else {
					//If enough spaces exist then show the form
					//Check to see if the Members plugin is installed.
					if (!is_user_logged_in() && defined( 'EVENT_ESPRESSO_MEMBERS_DIR' ) && $member_only) {
						event_espresso_user_login();
					} else {

						require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'functions/event_details.helper.php');

						require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'models/EEM_Datetime.model.php');
						$DTM_MDL = EEM_Datetime::instance();
						$event->datetimes = $DTM_MDL->get_all_event_dates($event->id);
	//						echo printr($event->times, 'EVENT TIMES <span style="margin:0 0 0 3em;font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span>', 'auto' );						

	//					require_once ( EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Event_Price.class.php' );
	//					$EVT_Prices = new EE_Event_Prices($event->id);
	//					$event->prices = $EVT_Prices->get_final_event_prices();
						require_once ( EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Ticket_Prices.class.php' );
						//$event->prices = new EE_Ticket_Prices( $event->id );
						$TKT_PRCs = new EE_Ticket_Prices( $event->id );
						$event->prices = $TKT_PRCs->get_all_final_event_prices();

						//echo printr($event->prices, 'EVENT PRICES <span style="margin:0 0 0 3em;font-size:10px;font-weight:normal;">( file: '. __FILE__ . ' - line no: ' . __LINE__ . ' )</span>', 'auto' );						

						$event->currency_symbol = $org_options['currency_symbol'];

						$event->display_available_spaces = ( $event->display_reg_form && $event->externalURL == '' ) ? TRUE : FALSE;
						$event->available_spaces = get_number_of_attendees_reg_limit($event_id, 'available_spaces');

						// ticket selector
						require_once(EVENT_ESPRESSO_INCLUDES_DIR . 'classes/EE_Ticket_Selector.class.php');
						add_action( 'AHEE_ticket_selector', array( 'EE_Ticket_Selector', 'init' ), 10, 1 );		

						$registration_url = add_query_arg(array('e_reg' => 'process_ticket_selections'), espresso_get_reg_page_full_url());

						//Serve up the registration form
						require(espresso_get_registration_display_template());
						// The following keys are available in the $data array:
	// event_id, event_name, is_active, registration_url, reg_start_date, display_reg_form, event, use_coupon_code, use_groupon_code, location, org_options, google_map_link, show_ee_gmap_no_shortcode, ee_gmap_display, end_date, start_date, display_desc, event_desc
						$data=array();
						$data['event_id'] = $event_id;
						$data['event_name'] = $event_name;
						$data['is_active'] = $is_active;
						$data['registration_url'] = $registration_url;
						$data['reg_start_date'] = $reg_start_date;
						$data['display_reg_form'] = $display_reg_form;
						$data['event'] = $event;
						$data['location'] = $location;
						$data['org_options'] = $org_options;
						$data['google_map_link'] = $google_map_link;
						$data['show_google_map'] = $show_google_map;
						$data['ee_gmaps_opts'] = $ee_gmaps_opts;
						$data['end_date'] = $end_date;
						$data['start_date'] = $start_date;
						$data['display_desc'] = $display_desc;
						$data['event_desc'] = $event_desc;
						$data['event_meta'] = $event_meta;
						$data['single_event'] = $single_event;
						espresso_display_reg_page($data);
					}
				}//End if ($num_attendees >= $reg_limit) (Shows the regsitration form if enough spaces exist)
			} else {//If there are no results from the query, display this message
				echo '<h3>' . __('This event has expired or is no longer available.', 'event_espresso') . '</h3>';
			}
		}

		echo espresso_registration_footer();

		//Check to see how many database queries were performed
		//echo '<p>Database Queries: ' . get_num_queries() .'</p>';
	}

}
