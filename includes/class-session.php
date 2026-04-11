<?php
/**
 * Session layer for guest wishlists.
 *
 * @package SSF_Smart_Favorites
 */

declare( strict_types=1 );

namespace SSF\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class Session {
	/**
	 * Singleton instance.
	 *
	 * @var Session|null
	 */
	private static $instance = null;

	/**
	 * Session key for owner token.
	 *
	 * @var string
	 */
	private $owner_key = 'ssf_owner';

	/**
	 * Session key for wishlist data.
	 *
	 * @var string
	 */
	private $data_key = 'ssf_wishlist';

	/**
	 * Cached owner.
	 *
	 * @var string|null
	 */
	private $cached_owner = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Session
	 */
	public static function instance(): Session {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		\add_action( 'woocommerce_init', array( $this, 'maybe_init_session' ) );
	}

	/**
	 * Ensure WC session exists and owner is set.
	 *
	 * @return void
	 */
	public function maybe_init_session(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$this->get_owner();
	}

	/**
	 * Get session owner token (guest) or user-based token.
	 *
	 * @return string
	 */
	public function get_owner(): string {
		if ( null !== $this->cached_owner ) {
			return $this->cached_owner;
		}

		if ( \is_user_logged_in() ) {
			$this->cached_owner = 'user:' . (string) \get_current_user_id();
			return $this->cached_owner;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			$this->cached_owner = 'guest:0';
			return $this->cached_owner;
		}

		$owner = (string) WC()->session->get( $this->owner_key, '' );

		if ( '' === $owner ) {
			$owner = 'guest:' . \wp_generate_password( 20, false, false );
			WC()->session->set( $this->owner_key, $owner );
		}

		$this->cached_owner = $owner;
		return $owner;
	}

	/**
	 * Get wishlists data from session.
	 *
	 * Format:
	 * [
	 *   'default' => [ product_id => [ 'product_id' => int, 'qty' => int ] ],
	 *   'custom:<id>' => [ ... ],
	 * ]
	 *
	 * @return array<string, array<int, array<string, int>>>
	 */
	public function get_data(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return array();
		}

		$data = WC()->session->get( $this->data_key, array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Persist wishlists data in session.
	 *
	 * @param array $data Data to store.
	 * @return void
	 */
	public function set_data( array $data ): void {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		WC()->session->set( $this->data_key, $data );
	}

	/**
	 * Add a product to a session wishlist.
	 *
	 * @param int    $product_id Product ID.
	 * @param int    $qty Quantity.
	 * @param string $wishlist_key Wishlist key.
	 * @return array Updated data.
	 */
	public function add_item( int $product_id, int $qty = 1, string $wishlist_key = 'default' ): array {
		$data = $this->get_data();

		if ( ! isset( $data[ $wishlist_key ] ) || ! is_array( $data[ $wishlist_key ] ) ) {
			$data[ $wishlist_key ] = array();
		}

		$current_qty = 0;
		if ( isset( $data[ $wishlist_key ][ $product_id ]['qty'] ) ) {
			$current_qty = absint( $data[ $wishlist_key ][ $product_id ]['qty'] );
		}

		$data[ $wishlist_key ][ $product_id ] = array(
			'product_id' => $product_id,
			'qty'        => max( 1, $current_qty + $qty ),
		);

		$this->set_data( $data );
		return $data;
	}

	/**
	 * Remove a product from a session wishlist.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $wishlist_key Wishlist key.
	 * @return array Updated data.
	 */
	public function remove_item( int $product_id, string $wishlist_key = 'default' ): array {
		$data = $this->get_data();

		if ( isset( $data[ $wishlist_key ][ $product_id ] ) ) {
			unset( $data[ $wishlist_key ][ $product_id ] );
		}

		$this->set_data( $data );
		return $data;
	}
}

