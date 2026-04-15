( function( $ ) {
	$( document ).ready( function() {
		$( '.el-datepicker' ).datepicker({
			changeMonth: true,
			changeYear: true,
			dateFormat: 'yy-mm-dd'
		});

		$( document ).on( 'click', '#thickbox-footer-close', function( event ) {
			event.preventDefault();
			tb_remove();
		});

		$( '.el-help' ).tooltip({
			content: function() { return $( this ).prop( 'title' ); },
			position: { my: 'center top', at: 'center bottom+10', collision: 'flipfit' },
			hide: { duration: 100 },
			show: { duration: 100 }
		});

		$( document ).on( 'click', '.el-resend-email', function( event ) {
			event.preventDefault();
			var $link = $( this );
			if ( ! confirm( 'Resend this email?' ) ) {
				return;
			}
			$.get( $link.attr( 'href' ), function( response ) {
				if ( response.success ) {
					alert( response.data );
				} else {
					alert( 'Error: ' + response.data );
				}
			}).fail( function() {
				alert( 'Request failed.' );
			});
		});

		// Initialize tabs inside thickbox when they appear.
		$( document ).on( 'tb_init', function() {
			var checkTabs = setInterval( function() {
				var $tabs = $( '#tabs' );
				if ( $tabs.length && ! $tabs.hasClass( 'ui-tabs' ) ) {
					var activeTabIndex = parseInt( $tabs.find( 'ul' ).data( 'active-tab' ) );
					activeTabIndex = isNaN( activeTabIndex ) ? 1 : activeTabIndex;
					$tabs.tabs({ active: activeTabIndex });
					clearInterval( checkTabs );
				}
			}, 100 );
			setTimeout( function() { clearInterval( checkTabs ); }, 5000 );
		});
	});
})( jQuery );
