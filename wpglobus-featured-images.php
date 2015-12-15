<?php
/**
 * Plugin Name: WPGlobus Featured Images
 * Plugin URI: https://github.com/WPGlobus/wpglobus-featured-images
 * Description: Set featured image separately for each language defined in <a href="https://wordpress.org/plugins/wpglobus/">WPGlobus</a>.
 * Version: 1.1.1
 * Author: WPGlobus
 * Author URI: http://www.wpglobus.com/
 * Network: false
 * License: GPL2
 * Credits: Alex Gor (alexgff) and Gregory Karpinsky (tivnet)
 * Copyright 2015 WPGlobus
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPGLOBUS_FEATURED_IMAGES_VERSION', '1.1.1' );

add_action( 'plugins_loaded', 'wpglobus_featured_images_load', 11 );
function wpglobus_featured_images_load() {
	if ( class_exists( 'WPGlobus' ) && 'off' != WPGlobus::Config()->toggle ) {
		new WPGlobus_Featured_Images();
	}
}

if ( ! class_exists( 'WPGlobus_Featured_Images' ) ) :
	
	/**
	 * WPGlobus_Featured_Images
	 */
	class WPGlobus_Featured_Images {

		/**
		 * @var bool $_SCRIPT_DEBUG Internal representation of the define('SCRIPT_DEBUG')
		 */
		protected static $_SCRIPT_DEBUG = false;

		/**
		 * @var string $_SCRIPT_SUFFIX Whether to use minimized or full versions of JS and CSS.
		 */
		protected static $_SCRIPT_SUFFIX = '.min';	
	
		/** */
		function __construct() {

			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				self::$_SCRIPT_DEBUG  = true;
				self::$_SCRIPT_SUFFIX = '';
			}		
		
			if ( is_admin() ) {

				add_action( 'admin_head', array(
					$this,
					'on_admin_head'
				) );
				
				add_action( 'admin_print_scripts', array(
					$this,
					'on_admin_scripts'
				) );

				add_action( 'add_meta_boxes', array(
					$this,
					'on_add_meta_boxes'
				) );

				add_action( 'wp_ajax_' . __CLASS__ . '_process_ajax', array(
					$this,
					'on_process_ajax'
				) );

			} else {

				add_filter( 'post_thumbnail_html', array(
					$this,
					'on_post_thumbnail_html'
				), 10, 5 );

			}

		}

		/**
		 * Add ajaxComplete handler 
		 * @see jqxhr.abort() in wpglobus-featured-images.js
		 * @since 1.2.0
		 * fix Uncaught TypeError: Cannot read property 'match' of undefined in  wp-seo-post-scraper-305.js?ver=3.0.6:447
		 */
		function on_admin_head() { ?>
<script type="text/javascript">
//<![CDATA[
jQuery( document ).on( 'ajaxComplete', function( ev, response ) {
	if ( response.statusText === 'abort' && 'undefined' === typeof response.responseText ) {
		// response is Object {readyState: 0, status: 0, statusText: "abort"}
		response.responseText = '';
	}
});
//]]>
</script><?php
		}	

		/**
		 * Handle ajax process
		 * @since 1.0.0
		 */
		function on_process_ajax() {

			$ajax_return = array();

			$order = $_POST['order'];

			switch ( $order['action'] ) :
				case 'wpglobus-remove-post-thumbnail':

					$ajax_return['action'] = $order['action'];
					if ( $order['language'] == WPGlobus::Config()->default_language ) {

						if ( delete_post_meta( $order['attr']['post_id'], '_thumbnail_id' ) ) {
							$ajax_return['result'] = 'ok';
							$ajax_return['html']   =
								$this->_post_thumbnail_html( null, get_post( $order['attr']['post_id'] ), $order['language'] );
						} else {
							$ajax_return['html'] = 'Error';
						}

					} else {

						$wpglobus_thumbnail_ids =
							(array) get_post_meta( $order['attr']['post_id'], 'wpglobus_thumbnail_ids', true );


						if ( ! empty( $wpglobus_thumbnail_ids[ $order['language'] ] ) ) {

							unset( $wpglobus_thumbnail_ids[ $order['language'] ] );

							if ( update_post_meta( $order['attr']['post_id'], 'wpglobus_thumbnail_ids', $wpglobus_thumbnail_ids ) ) {

								$ajax_return['result'] = 'ok';
								$ajax_return['html']   =
									$this->_post_thumbnail_html( null, get_post( $order['attr']['post_id'] ), $order['language'] );


							}

						}

					}

					break;
				case 'wpglobus-set-post-thumbnail':

					$ajax_return['action'] = $order['action'];

					if ( $order['language'] == WPGlobus::Config()->default_language ) {

						if ( update_post_meta( $order['attr']['post_id'], '_thumbnail_id', $order['attr']['thumbnail_id'] ) ) {
							$ajax_return['result'] = 'ok';
							$ajax_return['html']   =
								$this->_post_thumbnail_html( $order['attr']['thumbnail_id'], get_post( $order['attr']['post_id'] ), $order['language'] );
						} else {
							$ajax_return['result'] = 'error';
							$ajax_return['html']   = '<p>Error</p>';
						}

					} else {

						$wpglobus_thumbnail_ids =
							(array) get_post_meta( $order['attr']['post_id'], 'wpglobus_thumbnail_ids', true );

						$wpglobus_thumbnail_ids[ $order['language'] ] = $order['attr']['thumbnail_id'];

						if ( update_post_meta( $order['attr']['post_id'], 'wpglobus_thumbnail_ids', $wpglobus_thumbnail_ids ) ) {
							$ajax_return['result'] = 'ok';
							$ajax_return['html']   =
								$this->_post_thumbnail_html( $order['attr']['thumbnail_id'], get_post( $order['attr']['post_id'] ), $order['language'] );
						} else {
							$ajax_return['result'] = 'error';
							$ajax_return['html']   = '<p>Error</p>';
						}

					}
					break;
			endswitch;

			echo json_encode( $ajax_return );
			die();
		}

		/**
		 * Retrieve html for thumbnail at front-end.
		 * @since 1.0.0
		 * @see   post_thumbnail_html filter
		 * @var string $html
		 * @var int    $post_id
		 * @var int    $post_thumbnail_id
		 * @var array  $size
		 * @var array  $attr
		 * @return string html
		 */
		function on_post_thumbnail_html(
			$html, $post_id,
			/** @noinspection PhpUnusedParameterInspection */
			$post_thumbnail_id,
			$size, $attr
		) {

			/** @global WP_Post $post */
			global $post;
			$post_type = empty( $post ) ? '' : $post->post_type;
			if ( empty( $post_type ) ) {
				return $html;
			}

			if ( ! empty( WPGlobus::Config()->disabled_entities ) && in_array( $post_type, WPGlobus::Config()->disabled_entities ) ) {
				return $html;
			}

			if ( WPGlobus::Config()->language != WPGlobus::Config()->default_language ) {
				$wpglobus_thumbnail_ids = (array) get_post_meta( $post_id, 'wpglobus_thumbnail_ids', true );
				if ( ! empty( $wpglobus_thumbnail_ids[ WPGlobus::Config()->language ] ) ) {
					$html =
						wp_get_attachment_image( $wpglobus_thumbnail_ids[ WPGlobus::Config()->language ], $size, false, $attr );
				}
			}

			return $html;

		}

		/**
		 * Enqueue admin scripts
		 * @since 1.0.0
		 * @return void
		 */
		function on_admin_scripts() {

			/** @global WP_Post $post */
			global $post;
			$post_type = empty( $post ) ? '' : $post->post_type;
			if ( empty( $post_type ) ) {
				return;
			}

			/**
			 * @todo WPGlobus should have a method for this. Add-ons must not use vars directly.
			 */
			if ( ! empty( WPGlobus::Config()->disabled_entities ) && in_array( $post_type, WPGlobus::Config()->disabled_entities ) ) {
				return;
			}

			/** @global string $pagenow */
			global $pagenow;

			if ( $pagenow == 'post.php' ) :
			
				wp_register_script(
					'wpglobus-featured-images',
					plugin_dir_url( __FILE__ ) . 'wpglobus-featured-images' . self::$_SCRIPT_SUFFIX . ".js",
					array( 'jquery' ),
					WPGLOBUS_FEATURED_IMAGES_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-featured-images' );
				wp_localize_script(
					'wpglobus-featured-images',
					'WPGlobusFImages',
					array(
						'version'      => '',
						'ajaxurl'      => admin_url( 'admin-ajax.php' ),
						'parentClass'  => __CLASS__,
						'process_ajax' => __CLASS__ . '_process_ajax'
					)
				);
				
			endif;
			
		}

		/**
		 * Add meta box
		 * @since 1.0.0
		 * @return void
		 */
		function on_add_meta_boxes() {

			/** @global WP_Post $post */
			global $post;
			$post_type = empty( $post ) ? '' : $post->post_type;
			if ( empty( $post_type ) ) {
				return;
			}

			if ( ! empty( WPGlobus::Config()->disabled_entities ) && in_array( $post_type, WPGlobus::Config()->disabled_entities ) ) {
				return;
			}

			/** @global string $pagenow */
			global $pagenow;

			if ( $pagenow == 'post.php' ) :

				$thumbnail_support =
					current_theme_supports( 'post-thumbnails', $post_type ) && post_type_supports( $post_type, 'thumbnail' );
				if ( ! $thumbnail_support && 'attachment' === $post_type && $post->post_mime_type ) {
					if ( 0 === strpos( $post->post_mime_type, 'audio/' ) ) {
						$thumbnail_support =
							post_type_supports( 'attachment:audio', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:audio' );
					} elseif ( 0 === strpos( $post->post_mime_type, 'video/' ) ) {
						$thumbnail_support =
							post_type_supports( 'attachment:video', 'thumbnail' ) || current_theme_supports( 'post-thumbnails', 'attachment:video' );
					}
				}

				if ( $thumbnail_support && current_user_can( 'upload_files' ) ) {
					add_meta_box(
						'wpglobus_postimagediv',
						'WPGlobus Featured Images',
						array( $this, 'post_thumbnail_meta_box' ),
						null,
						'side',
						'low'
					);
				}

			endif;

		}

		/**
		 * Display post thumbnail meta box.
		 * @since 1.0.0
		 * @return void
		 */
		function post_thumbnail_meta_box() {
			/** @global WP_Post $post */
			global $post;

			$thumbnail_id           = get_post_meta( $post->ID, '_thumbnail_id', true );
			$wpglobus_thumbnail_ids = (array) get_post_meta( $post->ID, 'wpglobus_thumbnail_ids', true );

			echo $this->featured_images_tabs( $thumbnail_id, $wpglobus_thumbnail_ids, $post->ID );

		}

		/**
		 * Output Tabs for the post thumbnail meta-box.
		 * @since 1.0.0
		 *
		 * @param int   $thumbnail_id_default_language ID of the attachment used for thumbnail
		 * @param array $wpglobus_thumbnail_ids        The IDs of attachment used for language thumbnail
		 * @param mixed $post                          The post ID or object associated with the thumbnail, defaults to global $post.
		 *
		 * @return string html
		 */
		function featured_images_tabs( $thumbnail_id_default_language = null, $wpglobus_thumbnail_ids = null, $post = null ) {

			/** @global WP_Post $post */
			global $post;

			?>

			<div id="wpglobus-featured-images-tabs" class="wpglobus-post-body-tabs" data-featured-image-language="">
				<ul class="wpglobus-featured-images-list">    <?php
					foreach ( WPGlobus::Config()->open_languages as $language ) { ?>
						<li id="featured-images-link-tab-<?php echo $language; ?>"
						    data-language="<?php echo $language; ?>"
						    class="wpglobus-featured-images-tab"><a
								href="#featured-images-tab-<?php echo $language; ?>"><?php echo WPGlobus::Config()->en_language_name[ $language ]; ?></a>
						</li> <?php
					} ?>
				</ul>    <?php

				foreach ( WPGlobus::Config()->open_languages as $language ) { ?>
					<div style="padding-top:25px;" id="featured-images-tab-<?php echo $language; ?>" 
						class="wpglobus-featured-images-general"
					    data-language="<?php echo $language; ?>">
						<?php
						if ( $language != WPGlobus::Config()->default_language ) {

							if ( ! empty( $wpglobus_thumbnail_ids[ $language ] ) ) {
								$thumbnail_id = $wpglobus_thumbnail_ids[ $language ];
							} else {
								$thumbnail_id = null;
							}

						} else {
							$thumbnail_id = $thumbnail_id_default_language;
						}

						echo $this->_post_thumbnail_html( $thumbnail_id, $post, $language );
						?>
					</div> <?php
				} ?>
			</div> <?php

		}

		/**
		 * Retrieve html for thumbnail.
		 * @since 1.0.0
		 *
		 * @param int    $thumbnail_id ID of the attachment used for thumbnail
		 * @param mixed  $post         The post ID or object associated with the thumbnail, defaults to global $post.
		 * @param string $language
		 *
		 * @return string html
		 */
		function _post_thumbnail_html( $thumbnail_id = null, $post = null, $language ) {

			/** @global int $content_width */
			/** @global array $_wp_additional_image_sizes */
			global $content_width, $_wp_additional_image_sizes;

			$upload_iframe_src = esc_url( get_upload_iframe_src( 'image', $post->ID ) );

			$upload_iframe_src = str_replace( 'type=image', 'type=image&language=' . $language, $upload_iframe_src );

			$set_thumbnail_link =
				'<p style="clear:both;" class="hide-if-no-js"><a title="' . esc_attr__( 'Set featured image' ) . '" href="%s" id="set-post-thumbnail-' . $language . '" class="thickbox wpglobus-set-post-thumbnail" data-language="' . $language . '">%s</a></p>';
			$content            =
				sprintf( $set_thumbnail_link, $upload_iframe_src, esc_html__( 'Set featured image' ) );

			if ( $thumbnail_id && get_post( $thumbnail_id ) ) {
				$old_content_width = $content_width;
				$content_width     = 220;  // 266

				if ( ! isset( $_wp_additional_image_sizes['post-thumbnail'] ) ) {
					$thumbnail_html = wp_get_attachment_image( $thumbnail_id, array( $content_width, $content_width ) );
				} else {
					$thumbnail_html = wp_get_attachment_image( $thumbnail_id, 'post-thumbnail' );
				}

				if ( ! empty( $thumbnail_html ) ) {
					$ajax_nonce = wp_create_nonce( 'set_post_thumbnail-' . $post->ID );

					$onclick =
						"WPGlobusFeaturedImages.removeThumbnail('" . $ajax_nonce . "', '" . $language . "', '" . $post->ID . "');return false;";

					$content = sprintf( $set_thumbnail_link, $upload_iframe_src, $thumbnail_html );
					$content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail-' . $language . '" onclick="' . $onclick . '">' . esc_html__( 'Remove featured image' ) . '</a></p>';
				}
				$content_width = $old_content_width;
			}

			/**
			 * Filter the admin post thumbnail HTML markup to return.
			 * @since 1.0.0
			 *
			 * @param string $content Admin post thumbnail HTML markup.
			 * @param int    $post_id Post ID.
			 */

			//return apply_filters( 'admin_post_thumbnail_html', $content, $post->ID );

			return $content;

		}

	} // class

endif;


# --- EOF