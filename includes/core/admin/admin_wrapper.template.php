<div class="wrap espresso-admin">

	<div id="icon-options-event" class="icon32"></div>	
		
	<h2><?php esc_attr_e( 'Event Espresso', 'event_espresso' );?>&nbsp;-&nbsp;<?php  echo $admin_page_title; ?></h2>
	<div id="ajax-notices-container"></div>
	
	<?php echo $nav_tabs; ?>


<?php 
	do_action( 'AHEE_before_admin_page_content' );
	echo $before_admin_page_content;
	echo $admin_page_content; 
	echo $after_admin_page_content;
	do_action( 'AHEE_after_admin_page_content' );
?>

</div>
