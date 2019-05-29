/**
 * WPGlobus Featured Images.
 * Interface JS functions
 *
 * @since 2.0.0
 *
 * @package WPGlobus Featured Images
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusCoreData, WPGlobusFeaturedImages*/
jQuery(document).ready(function($) {
	"use strict";
	var api = {
		init: function(){
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
			var title = $('#postimagediv h2 span').text();
			$('#postimagediv h2 span').text(title+' ('+api.getCurrentLanguage('name')+')');
		}
	}
	
	WPGlobusFeaturedImages =  $.extend( {}, WPGlobusFeaturedImages, api );
	WPGlobusFeaturedImages.init();	
});