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

$wishlists_table = \esc_sql( $wpdb->prefix . 'ssf_wishlists' );
$items_table     = \esc_sql( $wpdb->prefix . 'ssf_wishlist_items' );

// Table names are derived from $wpdb->prefix and constant suffixes.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( sprintf( 'DROP TABLE IF EXISTS %s', $wishlists_table ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( sprintf( 'DROP TABLE IF EXISTS %s', $items_table ) );

\delete_option( 'ssf_settings' );

