<?php
/**
 * Frontend hooks and rendering.
 *
 * @package SKYNSMFA_Smart_Favorites
 */

declare( strict_types=1 );

namespace SKYNSMFA\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class Frontend {
	/**
	 * Singleton instance.
	 *
	 * @var Frontend|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Frontend
	 */
	public static function instance(): Frontend {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_single_button_before' ), 10 );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_single_button_after' ) );
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'render_single_button_over_image' ), 30 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'render_loop_button_before' ), 20 );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_loop_button' ), 20 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_over_image_button' ), 10 );
		add_shortcode( 'skynsmfa_wishlist', array( $this, 'render_shortcode' ) );
		add_shortcode( 'skynsmfa_wishlist_icon', array( $this, 'render_icon_shortcode' ) );
	}

	/**
	 * Ensure WooCommerce session is initialized.
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
	 * Enqueue assets only when needed.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {

		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return;
		}

		wp_register_style(
			'skynsmfa-wishlist',
			SKYNSMFA_URL . 'assets/css/skynsmfa-wishlist.css',
			array(),
			SKYNSMFA_VERSION
		);

		wp_register_script(
			'skynsmfa-wishlist',
			SKYNSMFA_URL . 'assets/js/skynsmfa-wishlist.js',
			array(),
			SKYNSMFA_VERSION,
			true
		);

		wp_localize_script(
			'skynsmfa-wishlist',
			'skynsmfaWishlist',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'skynsmfa_wishlist_nonce' ),
				'i18n'     => array(
					'added'   => __( 'Added to wishlist.', 'skynet-smart-favorites' ),
					'removed' => __( 'Removed from wishlist.', 'skynet-smart-favorites' ),
					'error'   => __( 'Something went wrong.', 'skynet-smart-favorites' ),
				),
				'settings' => array(
					'notification_type' => $settings['skynsmfa_notification_type'] ?? 'toast',
					'display_mode' => $settings['skynsmfa_display_mode'] ?? 'button',
				),
			)
		);

		wp_enqueue_style( 'skynsmfa-wishlist' );
		wp_enqueue_script( 'skynsmfa-wishlist' );
	}

	/**
	 * Render button on loop items.
	 *
	 * @return void
	 */
	public function render_loop_button(): void {
		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return;
		}
		if ( 'yes' !== ( $settings['skynsmfa_loop_icon'] ?? 'yes' ) ) {
			return;
		}
		if ( 'after_add_to_cart' !== ( $settings['skynsmfa_loop_position'] ?? 'after_add_to_cart' ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$this->render_button( absint( $product->get_id() ), 'loop' );
	}

	/**
	 * Render button before loop add to cart.
	 *
	 * @return void
	 */
	public function render_loop_button_before(): void {
		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return;
		}
		if ( 'yes' !== ( $settings['skynsmfa_loop_icon'] ?? 'yes' ) ) {
			return;
		}
		if ( 'before_add_to_cart' !== ( $settings['skynsmfa_loop_position'] ?? 'after_add_to_cart' ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$this->render_button( absint( $product->get_id() ), 'loop' );
	}

	/**
	 * Render button over product image.
	 *
	 * @return void
	 */
	public function render_over_image_button(): void {
		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return;
		}
		if ( 'yes' !== ( $settings['skynsmfa_loop_icon'] ?? 'yes' ) ) {
			return;
		}
		if ( 'over_image' !== ( $settings['skynsmfa_loop_position'] ?? 'after_add_to_cart' ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$this->render_button( absint( $product->get_id() ), 'over_image' );
	}

	/**
	 * Render button on single product page.
	 *
	 * @return void
	 */
	public function render_single_button_before(): void {
		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return;
		}
		if ( 'yes' !== ( $settings['skynsmfa_single_icon'] ?? 'yes' ) ) {
			return;
		}
		if ( 'before_add_to_cart' !== ( $settings['skynsmfa_single_position'] ?? 'after_add_to_cart' ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$this->render_button( absint( $product->get_id() ), 'single' );
	}

	public function render_single_button_after(): void {
		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return;
		}
		if ( 'yes' !== ( $settings['skynsmfa_single_icon'] ?? 'yes' ) ) {
			return;
		}
		if ( 'after_add_to_cart' !== ( $settings['skynsmfa_single_position'] ?? 'after_add_to_cart' ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$this->render_button( absint( $product->get_id() ), 'single' );
	}

	/**
	 * Render button over the single product image.
	 *
	 * @return void
	 */
	public function render_single_button_over_image(): void {
		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return;
		}
		if ( 'yes' !== ( $settings['skynsmfa_single_icon'] ?? 'yes' ) ) {
			return;
		}
		if ( 'over_image' !== ( $settings['skynsmfa_single_position'] ?? 'after_add_to_cart' ) ) {
			return;
		}

		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$this->render_button( absint( $product->get_id() ), 'single' );
	}

	/**
	 * Render wishlist button.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $context Context.
	 * @return void
	 */
	private function render_button( int $product_id, string $context ): void {
		$this->ensure_session();

		$data = Session::instance()->get_data();
		$is_in_wishlist = false;
		
		if ( is_array( $data ) ) {
			foreach ( $data as $list ) {
				if ( is_array( $list ) && isset( $list[ $product_id ] ) ) {
					$is_in_wishlist = true;
					break;
				}
			}
		}

		$settings = $this->get_settings();
		$display_mode = $settings['skynsmfa_display_mode'] ?? 'button';

		if ( 'icon' === $display_mode ) {
			$this->render_icon_button( $product_id, $context, $is_in_wishlist );
		} else {
			$this->render_text_button( $product_id, $context, $is_in_wishlist );
		}
	}

	/**
	 * Render icon button.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $context Context.
	 * @param bool   $is_in_wishlist Whether product is in wishlist.
	 * @return void
	 */
	private function render_icon_button( int $product_id, string $context, bool $is_in_wishlist ): void {
		$extra_class = $is_in_wishlist ? ' is-added' : '';
		$context_class = 'over_image' === $context ? ' skynsmfa-over-image' : '';

		ob_start();
		include SKYNSMFA_PATH . 'templates/icon.php';
		$icon_html = ob_get_clean();

		$html = '<button type="button" class="skynsmfa-wishlist-button skynsmfa-icon-button' . esc_attr( $extra_class . $context_class ) . '" data-product-id="' . esc_attr( (string) $product_id ) . '" data-wishlist-key="default" data-context="' . esc_attr( $context ) . '" aria-label="' . esc_attr( $is_in_wishlist ? __( 'Remove from wishlist', 'skynet-smart-favorites' ) : __( 'Add to wishlist', 'skynet-smart-favorites' ) ) . '">';
		$html .= $icon_html;
		$html .= '</button>';

		/**
		 * Filter the wishlist icon HTML.
		 *
		 * @param string $html HTML.
		 * @param int    $product_id Product ID.
		 * @param string $context Context.
		 */
		$html = (string) apply_filters( 'skynsmfa_icon_html', $html, $product_id, $context );

		echo wp_kses( $html, $this->get_allowed_html() );
	}

	/**
	 * Render text button.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $context Context.
	 * @param bool   $is_in_wishlist Whether product is in wishlist.
	 * @return void
	 */
	private function render_text_button( int $product_id, string $context, bool $is_in_wishlist ): void {
		$label       = $is_in_wishlist ? __( 'Already in wishlist', 'skynet-smart-favorites' ) : __( 'Add to wishlist', 'skynet-smart-favorites' );
		$extra_class = $is_in_wishlist ? ' is-added' : '';
		$context_class = 'over_image' === $context ? ' skynsmfa-over-image' : '';

		$html  = '<button type="button" class="button skynsmfa-wishlist-button' . esc_attr( $extra_class . $context_class ) . '" data-product-id="' . esc_attr( (string) $product_id ) . '" data-wishlist-key="default" data-context="' . esc_attr( $context ) . '">';
		$html .= esc_html( $label );
		$html .= '</button>';

		/**
		 * Filter the wishlist button HTML.
		 *
		 * @param string $html HTML.
		 * @param int    $product_id Product ID.
		 * @param string $context Context.
		 */
		$html = (string) apply_filters( 'skynsmfa_icon_html', $html, $product_id, $context );

		echo wp_kses( $html, $this->get_allowed_html() );
	}

	/**
	 * Render the [skynsmfa_wishlist_icon] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_icon_shortcode( array $atts = array() ): string {
		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return '';
		}
		if ( 'yes' !== ( $settings['skynsmfa_custom_shortcode'] ?? 'yes' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'product_id' => 0,
			),
			$atts,
			'skynsmfa_wishlist_icon'
		);

		$product_id = absint( $atts['product_id'] );
		if ( $product_id <= 0 ) {
			return '';
		}

		$this->ensure_session();

		$data = Session::instance()->get_data();
		$is_in_wishlist = false;
		
		if ( is_array( $data ) ) {
			foreach ( $data as $list ) {
				if ( is_array( $list ) && isset( $list[ $product_id ] ) ) {
					$is_in_wishlist = true;
					break;
				}
			}
		}

		$extra_class = $is_in_wishlist ? ' is-added' : '';

		ob_start();
		include SKYNSMFA_PATH . 'templates/icon.php';
		$icon_html = ob_get_clean();

		$html = '<button type="button" class="skynsmfa-wishlist-button skynsmfa-icon-button skynsmfa-shortcode' . esc_attr( $extra_class ) . '" data-product-id="' . esc_attr( (string) $product_id ) . '" data-wishlist-key="default" data-context="shortcode" aria-label="' . esc_attr( $is_in_wishlist ? __( 'Remove from wishlist', 'skynet-smart-favorites' ) : __( 'Add to wishlist', 'skynet-smart-favorites' ) ) . '">';
		$html .= $icon_html;
		$html .= '</button>';

		/**
		 * Filter the wishlist icon HTML.
		 *
		 * @param string $html HTML.
		 * @param int    $product_id Product ID.
		 * @param string $context Context.
		 */
		$html = (string) apply_filters( 'skynsmfa_icon_html', $html, $product_id, 'shortcode' );

		return wp_kses( $html, $this->get_allowed_html() );
	}

	/**
	 * Render the [skynsmfa_wishlist] shortcode.
	 *
	 * @return string
	 */
	public function render_shortcode(): string {
		$this->ensure_session();

		$settings = $this->get_settings();
		if ( 'yes' !== ( $settings['skynsmfa_enable'] ?? 'yes' ) ) {
			return '';
		}

		if ( ! function_exists( 'WC' ) ) {
			return '<p>' . esc_html__( 'WooCommerce is required for wishlist.', 'skynet-smart-favorites' ) . '</p>';
		}

		$data = Session::instance()->get_data();
		$multiple_enabled = 'yes' === ( $settings['skynsmfa_multiple_enable'] ?? 'yes' );

		if ( ! $multiple_enabled ) {
			$data = array(
				'default' => isset( $data['default'] ) && is_array( $data['default'] ) ? $data['default'] : array(),
			);
		}

		ob_start();

		echo '<div class="skynsmfa-wishlist-container">';

		if ( $multiple_enabled ) {
			echo '<div class="skynsmfa-wishlist-controls">';
			echo '<button type="button" class="button button-secondary skynsmfa-create-list-btn">' . esc_html__( 'Create New Wishlist', 'skynet-smart-favorites' ) . '</button>';
			echo '</div>';
		}

		if ( empty( $data ) ) {
			echo '<p class="skynsmfa-empty">' . esc_html__( 'Your wishlist is currently empty.', 'skynet-smart-favorites' ) . '</p>';
			echo '</div>';
			return ob_get_clean();
		}

		foreach ( $data as $list_key => $items ) {
			$list_name = 'default' === $list_key ? __( 'My Wishlist', 'skynet-smart-favorites' ) : ucfirst( str_replace( 'custom:', '', $list_key ) );
			
			echo '<div class="skynsmfa-wishlist-box">';
			echo '<h3>' . esc_html( $list_name ) . '</h3>';
			
			if ( empty( $items ) ) {
				echo '<p>' . esc_html__( 'No items here.', 'skynet-smart-favorites' ) . '</p>';
				echo '</div>';
				continue;
			}
			
			echo '<table class="skynsmfa-wishlist-table custom-shop_table">';
			echo '<thead><tr>';
			echo '<th class="product-select"><input type="checkbox" class="skynsmfa-select-all" title="' . esc_attr__( 'Select All', 'skynet-smart-favorites' ) . '"></th>';
			echo '<th class="product-remove"></th>';
			echo '<th class="product-thumbnail"></th>';
			echo '<th class="product-name">' . esc_html__( 'Product', 'skynet-smart-favorites' ) . '</th>';
			echo '<th class="product-price">' . esc_html__( 'Price', 'skynet-smart-favorites' ) . '</th>';
			echo '<th class="product-action"></th>';
			echo '</tr></thead>';
			echo '<tbody>';

			foreach ( $items as $product_id => $item_data ) {
				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					continue;
				}

				echo '<tr class="skynsmfa-wishlist-item" data-product-id="' . esc_attr( (string) $product_id ) . '" data-wishlist-key="' . esc_attr( $list_key ) . '">';
				
				// Select
				echo '<td class="product-select">';
				echo '<input type="checkbox" class="skynsmfa-item-select" value="' . esc_attr( (string) $product_id ) . '">';
				echo '</td>';

				// Remove
				echo '<td class="product-remove">';
				echo '<a href="#" class="skynsmfa-remove-btn" title="' . esc_attr__( 'Remove this item', 'skynet-smart-favorites' ) . '" data-product-id="' . esc_attr( (string) $product_id ) . '" data-wishlist-key="' . esc_attr( $list_key ) . '">&times;</a>';
				echo '</td>';
				
				// Image
				echo '<td class="product-thumbnail">';
				echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) );
				echo '</td>';

				// Name
				echo '<td class="product-name">';
				echo '<a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
				echo '</td>';

				// Price
				echo '<td class="product-price">';
				echo wp_kses_post( $product->get_price_html() );
				echo '</td>';

				// Add to cart
				echo '<td class="product-action">';
				if ( $product->is_in_stock() ) {
					echo '<button type="button" class="button skynsmfa-move-to-cart-btn" data-product-id="' . esc_attr( (string) $product_id ) . '" data-wishlist-key="' . esc_attr( $list_key ) . '">' . esc_html__( 'Move to Cart', 'skynet-smart-favorites' ) . '</button>';
				} else {
					echo '<span class="out-of-stock">' . esc_html__( 'Out of stock', 'skynet-smart-favorites' ) . '</span>';
				}
				echo '</td>';

				echo '</tr>';
			}

			echo '</tbody></table>';

			echo '<div class="skynsmfa-wishlist-actions">';
			echo '<button type="button" class="button button-primary skynsmfa-bulk-move-btn" data-wishlist-key="' . esc_attr( $list_key ) . '">' . esc_html__( 'Move Selected to Cart', 'skynet-smart-favorites' ) . '</button>';
			echo '</div>';
			echo '</div>'; // box
		}

		echo '</div>'; // container

		return ob_get_clean();
	}

	/**
	 * Read saved settings.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		$settings = get_option( 'skynsmfa_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get allowed HTML for icon and buttons.
	 *
	 * @return array
	 */
	private function get_allowed_html(): array {
		return array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'svg' => array(
					'class'       => true,
					'aria-hidden' => true,
					'focusable'   => true,
					'viewbox'     => true,
					'viewBox'     => true,
					'xmlns'       => true,
				),
				'path' => array(
					'd'    => true,
					'fill' => true,
				),
				'button' => array(
					'type'              => true,
					'class'             => true,
					'data-product-id'   => true,
					'data-wishlist-key' => true,
					'data-context'      => true,
					'aria-label'        => true,
				),
			)
		);
	}
}

