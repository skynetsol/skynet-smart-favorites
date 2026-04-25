<?php
/**
 * AJAX handlers.
 *
 * @package SKYNSMFA_Smart_Favorites
 */

declare( strict_types=1 );

namespace SKYNSMFA\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class Ajax {
	/**
	 * Singleton instance.
	 *
	 * @var Ajax|null
	 */
	private static $instance = null;

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	private $nonce_action = 'skynsmfa_wishlist_nonce';

	/**
	 * Get singleton instance.
	 *
	 * @return Ajax
	 */
	public static function instance(): Ajax {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_ajax_skynsmfa_add_to_wishlist', array( $this, 'add_to_wishlist' ) );
		add_action( 'wp_ajax_nopriv_skynsmfa_add_to_wishlist', array( $this, 'add_to_wishlist' ) );

		add_action( 'wp_ajax_skynsmfa_remove_from_wishlist', array( $this, 'remove_from_wishlist' ) );
		add_action( 'wp_ajax_nopriv_skynsmfa_remove_from_wishlist', array( $this, 'remove_from_wishlist' ) );

		add_action( 'wp_ajax_skynsmfa_create_wishlist', array( $this, 'create_wishlist' ) );
		add_action( 'wp_ajax_nopriv_skynsmfa_create_wishlist', array( $this, 'create_wishlist' ) );

		add_action( 'wp_ajax_skynsmfa_get_wishlist', array( $this, 'get_wishlist' ) );
		add_action( 'wp_ajax_nopriv_skynsmfa_get_wishlist', array( $this, 'get_wishlist' ) );

		add_action( 'wp_ajax_skynsmfa_move_to_cart', array( $this, 'move_to_cart' ) );
		add_action( 'wp_ajax_nopriv_skynsmfa_move_to_cart', array( $this, 'move_to_cart' ) );

		add_action( 'wp_ajax_skynsmfa_move_multiple_to_cart', array( $this, 'move_multiple_to_cart' ) );
		add_action( 'wp_ajax_nopriv_skynsmfa_move_multiple_to_cart', array( $this, 'move_multiple_to_cart' ) );
	}

	/**
	 * Ensure WooCommerce session is initialized for AJAX requests.
	 *
	 * @return void
	 */
	private function ensure_session(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	/**
	 * Add item to wishlist (session-backed skeleton).
	 *
	 * @return void
	 */
	public function add_to_wishlist(): void {
		$this->ensure_session();

		check_ajax_referer( $this->nonce_action, 'nonce' );

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$qty          = isset( $_POST['qty'] ) ? absint( wp_unslash( $_POST['qty'] ) ) : 1;
		$wishlist_key = isset( $_POST['wishlist_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wishlist_key'] ) ) : 'default';

		if ( $product_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid product.', 'skynet-smart-favorites' ),
				)
			);
		}

		$data = Session::instance()->add_item( $product_id, max( 1, $qty ), $wishlist_key );

		/**
		 * Fires after a product is added to wishlist.
		 *
		 * @param int    $product_id Product ID.
		 * @param string $wishlist_key Wishlist key.
		 */
		do_action( 'skynsmfa_wishlist_added', $product_id, $wishlist_key );

		wp_send_json_success(
			array(
				'owner' => Session::instance()->get_owner(),
				'data'  => $data,
			)
		);
	}

	/**
	 * Remove item from wishlist (session-backed skeleton).
	 *
	 * @return void
	 */
	public function remove_from_wishlist(): void {
		$this->ensure_session();

		check_ajax_referer( $this->nonce_action, 'nonce' );

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$wishlist_key = isset( $_POST['wishlist_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wishlist_key'] ) ) : 'default';

		if ( $product_id <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid product.', 'skynet-smart-favorites' ),
				)
			);
		}

		$data = Session::instance()->remove_item( $product_id, $wishlist_key );

		/**
		 * Fires after a product is removed from wishlist.
		 *
		 * @param int    $product_id Product ID.
		 * @param string $wishlist_key Wishlist key.
		 */
		do_action( 'skynsmfa_wishlist_removed', $product_id, $wishlist_key );

		wp_send_json_success(
			array(
				'owner' => Session::instance()->get_owner(),
				'data'  => $data,
			)
		);
	}

	/**
	 * Create a new wishlist list.
	 *
	 * @return void
	 */
	public function create_wishlist(): void {
		$this->ensure_session();

		check_ajax_referer( $this->nonce_action, 'nonce' );

		$list_name = isset( $_POST['list_name'] ) ? sanitize_text_field( wp_unslash( $_POST['list_name'] ) ) : '';

		if ( '' === $list_name ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please provide a wishlist name.', 'skynet-smart-favorites' ),
				)
			);
		}

		$data = Session::instance()->get_data();
		$base_key = 'custom:' . sanitize_title( $list_name );
		$key = $base_key;
		$index = 1;

		while ( isset( $data[ $key ] ) ) {
			$key = $base_key . '-' . $index;
			$index++;
		}

		$data[ $key ] = array();
		Session::instance()->set_data( $data );

		wp_send_json_success(
			array(
				'key'  => $key,
				'name' => $list_name,
				'data' => $data,
			)
		);
	}

	/**
	 * Fetch wishlist data.
	 *
	 * @return void
	 */
	public function get_wishlist(): void {
		$this->ensure_session();

		check_ajax_referer( $this->nonce_action, 'nonce' );

		wp_send_json_success(
			array(
				'owner' => Session::instance()->get_owner(),
				'data'  => Session::instance()->get_data(),
			)
		);
	}

	/**
	 * Move a single item to cart.
	 *
	 * @return void
	 */
	public function move_to_cart(): void {
		$this->ensure_session();

		check_ajax_referer( $this->nonce_action, 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not available.', 'skynet-smart-favorites' ) ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$wishlist_key = isset( $_POST['wishlist_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wishlist_key'] ) ) : 'default';

		if ( $product_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'skynet-smart-favorites' ) ) );
		}

		// Add to WC Cart
		$added = WC()->cart->add_to_cart( $product_id );

		if ( $added ) {
			// Remove from wishlist
			Session::instance()->remove_item( $product_id, $wishlist_key );
			wp_send_json_success( array( 'message' => __( 'Product moved to cart.', 'skynet-smart-favorites' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not add to cart.', 'skynet-smart-favorites' ) ) );
		}
	}

	/**
	 * Move multiple items to cart.
	 *
	 * @return void
	 */
	public function move_multiple_to_cart(): void {
		$this->ensure_session();

		check_ajax_referer( $this->nonce_action, 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not available.', 'skynet-smart-favorites' ) ) );
		}

		$product_ids = isset( $_POST['product_ids'] ) && is_array( $_POST['product_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['product_ids'] ) ) : array();
		$wishlist_key = isset( $_POST['wishlist_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wishlist_key'] ) ) : 'default';

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No products selected.', 'skynet-smart-favorites' ) ) );
		}

		$added_count = 0;
		foreach ( $product_ids as $pid ) {
			if ( $pid > 0 ) {
				$added = WC()->cart->add_to_cart( $pid );
				if ( $added ) {
					Session::instance()->remove_item( $pid, $wishlist_key );
					$added_count++;
				}
			}
		}

		if ( $added_count > 0 ) {
			// translators: %d is the number of products moved from the wishlist to the cart.
			wp_send_json_success( array( 'message' => sprintf( _n( '%d product moved to cart.', '%d products moved to cart.', $added_count, 'skynet-smart-favorites' ), $added_count ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not add products to cart.', 'skynet-smart-favorites' ) ) );
		}
	}
}

