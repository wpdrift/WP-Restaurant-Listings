jQuery(document).ready(function($) {
	// Slide toggle
	if ( ! $( 'body' ).hasClass( 'restaurant-application-details-keep-open' ) ) {
		$('.application_details').hide();
	}
	$( '.application_button' ).click(function() {
		$( '.application_details' ).slideToggle();
	});
});
