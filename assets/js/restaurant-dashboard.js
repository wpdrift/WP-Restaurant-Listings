/* global restaurant_listings_restaurant_dashboard */
jQuery(document).ready(function($) {

	$('.restaurant-dashboard-action-delete').click(function() {
		return window.confirm( restaurant_listings_restaurant_dashboard.i18n_confirm_delete );
	});

});