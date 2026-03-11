=== MR Wordy Shop ===
Contributors: mihastele
Tags: ecommerce, shop, storefront, products, woocommerce, cookie-consent
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 0.2.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Build a customizable WordPress storefront with product management, sleek card-based design, cookie consent, WooCommerce integration, and a product listing shortcode.

== Description ==

MR Wordy Shop provides a modern e-commerce foundation for WordPress projects that need custom product experiences with a sleek design, GDPR cookie consent, and optional WooCommerce integration.

Features:

* Custom `Products` post type with category taxonomy
* Product details metabox for price and SKU
* **Sleek card-based product grid** with responsive CSS and hover effects
* **Cookie consent banner** with admin settings (GDPR-ready)
* **WordPress Customizer integration** for colours, layout, and border radius
* **WooCommerce integration** – auto-sync products and add "Add to Cart" buttons
* Shop settings for currency, pagination, cookie policy, and WooCommerce options
* Admin product list with image and price columns
* `[mr_wordy_shop_products]` shortcode with featured images
* Filters for labels, registration args, currencies, shortcode queries, and output

== Installation ==

1. Copy the `mr-wordy-shop` plugin folder into `/wp-content/plugins/`
2. Activate **MR Wordy Shop** from the WordPress admin
3. Add products under **Shop**
4. Insert `[mr_wordy_shop_products]` into any page or post
5. To show the shop at the site root, create a page with the shortcode and assign it as the homepage under **Settings > Reading**
6. Optionally install **WooCommerce** for cart and checkout support

== Shortcode ==

`[mr_wordy_shop_products]`

Optional attributes:

* `limit` - override the configured products per page value
* `category` - filter products by category slug

== Cookie Consent ==

Enable the cookie consent banner under **Shop > Settings > Cookie Consent**.
Configure the banner message, link a cookie policy page, and customise the link text.

== Customizer ==

Go to **Appearance > Customize > MR Wordy Shop** to adjust:

* Primary colour
* Card background colour
* Text colour
* Grid columns (2, 3, or 4)
* Card border radius

== WooCommerce ==

When WooCommerce is active, enable auto-sync and "Add to Cart" buttons under **Shop > Settings > WooCommerce Integration**.

== Extensibility ==

Available filters:

* `mr_wordy_shop_product_labels`
* `mr_wordy_shop_product_post_type_args`
* `mr_wordy_shop_category_labels`
* `mr_wordy_shop_product_taxonomy_args`
* `mr_wordy_shop_currencies`
* `mr_wordy_shop_products_query_args`
* `mr_wordy_shop_products_shortcode_output`
