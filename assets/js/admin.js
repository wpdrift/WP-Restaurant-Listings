/* global restaurant_listings_admin */
jQuery(document).ready(function($) {
	// Tooltips
	$( '.tips, .help_tip' ).tipTip({
		'attribute' : 'data-tip',
		'fadeIn' : 50,
		'fadeOut' : 50,
		'delay' : 200
	});

	// Author
	$( 'p.form-field-author' ).on( 'click', 'a.change-author', function() {
		$(this).closest( 'p' ).find('.current-author').hide();
		$(this).closest( 'p' ).find('.change-author').show();
		return false;
	});

    if ( $.isFunction( $.fn.timepicker ) ) {
        // Timepicker
        $('.timepicker').timepicker({
            timeFormat: restaurant_listings_admin.time_format,
            noneOption: {
                label: restaurant_listings_admin.i18n_closed,
                value: restaurant_listings_admin.i18n_closed
            }
        });
    }

	// Uploading files
	var file_frame;
	var file_target_input;
	var file_target_wrapper;

	$(document).on('click', '.wp_restaurant_listings_add_another_file_button', function( event ){
		event.preventDefault();

		var field_name        = $( this ).data( 'field_name' );
		var field_placeholder = $( this ).data( 'field_placeholder' );
		var button_text       = $( this ).data( 'uploader_button_text' );
		var button            = $( this ).data( 'uploader_button' );
		var view_button       = $( this ).data( 'view_button' );

		$( this ).before( '<span class="file_url"><input type="text" name="' + field_name + '[]" placeholder="' + field_placeholder + '" /><button class="button button-small wp_restaurant_listings_upload_file_button" data-uploader_button_text="' + button_text + '">' + button + '</button><button class="button button-small wp_restaurant_listings_view_file_button">' + view_button + '</button></span>' );
	} );

	$(document).on('click', '.wp_restaurant_listings_view_file_button', function ( event ) {
		event.preventDefault();

		file_target_wrapper = $( this ).closest( '.file_url' );
		file_target_input = file_target_wrapper.find( 'input' );

		var attachment_url = file_target_input.val();

		if ( attachment_url.indexOf( '://' ) > - 1 ) {
			window.open( attachment_url, '_blank' );
		} else {
			file_target_input.addClass( 'file_no_url' );
			setTimeout( function () {
				file_target_input.removeClass( 'file_no_url' );
			}, 1000 );
		}

	});

	$(document).on('click', '.wp_restaurant_listings_upload_file_button', function( event ){
		event.preventDefault();

		file_target_wrapper = $( this ).closest('.file_url');
		file_target_input   = file_target_wrapper.find('input');

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
			file_frame.open();
			return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
			title: $( this ).data( 'uploader_title' ),
			button: {
				text: $( this ).data( 'uploader_button_text' )
			},
			multiple: false  // Set to true to allow multiple files to be selected
		});

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
			// We set multiple to false so only get one image from the uploader
			var attachment = file_frame.state().get('selection').first().toJSON();

			$( file_target_input ).val( attachment.url );
		});

		// Finally, open the modal
		file_frame.open();
	});

    var menu_file_frame;
    var menu_file_target_input;
    var menu_file_target_wrapper;
    var menu_ids = $('#menu_files');

    $(document).on('click', '.wp_restaurant_listings_add_another_menu_file_button', function( event ){
        event.preventDefault();

        var field_name        = $( this ).data( 'field_name' );
        var field_placeholder = $( this ).data( 'field_placeholder' );
        var button_text       = $( this ).data( 'uploader_button_text' );
        var button            = $( this ).data( 'uploader_button' );
        var view_button       = $( this ).data( 'view_button' );

        $( this ).before( '<span class="file_url"><input type="text" name="' + field_name + '[]" placeholder="' + field_placeholder + '" /><button class="button button-small wp_restaurant_listings_upload_menu_file_button" data-uploader_button_text="' + button_text + '">' + button + '</button><button class="button button-small wp_restaurant_listings_view_menu_file_button">' + view_button + '</button></span>' );
    } );

    $(document).on('click', '.wp_restaurant_listings_view_menu_file_button', function ( event ) {
        event.preventDefault();

        menu_file_target_wrapper = $( this ).closest( '.file_url' );
        menu_file_target_input = menu_file_target_wrapper.find( 'input' );

        var attachment_url = menu_file_target_input.val();

        if ( attachment_url.indexOf( '://' ) > - 1 ) {
            window.open( attachment_url, '_blank' );
        } else {
            menu_file_target_input.addClass( 'file_no_url' );
            setTimeout( function () {
                menu_file_target_input.removeClass( 'file_no_url' );
            }, 1000 );
        }

    });

    $(document).on('click', '.wp_restaurant_listings_upload_menu_file_button', function( event ) {
        event.preventDefault();

        menu_file_target_wrapper  = $( this ).closest('.file_url');
        menu_file_target_input    = menu_file_target_wrapper.find('input');

        // If the media frame already exists, reopen it.
        if ( menu_file_frame ) {
            menu_file_frame.open();
            return;
        }

        // Create the media frame.
        menu_file_frame = wp.media.frames.file_frame = wp.media({
            title: $( this ).data( 'uploader_title' ),
            button: {
                text: $( this ).data( 'uploader_button_text' )
            },
            multiple: false  // Set to true to allow multiple files to be selected
        });

        // When an image is selected, run a callback.
        menu_file_frame.on( 'select', function() {
            // We set multiple to false so only get one image from the uploader
            var attachment = menu_file_frame.state().get('selection').first().toJSON();
            var selection = menu_file_frame.state().get( 'selection' );
            var attachment_ids = menu_ids.val();

            selection.map( function( selected_attachment ) {
                selected_attachment = selected_attachment.toJSON();

                if ( selected_attachment.id ) {
                    attachment_ids   = attachment_ids ? attachment_ids + ',' + selected_attachment.id : selected_attachment.id;
                }
            });

            menu_ids.val( attachment_ids );

            $( menu_file_target_input ).val( attachment.url );
        });

        // Finally, open the modal
        menu_file_frame.open();
    });

    // Restaurant gallery file uploads.
    var restaurant_gallery_frame;
    var $image_gallery_ids = $( '#restaurant_image_gallery' );
    var $restaurant_images    = $( '#restaurant_images_container' ).find( 'ul.restaurant_images' );

    $( '.add_restaurant_images' ).on( 'click', 'a', function( event ) {
        var $el = $( this );

        event.preventDefault();

        // If the media frame already exists, reopen it.
        if ( restaurant_gallery_frame ) {
            restaurant_gallery_frame.open();
            return;
        }

        // Create the media frame.
        restaurant_gallery_frame = wp.media.frames.restaurant_gallery = wp.media({
            // Set the title of the modal.
            title: $el.data( 'choose' ),
            button: {
                text: $el.data( 'update' )
            },
            states: [
                new wp.media.controller.Library({
                    title: $el.data( 'choose' ),
                    filterable: 'all',
                    multiple: true
                })
            ]
        });

        // When an image is selected, run a callback.
        restaurant_gallery_frame.on( 'select', function() {
            var selection = restaurant_gallery_frame.state().get( 'selection' );
            var attachment_ids = $image_gallery_ids.val();

            selection.map( function( attachment ) {
                attachment = attachment.toJSON();

                if ( attachment.id ) {
                    attachment_ids   = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
                    var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                    $restaurant_images.append( '<li class="image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image + '" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data('delete') + '">' + $el.data('text') + '</a></li></ul></li>' );
                }
            });

            $image_gallery_ids.val( attachment_ids );
        });

        // Finally, open the modal.
        restaurant_gallery_frame.open();
    });

    if ( $.isFunction( $.fn.sortable ) ) {
        // Image ordering.
        $restaurant_images.sortable({
            items: 'li.image',
            cursor: 'move',
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            forceHelperSize: false,
            helper: 'clone',
            opacity: 0.65,
            placeholder: 'wc-metabox-sortable-placeholder',
            start: function (event, ui) {
                ui.item.css('background-color', '#f6f6f6');
            },
            stop: function (event, ui) {
                ui.item.removeAttr('style');
            },
            update: function () {
                var attachment_ids = '';

                $('#restaurant_images_container').find('ul li.image').css('cursor', 'default').each(function () {
                    var attachment_id = $(this).attr('data-attachment_id');
                    attachment_ids = attachment_ids + attachment_id + ',';
                });

                $image_gallery_ids.val(attachment_ids);
            }
        });
    }

    // Remove images.
    $( '#restaurant_images_container' ).on( 'click', 'a.delete', function() {
        $( this ).closest( 'li.image' ).remove();

        var attachment_ids = '';

        $( '#restaurant_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
            var attachment_id = $( this ).attr( 'data-attachment_id' );
            attachment_ids = attachment_ids + attachment_id + ',';
        });

        $image_gallery_ids.val( attachment_ids );

        // Remove any lingering tooltips.
        $( '#tiptip_holder' ).removeAttr( 'style' );
        $( '#tiptip_arrow' ).removeAttr( 'style' );

        return false;
    });
});

jQuery(document).ready(function($) {
	var taxonomy = 'restaurant_listings_type';
	$('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').live( 'click', function(){
		var t = $(this), c = t.is(':checked'), id = t.val();
		$('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);
		$('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).prop( 'checked', c );
	});
});
