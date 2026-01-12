( function( $ ) {

	$( document ).ready(function() {

		var intervalTextbox = $( 'input[name="el_auto_delete_logs[interval]"]' );

		if ( $.trim( intervalTextbox.val() ) === '' ) {
			intervalTextbox.val( '365' );
		}
		// Allow only numbers.
		intervalTextbox.numeric( { decimal: false, negative: false } );

	});

})( jQuery );