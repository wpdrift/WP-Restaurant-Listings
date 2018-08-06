/* global restaurant_listings_vars */

var drop;

jQuery(function($) {

	$('body')
		// Tabs
		.on('init', '.rl-tabs-wrapper, .restaurant-listings-tabs', function() {
			$('.rl-tab, .restaurant-listings-tabs .panel:not(.panel .panel)').hide();

			var hash = window.location.hash;
			var url = window.location.href;
			var $tabs = $(this).find('.rl-tabs, ul.tabs').first();

			if (hash.toLowerCase().indexOf('comment-') >= 0 || hash === '#reviews' || hash === '#tab-reviews') {
				$tabs.find('li.reviews_tab a').click();
			} else if (url.indexOf('comment-page-') > 0 || url.indexOf('cpage=') > 0) {
				$tabs.find('li.reviews_tab a').click();
			} else if (hash === '#tab-additional_information') {
				$tabs.find('li.additional_information_tab a').click();
			} else {
				$tabs.find('li:first a').click();
			}
		})
		.on('click', '.rl-tabs li a, ul.tabs li a', function(e) {
			e.preventDefault();
			var $tab = $(this);
			var $tabs_wrapper = $tab.closest('.rl-tabs-wrapper, .restaurant-listings-tabs');
			var $tabs = $tabs_wrapper.find('.rl-tabs, ul.tabs');

			$tabs.find('li').removeClass('active');
			$tabs_wrapper.find('.rl-tab, .panel:not(.panel .panel)').hide();

			$tab.closest('li').addClass('active');
			$tabs_wrapper.find($tab.attr('href')).show();
		})
		// Review link
		.on('click', 'a.restaurant-listings-review-link', function() {
			$('.reviews_tab a').click();
			return true;
		})
		// Star ratings for comments
		.on('init', '#rating', function() {
			$('#rating').hide().before('<p class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></p>');
		})
		.on('click', '#respond p.stars a', function() {
			var $star = $(this),
				$rating = $(this).closest('#respond').find('#rating'),
				$container = $(this).closest('.stars');

			$rating.val($star.text());
			$star.siblings('a').removeClass('active');
			$star.addClass('active');
			$container.addClass('selected');

			return false;
		})
		.on('click', '#respond #submit', function() {
			var $rating = $(this).closest('#respond').find('#rating'),
				rating = $rating.val();

		})
		.one('click', '.menu_tab', function() {
			$('.restaurant-menu').slick('getSlick').refresh();
		});

	// Init Tabs and Star Ratings
	$('.rl-tabs-wrapper, .restaurant-listings-tabs, #rating').trigger('init');

	// Highlight today from the restaurant opening hours
	$('.business-hours-drop-wrapper').each(function(element, index) {
		let that = $(this);

		let businessHours = that.find('.business-hour').toArray();

		let days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		let today = new Date();
		let dayName = days[today.getDay()];

		let todayHours = businessHours.find(function(element, index, array) {
			return element.getAttribute('data-day') === dayName;
		});

		let todayOpeningHours;

		if (typeof todayHours !== 'undefined') {
			todayHours.classList.add('restaurant-bold');
			todayOpeningHours = todayHours.childNodes[3].innerHTML;
		} else {
			todayOpeningHours = ' - ' + restaurant_listings_vars.l10n.close;
		}

		that.find('.today.business-hours > .business-hour-time')[0].innerHTML = todayOpeningHours;

		drop = new Drop({
			target: that.find('.business-hours-drop-btn')[0],
			content: that.find('.business-hours-drop-element')[0],
			position: 'bottom left',
			openOn: 'click',
			classes: 'drop-theme-arrows'
		});

	});

	var PhotoSlider = function($target, args) {
		this.$target = $target;
		this.$window = $(window);
		this.flexslider = {
			vars: {}
		};

		// Bind functions to this.
		this.initPhotoslider = this.initPhotoslider.bind(this);
		this.getGridSize = this.getGridSize.bind(this);

		this.initPhotoslider();
	};

	/**
	 * Init Photoslider.
	 */
	PhotoSlider.prototype.initPhotoslider = function() {
		var that = this;
		var $slider = that.$target;

		this.$window.load(function() {
			$slider.slick({
				centerMode: true,
				centerPadding: '60px',
				slidesToShow: 3,
				responsive: [{
						breakpoint: 768,
						settings: {
							arrows: false,
							centerMode: true,
							centerPadding: '40px',
							slidesToShow: 3
						}
					},
					{
						breakpoint: 480,
						settings: {
							arrows: false,
							centerMode: true,
							centerPadding: '40px',
							slidesToShow: 1
						}
					}
				]
			});
		});

		// check grid size on resize event
		this.$window.resize(function() {
			var gridSize = $slider.getGridSize;

			that.flexslider.vars.minItems = gridSize;
			that.flexslider.vars.maxItems = gridSize;
		});
	};

	/**
	 * tiny helper function to add breakpoints
	 */
	PhotoSlider.prototype.getGridSize = function() {
		return (window.innerWidth < 600) ? 2 :
			(window.innerWidth < 900) ? 3 : 4;
	};

	/**
	 * Function to call restaurant_photo_gallery on jquery selector.
	 */
	$.fn.restaurant_photo_slider = function(args) {
		new PhotoSlider(this, args);
		return this;
	};

	/*
	 * Initialize all galleries on page.
	 */
	$('.restaurant-gallery-images').each(function() {
		$(this).restaurant_photo_slider();
	});

	/**
	 * Photo gallery class.
	 */
	var PhotoGallery = function($target, args) {
		this.$target = $target;
		this.$window = $(window);
		this.$images = $('.gallery-preview-image', $target);

		// No images? Abort.
		if (0 === this.$images.length) {
			this.$target.css('opacity', 1);
			return;
		}

		// Make this object available.
		$target.data('photo_gallery', this);

		// Bind functions to this.
		this.initPhotoswipe = this.initPhotoswipe.bind(this);
		this.getGalleryItems = this.getGalleryItems.bind(this);
		this.openPhotoswipe = this.openPhotoswipe.bind(this);
		this.$target.css('opacity', 1);
		this.initPhotoswipe();
	};

	/**
	 * Init PhotoSwipe.
	 */
	PhotoGallery.prototype.initPhotoswipe = function() {
		this.$target.on('click', '.restaurant-gallery__item-trigger', this.openPhotoswipe);
	};

	/**
	 * Get gallery image items.
	 */
	PhotoGallery.prototype.getGalleryItems = function() {
		var $slides = this.$images,
			items = [];

		if ($slides.length > 0) {
			$slides.each(function(i, el) {
				var img = $(el).find('img'),
					large_image_src = img.attr('data-large_image'),
					large_image_w = img.attr('data-large_image_width'),
					large_image_h = img.attr('data-large_image_height'),
					item = {
						src: large_image_src,
						w: large_image_w,
						h: large_image_h,
						title: img.attr('data-caption') ? img.attr('data-caption') : img.attr('title')
					};
				items.push(item);
			});
		}

		return items;
	};

	/**
	 * Open photoswipe modal.
	 */
	PhotoGallery.prototype.openPhotoswipe = function(e) {
		e.preventDefault();

		var pswpElement = $('.pswp')[0],
			items = this.getGalleryItems(),
			eventTarget = $(e.target),
			clicked = 0;

		if (!eventTarget.is('.restaurant-gallery__item-trigger')) {
			clicked = eventTarget;
		}

		var options = $.extend({
			index: $(clicked).index('.restaurant-gallery-images img')
		}, {
			closeOnScroll: false,
			hideAnimationDuration: 0,
			history: false,
			shareEl: false,
			showAnimationDuration: 0
		});

		// Initializes and opens PhotoSwipe.
		var photoswipe = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
		photoswipe.init();
	};

	/**
	 * Function to call restaurant_photo_gallery on jquery selector.
	 */
	$.fn.restaurant_photo_gallery = function(args) {
		new PhotoGallery(this, args);
		return this;
	};

	/*
	 * Initialize all galleries on page.
	 */
	$('.restaurant-gallery-images').each(function() {
		$(this).restaurant_photo_gallery();
	});

	var SlickSlider = function($target, args) {
		this.$target = $target;
		this.$window = $(window);

		// Bind functions to this.
		this.initSlickSlider = this.initSlickSlider.bind(this);
		this.initSlickSlider();
	};

	SlickSlider.prototype.initSlickSlider = function() {
		const $target = this.$target;
		this.$window.load(function() {
			$target.slick({
				lazyLoad: 'ondemand',
				slidesToShow: 1,
				slidesToScroll: 1
			});
		});
	};

	$.fn.restaurant_menu = function(args) {
		new SlickSlider(this, args);
		return this;
	};

	$('.restaurant-menu').each(function() {
		$(this).restaurant_menu();
	});

});

window.onload = () => {
	let restaurant_map = document.getElementById('restaurant-locator-map');
	if (restaurant_map) {
		mapboxgl.accessToken = restaurant_listings_vars.access_token;
		restaurantMapBox.init();
	}
};

const restaurantMapBox = {

	container: 'restaurant-locator-map',
	style: 'mapbox://styles/mapbox/streets-v9',
	zoom: 14,
	lastGeocode: undefined,

	init() {

		this.map = new mapboxgl.Map({
			container: this.container,
			style: this.style,
			zoom: this.zoom
		});

		this.map.on('load', event => this.loadMap());

		navigator.geolocation.getCurrentPosition(location => this.showNearByRestaurant(location));
	},

	loadMap() {

		// Add zoom and rotation controls to the map.
		this.map.addControl(new mapboxgl.NavigationControl());
		this.map.on('dragend', data => this.dragEndOnMap(data));

		this.addGeocoder();
	},

	addGeocoder() {

		this.geocoder = new MapboxGeocoder({
			accessToken: mapboxgl.accessToken
		});

		this.geocoder.on('result', event => this.geocoderResult(event));

		this.map.addControl(this.geocoder, 'top-left');
	},

	geocoderResult(event) {

		// Fix: https://github.com/mapbox/mapbox-gl-geocoder/issues/99
		if (event.result.center.toString() !== this.lastGeocode) {
			let lng = event.result.center[0],
				lat = event.result.center[1];
			this.getNearbyRestaurant(lng, lat, true);
		}

		this.lastGeocode = event.result.center.toString();
	},

	dragEndOnMap(data) {
		const center = data.target.transform._center;
		this.getNearbyRestaurant(center.lng, center.lat);
	},

	showNearByRestaurant(location) {
		let [lng, lat] = [location.coords.longitude, location.coords.latitude];
		this.map.setCenter([lng, lat]);
		this.getNearbyRestaurant(lng, lat);
	},

	fillRestaurantDate(restaurnats) {

		restaurnats.forEach((marker, i) => {
			let el = document.createElement('div'); // Create an img element for the marker
			el.id = 'marker-' + i;
			el.className = 'marker';
			// Add markers to the map at all points
			new mapboxgl.Marker(el, {
					offset: [-28, -46]
				})
				.setLngLat(marker.geometry.coordinates)
				.addTo(this.map);

			el.addEventListener('click', event => this.clickMarkerOnMap(event, marker, i));
		});
	},

	clickMarkerOnMap(event, marker, index) {
		this.flyToStore(marker); // Fly to the point
		this.createPopUp(marker); // Close all other popups and display popup for clicked store
		let activeItem = document.getElementsByClassName('active'); // Highlight listings in sidebar (and remove highlight for all other listings)

		event.stopPropagation();
		if (activeItem[0]) {
			activeItem[0].classList.remove('active');
		}

		let listings = document.getElementById('listings-' + index);
		listings.classList.add('active');
	},

	flyToStore(currentFeature) {
		this.map.flyTo({
			center: currentFeature.geometry.coordinates,
			zoom: 15,
		});
	},

	createPopUp(currentFeature) {
		let popUps = document.getElementsByClassName('mapboxgl-popup');
		if (popUps[0]) popUps[0].remove();

		let popup = new mapboxgl.Popup({
				closeOnClick: false
			})
			.setLngLat(currentFeature.geometry.coordinates)
			.setHTML('<div><a href="#">' + currentFeature.properties.name + '</a></div><p>' + currentFeature.properties.address + '</p>')
			.addTo(this.map);
	},

	buildLocationList(data) {
		for (let i = 0; i < data.length; i++) {
			let currentFeature = data[i];
			let prop = currentFeature.properties;

			let listings = document.getElementById('listings');
			listings = listings.appendChild(document.createElement('div'));
			listings.className = 'item';
			listings.id = 'listings-' + i;

			let button = listings.appendChild(document.createElement('button'));
			button.className = 'card-button';
			button.dataPosition = i;

			let link = listings.appendChild(document.createElement('a'));
			link.href = '#';
			link.className = 'title';

			link.innerHTML = prop.name;

			let details = listings.appendChild(document.createElement('div'));
			details.innerHTML = prop.address;
			if (prop.phone) {
				details.innerHTML += ' &middot; ' + prop.phoneFormatted;
			}

			// Add rounded distance here
			button.addEventListener('click', event => this.clickMarkerOnMap(event, currentFeature, i));
		}
	},

	resetLocationList() {
		document.querySelector('#listings').innerHTML = '';
	},

	getNearbyRestaurant(lng, lat, geocode = false) {
		jQuery.ajax({
			url: restaurant_listings_vars.ajax_url,
			type: 'GET',
			data: {
				action: 'restaurant_listings_locate_restaurant',
				origLat: lat,
				origLng: lng
			},
			success: response => {
				if (geocode) {
					this.setMapBounds(response);
				}

				this.fillRestaurantDate(response);
				this.resetLocationList();
				this.buildLocationList(response);
			}
		});
	},

	setMapBounds(restaurants) {
		// Geographic coordinates of the LineString

		let coordinates = [];
		restaurants.forEach((marker, i) => {
			coordinates.push(marker.geometry.coordinates);
		});

		// Pass the first coordinates in the LineString to `lngLatBounds` &
		// wrap each coordinate pair in `extend` to include them in the bounds
		// result. A variation of this technique could be applied to zooming
		// to the bounds of multiple Points or Polygon geomteries - it just
		// requires wrapping all the coordinates with the extend method.
		let bounds = coordinates.reduce(function(bounds, coord) {
			return bounds.extend(coord);
		}, new mapboxgl.LngLatBounds(coordinates[0], coordinates[0]));

		this.map.fitBounds(bounds, {
			padding: 30
		});
	}
};
