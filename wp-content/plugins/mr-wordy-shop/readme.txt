=== MR Wordy Shop ===
Contributors: mihastele
Tags: ecommerce, shop, storefront, products
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Build a customizable WordPress storefront with product management, category organization, and a product listing shortcode.

== Description ==

MR Wordy Shop provides a lightweight e-commerce foundation for WordPress projects that need custom product experiences without locking the site into a rigid storefront theme.

Features:

* Custom `Products` post type with category taxonomy
* Product details metabox for price and SKU
* Shop settings for currency and storefront pagination
* `[mr_wordy_shop_products]` shortcode for storefront rendering
* Filters for labels, registration args, currencies, shortcode queries, and output

== Installation ==

1. Copy the `mr-wordy-shop` plugin folder into `/wp-content/plugins/`
2. Activate **MR Wordy Shop** from the WordPress admin
3. Add products under **Shop**
4. Insert `[mr_wordy_shop_products]` into any page or post
5. To show the shop at the site root, create a page with the shortcode and assign it as the homepage under **Settings > Reading**

== Shortcode ==

`[mr_wordy_shop_products]`

Optional attributes:

* `limit` - override the configured products per page value
* `category` - filter products by category slug

== Extensibility ==

Available filters:

* `mr_wordy_shop_product_labels`
* `mr_wordy_shop_product_post_type_args`
* `mr_wordy_shop_category_labels`
* `mr_wordy_shop_product_taxonomy_args`
* `mr_wordy_shop_currencies`
* `mr_wordy_shop_products_query_args`
* `mr_wordy_shop_products_shortcode_output`
