<?php
/**
 * Database installer and helpers.
 *
 * @package SSF_Smart_Favorites
 */

declare( strict_types=1 );

namespace SSF\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class DB {
	/**
	 * Singleton instance.
	 *
	 * @var DB|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return DB
	 */
	public static function instance(): DB {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
	}

	/**
	 * Install tables.
	 *
	 * @return void
	 */
	public function install(): void {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();

		$wishlists_table = $wpdb->prefix . 'ssf_wishlists';
		$items_table     = $wpdb->prefix . 'ssf_wishlist_items';

		$sql_wishlists = "CREATE TABLE {$wishlists_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			session_key VARCHAR(64) NOT NULL DEFAULT '',
			name VARCHAR(190) NOT NULL DEFAULT '',
			is_default TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY session_key (session_key)
		) {$charset_collate};";

		$sql_items = "CREATE TABLE {$items_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			wishlist_id BIGINT(20) UNSIGNED NOT NULL,
			product_id BIGINT(20) UNSIGNED NOT NULL,
			variation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			quantity INT(11) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY wishlist_product (wishlist_id, product_id, variation_id),
			KEY product_id (product_id)
		) {$charset_collate};";

		\dbDelta( $sql_wishlists );
		\dbDelta( $sql_items );
	}
}

