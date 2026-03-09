# MR Wordy Shop

MR Wordy Shop is a lightweight WordPress plugin scaffold for building a highly customizable e-commerce experience.

The current implementation focuses on the core building blocks needed to start a shop project:

- a custom **Products** content type
- a **Product Categories** taxonomy
- product fields for **price** and **SKU**
- shop-wide settings for **currency** and **catalog pagination**
- a storefront shortcode for rendering product listings
- WordPress hooks and filters for customization

This repository currently provides the **foundation layer** for a custom shop. It does **not** yet include a full cart, checkout, shipping, inventory, or payment workflow.

## Requirements

- WordPress 6.0+
- PHP supported by your WordPress installation
- A WordPress site where you can install custom plugins

## Installation

1. Copy the plugin directory from this repository into your WordPress installation:

   ```text
   /wp-content/plugins/mr-wordy-shop
   ```

2. In WordPress admin, go to **Plugins**.
3. Activate **MR Wordy Shop**.
4. After activation, a **Shop** menu will appear in the admin sidebar.

### Make the plugin installable in Docker

This repository now includes two Docker Compose setups:

- `docker-compose.dev.yml` for local development with the plugin bind-mounted from this repository
- `docker-compose.prod.yml` for a production-oriented image that bakes the plugin into WordPress

#### Development

1. Start the stack:

   ```bash
   docker compose -f docker-compose.dev.yml up -d
   ```

2. Open `http://localhost:8080` and finish the normal WordPress installer.
3. In the WordPress admin, activate **MR Wordy Shop** from **Plugins**.

Because the development compose file bind-mounts `./wp-content/plugins/mr-wordy-shop`, changes you make in this repository are available in the running container immediately.

#### Production-style deployment

1. Copy the environment template and change the passwords:

   ```bash
   cp .env.example .env
   ```

2. Review the values in `.env`, especially:
   - `WORDPRESS_PORT`
   - `WORDPRESS_DB_NAME`
   - `WORDPRESS_DB_USER`
   - `WORDPRESS_DB_PASSWORD`
   - `MARIADB_ROOT_PASSWORD`
3. Build and start the stack:

   ```bash
   docker compose --env-file .env -f docker-compose.prod.yml up -d --build
   ```

4. Open `http://localhost:8080` by default, or use the host/port you configured in `.env`, and finish the normal WordPress installer.
5. Activate **MR Wordy Shop** from **Plugins**.

The production image uses `Dockerfile.prod` to copy the plugin into the WordPress image template at `/usr/src/wordpress/wp-content/plugins/mr-wordy-shop`. On first container start, the official WordPress image copies that template into the runtime web root, so the plugin is already present in the container before activation.

### Create an installable plugin ZIP

If you want to install the plugin through **Plugins → Add New Plugin → Upload Plugin**, create a ZIP whose top-level folder is `mr-wordy-shop/`.

Example:

```bash
cd wp-content/plugins
zip -r /tmp/mr-wordy-shop.zip mr-wordy-shop
```

Then upload `/tmp/mr-wordy-shop.zip` in WordPress admin. Do not zip the whole repository root, because WordPress expects the plugin files to sit directly inside a single plugin directory.

## What the Plugin Adds

### 1. Products post type

The plugin registers a public custom post type named **Products**.

Use it to create catalog entries with:

- title
- main description
- excerpt
- featured image
- price
- SKU

### 2. Product categories

The plugin registers a hierarchical taxonomy named **Product Categories** for organizing products.

Use categories to:

- build grouped storefront pages
- filter shortcode output
- structure large catalogs

### 3. Shop settings

The plugin adds a settings page here:

```text
Shop → Settings
```

Available settings:

- **Currency Code** — defaults to `USD`
- **Products per page** — defaults to `12`

These settings affect default storefront rendering and can be overridden by customizations.

## Creating Products

1. In WordPress admin, open **Shop**.
2. Select **Add Product**.
3. Fill in:
   - product title
   - long description
   - excerpt
   - featured image
4. In the **Product Details** box, enter:
   - **Price**
   - **SKU**
5. Assign one or more **Product Categories**.
6. Publish the product.

### Price format

Prices are stored as normalized decimal values.

Examples of accepted values:

- `10`
- `10.50`
- `10,50`

Invalid or malformed values are sanitized to an empty value instead of storing inconsistent data.

## Displaying Products on the Frontend

The plugin includes the following shortcode:

```text
[mr_wordy_shop_products]
```

By default, it:

- queries published products
- uses the configured **Products per page** value
- renders the product title, excerpt, and price

### Shortcode attributes

#### `limit`

Override the number of products shown:

```text
[mr_wordy_shop_products limit="8"]
```

#### `category`

Filter products by category slug:

```text
[mr_wordy_shop_products category="featured"]
```

#### Combined example

```text
[mr_wordy_shop_products limit="8" category="featured"]
```

### Put the shop UI at the site root

Yes — the shop UI can live at the root URL without exposing a PHP filename in the public URL.

The recommended WordPress-native setup is:

1. Create a page such as **Shop**.
2. Add the shortcode:

   ```text
   [mr_wordy_shop_products]
   ```

3. Go to **Settings → Reading**.
4. Set **Your homepage displays** to **A static page**.
5. Choose your **Shop** page as the homepage.
6. Go to **Settings → Permalinks** and save your preferred permalink structure.

After that, visitors can reach the storefront at `/` instead of a query-string or PHP file path. The plugin already renders through normal WordPress pages, so no separate PHP entry file is required for the shop UI.

## Customization and Extensibility

MR Wordy Shop is intended to be customized through standard WordPress extension points.

### Available filters

#### Product registration

- `mr_wordy_shop_product_labels`
- `mr_wordy_shop_product_post_type_args`

Use these to rename labels, change visibility, adjust supported features, or customize rewrite behavior.

#### Category registration

- `mr_wordy_shop_category_labels`
- `mr_wordy_shop_product_taxonomy_args`

Use these to adjust taxonomy labels, slugs, or registration options.

#### Shop settings support

- `mr_wordy_shop_currencies`

Use this to add or remove supported currencies from the settings dropdown.

Example:

```php
add_filter( 'mr_wordy_shop_currencies', function ( $currencies ) {
	$currencies['JPY'] = 'Japanese Yen (JPY)';
	return $currencies;
} );
```

#### Storefront querying and output

- `mr_wordy_shop_products_query_args`
- `mr_wordy_shop_products_shortcode_output`

Use these to:

- change which products are shown
- inject custom ordering
- add custom HTML wrappers
- integrate template partials or theme markup

Example:

```php
add_filter( 'mr_wordy_shop_products_query_args', function ( $args, $atts ) {
	$args['orderby'] = 'title';
	$args['order']   = 'ASC';
	return $args;
}, 10, 2 );
```

## Typical Theme Integration

A common setup is:

1. Use the plugin for data modeling and admin management.
2. Use your theme for page layout and visual presentation.
3. Insert the shortcode into a page such as **Shop**, **Featured Products**, or **Catalog**.
4. Add CSS in your theme to style:
   - `.mr-wordy-shop-products`
   - `.mr-wordy-shop-product`
   - `.mr-wordy-shop-product__title`
   - `.mr-wordy-shop-product__excerpt`
   - `.mr-wordy-shop-product__price`

## Current Scope

The plugin currently provides:

- product modeling
- product categorization
- shop settings
- product listing output
- extension hooks

The plugin does not yet provide:

- cart management
- checkout flows
- payment processing
- shipping logic
- tax calculation
- stock management
- customer accounts

## Repository Structure

```text
wp-content/plugins/mr-wordy-shop/
├── mr-wordy-shop.php
└── readme.txt
```

## Development Notes

- Main plugin file:

  ```text
  wp-content/plugins/mr-wordy-shop/mr-wordy-shop.php
  ```

- WordPress plugin readme:

  ```text
  wp-content/plugins/mr-wordy-shop/readme.txt
  ```

There is currently no dedicated build, lint, or automated test setup in this repository.

## License

MIT
