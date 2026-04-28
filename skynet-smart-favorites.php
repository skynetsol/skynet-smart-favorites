<?php
/**
 * Plugin Name: SkyNet Smart Favorites
 * Description: Flexible wishlist and favorite product support for WooCommerce, including guest session handling and login sync.
 * Version:     1.0.0
 * Author:      SkyNet
 * Author URI:  https://skynetsol.com
 * Text Domain: skynet-smart-favorites
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package SKYNSMFA_Smart_Favorites
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKYNSMFA_VERSION', '1.0.0' );
define( 'SKYNSMFA_PATH', plugin_dir_path( __FILE__ ) );
define( 'SKYNSMFA_URL', plugin_dir_url( __FILE__ ) );
define( 'SKYNSMFA_FILE', __FILE__ );

require_once SKYNSMFA_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'SKYNSMFA\\Wishlist\\Plugin', 'activate' ) );
add_action( 'plugins_loaded', array( 'SKYNSMFA\\Wishlist\\Plugin', 'boot' ) );
