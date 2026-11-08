/* global jQuery, wp, newspaperArchive */
jQuery( function ( $ ) {
	var frame;

	$( '#newspaper_archive_pick' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( ! frame ) {
			frame = wp.media( {
				title: newspaperArchive.title,
				button: { text: newspaperArchive.button },
				library: { type: 'application/pdf' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var file = frame.state().get( 'selection' ).first().toJSON();
				$( '#newspaper_archive_pdf_id' ).val( file.id );
				$( '#newspaper_archive_pdf_name' ).text( file.filename );
			} );
		}
		frame.open();
	} );

	$( '#newspaper_archive_remove' ).on( 'click', function () {
		$( '#newspaper_archive_pdf_id' ).val( '' );
		$( '#newspaper_archive_pdf_name' ).text( newspaperArchive.none );
	} );
} );
