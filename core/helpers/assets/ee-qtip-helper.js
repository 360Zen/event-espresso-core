jQuery(document).ready(function($) {

	//load all qtips
	if ( EE_QTIP_HELPER.qtips.length > 0 ) {
		var parse;

		console.log(EE_QTIP_HELPER);
		//loop through the qtips and set them up.
		$.each(EE_QTIP_HELPER.qtips, function(i, v ) {
			//make sure that content is refernecing content div
			console.log(v);
			v.options.content.text = $('#' + v.content_id);

			if ( typeof( v.options.position.target) !== 'undefined' && v.options.position.target.indexOf("jQuery::") > -1 ) {
				parse = v.options.position.target.replace('jQuery::', '');
				v.options.position.target = $(parse);
			}

			if ( typeof( v.options.position.container) !== 'undefined' && v.options.position.container !== false && v.options.position.container.indexOf('jQuery::') > -1 ) {
				parse = v.options.position.container.replace('jQuery::', '');
				v.options.position.container = $(parse);
			}

			if ( typeof( v.options.hide.target) !== 'undefined' && v.options.hide.target !== false && v.options.hide.target.indexOf('jQuery::') > -1 ) {
				parse = v.options.hide.target.replace('jQuery::', '');
				v.options.hide.target = $(parse);
			}

			$(v.target).qtip(v.options);
		});
	}

	//that's it. seriously.

});