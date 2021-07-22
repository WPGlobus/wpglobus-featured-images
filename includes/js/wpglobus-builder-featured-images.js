/**
 * WPGlobus Featured Images.
 * Interface JS functions
 *
 * @since 2.0.0
 * @since 2.3.0 Update.
 *
 * @package WPGlobus Featured Images
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusCoreData, WPGlobusFeaturedImages*/
jQuery(document).ready(function($) {
	"use strict";
	var api = {
		started: true,
		start: function(){
			api.addImageHint();
			api.setTitle();
		},
		getCurrentLanguage: function(mode){
			if ( 'undefined' == typeof mode ) {
				mode = 'name';
			}
			var cur = '';
			if ( 'code' == mode ) {
				cur = WPGlobusFeaturedImages.current_language;
			} else if ( 'name' == mode ) {
				cur = WPGlobusFeaturedImages.current_language_name;
			}	
			return cur;
		},
		setTitle: function(){
			// var title = $('#postimagediv h2 span').text();
			// $('#postimagediv h2 span').text(title+' ('+api.getCurrentLanguage('name')+')');
		},
		addImageHint: function(){
		
			if ( 'gutenberg' === WPGlobusFeaturedImages.builderID ) {
				
				setTimeout( function(){
					if ( $('.editor-post-featured-image').length == 1 ) {
						var hint = '<span style="margin-left:40px;">'+WPGlobusFeaturedImages.i18n['image_for']+': '+WPGlobusFeaturedImages.current_language_name+'</span>';
						hint += '<span style="cursor:pointer;">';
						hint += 	'<i class="hint-icon dashicons dashicons-editor-help" style="font-size:large;"></i>';
						hint += '</span>';
						hint += '<div class="hint-content hidden" style="margin: 0 10px;">';
						hint += 	'<p>'+WPGlobusFeaturedImages.i18n['help_content1'];WPGlobusFeaturedImages.i18n['help_content2']+'</p>';
						hint += 	'&nbsp;'+WPGlobusFeaturedImages.i18n['help_content2']+'</p>';
						hint += '</div>';
						$('<div class="wpglobus-featured-images-hint" style="width:100%;font-weight:500;margin-top:1em;">'+hint+'</div>').prependTo('.editor-post-featured-image');
						$(document).on('click', '.wpglobus-featured-images-hint .hint-icon', function( event ) {
							$('.wpglobus-featured-images-hint .hint-content').toggleClass('hidden');
						});				
					}
				}, 1000);
			} else {
			
				if ( $('#postimagediv .postbox-header').length == 1 ) {
					var hint = '<span style="margin-left:40px;">'+WPGlobusFeaturedImages.i18n['image_for']+': '+WPGlobusFeaturedImages.current_language_name+'</span>';
					hint += '<span style="cursor:pointer;">';
					hint += 	'<i class="hint-icon dashicons dashicons-editor-help" style="font-size:large;"></i>';
					hint += '</span>';
					hint += '<div class="hint-content hidden" style="margin: 0 10px;">';
					hint += 	'<p>'+WPGlobusFeaturedImages.i18n['help_content1'];WPGlobusFeaturedImages.i18n['help_content2']+'</p>';
					hint += 	'&nbsp;'+WPGlobusFeaturedImages.i18n['help_content2']+'</p>';
					hint += '</div>';
					$('<div class="wpglobus-featured-images-hint" style="width:100%;font-weight:500;margin-top:1em;">'+hint+'</div>').insertAfter('#postimagediv .postbox-header');
					$(document).on('click', '.wpglobus-featured-images-hint .hint-icon', function( event ) {
						$('.wpglobus-featured-images-hint .hint-content').toggleClass('hidden');
					});
				}
			}
		}
	}
	
	WPGlobusFeaturedImages =  $.extend({}, WPGlobusFeaturedImages, api);
	WPGlobusFeaturedImages.start();	
});