<tr valign="top" class="ticket-row<?php echo $ticket_archive_class; ?>" id="display-ticketrow-<?php echo $tkt_row; ?>">
	<td></td>
	<td><span class="ticket-display-row-TKT_name"><?php echo $TKT_name; ?></span></td>
	<td><span class="ticket-display-row-TKT_start_date"><?php echo $TKT_start_date; ?></span></td>
	<td><span class="ticket-display-row-TKT_end_date"><?php echo $TKT_end_date; ?></span></td>
	<td><span class="ticket-display-row-TKT_status"><?php echo $TKT_status; ?></span></td>
	<td><span class="ticket-display-row-TKT_price"><?php echo $TKT_price; ?></span></td>
	<td><span class="ticket-display-row-TKT_qty"><?php echo $TKT_qty; ?></span></td>
	<td><span class="ticket-display-row-TKT_sold"><?php echo $TKT_sold; ?></span></td>
	<td><span class="gear-icon clickable" data-ticket-row="<?php echo $tkt_row; ?>" data-context="ticket"></span><span class="<?php echo $clone_icon; ?>" data-ticket-row="<?php echo $tkt_row; ?>" data-context="ticket"></span><span class="<?php echo $trash_icon; ?>" data-ticket-row="<?php echo $tkt_row; ?>" data-context="ticket"<?php echo $trash_hidden; ?>></span></td>
</tr>
<tr id="edit-ticketrow-<?php echo $tkt_row; ?>" class="edit-ticket-row">
	<td colspan="9">
		<fieldset id="fieldset-edit-ticketrow-<?php echo $tkt_row; ?>" class="ticket-fieldset"<?php echo $display_edit_tkt_row; ?>>
			<legend><?php _e('Edit Ticket', 'event_espresso'); ?></legend>
			<input type="hidden" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_ID]" class="edit-ticket-TKT_ID" value="<?php echo $TKT_ID; ?>">
			<input type="hidden" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_row]" class="edit-ticket-TKT_row" value="<?php echo $tkt_row; ?>">
			<input type="text" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_name]" class="edit-ticket-TKT_name ee-large-text-inp" placeholder="Ticket Title" value="<?php echo $TKT_name; ?>">
			<div class="total-price-container"><?php printf( __('Total Final Price: %s', 'event_espresso'), '<span class="ticket-price-amount">' . $TKT_price . '</span>'); ?> </div>
			<textarea name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_description]" class="edit-ticket-TKT_description ee-full-textarea-inp" placeholder="Ticket Description"><?php echo $TKT_description; ?></textarea>
			<div class="basic-ticket-container">
				<h5 class="tickets-heading"><?php _e('Ticket Details', 'event_espresso'); ?></h5>
				<table class="basic-ticket-info">
					<thead>
						<tr valign="bottom">
							<td><?php _e('Goes on Sale', 'event_espresso'); ?></td>
							<td><?php _e('Sell Until', 'event_espresso'); ?></td>
							<td><?php _e('Quantity', 'event_espresso'); ?></td>
							<td><?php _e('#Uses', 'event_espresso'); ?></td>
							<td><?php _e('Min', 'event_espresso'); ?></td>
							<td><?php _e('Max', 'event_espresso'); ?></td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><input id="edit-ticket-TKT_start_date-<?php echo $tkt_row; ?>" type="text" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_start_date]" class="edit-ticket-TKT_start_date ee-text-inp ee-datepicker" value="<?php echo $TKT_start_date; ?>" data-context="start-ticket" data-date-field-context="#edit-ticketrow-<?php echo $tkt_row; ?>" data-related-field=".edit-ticket-TKT_end_date" data-next-field=".edit-ticket-TKT_end_date"<?php echo $disabled; ?>></td>
							<td><input id="edit-ticket-TKT_end_date-<?php echo $tkt_row; ?>" type="text" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_end_date]" class="edit-ticket-TKT_end_date ee-text-inp ee-datepicker" value="<?php echo $TKT_end_date; ?>" data-context="end-ticket" data-date-field-context="#edit-ticketrow-<?php echo $tkt_row; ?>" data-related-field=".edit-ticket-TKT_start_date" data-next-field=".edit-ticket-TKT_qty"<?php echo $disabled; ?>></td>
							<td><input type="text" class="edit-ticket-TKT_qty ee-small-text-inp" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_qty]" value="<?php echo $TKT_qty; ?>"<?php echo $disabled; ?>></td>
							<td><input type="text" class="edit-ticket-TKT_uses ee-small-text-inp" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_uses]" value="<?php echo $TKT_uses; ?>"<?php echo $disabled; ?>></td>
							<td><input type="text" class="edit-ticket-TKT_min ee-small-text-inp" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_min]" value="<?php echo $TKT_min; ?>"<?php echo $disabled; ?>></td>
							<td><input type="text" class="edit-ticket-TKT_max ee-small-text-inp" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_max]" value="<?php echo $TKT_max; ?>"<?php echo $disabled; ?>></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="price-table-container">
				<h5 class="tickets-heading"><?php _e('Base Ticket Price and Price Modifiers', 'event_espresso'); ?></h5>
				<table class="price-table">
					<thead>
						<tr>
							<td><?php _e('Price Type', 'event_espresso'); ?></td>
							<td><?php _e('Name', 'event_espresso'); ?></td>
							<td><?php _e('Description', 'event_espresso'); ?></td>
							<td></td>
							<td><?php _e('Amount', 'event_espresso'); ?></td>
							<td></td>
							<td></td>
						</tr>
					</thead>
					<tbody class="ticket-price-rows">
						<?php echo $ticket_price_rows; ?>
					</tbody>
					<tfoot>
						<tr class="price-subtotal-row TKT-taxes-display"<?php echo $display_subtotal; ?>>
							<td colspan="4">
								<span class="TKT-taxable-subtotal-label"><strong><?php _e('Subtotal', 'event_espresso'); ?></strong></span>
							</td>
							<td>
								<span class="TKT-taxable-subtotal-amount-display"><?php echo $TKT_subtotal_amount_display; ?></span>
								<input type="hidden" value="<?php echo $TKT_subtotal_amount; ?>" name="subtotal_amount_<?php echo $tkt_row; ?>" class="TKT-taxable-subtotal-amount">
							</td>
							<td></td>
							<td></td>
						</tr>
						<?php echo $tax_rows; ?>
						<tr class="price-total-row">
							<td>
								<input class="TKT-taxable-checkbox" id="edit-ticket-TKT_taxable-<?php echo $tkt_row; ?>" type="checkbox" name="<?php echo $edit_tickets_name;?>[<?php echo $tkt_row; ?>][TKT_taxable]" value="1"<?php echo $TKT_taxable; ?><?php echo $disabled; ?>><label for="edit-ticket-TKT_taxable-<?php echo $tkt_row; ?>"> <?php _e('This ticket is taxable.', 'event_espresso'); ?>
							</td>
							<td colspan="3">	
								<strong><?php _e('Total', 'event_espresso'); ?></strong>
							</td>
							<td>
								<span id="price-total-amount-<?php echo $tkt_row; ?>"><?php echo $TKT_price; ?></span>
								<input type="hidden" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_price]" class="edit-ticket-TKT_price" value="<?php echo $TKT_price_amount; ?>">
							</td>
							<td></td>
							<td><input type="hidden" name="price_total_rows_ticket[<?php echo $tkt_row; ?>]" id="price-total-rows-<?php echo $tkt_row; ?>" value="<?php echo $total_price_rows; ?>"></td>
						</tr>
					</tfoot>
				</table>
			</div>
			<div style="clear:both"></div>
			<h5 class="tickets-heading"><?php _e('Event Datetimes', 'event_espresso'); ?></h5>
			<p><?php _e('This ticket will be usable (allow entrance) for the following selected event datetimes (click to select):', 'event_espresso'); ?></p>
			<ul class="datetime-tickets-list">
				<?php echo $ticket_datetimes_list; ?>
			</ul>
			<div class="save-cancel-button-container">
				<label for="edit-ticket-TKT_is_default_selector"><?php _e('use this new ticket as a default ticket for any new events', 'event_espresso'); ?></label>
				<input type="checkbox" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_is_default_selector]" class="edit-ticket-TKT_is_default_selector" value="1"<?php echo $disabled; ?>>
				<input type="hidden" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TKT_is_default]" class="edit-ticket-TKT_is_default" value="<?php echo $TKT_is_default; ?>"> 
				<button class="button-primary ee-save-button" data-context="ticket" data-ticket-row="<?php echo $tkt_row; ?>"><?php _e('Update Ticket', 'event_espresso'); ?></button><button class="button-secondary ee-cancel-button" data-context="ticket" data-ticket-row="<?php echo $tkt_row; ?>"><?php _e('Cancel', 'event_espresso'); ?></button>
			</div>
			<!-- these hidden inputs are for tracking changes in dtts attached to tickets during a js session -->
			<input type="hidden" name="starting_ticket_datetime_rows[<?php echo $tkt_row; ?>]" id="starting-ticket-datetime-rows-<?php echo $tkt_row; ?>" value="<?php echo $starting_ticket_datetime_rows; ?>" class="starting-ticket-datetime-rows">
			<input type="hidden" name="ticket_datetime_rows[<?php echo $tkt_row; ?>]" class="ticket-datetime-rows" id="ticket-datetime-rows-<?php echo $tkt_row; ?>" value="<?php echo $ticket_datetime_rows; ?>">

			<!-- these hidden inputs are for tracking changes in prices attached to tickets during a js session -->
			<input type="hidden" name="ticket_price_ids[<?php echo $tkt_row; ?>][]" id="ticket-price-ids-<?php echo $tkt_row; ?>" class="ticket-price-ids" value="<?php echo $existing_ticket_price_ids; ?>">
			<input type="hidden" name="<?php echo $edit_tickets_name; ?>[<?php echo $tkt_row; ?>][TTM_ID]" class="ticket-template-id" value="<?php echo $ticket_template_id; ?>">
		</fieldset>
	</td>
</tr>
<?php 
/**
 * template args in use
 *
 * $tkt_row
 * $TKT_name
 * $TKT_start_date
 * $TKT_end_date
 * $TKT_status
 * $TKT_price
 * $TKT_qty
 * $TKT_uses
 * $TKT_min
 * $TKT_max
 * $TKT_sold
 * $TKT_ID
 * $TKT_description
 * $TKT_is_default
 * $TKT_price_rows
 * $disabled
 * $ticket_archive_class
 * $trash_icon
 * $trash_hidden
 * $clone_icon
 * $display_edit_tkt_row
 * 
 * $TKT_taxable
 * $display_subtotal
 * $TKT_subtotal_amount
 * $tax_rows
 * 
 * $total_price_rows
 * $ticket_datetimes_list
 * $starting_ticket_datetime_rows (datetimes attached to ticket on page load)
 * $existing_ticket_price_ids;
 * $ticket_template_id;
 */