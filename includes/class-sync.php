<?php
/**
 * Sync guest wishlist to user after login (skeleton).
 *
 * @package SSF_Smart_Favorites
 */

declare( strict_types=1 );

namespace SSF\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class Sync {
	/**
	 * Singleton instance.
	 *
	 * @var Sync|null
	 */
	private static $instance = null;

	/**
	 * Session flag key to prevent repeat sync.
	 *
	 * @var string
	 */
	private $synced_key = 'ssf_synced';

	/**
	 * Get singleton instance.
	 *
	 * @return Sync
	 */
	public static function instance(): Sync {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		\add_action( 'woocommerce_init', array( $this, 'maybe_sync' ), 20 );
	}

	/**
	 * Sync guest data into user context after login.
	 *
	 * @return void
	 */
	public function maybe_sync(): void {
		if ( ! \is_user_logged_in() ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$already = WC()->session->get( $this->synced_key, false );
		if ( $already ) {
			return;
		}

		$data = Session::instance()->get_data();
		if ( empty( $data ) ) {
			WC()->session->set( $this->synced_key, true );
			return;
		}

		// Skeleton behavior: keep data in session and mark as synced.
		// A production implementation would persist to {$wpdb->prefix}ssf_ tables for the user.
		WC()->session->set( $this->synced_key, true );
	}
}

