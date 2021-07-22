<?php
/**
 * Plugin Name: WPGlobus Featured Images
 * Plugin URI: https://github.com/WPGlobus/wpglobus-featured-images
 * Description: Set featured image separately for each language defined in <a href="https://wordpress.org/plugins/wpglobus/">WPGlobus</a>.
 * Text Domain: wpglobus-featured-images
 * Domain Path: /languages/
 * Version: 2.4.0
 * Author: WPGlobus
 * Author URI: https://wpglobus.com/
 * Network: false
 * License: GPL2
 * Credits: Alex Gor (alexgff) and Gregory Karpinsky (tivnet)
 * Copyright 2015-2021 WPGlobus
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

define( 'WPGLOBUS_FEATURED_IMAGES_VERSION', '2.4.0' );

add_action( 'plugins_loaded', 'wpglobus_featured_images_load', 11 );
function wpglobus_featured_images_load() {
	if ( class_exists( 'WPGlobus' ) && 'off' != WPGlobus::Config()->toggle ) {
		require_once dirname( __FILE__ ) . '/includes/class-wpglobus-featured-images.php';
		new WPGlobus_Featured_Images( __FILE__ );
	}
}

# --- EOF