=== SkyNet Smart Favorites ===
Contributors: satinath1, SkyNet
Donate link:https://skynetsol.com/
Tags: wishlist, woocommerce, multiple wishlist, ajax, shortcodes
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Flexible WooCommerce wishlist support with multiple lists and guest session preservation.

== Short Description ==
Flexible WooCommerce wishlist support with multiple wishlists, guest session preservation, and fast move-to-cart actions.

== Description ==

SkyNet Smart Favorites adds a polished wishlist experience to WooCommerce stores. Customers can save favorite products, organize items across multiple wishlists, and move products from the wishlist directly to the cart.

The plugin is optimized for WooCommerce workflows and includes admin settings for display, notifications, and wishlist page configuration.

- Save favorite products from shop and product pages.
- Manage multiple wishlist collections.
- Preserve guest wishlist choices during browsing sessions.
- Move wishlist items to cart in one click.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin from the WordPress 'Plugins' screen.
3. Go to the new admin menu item: **SkyNet Smart Favorites**.
4. Configure the plugin settings, choose display options, and confirm the wishlist page is created.

== Frequently Asked Questions ==

= Can I use the wishlist without WooCommerce? =

No. This plugin requires WooCommerce to be installed and active.

= Does it support guests? =

Yes. Guest wishlist data is stored in the WooCommerce customer session while the visitor is browsing.

= Is my data shared externally? =

No. This plugin stores wishlist data locally in the site's WooCommerce session and does not send customer data to external services.

= Will my guest wishlist sync after login? =

Yes. The plugin includes a login-sync foundation that preserves session wishlist data after a user logs in.

= How do I display the wishlist page? =

The plugin creates a wishlist page automatically using the `[skynsmfa_wishlist]` shortcode. You can also use `[skynsmfa_wishlist_icon product_id="123"]` to render an add/remove wishlist icon for a specific product.

= Where are the settings located? =

Open the **SkyNet Smart Favorites** admin menu to configure global wishlist behavior, button display, notification type, and page settings.

== Screenshots ==

=== 1. Settings Page ===

![Settings](assets/images/screenshot.png)

=== 2. Shop Loop Button ===

![Shop Loop](assets/images/screenshot-1.png)

=== 3. Product Page Button ===

![Product Page](assets/images/screenshot-3.png)

=== 4. Wishlist Page ===

![Wishlist](assets/images/screenshot-2.png)

== Upgrade Notice ==

= 1.0.0 =
Initial release with multiple wishlist support, guest session handling, and basic login sync.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added multiple wishlist support for WooCommerce.
* Added guest session wishlist support and login-sync foundation.
* Added shortcodes for wishlist display and icon actions.
* Added move-to-cart actions from wishlist items.

== Support ==

For support, questions, or bug reports, please use the plugin support forum on WordPress.org after the plugin is published. Include your WooCommerce and WordPress versions, and any relevant error messages.

== License ==

This plugin is released under the GPLv2 or later license. See the plugin header for full license details.

