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
 * @package SSF_Smart_Favorites
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSF_VERSION', time() );
define( 'SSF_PATH', plugin_dir_path( __FILE__ ) );
define( 'SSF_URL', plugin_dir_url( __FILE__ ) );
define( 'SSF_FILE', __FILE__ );

require_once SSF_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'SSF\\Wishlist\\Plugin', 'activate' ) );
add_action( 'plugins_loaded', array( 'SSF\\Wishlist\\Plugin', 'boot' ) );
