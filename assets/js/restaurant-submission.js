/* global restaurant_listings_restaurant_submission */
jQuery(document).ready(function($) {
	$('body').on('click', '.restaurant-listings-remove-uploaded-file', function() {
		$(this).closest('.restaurant-listings-uploaded-file').remove();
		return false;
	});

	// Timepicker
	$('.timepicker').timepicker({
		timeFormat: restaurant_listings_restaurant_submission.time_format,
		noneOption: {
			label: restaurant_listings_restaurant_submission.i18n_closed,
			value: restaurant_listings_restaurant_submission.i18n_closed
		}
	});
});
