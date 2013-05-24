<?php
//This is the event list template page.
//This is a template file for displaying an event lsit on a page.
//There should be a copy of this file in your wp-content/uploads/espresso/ folder.
/*
 * use the following shortcodes in a page or post:
 * [EVENT_LIST]
 * [EVENT_LIST limit=1]
 * [EVENT_LIST css_class=my-custom-class]
 * [EVENT_LIST show_expired=true]
 * [EVENT_LIST show_deleted=true]
 * [EVENT_LIST show_secondary=false]
 * [EVENT_LIST category_identifier=your_category_identifier]
 *
 *
 */
?>

<div id="event_data-<?php echo $event_id ?>" class="event_data <?php echo $category_identifier; ?> event-list-display-dv">
	<?php do_action('AHEE_before_event_list_listing', $event_id); ?>


	<h3 id="event_title-<?php echo $event_id ?>" class="big-event-title-hdr">
		<a 	href="<?php echo $registration_url; ?>"
				id="a_event_title-<?php echo $event_id ?>"
				class="a_event_title"
				title="<?php echo $event_name ?>"
				>
					<?php echo $event_name ?>
		</a>
	</h3>

	<h4 class="event-date-hdr"><?php echo $display_event_date->start_date( 'l F jS, Y' ); ?></h4>

	<?php do_action('AHEE_social_display_buttons', $event_id); ?>

	<?php /* These are custom messages that can be displayed based on the event status. Just un-comment the one you want to use. */ ?>
	<?php //echo $status_display; //Turn this on to display the overall status of the event.  ?>
	<?php //echo $status_display_ongoing; //Turn this on to display the ongoing message. ?>
	<?php //echo $status_display_deleted; //Turn this on to display the deleted message. ?>
	<?php //echo $status_display_secondary; //Turn this on to display the waitlist message. ?>
	<?php //echo $status_display_reg_closed; //Turn this on to display the registration closed message. ?>
	<?php //echo $status_display_not_open; //Turn this on to display the secondary message. ?>
	<?php //echo $status_display_open; //Turn this on to display the not open message. ?>
	<?php //echo $status_display_custom_closed; //Turn this on to display the closed message. ?>

	<div class="event-list-ticket-info-div event-display-boxes">
		<h3 class="ui-widget-header ui-corner-top"><?php echo __('Registration &amp; Ticket Information', 'event_espresso'); ?></h3>
		<div class="event-data-display-dv ui-widget-content ui-corner-bottom">

			<p id="event-status-pg-<?php echo $event_id ?>" class="event-status-pg">
				<span class="section-title"><?php echo __('Event Status: ', 'event_espresso'); ?></span>&nbsp;<?php echo $status_display; ?>
			</p>

			<?php
			do_action('AHEE_inside_event_list_listing', $event_id);
			if ($can_register_for_event) {
				if ($display_available_spaces) {
					?>

					<p id="available-spaces-pg-<?php echo $event_id ?>" class="available-spaces-pg">
						<span class="section-title"><?php _e('Available Spaces:', 'event_espresso') ?></span>&nbsp;
						<span id="available-spaces-spn-<?php echo $event_id ?>" class="available-spaces-spn"><?php echo $available_spaces; ?></span>
					</p>

				<?php
				}

				// $display_available_spaces
				if ($event_is_sold_out) {
					if ($overflow_event) {
						?>

						<p id="register_link-<?php echo $overflow_event_id; ?>" class="register-link">
							<a 	id="a_register_link-<?php echo $overflow_event_id; ?>"
									class="a_register_link ui-priority-secondary ui-state-default"
									href="<?php echo $overflow_reg_url; ?>"
									title="<?php echo $event_name; ?>"
									>
						<?php _e('Join Waiting List', 'event_espresso'); ?>
							</a>
							<div class="clear"></div>
						</p>

						<?php
					} else {
						// end $overflow_event
						echo '<p class="event-list-sold-out-msg-pg">' . __('Sorry. This Event is sold out.', 'event_espresso') . '</p>';
					}
				} else {
					// event is not sold out
					do_action( 'AHEE_ticket_selector', $event );
					echo $event_reg_link;
				}
			} else {	// end if $can_register_for_event
				echo $registration_closed_msg;
			}
			do_action('AHEE_end_event_list_listing',$event_id);
			?>
			<div class="clear"></div>
		</div>
	</div>

<?php if ( $display_address || (isset($display_desc) && $display_desc) ) { ?>

	<div class="event-list-ticket-info-dv event-display-boxes">
		<h3 class="ui-widget-header ui-corner-top"><?php echo __('Event Information', 'event_espresso'); ?></h3>
		<div class="event-data-display-dv ui-widget-content ui-corner-bottom">

		<?php if ($display_address) { ?>
			<div id="event_address-<?php echo $event_id ?>"  class="event-address-pg">
				<h6><?php echo __('Address:', 'event_espresso'); ?></h6>
				<?php echo $location; ?>
				<?php echo $google_map_link; ?>				
			</div>
			<?php echo $google_map; ?>
			<div class="clear"></div>
		<?php } ?>

		<?php if ($display_desc) { ?>
			<div  id="event-<?php echo $event_id; ?>-desc-dv" class="event-desc  clear-float">
				<h6><?php echo __('Description: ', 'event_espresso'); ?></h6>
				<?php echo $event_desc; ?>
			</div>
			<div class="clear"></div>
		<?php } ?>

			<div class="event-more-info-dv clear-float">
				<a 	id="event-more-info-btn-lnk-<?php echo $event_id ?>"
						class="event-more-info-btn-lnk ui-button ui-state-default ui-corner-all add-hover-fx"
						title="<?php echo __('click here for more details about ', 'event_espresso') . $event_name; ?>"
						href="<?php echo $registration_url; ?>"
						>
<?php echo __('click for more details', 'event_espresso'); ?>
				</a>
			</div>

			<div class="clear-float"></div>
		</div>
	</div>

<?php } ?>


	<div class="clear-float"><br /></div>

<?php do_action('AHEE_after_event_list_listing', $event_id); ?>
</div>
<!-- / .event-display-boxes -->
