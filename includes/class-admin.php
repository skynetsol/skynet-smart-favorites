<?php
/**
 * Admin settings via WooCommerce Settings API.
 *
 * @package SSF_Smart_Favorites
 */

declare( strict_types=1 );

namespace SSF\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class Admin {
	/**
	 * Singleton instance.
	 *
	 * @var Admin|null
	 */
	private static $instance = null;

	/**
	 * Section ID.
	 *
	 * @var string
	 */
	private $section_id = 'ssf_wishlist';

	/**
	 * Get singleton instance.
	 *
	 * @return Admin
	 */
	public static function instance(): Admin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		\add_action( 'admin_menu', array( $this, 'register_menu' ) );
		\add_action( 'admin_init', array( $this, 'register_settings' ) );
		\add_action( 'admin_init', array( $this, 'handle_recreate_page' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the admin menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		\add_menu_page(
			\__( 'SkyNet Smart Favorites', 'skynet-smart-favorites' ),
			\__( 'SkyNet Smart Favorites', 'skynet-smart-favorites' ),
			'manage_woocommerce',
			'skynet-smart-favorites',
			array( $this, 'render_settings_page' ),
			'data:image/svg+xml;base64,' . \base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>' ),
			56
		);
	}

	/**
	 * Register the settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		\register_setting(
			'ssf_wishlist_group',
			'ssf_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize incoming settings before saving.
	 *
	 * @param mixed $input Input settings.
	 * @return array
	 */
	public function sanitize_settings( $input ): array {
		$sanitized = array();
		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		$sanitized['ssf_enable']          = isset( $input['ssf_enable'] ) && 'yes' === $input['ssf_enable'] ? 'yes' : 'no';
		$sanitized['ssf_multiple_enable'] = isset( $input['ssf_multiple_enable'] ) && 'yes' === $input['ssf_multiple_enable'] ? 'yes' : 'no';
		$sanitized['ssf_loop_icon']       = isset( $input['ssf_loop_icon'] ) && 'yes' === $input['ssf_loop_icon'] ? 'yes' : 'no';
		$sanitized['ssf_single_icon']     = isset( $input['ssf_single_icon'] ) && 'yes' === $input['ssf_single_icon'] ? 'yes' : 'no';
		$sanitized['ssf_loop_position']   = isset( $input['ssf_loop_position'] ) ? \sanitize_text_field( $input['ssf_loop_position'] ) : 'after_add_to_cart';
		$sanitized['ssf_single_position'] = isset( $input['ssf_single_position'] ) ? \sanitize_text_field( $input['ssf_single_position'] ) : 'after_add_to_cart';
		$sanitized['ssf_display_mode']     = isset( $input['ssf_display_mode'] ) && in_array( $input['ssf_display_mode'], array( 'button', 'icon' ), true ) ? $input['ssf_display_mode'] : 'button';
		$sanitized['ssf_custom_shortcode'] = isset( $input['ssf_custom_shortcode'] ) && 'yes' === $input['ssf_custom_shortcode'] ? 'yes' : 'no';
		$sanitized['ssf_notification_type'] = isset( $input['ssf_notification_type'] ) && in_array( $input['ssf_notification_type'], array( 'none', 'toast', 'tooltip' ), true ) ? $input['ssf_notification_type'] : 'toast';
		$sanitized['wishlist_page_id']     = isset( $input['wishlist_page_id'] ) ? \absint( $input['wishlist_page_id'] ) : 0;

		return $sanitized;
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook The current admin page.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_skynet-smart-favorites' !== $hook ) {
			return;
		}

		\wp_enqueue_style(
			'ssf-admin-style',
			SSF_URL . 'assets/css/admin-settings.css',
			array(),
			SSF_VERSION
		);
	}

	/**
	 * Handle manual recreation of the wishlist page.
	 *
	 * @return void
	 */
	public function handle_recreate_page(): void {
		if ( ! isset( $_POST['ssf_recreate_page'] ) || ! \current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		\check_admin_referer( 'ssf_recreate_action' );

		Plugin::create_pages();

		\wp_safe_redirect( \add_query_arg( array( 'page' => 'skynet-smart-favorites', 'recreated' => 'true' ), \admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the custom branded settings html.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! \current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings = \get_option( 'ssf_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

 		$recreated = \sanitize_text_field( (string) \filter_input( \INPUT_GET, 'recreated', \FILTER_SANITIZE_STRING ) );
 		$settings_updated = \sanitize_text_field( (string) \filter_input( \INPUT_GET, 'settings-updated', \FILTER_SANITIZE_STRING ) );

 		$notice_html = '';
 		if ( 'true' === $recreated ) {
 			$notice_html .= '<div class="notice notice-success is-dismissible"><p>' . \esc_html__( 'Wishlist page checked and created successfully.', 'skynet-smart-favorites' ) . '</p></div>';
 		}
 		if ( 'true' === $settings_updated ) {
			$notice_html .= '<div class="notice notice-success is-dismissible"><p>' . \esc_html__( 'Settings saved successfully.', 'skynet-smart-favorites' ) . '</p></div>';
		}

		$fields = array(
			'ssf_enable'           => $settings['ssf_enable'] ?? 'yes',
			'ssf_multiple_enable'  => $settings['ssf_multiple_enable'] ?? 'yes',
			'ssf_loop_icon'        => $settings['ssf_loop_icon'] ?? 'yes',
			'ssf_single_icon'      => $settings['ssf_single_icon'] ?? 'yes',
			'ssf_loop_position'    => $settings['ssf_loop_position'] ?? 'after_add_to_cart',
			'ssf_single_position'  => $settings['ssf_single_position'] ?? 'after_add_to_cart',
			'ssf_display_mode'     => $settings['ssf_display_mode'] ?? 'button',
			'ssf_custom_shortcode' => $settings['ssf_custom_shortcode'] ?? 'yes',
			'ssf_notification_type' => $settings['ssf_notification_type'] ?? 'toast',
			'wishlist_page_id'     => $settings['wishlist_page_id'] ?? 0,
		);
		?>
		<div class="ssf-wrap wrap">
			<?php echo $notice_html; ?>
			<div class="ssf-header">
				<div class="ssf-header-inner">
					<h1><?php \esc_html_e( 'SkyNet Smart Favorites', 'skynet-smart-favorites' ); ?></h1>
					<p><?php \esc_html_e( 'Configure the ultimate standalone wishlist experience for your customers.', 'skynet-smart-favorites' ); ?></p>
				</div>
			</div>

			<div class="ssf-content-grid">
				<div class="ssf-form-wrapper ssf-card">
					<form action="options.php" method="post">
						<?php \settings_fields( 'ssf_wishlist_group' ); ?>
						
						<h2 class="ssf-section-title"><?php \esc_html_e( 'General Configuration', 'skynet-smart-favorites' ); ?></h2>
						
						<div class="ssf-field-row">
							<label for="ssf_enable" class="ssf-toggle">
								<input type="checkbox" id="ssf_enable" name="ssf_settings[ssf_enable]" value="yes" <?php \checked( $fields['ssf_enable'], 'yes' ); ?>>
								<span class="ssf-slider"></span>
								<span class="ssf-label-text"><?php \esc_html_e( 'Enable Wishlist Features globally', 'skynet-smart-favorites' ); ?></span>
							</label>
						</div>

						<div class="ssf-field-row">
							<label for="ssf_multiple_enable" class="ssf-toggle">
								<input type="checkbox" id="ssf_multiple_enable" name="ssf_settings[ssf_multiple_enable]" value="yes" <?php \checked( $fields['ssf_multiple_enable'], 'yes' ); ?>>
								<span class="ssf-slider"></span>
								<span class="ssf-label-text"><?php \esc_html_e( 'Allow users to create Multiple Wishlists', 'skynet-smart-favorites' ); ?></span>
							</label>
						</div>

						<h2 class="ssf-section-title"><?php \esc_html_e( 'Design & Display', 'skynet-smart-favorites' ); ?></h2>

						<div class="ssf-field-row">
							<label for="ssf_loop_icon" class="ssf-toggle">
								<input type="checkbox" id="ssf_loop_icon" name="ssf_settings[ssf_loop_icon]" value="yes" <?php \checked( $fields['ssf_loop_icon'], 'yes' ); ?>>
								<span class="ssf-slider"></span>
								<span class="ssf-label-text"><?php \esc_html_e( 'Show icon on Product Loop (Shop page)', 'skynet-smart-favorites' ); ?></span>
							</label>
						</div>

						<div class="ssf-field-row ssf-select-wrap">
						<label for="ssf_loop_position"><?php \esc_html_e( 'Button Position', 'skynet-smart-favorites' ); ?></label>
						<select id="ssf_loop_position" name="ssf_settings[ssf_loop_position]" class="ssf-select">
							<option value="before_add_to_cart" <?php \selected( $fields['ssf_loop_position'], 'before_add_to_cart' ); ?>><?php \esc_html_e( 'Before "Add to cart"', 'skynet-smart-favorites' ); ?></option>
							<option value="after_add_to_cart" <?php \selected( $fields['ssf_loop_position'], 'after_add_to_cart' ); ?>><?php \esc_html_e( 'After "Add to cart"', 'skynet-smart-favorites' ); ?></option>
							<option value="over_image" <?php \selected( $fields['ssf_loop_position'], 'over_image' ); ?>><?php \esc_html_e( 'Over Product Image', 'skynet-smart-favorites' ); ?></option>
						</select>
					</div>
						<div class="ssf-field-row">
							<label for="ssf_single_icon" class="ssf-toggle">
								<input type="checkbox" id="ssf_single_icon" name="ssf_settings[ssf_single_icon]" value="yes" <?php \checked( $fields['ssf_single_icon'], 'yes' ); ?>>
								<span class="ssf-slider"></span>
								<span class="ssf-label-text"><?php \esc_html_e( 'Show icon on Single Product Page', 'skynet-smart-favorites' ); ?></span>
							</label>
						</div>

				<div class="ssf-field-row ssf-select-wrap">
					<label for="ssf_single_position"><?php \esc_html_e( 'Button Position', 'skynet-smart-favorites' ); ?></label>
					<select id="ssf_single_position" name="ssf_settings[ssf_single_position]" class="ssf-select">
						<option value="before_add_to_cart" <?php \selected( $fields['ssf_single_position'], 'before_add_to_cart' ); ?>><?php \esc_html_e( 'Before "Add to cart"', 'skynet-smart-favorites' ); ?></option>
						<option value="after_add_to_cart" <?php \selected( $fields['ssf_single_position'], 'after_add_to_cart' ); ?>><?php \esc_html_e( 'After "Add to cart"', 'skynet-smart-favorites' ); ?></option>
						<option value="over_image" <?php \selected( $fields['ssf_single_position'], 'over_image' ); ?>><?php \esc_html_e( 'Over Product Image', 'skynet-smart-favorites' ); ?></option>
					</select>
				</div>


						<div class="ssf-field-row ssf-select-wrap">
							<label for="ssf_display_mode"><?php \esc_html_e( 'Display Mode', 'skynet-smart-favorites' ); ?></label>
							<select id="ssf_display_mode" name="ssf_settings[ssf_display_mode]" class="ssf-select">
								<option value="button" <?php \selected( $fields['ssf_display_mode'], 'button' ); ?>><?php \esc_html_e( 'Button with Text', 'skynet-smart-favorites' ); ?></option>
								<option value="icon" <?php \selected( $fields['ssf_display_mode'], 'icon' ); ?>><?php \esc_html_e( 'Icon Only', 'skynet-smart-favorites' ); ?></option>
							</select>
						</div>

						<div class="ssf-field-row ssf-select-wrap">
							<label for="ssf_notification_type"><?php \esc_html_e( 'Notification Type', 'skynet-smart-favorites' ); ?></label>
							<select id="ssf_notification_type" name="ssf_settings[ssf_notification_type]" class="ssf-select">
								<option value="toast" <?php \selected( $fields['ssf_notification_type'], 'toast' ); ?>><?php \esc_html_e( 'Toast notification', 'skynet-smart-favorites' ); ?></option>
								<option value="tooltip" <?php \selected( $fields['ssf_notification_type'], 'tooltip' ); ?>><?php \esc_html_e( 'Tooltip notification', 'skynet-smart-favorites' ); ?></option>
								<option value="none" <?php \selected( $fields['ssf_notification_type'], 'none' ); ?>><?php \esc_html_e( 'No notification', 'skynet-smart-favorites' ); ?></option>
							</select>
						</div>



						<div class="ssf-field-row">
							<label for="ssf_custom_shortcode" class="ssf-toggle">
								<input type="checkbox" id="ssf_custom_shortcode" name="ssf_settings[ssf_custom_shortcode]" value="yes" <?php \checked( $fields['ssf_custom_shortcode'], 'yes' ); ?>>
								<span class="ssf-slider"></span>
								<span class="ssf-label-text"><?php \esc_html_e( 'Enable custom shortcode [ssf_wishlist_icon]', 'skynet-smart-favorites' ); ?></span>
							</label>
						</div>

						<h2 class="ssf-section-title"><?php \esc_html_e( 'Wishlist Page Selection', 'skynet-smart-favorites' ); ?></h2>
						
						<div class="ssf-field-row ssf-select-wrap">
							<label for="wishlist_page_id"><?php \esc_html_e( 'Frontend Display Page', 'skynet-smart-favorites' ); ?></label>
							<?php
							\wp_dropdown_pages(
								array(
									'name'             => 'ssf_settings[wishlist_page_id]',
									'id'               => 'wishlist_page_id',
									'show_option_none' => \__( '&mdash; Select a Page &mdash;', 'skynet-smart-favorites' ),
									'option_none_value'=> '0',
									'selected'         => $fields['wishlist_page_id'],
									'class'            => 'ssf-select'
								)
							);
							?>
							<p class="description"><?php \esc_html_e( 'Ensure this page contains the [ssf_wishlist] shortcode.', 'skynet-smart-favorites' ); ?></p>
						</div>

						<div class="ssf-submit-wrap">
							<?php \submit_button( \__( 'Save All Changes', 'skynet-smart-favorites' ), 'primary ssf-btn-primary', 'submit', false ); ?>
						</div>
					</form>
				</div>

				<div class="ssf-sidebar">
					<div class="ssf-card">
						<h3><?php \esc_html_e( 'System Tools', 'skynet-smart-favorites' ); ?></h3>
						<p><?php \esc_html_e( 'If you accidentally deleted the Wishlist page, you can recreate it easily. This will generate a new page with the necessary shortcode.', 'skynet-smart-favorites' ); ?></p>
						<form method="post" action="">
							<?php \wp_nonce_field( 'ssf_recreate_action' ); ?>
							<button type="submit" name="ssf_recreate_page" class="button button-secondary ssf-btn-secondary">
								<?php \esc_html_e( 'Recreate Wishlist Page', 'skynet-smart-favorites' ); ?>
							</button>
						</form>
					</div>

					<div class="ssf-card ssf-pro-tip">
						<h3><?php \esc_html_e( 'Pro Tip', 'skynet-smart-favorites' ); ?> &#128161;</h3>
						<p><?php \esc_html_e( 'Use the shortcode [ssf_wishlist] on any page to immediately render a user\'s saved items and lists.', 'skynet-smart-favorites' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

