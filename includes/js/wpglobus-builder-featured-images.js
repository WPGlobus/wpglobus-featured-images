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
				var _c = '';
				_c += '<div style="margin:5px 0 10px 40px;">';
				_c += 	'<span>'+WPGlobusFeaturedImages.i18n['image_for']+': '+WPGlobusFeaturedImages.current_language_name+'</span>';
				_c += 	'<span style="cursor:pointer;">';
				_c += 		'<i class="hint-icon dashicons dashicons-editor-help" style="font-size:large;display:contents;"></i>';
				_c += 	'</span>';
				_c += '</div>';
				_c += '<div class="hint-content hidden" style="margin: 10px 10px;background-color:rgb(240, 240, 241);padding: 1px 10px;">';
				// _c += 	'<p>'+WPGlobusFeaturedImages.i18n['help_content1']+'</p>';
			  _c += 	'<p>'+WPGlobusFeaturedImages.i18n['help_content2']+'</p>';
				_c += '</div>';

				var hint = {
					started: false,
					panelSelector: '.components-panel__body',
					prependSelector: '.editor-post-featured-image',
					iconSelector: '.wpglobus-featured-images-hint .hint-icon',
					contentClassName: 'wpglobus-featured-images-hint',
					contentToggleSelector: '.wpglobus-featured-images-hint .hint-content',
					content: _c,
					hasContent: function(){
						if ( $('.'+hint.contentClassName).length === 1 ) {
							return true;
						}
						return false;
					},
					insertContent: function(){
						if ( ! hint.hasContent() ) {
							$('<div class="'+hint.contentClassName+'" style="width:100%;font-weight:500;margin-top:1em;">'+hint.content+'</div>').prependTo(hint.prependSelector);
						}					
					},
					init: function(){
						var intervalID;
						var i = 1;
						intervalID = setInterval( function(){
							if ( $(hint.prependSelector).length === 1 ) { 
								clearInterval(intervalID);
								hint.insertContent();
							} else {
								if (i>=10) {
									clearInterval(intervalID);
								}
							}
							i++;
						}, 1000);
					},
					start: function(){
						if ( ! hint.started ) {
							hint.init();
							$(document).on('click',hint.panelSelector,function(event){
								if ( $(hint.prependSelector).length === 1 ) { 
									hint.insertContent();
								}
							});
							$(document).on('click', hint.iconSelector, function( event ) {
								event.stopPropagation();
								$(hint.contentToggleSelector).toggleClass('hidden');
							});
							hint.started = true;							
						}
					}
				}
				
				hint.start();
			} else {
			
				if ( $('#postimagediv .postbox-header').length == 1 ) {
					var hint = '<span style="margin-left:40px;">'+WPGlobusFeaturedImages.i18n['image_for']+': '+WPGlobusFeaturedImages.current_language_name+'</span>';
					hint += '<span style="cursor:pointer;">';
					hint += 	'<i class="hint-icon dashicons dashicons-editor-help" style="font-size:large;display:contents;"></i>';
					hint += '</span>';
					hint += '<div class="hint-content hidden" style="margin: 10px 10px;background-color:rgb(240, 240, 241);padding: 1px 10px;">';
					hint += 	'<p>'+WPGlobusFeaturedImages.i18n['help_content1']+'</p>';
					hint += 	'<p>'+WPGlobusFeaturedImages.i18n['help_content2']+'</p>';
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