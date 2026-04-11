<?php
/**
 * Main plugin bootstrap.
 *
 * @package SSF_Smart_Favorites
 */

declare( strict_types=1 );

namespace SSF\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether WooCommerce is active.
	 *
	 * @var bool
	 */
	private $wc_active = false;

	/**
	 * Boot plugin on plugins_loaded.
	 *
	 * @return void
	 */
	public static function boot(): void {
		self::instance()->init();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {
	}

	/**
	 * Prevent unserializing.
	 *
	 * @return void
	 */
	public function __wakeup(): void {
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	private function init(): void {
		$this->wc_active = class_exists( 'WooCommerce' );

		if ( ! $this->wc_active ) {
			\add_action( 'admin_notices', array( $this, 'render_wc_missing_notice' ) );
			return;
		}

		\spl_autoload_register( array( __CLASS__, 'autoload' ) );
		Admin::instance();
		Session::instance();
		Ajax::instance();
		Frontend::instance();
		Sync::instance();
	}

	/**
	 * Simple autoloader for SSF\Wishlist classes.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== \strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = \substr( $class_name, \strlen( $prefix ) );
		$relative = \strtolower( \str_replace( '\\', '-', $relative ) );

		$path = SSF_PATH . 'includes/class-' . $relative . '.php';

		if ( \file_exists( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		\spl_autoload_register( array( __CLASS__, 'autoload' ) );

		DB::instance()->install();

		if ( false === \get_option( 'ssf_settings', false ) ) {
			\add_option(
				'ssf_settings',
				array(
					'ssf_enable'          => 'yes',
					'ssf_multiple_enable' => 'yes',
					'ssf_loop_icon'       => 'yes',
					'ssf_single_icon'     => 'yes',
					'ssf_loop_position'   => 'after_add_to_cart',
					'ssf_single_position' => 'after_add_to_cart',
					'ssf_display_mode'    => 'button',
					'ssf_custom_shortcode' => 'yes',
					'ssf_notification_type' => 'toast',
				),
				'',
				false
			);
		}

		self::create_pages();
	}

	/**
	 * Create default wishlist page if it doesn't exist.
	 *
	 * @return void
	 */
	public static function create_pages(): void {
		$settings = \get_option( 'ssf_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Check if page already exists and is valid
		if ( ! empty( $settings['wishlist_page_id'] ) && 'publish' === \get_post_status( $settings['wishlist_page_id'] ) ) {
			return;
		}

		// Try to find an existing wishlist page by title.
		$pages = \get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				's'              => __( 'Wishlist', 'skynet-smart-favorites' ),
				'posts_per_page' => 5,
			)
		);

		$page = null;
		foreach ( $pages as $found_page ) {
			if ( $found_page instanceof \WP_Post && $found_page->post_title === __( 'Wishlist', 'skynet-smart-favorites' ) ) {
				$page = $found_page;
				break;
			}
		}

		if ( $page instanceof \WP_Post && 'publish' === $page->post_status ) {
			$page_id_to_save = $page->ID;

			if ( ! \has_shortcode( $page->post_content, 'ssf_wishlist' ) ) {
				\wp_update_post(
					array(
						'ID'           => $page_id_to_save,
						'post_content' => trim( $page->post_content ) . "\n\n[ssf_wishlist]",
					)
				);
			}
		} else {
			// Create a new one
			$page_id_to_save = \wp_insert_post(
				array(
					'post_title'     => 'Wishlist',
					'post_content'   => '[ssf_wishlist]',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
				)
			);
		}

		if ( ! \is_wp_error( $page_id_to_save ) ) {
			$settings['wishlist_page_id'] = $page_id_to_save;
			\update_option( 'ssf_settings', $settings );
		}
	}

	/**
	 * Admin notice for missing WooCommerce.
	 *
	 * @return void
	 */
	public function render_wc_missing_notice(): void {
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo \esc_html__( 'SkyNet Smart Favorites requires WooCommerce to be installed and active.', 'skynet-smart-favorites' );
		echo '</p></div>';
	}
}
