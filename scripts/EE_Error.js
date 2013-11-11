jQuery(document).ready(function($) {
	
	var message = $('#ee-error-message');
	var target;
	
	if ( message.length ) {
		$('#ee-error-message').remove();
		if ( $('#content').size() ) {
			target = $('#content');
		} else if ( $('#screen-meta-links').size() ) {
			target = $('#screen-meta-links');
		} else if ( $('#wpbody-content').size() ) {
			target = $('#wpbody-content');
		}
		
		$( target ).after( message );
		if(ee_settings.wp_debug !='1')//set from wp_localize_script in php
			$( '.ee-error-trace-dv').hide();
			
		$('body').on( 'click', '.display-ee-error-trace-lnk', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var traceTable = '#' + $(this).attr('rel');
			$( traceTable ).slideToggle();
		});
	}

	
});
