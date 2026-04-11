<?php
/**
 * Uninstall cleanup.
 *
 * @package SSF_Smart_Favorites
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Only plugin options are removed during uninstall to avoid database schema changes.
\delete_option( 'ssf_settings' );

