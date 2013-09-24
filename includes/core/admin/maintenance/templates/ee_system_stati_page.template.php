<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function ee_resurse_into_array($data){
	if(is_object($data)){
		$data = (array)$data;
	}
	if(is_array($data)){
		if(EEH_Array::is_associative_array($data)){
			?>
<table class='widefat'><tbody>
	<?php foreach($data as $data_key => $data_values){
		?><tr><td><?php echo $data_key?></td><td><?php ee_resurse_into_array($data_values);?></td></tr>
	<?php }?>
	</tbody></table>
<?php
		}else{
			?>
	<ul>
	<?php foreach($data as $datum){
		echo "<li>";ee_resurse_into_array($datum);echo "</li>";
	}?>
	</ul>
<?php
		}
	}else{//simple value
		echo $data;
	}
}
?>
<h1><?php _e("System Status", "event_espresso");?></h1>
<div class='main'>
	<h3><?php _e("Raw System Data", "event_espresso");?></h3>
	<textarea style='width:100%;height:100px;overflow-y:scroll'><?php print_r($system_stati)?></textarea>
	<table class='widefat'>
<?php	
	foreach($system_stati as $status_category_slug => $data){
		if(is_object($data)){
			$data = (array)$data;
		}
		?>
		<thead><tr><th colspan=2><?php echo $status_category_slug?></th></tr></thead>
		<tbody>
			<?php if(is_array($data)){ foreach($data as $data_key => $data_values){?>
			<tr><td><?php echo $data_key?></td>
				<td><?php ee_resurse_into_array($data_values)?></td></tr>
			<?php }?>
		</tbody>
		<?php
			}else{
				?><tbody><tr><td colspan=2><?php echo $data?></td></tr></tbody>
		<?php
			}
	}
?>
		</table>
</div>