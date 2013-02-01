jQuery(document).ready(function($) {

	$.ajaxSetup ({ cache: false });
	// clear firefox and safari cache
	$(window).unload( function() {}); 
	
	var overlay = $( "#espresso-admin-page-overlay-dv" );
	window.eeTimeout = false;
	window.overlay = overlay;
	
	/*
	Floating "Save" and "Save & Close" buttons
	 */
	$(window).scroll(function() {
		var scrollTop = $(this).scrollTop();
		var offset = $('#event_editor_major_buttons_wrapper').offset();
		if(offset !== null) {
			if ( (scrollTop+25) > offset.top ) {
				$('#event-editor-floating-save-btns').removeClass('hidden');
				$('#event_editor_major_buttons_wrapper .button-primary').addClass('hidden');
			} else {
				$('#event-editor-floating-save-btns').addClass('hidden');
				$('#event_editor_major_buttons_wrapper .button-primary').removeClass('hidden');
			}
		}
	});



	/*
	Tabs for Messages box on Event Editor Page
	 */
	$('.nav-tab-wrapper', '.ee-nav-tabs').on('click', '.nav-tab', function(e) {
		e.preventDefault();
		var content_id = $(this).attr('rel');
		//first set all content as hidden and other nav tabs as not active
		$('.ee-nav-tabs .nav-tab-content').hide();
		$('.ee-nav-tabs .nav-tab').removeClass('nav-tab-active');
		//set new active tab
		$(this).addClass('nav-tab-active');
		$('#'+content_id).show();
	});




	/*
	generates background overlay for a lightbox affect
	 */
	window.position_overlay = function position_overlay() {
		var dcmntWidth = parseInt($(document).width() );
		var dcmntHeight = parseInt($(document).height() );
		$(window).scrollTop(0);
		var ovrParOff = overlay.parent().offset();
		var ovrlyTop = ovrParOff.top * (-1);
		var ovrlyLeft = ovrParOff.left * (-1);
		overlay.css({ 'top' : ovrlyTop, 'left' : ovrlyLeft, 'width' : dcmntWidth, 'height' : dcmntHeight }).fadeIn('fast').addClass('active');
	}


	window.position_dialog = function position_dialog() {
		var wndwWidth = parseInt( $(window).width() );
		var wndwHeight = parseInt( $(window).height() );		
		var scrllTp = $('html').scrollTop();
		var parOff = dialog.parent().offset();
		var dialogTop =  ( wndwHeight / 10 ) - parOff.top + scrllTp;
		var dialogLeft = ( wndwWidth / 4 - parOff.left );
		var dialogWidth = wndwWidth / 2;
		dialog.css({ 'top' : dialogTop, 'left' : dialogLeft, 'width' : dialogWidth }).fadeIn('fast');		
	}


	window.doneResizing = function doneResizing(){
		if ( overlay.hasClass('active') ) {
			position_overlay( /*$( "#admin-page-overlay-dv" ), false*/ ); 
			position_dialog( /*$( "#txn-admin-apply-payment-dv" )*/ ); 
			eeTimeout = false;	
		}
	}



	$(window).resize(function(){	
	 if( eeTimeout !== false) {
		    clearTimeout(eeTimeout);
		}
		eeTimeout = setTimeout(doneResizing, 200);
	});	
	

	window.do_before_admin_page_ajax = function do_before_admin_page_ajax() {
		// stop any message alerts that are in progress
		$('#message').stop().hide();
		// spinny things pacify the masses
		var st = $('html').scrollTop();
		var po = $('#espresso-admin-page-ajax-loading').parent().offset();		
		var mal_top = ( st+( parseInt( $(window).height() )/5 )-po.top ) - 15;
		var ww = $('#espresso-admin-page-ajax-loading').parent().width();
		var mal_left = ( ww/2 ) -15;
		//alert( 'mal_top = ' + mal_top + '\n' + 'mal_left = ' + mal_left );
		$('#espresso-admin-page-ajax-loading').css({ 'top' : mal_top, 'left' : mal_left }).show();	
	}		
	


	window.show_admin_page_ajax_msg = function show_admin_page_ajax_msg( response, beforeWhat, closeModal ) {
			
		$('#espresso-admin-page-ajax-loading').fadeOut('fast');
		//alert( response.toSource() );
		if (( response.success != undefined && response.success != '' ) || ( response.errors != undefined && response.errors != '' )) {
		
			if ( closeModal == undefined ) {
				closeModal = false;
			}

			var fadeaway = true;

			if ( response.success != undefined && response.success != '' ) {
				msg = '<div id="message" class="updated hidden"><p>' + response.success + '</p></div>';
				//closeModal = true;
			}
		
			if ( response.errors != undefined && response.errors != '' ) {
				msg = '<div id="message" class="error hidden"><p>' + response.errors + '</p></div>';
				//closeModal = false;
				fadeaway = false;
			}
			
			if ( beforeWhat == undefined ) {
				beforeWhat = '#post-body-content';
			}
			
			// display message
			$( beforeWhat ).before( msg );
			if ( fadeaway == true ) {
				$('#message').removeClass('hidden').show().delay(8000).fadeOut();
//				$('#message').removeClass('hidden').show().delay(8000).fadeOut( function(){
//						if ( closeModal ) {
//							overlay.trigger('click');
//						}
//				});
			} else {
				$('#message').removeClass('hidden').show();
//				$('#message').removeClass('hidden').show().delay(8000).queue( function() {
//						if ( closeModal ) {
//							overlay.trigger('click');
//						}
//				});
			}

		} 

	}


	// add functions to global scope
//	window.overlay = overlay;
//	window.eeTimeout = eeTimeout;
//	window.position_overlay = position_overlay;
//	window.position_dialog = position_dialog;
//	window.doneResizing = doneResizing;
//	window.do_before_admin_page_ajax = do_before_admin_page_ajax;
//	window.show_admin_page_ajax_msg = show_admin_page_ajax_msg;
	
});

