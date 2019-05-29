<?php
/**
 * Plugin Name: WPGlobus Featured Images
 * Plugin URI: https://github.com/WPGlobus/wpglobus-featured-images
 * Description: Set featured image separately for each language defined in <a href="https://wordpress.org/plugins/wpglobus/">WPGlobus</a>.
 * Text Domain: wpglobus-featured-images
 * Domain Path: /languages/
 * Version: 2.0.0
 * Author: WPGlobus
 * Author URI: https://wpglobus.com/
 * Network: false
 * License: GPL2
 * Credits: Alex Gor (alexgff) and Gregory Karpinsky (tivnet)
 * Copyright 2015-2019 WPGlobus
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

define( 'WPGLOBUS_FEATURED_IMAGES_VERSION', '2.0.0' );

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
		
		/**
		 * Default meta key for thumbnail.
		 * @since 2.0.0
		 */
		protected static $thumbnail_meta_key = '_thumbnail_id';
		
		/**
		 * WPGlobus meta key for thumbnail.
		 * @since 2.0.0
		 */		
		protected static $wpglobus_thumbnail_meta_key = 'wpglobus_thumbnail_ids';

		/**
		 * @since 2.0.0
		 */			
		protected static $thumbnail_id = null;
		
		/**
		 * @since 2.0.0
		 */	
		protected static $featured_media = null;

		/**
		 * Tab ID for WPGlobus admin central page.
		 */
		protected static $central_tab_id = 'tab-featured-images';

		/**
		 * Array of disabled post types.
		 *
		 * @var array
		 */
		protected $disabled_entities = array();

		/**
		 * Constructor.
		 */
		function __construct() {

			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				self::$_SCRIPT_DEBUG  = true;
				self::$_SCRIPT_SUFFIX = '';
			}
			
			/**
			 * Filter of disabled entities.
			 * Returning array.
			 
			 * @since 1.7.0
			 *
			 * @param array WPGlobus::Config()->disabled_entities Array of disabled entities.
			 */
			$this->disabled_entities = apply_filters( 'wpglobus_disabled_entities', WPGlobus::Config()->disabled_entities );

			/**
			 * @see wp-includes\rest-api\class-wp-rest-server.php
			 */
			add_filter( 'rest_request_before_callbacks', array( $this, 'filter__rest_before_callbacks' ), 5, 3 );
			
			/**
			 * @see wp-includes\rest-api\class-wp-rest-server.php
			 */			
			add_filter( 'rest_request_after_callbacks', array( $this, 'filter__rest_after_callbacks' ), 5, 3 );
			
			if ( is_admin() ) {
					
				if ( WPGlobus::Config()->builder->is_builder_page() ) {
					
					/**
					 * Builder mode.
					 */
					add_filter( 'get_post_metadata', array( $this, 'filter__post_metadata' ), 5, 4 );
					
					if ( 'gutenberg' == WPGlobus::Config()->builder->get_id() ) {
						
						/**
						 * @see 'rest_request_before_callbacks' and 'rest_request_after_callbacks' filters above.
						 */
						
					} else {

						add_filter( 'update_post_metadata', array( $this, 'filter__update_post_metadata' ), 5, 5 );
						
						add_filter( 'delete_post_metadata', array( $this, 'filter__delete_post_metadata' ), 5, 5 );
						
						add_action( 'admin_print_scripts', array(
							$this,
							'on__builder_admin_scripts',
						) );
						
					}
					
				} else {
				
					/**
					 * Standard mode.
					 */
					add_action( 'admin_head', array(
						$this,
						'on_admin_head',
					) );

					add_action( 'admin_print_scripts', array(
						$this,
						'on__admin_scripts',
					) );

					add_action( 'add_meta_boxes', array(
						$this,
						'on__add_meta_boxes',
					) );

					add_action( 'wp_ajax_' . __CLASS__ . '_process_ajax', array(
						$this,
						'on__process_ajax',
					) );
					
				}

				if ( class_exists( 'WPGlobus_Admin_Central' ) ) {

					/**
					 * @scope admin
					 * @since 1.4.0
					 */
					add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
						$this,
						'filter__plugin_action_links',
					) );

					/**
					 * @scope admin
					 * @since 1.4.0
					 */
					add_filter( 'wpglobus_admin_central_tabs', array(
						$this,
						'filter__central_tabs',
					), 10, 2 );

					/**
					 * @scope admin
					 * @since 1.4.0
					 */
					add_action( 'wpglobus_admin_central_panel', array(
						$this,
						'filter__admin_central_panel',
					) );

				}

			} else {
				
				/**
				 * front-end.
				 */

				add_filter( 'post_thumbnail_html', array(
					$this,
					'on_post_thumbnail_html',
				), 10, 5 );

				/**
				 * @since 1.7.0
				 */
				if ( defined( 'WOOCOMMERCE_WPGLOBUS_VERSION' ) ) {
					add_filter( 'woocommerce_product_get_image_id', array(
						$this,
						'filter__woocommerce_product_get_image_id',
					) );
				}

			}

		}

		/**
		 * @since 2.0.0
		 */
		public static function filter__rest_before_callbacks( $response, $handler, $request ) {
			
			$builder_language = WPGlobus::Config()->builder->get_language();
			
			if ( WPGlobus::Config()->default_language == $builder_language ) {
				return $response;
			}

			$body = json_decode( $request->get_body() );
			if ( empty($body) ) {
				return $response;
			}
	
			if ( (int) $body->featured_media == 0 ) {
				/**
				 * Image was removed by clicking on `Remove featured image` button.
				 */
				self::$featured_media = 0;
				
			} else {
				/**
				 * Featured image was added or changed.
				 */
				self::$featured_media = (int) $body->featured_media;
			}

			$thumbnail_id = get_post_meta( $body->id, self::$thumbnail_meta_key, true );
			if ( ! empty($thumbnail_id) && (int) $thumbnail_id > 0 ) {
				self::$thumbnail_id = $thumbnail_id;
			}

			return $response;
		}
		
		/**
		 * @since 2.0.0
		 */
		public static function filter__rest_after_callbacks( $response, $handler, $request ) {
			
			if ( ! empty( $handler['methods']['POST'] ) && ! empty( $handler['methods']['PUT'] ) && ! empty( $handler['methods']['PATCH'] ) ) {
				
				/**
				 * Update post.
				 */
				 
				$builder_language = WPGlobus::Config()->builder->get_language();
				
				if ( WPGlobus::Config()->default_language == $builder_language ) {
					return $response;
				}				 
 
				$post_id = $response->data['id'];
					
				if ( ! is_null(self::$thumbnail_id) ) {
					update_post_meta( $post_id, self::$thumbnail_meta_key, self::$thumbnail_id );
				}	
					
				if ( is_null( self::$featured_media ) ) {

					$response->data['featured_media'] = '';
					
				} else if ( self::$featured_media == 0 ) {
					
					/**
					 * Featured image was removed.
					 */
					$wpglobus_thumbnail_ids = (array) get_post_meta( $post_id, self::$wpglobus_thumbnail_meta_key, true );
					
					if ( ! empty( $wpglobus_thumbnail_ids[$builder_language] ) ) {
						unset( $wpglobus_thumbnail_ids[$builder_language] );
						update_post_meta( $post_id, self::$wpglobus_thumbnail_meta_key, $wpglobus_thumbnail_ids );
					}
				
				} else {
					
					/**
					 * Featured image was added or changed.
					 */
					$wpglobus_thumbnail_ids = (array) get_post_meta( $post_id, self::$wpglobus_thumbnail_meta_key, true );
					
					$wpglobus_thumbnail_ids[$builder_language] = $response->data['featured_media'];
					
					update_post_meta( $post_id, self::$wpglobus_thumbnail_meta_key, $wpglobus_thumbnail_ids );
					
				}
	
			}

			return $response;
		}
		
		/**
		 * Delete post meta.
		 *
		 * @since 2.0.0
		 */
		public static function filter__delete_post_metadata( $check, $object_id, $meta_key, $meta_value, $delete_all ) {
			
			if ( self::$thumbnail_meta_key != $meta_key ) {
				return $check;	
			}
			
			$language = WPGlobus::Config()->builder->get_language();
			
			if ( WPGlobus::Config()->default_language == $language ) {
				return $check;	
			}
			
			$wpglobus_thumbnail_ids = (array) get_post_meta( $object_id, self::$wpglobus_thumbnail_meta_key, true );
			
			if ( ! empty($wpglobus_thumbnail_ids[$language]) ) {
				unset($wpglobus_thumbnail_ids[$language]);
			}
			
			if ( update_post_meta( $object_id, self::$wpglobus_thumbnail_meta_key, $wpglobus_thumbnail_ids ) ) {
				return true;
			}
			
			return false;			
		}
		
		/**
		 * Update post meta.
		 */
		public static function filter__update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
			
			if ( self::$thumbnail_meta_key != $meta_key ) {
				return $check;	
			}

			if ( WPGlobus::Config()->default_language == WPGlobus::Config()->builder->get_language() ) {
				return $check;	
			}

			$wpglobus_thumbnail_ids = (array) get_post_meta( $object_id, self::$wpglobus_thumbnail_meta_key, true );
			
			$wpglobus_thumbnail_ids[WPGlobus::Config()->builder->get_language()] = $meta_value;
			
			if ( update_post_meta( $object_id, self::$wpglobus_thumbnail_meta_key, $wpglobus_thumbnail_ids ) ) {
				return true;
			}
			
			return false;
			
		}
		
		/**
		 * Get post meta.
		 */
		public static function filter__post_metadata( $check, $object_id, $meta_key, $single ) {

			if ( self::$thumbnail_meta_key != $meta_key ) {
				return $check;	
			}
			
			static $new_id = null;
			if ( ! is_null($new_id) ) {
				return $new_id;
			}
			
			if ( WPGlobus::Config()->default_language == WPGlobus::Config()->builder->get_language() ) {
				return $check;	
			}
			
			$wpglobus_thumbnail_ids = (array) get_post_meta( $object_id, self::$wpglobus_thumbnail_meta_key, true );

			if ( ! empty( $wpglobus_thumbnail_ids[WPGlobus::Config()->builder->get_language()] ) ) {
				
				$new_id = $wpglobus_thumbnail_ids[WPGlobus::Config()->builder->get_language()];
				
				return $new_id;
			}
			
			/**
			 * Return false to prevent loading featured image for default language.
			 */
			return false;
			
		}
		
		/**
		 * Add panel for WPGlobus admin central.
		 *
		 * @since 1.4.0
		 */
		function filter__admin_central_panel() {

			$post_types = array_merge(
				array(
					'post' => 'post',
					'page' => 'page',
				),
				get_post_types(
					array(
						'_builtin' => false,
					)
				)
			);
			
			/**
			 * Unset unneeded post types.
			 */
			unset( $post_types['wp_block'] );
			
			?>
			<div id="<?php echo self::$central_tab_id; ?>" style="display:none;margin: 0 30px;"
					class="wpglobus-admin-central-tab">
				<p>
					Before using WPGlobus Featured Images with existing post types,<br/>
					please, be sure they are supporting "thumbnail" feature.<br/>
				</p>
				<h3>List of post types:</h3>
				<ul>
					<?php foreach ( $post_types as $post_type ) : ?>
						<?php if ( post_type_supports( $post_type, 'thumbnail' ) ) : ?>
							<li><span style="text-decoration:underline;">Post type <b><?php echo $post_type; ?></b>&nbsp;supports thumbnail</span>.
							</li>
						<?php else : ?>
							<li>Post type <b><?php echo $post_type; ?></b> doesn't support thumbnail.</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}

		/**
		 * Add tab for WPGlobus admin central.
		 *
		 * @since 1.4.0
		 *
		 * @param array  $tabs
		 * @param string $link_template
		 *
		 * @return mixed
		 */
		function filter__central_tabs( $tabs, $link_template ) {

			$tab = array(
				'title'      => __( 'WPGlobus Featured Images', 'wpglobus' ),
				'link_class' => array( 'nav-tab', 'nav-tab-active' ),
				'span_class' => array( 'dashicons', 'dashicons-images-alt' ),
				'link'       => $link_template,
				'href'       => '#',
				'tab_id'     => self::$central_tab_id,
			);

			array_unshift( $tabs, $tab );

			return $tabs;

		}

		/**
		 * Add ajaxComplete handler.
		 *
		 * @see   jqxhr.abort() in wpglobus-featured-images.js
		 * @since 1.2.0
		 * fix Uncaught TypeError: Cannot read property 'match' of undefined in  wp-seo-post-scraper-305.js?ver=3.0.6:447
		 */
		function on_admin_head() { ?>
			<script type="text/javascript">
                //<![CDATA[
                jQuery(document).on('ajaxComplete', function (ev, response) {
                    if (response.statusText === 'abort' && 'undefined' === typeof response.responseText) {
                        // response is Object {readyState: 0, status: 0, statusText: "abort"}
                        response.responseText = '';
                    }
                });
                //]]>
			</script><?php
		}

		/**
		 * Handle ajax process.
		 *
		 * @since 1.0.0
		 */
		function on__process_ajax() {

			$ajax_return = array();

			$order = $_POST['order'];

			switch ( $order['action'] ) :
				case 'wpglobus-remove-post-thumbnail':

					$ajax_return['action'] = $order['action'];
					if ( $order['language'] == WPGlobus::Config()->default_language ) {

						if ( delete_post_meta( $order['attr']['post_id'], self::$thumbnail_meta_key ) ) {
							$ajax_return['result'] = 'ok';
							$ajax_return['html']   =
								$this->_post_thumbnail_html( null, get_post( $order['attr']['post_id'] ), $order['language'] );
						} else {
							$ajax_return['html'] = 'Error';
						}

					} else {

						$wpglobus_thumbnail_ids =
							(array) get_post_meta( $order['attr']['post_id'], self::$wpglobus_thumbnail_meta_key, true );


						if ( ! empty( $wpglobus_thumbnail_ids[ $order['language'] ] ) ) {

							unset( $wpglobus_thumbnail_ids[ $order['language'] ] );

							if ( update_post_meta( $order['attr']['post_id'], self::$wpglobus_thumbnail_meta_key, $wpglobus_thumbnail_ids ) ) {

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

						if ( update_post_meta( $order['attr']['post_id'], self::$thumbnail_meta_key, $order['attr']['thumbnail_id'] ) ) {
							$ajax_return['result'] = 'ok';
							$ajax_return['html']   =
								$this->_post_thumbnail_html( $order['attr']['thumbnail_id'], get_post( $order['attr']['post_id'] ), $order['language'] );
						} else {
							$ajax_return['result'] = 'error';
							$ajax_return['html']   = '<p>Error</p>';
						}

					} else {

						$wpglobus_thumbnail_ids =
							(array) get_post_meta( $order['attr']['post_id'], self::$wpglobus_thumbnail_meta_key, true );

						$wpglobus_thumbnail_ids[ $order['language'] ] = $order['attr']['thumbnail_id'];

						if ( update_post_meta( $order['attr']['post_id'], self::$wpglobus_thumbnail_meta_key, $wpglobus_thumbnail_ids ) ) {
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

			$ajax_return['order'] = $order;
			echo json_encode( $ajax_return );
			die();
		}

		/**
		 * Retrieve html for thumbnail at front-end.
		 *
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

			if ( $this->is_disabled( $post_type ) ) {
				return $html;
			}

			if ( WPGlobus::Config()->language != WPGlobus::Config()->default_language ) {
				$wpglobus_thumbnail_ids = (array) get_post_meta( $post_id, self::$wpglobus_thumbnail_meta_key, true );
				if ( ! empty( $wpglobus_thumbnail_ids[ WPGlobus::Config()->language ] ) ) {
					$html =
						wp_get_attachment_image( $wpglobus_thumbnail_ids[ WPGlobus::Config()->language ], $size, false, $attr );
				}
			}

			return $html;

		}

		/**
		 * Replace Product thumbnail with the one set for the current language.
		 *
		 * @param int $image_id WC Product featured image ID.
		 *
		 * @return int
		 *
		 * @since 1.7.0
		 */
		public function filter__woocommerce_product_get_image_id( $image_id ) {
			/** @global WC_Product $product */
			global $product;

			if ( $product && $product instanceof WC_Product ) {

				$wpglobus_thumbnail_ids = (array) get_post_meta( $product->get_id(), self::$wpglobus_thumbnail_meta_key, true );
				if ( ! empty( $wpglobus_thumbnail_ids[ WPGlobus::Config()->language ] ) ) {
					$image_id = (int) $wpglobus_thumbnail_ids[ WPGlobus::Config()->language ];
				}
			}

			return $image_id;
		}

		/**
		 * @since 2.0.0
		 */
		function on__builder_admin_scripts() {
			
			/** @global WP_Post $post */
			global $post;
			$post_type = empty( $post ) ? '' : $post->post_type;
			if ( empty( $post_type ) ) {
				return;
			}

			if ( $this->is_disabled( $post_type ) ) {	 
				return;
			}

			/** @global string $pagenow */
			global $pagenow;

			if ( $pagenow == 'post.php' ) {
				
				wp_register_script(
					'wpglobus-builder-featured-images',
					plugin_dir_url( __FILE__ ) . 'includes/js/wpglobus-builder-featured-images' . self::$_SCRIPT_SUFFIX . ".js",
					array( 'jquery' ),
					WPGLOBUS_FEATURED_IMAGES_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-builder-featured-images' );
				wp_localize_script(
					'wpglobus-builder-featured-images',
					'WPGlobusFeaturedImages',
					array(
						'version'			=> WPGLOBUS_FEATURED_IMAGES_VERSION,
						'builderID'			=> WPGlobus::Config()->builder->get_id(),
						'current_language'  => WPGlobus::Config()->builder->get_language(),
						'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
						'parentClass' 		=> __CLASS__,
						'process_ajax' 		=> __CLASS__ . '_process_ajax',
						'current_language_name' => WPGlobus::Config()->en_language_name[WPGlobus::Config()->builder->get_language()],
					)
				);
				
			}				
		}
		
		/**
		 * Enqueue admin scripts.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		function on__admin_scripts() {

			/** @global WP_Post $post */
			global $post;
			$post_type = empty( $post ) ? '' : $post->post_type;
			if ( empty( $post_type ) ) {
				return;
			}

			/**
			 * @todo WPGlobus should have a method for this. Add-ons must not use vars directly.
			 * if ( ! empty( $this->disabled_entities ) && in_array( $post_type, $this->disabled_entities ) ) {
			 */
			if ( $this->is_disabled( $post_type ) ) {	 
				return;
			}

			/** @global string $pagenow */
			global $pagenow;

			if ( $pagenow == 'post.php' ) :

				global $wp_version;

				/**
				 * Action id has been changed from WP 4.6
				 */
				$get_thumbnail_action = 'action=get-post-thumbnail-html';
				if ( version_compare( $wp_version, '4.6-RC1', '<' ) ) :
					$get_thumbnail_action = 'action=set-post-thumbnail';
				endif;

				wp_register_script(
					'wpglobus-featured-images',
					plugin_dir_url( __FILE__ ) . 'includes/js/wpglobus-featured-images' . self::$_SCRIPT_SUFFIX . ".js",
					array( 'jquery' ),
					WPGLOBUS_FEATURED_IMAGES_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-featured-images' );
				wp_localize_script(
					'wpglobus-featured-images',
					'WPGlobusFImages',
					array(
						'version'                         => WPGLOBUS_FEATURED_IMAGES_VERSION,
						'ajaxurl'                         => admin_url( 'admin-ajax.php' ),
						'parentClass'                     => __CLASS__,
						'process_ajax'                    => __CLASS__ . '_process_ajax',
						'getThumbnailAction'              => $get_thumbnail_action,
						'thumbnailElementDefaultLanguage' => 'input[name="_thumbnail_id"]',
					)
				);

			endif;

		}

		/**
		 * Add meta box
		 *
		 * @since 1.0.0
		 * @return void
		 */
		function on__add_meta_boxes() {

			/** @global WP_Post $post */
			global $post;
			$post_type = empty( $post ) ? '' : $post->post_type;
			if ( empty( $post_type ) ) {
				return;
			}

			if ( $this->is_disabled( $post_type ) ) {	
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
						$this->get_metabox_title($post_type),
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
		 *
		 * @since 1.0.0
		 * @return void
		 */
		function post_thumbnail_meta_box() {
			/** @global WP_Post $post */
			global $post;

			$thumbnail_id           = get_post_meta( $post->ID, self::$thumbnail_meta_key, true );
			$wpglobus_thumbnail_ids = (array) get_post_meta( $post->ID, self::$wpglobus_thumbnail_meta_key, true );

			$this->featured_images_tabs( $thumbnail_id, $wpglobus_thumbnail_ids, $post->ID );

		}

		/**
		 * Output Tabs for the post thumbnail meta-box.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $thumbnail_id_default_language ID of the attachment used for thumbnail
		 * @param array $wpglobus_thumbnail_ids        The IDs of attachment used for language thumbnail
		 * @param mixed $post                          The post ID or object associated with the thumbnail, defaults to global $post.
		 *
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
				</ul> <?php

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
		 *
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
					
					/**
					 * @since 1.7.0
					 */
					$_id = '';
					if ( $language != WPGlobus::Config()->default_language ) {
						$_id = '-' . $language;
					}
					$content .= '<p class="hide-if-no-js howto" id="set-post-thumbnail-desc' . $_id . '">' . esc_html__( 'Click the image to edit or update' ) . '</p>';	
					
					$content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail-' . $language . '" onclick="' . $onclick . '">' . esc_html__( 'Remove featured image' ) . '</a></p>';
				}
				$content_width = $old_content_width;
			}

			/**
			 * Filter the admin post thumbnail HTML markup to return.
			 *
			 * @since 1.0.0
			 *
			 * @param string $content Admin post thumbnail HTML markup.
			 * @param int    $post_id Post ID.
			 */

			//return apply_filters( 'admin_post_thumbnail_html', $content, $post->ID );

			return $content;

		}

		/**
		 * Add a link to the settings page to the plugins list.
		 *
		 * @since 1.4.0
		 *
		 * @param array $links array of links for the plugins, adapted when the current plugin is found.
		 *
		 * @return array $links
		 */
		function filter__plugin_action_links( $links ) {

			$link = add_query_arg(
				array(
					'page' => WPGlobus::PAGE_WPGLOBUS_ADMIN_CENTRAL . '#' . self::$central_tab_id,
				),
				admin_url( 'admin.php' )
			);

			$settings_link = '<a class="dashicons-before dashicons-admin-site" href="' . esc_url( $link ) . '">&nbsp;' . esc_html__( 'Info' ) . '</a>';
			array_unshift( $links, $settings_link );

			return $links;
		}
		
		/**
		 * Get metabox title.
		 *
		 * @since 1.7.0
		 */
		protected function get_metabox_title($post_type = '') {
			
			/** @global WP_Post $post */
			global $post;		
			
			if ( '' == $post_type ) {
				$post_type =  $post->post_type;
			}
			
			$title = 'WPGlobus Featured Images';
			
			if ( $this->is_woo() && 'product' == $post_type ) {
				$title = 'Product images (WPGlobus)';
			}
			
			return $title;
			
		}
	
		/**
		 * Check woocommerce.
		 *
		 * @since 1.7.0
		 */	
		protected function is_woo() {
			
			if ( defined( 'WOOCOMMERCE_WPGLOBUS_VERSION' ) && (defined( 'WC_VERSION' ) || defined( 'WOOCOMMERCE_VERSION' )) ) {
				return true;
			}
			
			return false;
		}		

		/**
		 * Check for disabled post type.
		 *
		 * @since 1.7.0
		 */
		public function is_disabled( $post_type = '' ) {
			
			if ( '' == $post_type ) {
				return true;
			}
			
			if ( ! empty( $this->disabled_entities ) && in_array( $post_type, $this->disabled_entities ) ) {
				return true;
			}
			
			return false;
		}		

		
	} // class

endif;
