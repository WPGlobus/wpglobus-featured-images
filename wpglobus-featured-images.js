/**
 * WPGlobus Featured Images
 * Interface JS functions
 *
 * @since 1.0.0
 *
 * @package WPGlobus Featured Images
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusCoreData, WPGlobusFImages*/
var WPGlobusFeaturedImages;

jQuery(document).ready(function($) {

	var api;
	api = WPGlobusFeaturedImages = {
		start : true,
		action : '',
		language : '',
		default_language : '',
		win : '',
		wp_native_action : false, 
		init : function() {
			try {
				$('#wpglobus-featured-images-tabs').tabs();
				
			} catch (exception) {api.start=false}
			
			api.win = window.dialogArguments || opener || parent || top;
			if ( 'undefined' !== typeof api.win.WPGlobusCoreData ) {
				api.default_language = api.win.WPGlobusCoreData.default_language;	
				$('#postimagediv').css({'display':'none'});
				$('#postimagediv-hide').prop('checked',false);
				this.attachListener();
			}	
		},
		removeThumbnail : function(nonce, language, post_id) {
			api.wp_native_action = false;
			var order = {};
			order['action']   = 'wpglobus-remove-post-thumbnail';
			order['language']  = language;
			order['attr'] 	  = {};		
			order['attr']['post_id'] = post_id;		
			order['attr']['thumbnail_id'] = -1;		
		
			api.ajax(order);			
		},	
		setThumbnailHTML : function(html, language){
			$('#featured-images-tab-'+language).html(html);
		},
		setLanguage : function() {
			return $('#wpglobus-featured-images-tabs').data('featured-image-language');
		},
		ajax : function(order) {

			$.ajax({type:'POST', url:WPGlobusFImages.ajaxurl, data:{action:WPGlobusFImages.process_ajax, order:order}, dataType:'json'})
			.done(function (result) {
				if ( result['result'] == 'ok' ) {
					if ( result['order']['language'] == WPGlobusCoreData.default_language ) {
						if ( $( WPGlobusFImages.thumbnailElementDefaultLanguage ).length == 1 ) {
							$( WPGlobusFImages.thumbnailElementDefaultLanguage ).val( result['order']['attr']['thumbnail_id'] );
						}	
					}	
					api.win.WPGlobusFeaturedImages.setThumbnailHTML(result['html'], order['language']);
				}	
			})
			.fail(function (error){})
			.always(function (jqXHR, status){});		
		},	
		attachListener : function() {
			$('#wpglobus_postimagediv').on( 'click', '.wpglobus-set-post-thumbnail', function( event ) {
				event.preventDefault();
				// Stop propagation to prevent thickbox from activating.
				event.stopPropagation();
				api.language = $(this).data('language');	
				wp.media.featuredImage.frame().open();
			});			
			
			$('#set-post-thumbnail').on('click', function(event){
				api.wp_native_action = true;
			});
			
			$(document).ajaxSend(function(event, jqxhr, settings){
				if ( 'undefined' === typeof settings.data ) {
					return;	
				}	
				if ( settings.data.indexOf( WPGlobusFImages.getThumbnailAction ) >= 0 && ! api.wp_native_action ) {
					
					if ( settings.data.indexOf('thumbnail_id=-1') >= 0 ) {
						return;	
					}
					jqxhr.abort();
					var s=settings.data.split('&'),
						attr = {};
					$.each(s, function(i,e){
						var ss = e.split('=');
						attr[ss[0]] = {};
						attr[ss[0]] = ss[1];
					});
					var order = {};
					order['action']   = 'wpglobus-set-post-thumbnail';
					order['language']  = api.language;
					order['attr'] 	  = attr;	
			
					$('a#wp-post-thumbnail-' + attr['thumbnail_id']).fadeOut(2000);
					api.ajax(order);
				}
			});
			
		}	
	
	};

	WPGlobusFeaturedImages.init();

});