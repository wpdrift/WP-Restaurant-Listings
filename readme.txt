=== WP Restaurant Listings ===
Contributors: WPdrift, upnrunn, kishores, bhoot
Tags: restaurant manager, restaurant listing, restaurant board, restaurant management, restaurant lists, restaurant list, restaurant
Requires at least: 4.7
Tested up to: 5.2.2
Stable tag: 1.0.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Manage restaurant listings from the WordPress admin panel, and allow users to post restaurants directly to your site.

== Description ==

WP Restaurant Listings is a lightweight restaurant listing plugin for adding restaurant listing functionality to your WordPress site. Being shortcode based, it can work with any theme (given a bit of CSS styling) and is really simple to setup.

= Features =

* Add, manage, and categorize restaurant listings using the familiar WordPress UI.
* Searchable & filterable ajax powered restaurant listings added to your pages via shortcodes.
* Frontend forms for guests and registered users to submit & manage restaurant listings.
* Allow restaurant listers to preview their listing before it goes live. The preview matches the appearance of a live restaurant listing.
* Searches also display RSS links to allow restaurant seekers to be alerted to new restaurants matching their search.
* Allow logged in listers to view, edit, or delete their active restaurant listings.
* Developer friendly code — Custom Post Types & template files.

The plugin comes with several shortcodes to output restaurants in various formats, and since it's built with Custom Post Types you are free to extend it further through themes.

[Read more about WP Restaurant Listings](https://wpdrift.com/restaurants/).

= Documentation =

Documentation for the core plugin and add-ons can be found [on the docs site here](https://wpdrift.com/docs/restaurants/). Please take a look before requesting support because it covers all frequently asked questions!

= Support =

Use the WordPress.org forums for community support where we try to help all users. If you spot a bug, you can log it (or fix it) on [Github](https://github.com/wpdrift/WP-Restaurant-Listings) where we can act upon them more efficiently.

If you need help with one of our add-ons, [please raise a ticket at our help desk](http://wpdrift.com/).

If you want help with a customization, please consider hiring a developer! [https://upnrunn.com/services/](https://upnrunn.com/services/) is a good place to start.

== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "WP Restaurant Listings" and click Search Plugins. Once you've found the plugin you can view details about it such as the point release, rating, and description. Most importantly, of course, you can install it by clicking _Install Now_.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your web server via your favorite FTP application.

* Download the plugin file to your computer and unzip it
* Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's `wp-content/plugins/` directory.
* Activate the plugin from the Plugins menu within the WordPress admin.

= Getting started =

Once installed:

1. Create a page called "restaurants" and inside place the `[restaurants]` shortcode. This will list your restaurants.
2. Create a page called "submit restaurant" and inside place the `[submit_restaurant_form]` shortcode if you want front-end submissions.
3. Create a page called "restaurant dashboard" and inside place the `[restaurant_dashboard]` shortcode for logged in users to manage their listings.

**Note when using shortcodes**, if the content looks blown up/spaced out/poorly styled, edit your page and above the visual editor click on the 'text' tab. Then remove any 'pre' or 'code' tags wrapping your shortcode.

For more information, [read the documentation](https://wpdrift.com/docs/article-categories/restaurants/).

== Frequently Asked Questions ==

= How do I setup WP Restaurant Listings? =
View the getting [installation](https://wpdrift.com/docs/knowledge-base/installation-guide/) and [setup](https://wpdrift.com/docs/knowledge-base/setting-up-wp-restaurant-listings/) guide for advice getting started with the plugin. In most cases it's just a case of adding some shortcodes to your pages!

= Can I use WP Restaurant Listings without frontend restaurant submission? =
Yes! If you don't setup the [submit_restaurant_form] shortcode, you can just post from the admin backend.

= How can I customize the restaurant submission form? =
There are three ways to customize the fields in WP Restaurant Listings;

1. For simple text changes, using a localisation file or a plugin such as https://wordpress.org/plugins/say-what/
2. For field changes, or adding new fields, using functions/filters inside your theme's functions.php file: [https://wpdrift.com/docs/knowledge-base/editing-restaurant-submission-fields/](https://wpdrift.com/docs/knowledge-base/editing-restaurant-submission-fields/)
3. Use a 3rd party plugin which has a UI for field editing.

If you'd like to learn about WordPress filters, here is a great place to start: [https://pippinsplugins.com/a-quick-introduction-to-using-filters/](https://pippinsplugins.com/a-quick-introduction-to-using-filters/)

== Screenshots ==

1. The submit restaurant form.
2. Submit restaurant preview.
3. A single restaurant listing.
4. Restaurant dashboard.
5. Restaurant Listings and filters.
6. Restaurant Listings in admin.

== Changelog ==

= 1.0.2 =
* Replace Chosen with Select2 for enhanced dropdown handling and better mobile support.
* Fix: Undefined variable: restaurant_image_gallery

= 1.0.1 =
* Enhancement: In WP Admin just strip tags from custom field labels instead of escaping them.
* Fix: When using Polylang, only the active language's restaurant listings will be displayed in the [restaurants] shortcode.
* Enhancement: Sanitize field input using different strategies.
* Change: Updates account-signin.php template to warn users email will be confirmed only if that is enabled.
* Enhancement: When retrieving listings in [restaurants] shortcode, setting orderby to rand_featured will still place featured listings at the top.
* Dev: Runs new actions (restaurant_listings_recent_restaurants_widget_before and restaurant_listings_recent_restaurants_widget_after) inside Recent Restaurants widget.
* Dev: Change get_the_restaurant_types() to return an empty array when restaurant types are disabled.
* Enhancement: Update language for setup wizard with more clear descriptions.
* Fix: Prevent duplicate attachments to restaurant listing posts for non-image media.
* Fix: PHP error on registration form due to missing placeholder text.
* Fix: Properly reset category selector on [restaurants] shortcode
* Fix: Show restaurant listing's published date in localized format
* Dev: Adds versions to template files so it is easier to tell when they are updated.
* Dev: Adds a new `wprl_notify_new_user` action that allows you to override default behavior.

= 1.0.0 =
* First stable release.
