<div style="padding:1em;">	

	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th><label for="PRT_ID"><?php _e('Type', 'event_espresso'); ?></label></th>
				<td>
					<?php echo EE_Form_Fields::select_input('PRT_ID', $price_types, $price->type(), 'id="PRT_ID"'); ?>
					<p class="description"><?php _e('Whether this is an Event Price, Discount, Surcharge, or Tax. Default items will apply to ALL new events you create.', 'event_espresso'); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th><label for="PRC_name"><?php _e('Name', 'event_espresso'); ?></label></th>
				<td>
					<input class="regular-text" type="text" id="PRC_name" name="PRC_name" value="<?php echo html_entity_decode( stripslashes( $price->name() ), ENT_QUOTES, 'UTF-8' ); ?>"/>
					<p class="description"><?php _e('The name that site visitors will see for this Price.', 'event_espresso'); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th><label for="PRC_desc"><?php _e('Description', 'event_espresso'); ?></label></th>
				<td>
					<textarea class="regular-text" id="PRC_desc" name="PRC_desc" cols="70" rows="1" ><?php
					 echo html_entity_decode( stripslashes( $price->desc() ), ENT_QUOTES, 'UTF-8' ); 
					?></textarea><br/>
					<p class="description"><?php _e('A brief description for this Price. More for your benefit, as it is currently not displayed to site visitors.', 'event_espresso'); ?></p>
				</td>							
			</tr>
			<tr valign="top">
				<th><label for="PRC_amount"><?php _e('Amount', 'event_espresso'); ?></label></th>
				<td>
					<input class="small-text" type="text" id="PRC_amount" name="PRC_amount" value="<?php echo $price->amount(); ?>"/>
					<p class="description"><?php _e('The dollar or percentage amount for this Price.', 'event_espresso'); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th><label><?php _e('Active', 'event_espresso'); ?></label></th>
				<td>
					<?php $yes_checked = $price->is_active() ? ' checked="checked"' : ''; ?>
					<label style="margin-right:15px;"><input type="radio" name="PRC_is_active" value="1"<?php echo $yes_checked;?> style="margin-right:5px;"><?php _e('Yes', 'event_espresso');?></label>
					<?php $no_checked = $price->is_active() ? '' : ' checked="checked"'; ?>
					<label style="margin-right:15px;"><input type="radio" name="PRC_is_active" value="0"<?php echo $no_checked;?> style="margin-right:5px;"><?php _e('No', 'event_espresso');?></label>
					<p class="description"><?php _e('Whether this Price is currently being used and displayed on the site.', 'event_espresso'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>

	<div class="clear"></div>
	
</div>