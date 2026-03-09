<?php
/**
 * Plugin Name: MR Wordy Shop
 * Plugin URI:  https://github.com/mihastele/mr-wordy-shop
 * Description: A customizable e-commerce foundation for WordPress with products, storefront rendering, and extension hooks.
 * Version:     0.1.0
 * Author:      Miha
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: mr-wordy-shop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MR_Wordy_Shop_Plugin {
	const OPTION_GROUP = 'mr_wordy_shop_options';
	const OPTION_NAME  = 'mr_wordy_shop_settings';
	const META_PRICE   = '_mr_wordy_shop_price';
	const META_SKU     = '_mr_wordy_shop_sku';

	/**
	 * Boot the plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_product_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_product_taxonomy' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_product_metabox' ) );
		add_action( 'save_post_mrws_product', array( __CLASS__, 'save_product_meta' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_shortcode( 'mr_wordy_shop_products', array( __CLASS__, 'render_products_shortcode' ) );
	}

	/**
	 * Register the product post type.
	 *
	 * @return void
	 */
	public static function register_product_post_type() {
		$labels = array(
			'name'               => __( 'Products', 'mr-wordy-shop' ),
			'singular_name'      => __( 'Product', 'mr-wordy-shop' ),
			'add_new'            => __( 'Add Product', 'mr-wordy-shop' ),
			'add_new_item'       => __( 'Add New Product', 'mr-wordy-shop' ),
			'edit_item'          => __( 'Edit Product', 'mr-wordy-shop' ),
			'new_item'           => __( 'New Product', 'mr-wordy-shop' ),
			'view_item'          => __( 'View Product', 'mr-wordy-shop' ),
			'search_items'       => __( 'Search Products', 'mr-wordy-shop' ),
			'not_found'          => __( 'No products found.', 'mr-wordy-shop' ),
			'not_found_in_trash' => __( 'No products found in Trash.', 'mr-wordy-shop' ),
			'menu_name'          => __( 'Shop', 'mr-wordy-shop' ),
		);

		$args = array(
			'labels'             => apply_filters( 'mr_wordy_shop_product_labels', $labels ),
			'public'             => true,
			'show_in_rest'       => true,
			'has_archive'        => true,
			'menu_icon'          => 'dashicons-cart',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'            => array( 'slug' => 'products' ),
			'menu_position'      => 25,
		);

		register_post_type( 'mrws_product', apply_filters( 'mr_wordy_shop_product_post_type_args', $args ) );
	}

	/**
	 * Register the product taxonomy.
	 *
	 * @return void
	 */
	public static function register_product_taxonomy() {
		$labels = array(
			'name'          => __( 'Product Categories', 'mr-wordy-shop' ),
			'singular_name' => __( 'Product Category', 'mr-wordy-shop' ),
			'search_items'  => __( 'Search Product Categories', 'mr-wordy-shop' ),
			'all_items'     => __( 'All Product Categories', 'mr-wordy-shop' ),
			'edit_item'     => __( 'Edit Product Category', 'mr-wordy-shop' ),
			'update_item'   => __( 'Update Product Category', 'mr-wordy-shop' ),
			'add_new_item'  => __( 'Add New Product Category', 'mr-wordy-shop' ),
			'new_item_name' => __( 'New Product Category Name', 'mr-wordy-shop' ),
			'menu_name'     => __( 'Categories', 'mr-wordy-shop' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => apply_filters( 'mr_wordy_shop_category_labels', $labels ),
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'product-category' ),
		);

		register_taxonomy( 'mrws_product_category', array( 'mrws_product' ), apply_filters( 'mr_wordy_shop_product_taxonomy_args', $args ) );
	}

	/**
	 * Add product fields.
	 *
	 * @return void
	 */
	public static function register_product_metabox() {
		add_meta_box(
			'mr_wordy_shop_product_details',
			__( 'Product Details', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_product_metabox' ),
			'mrws_product',
			'side'
		);
	}

	/**
	 * Output the product field UI.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_product_metabox( $post ) {
		$price = get_post_meta( $post->ID, self::META_PRICE, true );
		$sku   = get_post_meta( $post->ID, self::META_SKU, true );

		wp_nonce_field( 'mr_wordy_shop_save_product', 'mr_wordy_shop_product_nonce' );
		?>
		<p>
			<label for="mr-wordy-shop-price"><strong><?php esc_html_e( 'Price', 'mr-wordy-shop' ); ?></strong></label>
			<input type="text" class="widefat" id="mr-wordy-shop-price" name="mr_wordy_shop_price" value="<?php echo esc_attr( $price ); ?>" />
		</p>
		<p>
			<label for="mr-wordy-shop-sku"><strong><?php esc_html_e( 'SKU', 'mr-wordy-shop' ); ?></strong></label>
			<input type="text" class="widefat" id="mr-wordy-shop-sku" name="mr_wordy_shop_sku" value="<?php echo esc_attr( $sku ); ?>" />
		</p>
		<?php
	}

	/**
	 * Persist product fields.
	 *
	 * @param int $post_id Current post ID.
	 * @return void
	 */
	public static function save_product_meta( $post_id ) {
		if ( ! isset( $_POST['mr_wordy_shop_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mr_wordy_shop_product_nonce'] ) ), 'mr_wordy_shop_save_product' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['mr_wordy_shop_price'] ) ) {
			$price = self::sanitize_price( wp_unslash( $_POST['mr_wordy_shop_price'] ) );
			update_post_meta( $post_id, self::META_PRICE, $price );
		}

		if ( isset( $_POST['mr_wordy_shop_sku'] ) ) {
			update_post_meta( $post_id, self::META_SKU, sanitize_text_field( wp_unslash( $_POST['mr_wordy_shop_sku'] ) ) );
		}
	}

	/**
	 * Register the plugin settings page.
	 *
	 * @return void
	 */
	public static function register_settings_page() {
		add_submenu_page(
			'edit.php?post_type=mrws_product',
			__( 'Shop Settings', 'mr-wordy-shop' ),
			__( 'Settings', 'mr-wordy-shop' ),
			'manage_options',
			'mr-wordy-shop-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin options.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);

		add_settings_section(
			'mr_wordy_shop_general',
			__( 'General Shop Settings', 'mr-wordy-shop' ),
			'__return_false',
			'mr-wordy-shop-settings'
		);

		add_settings_field(
			'currency_code',
			__( 'Currency Code', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_currency_field' ),
			'mr-wordy-shop-settings',
			'mr_wordy_shop_general'
		);

		add_settings_field(
			'products_per_page',
			__( 'Products per page', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_products_per_page_field' ),
			'mr-wordy-shop-settings',
			'mr_wordy_shop_general'
		);
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * @param array $settings Posted settings.
	 * @return array
	 */
	public static function sanitize_settings( $settings ) {
		$defaults = self::get_default_settings();
		$settings = is_array( $settings ) ? $settings : array();

		return array(
			'currency_code'     => strtoupper( sanitize_text_field( $settings['currency_code'] ?? $defaults['currency_code'] ) ),
			'products_per_page' => max( 1, absint( $settings['products_per_page'] ?? $defaults['products_per_page'] ) ),
		);
	}

	/**
	 * Render the currency select field.
	 *
	 * @return void
	 */
	public static function render_currency_field() {
		$settings   = self::get_settings();
		$currencies = apply_filters(
			'mr_wordy_shop_currencies',
			array(
				'USD' => __( 'US Dollar (USD)', 'mr-wordy-shop' ),
				'EUR' => __( 'Euro (EUR)', 'mr-wordy-shop' ),
				'GBP' => __( 'British Pound (GBP)', 'mr-wordy-shop' ),
			)
		);
		?>
		<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[currency_code]">
			<?php foreach ( $currencies as $code => $label ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['currency_code'], $code ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render the products per page field.
	 *
	 * @return void
	 */
	public static function render_products_per_page_field() {
		$settings = self::get_settings();
		?>
		<input type="number" min="1" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[products_per_page]" value="<?php echo esc_attr( $settings['products_per_page'] ); ?>" />
		<?php
	}

	/**
	 * Output the shop settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MR Wordy Shop Settings', 'mr-wordy-shop' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'mr-wordy-shop-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a product grid shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_products_shortcode( $atts ) {
		$settings = self::get_settings();
		$atts     = shortcode_atts(
			array(
				'limit'    => $settings['products_per_page'],
				'category' => '',
			),
			$atts,
			'mr_wordy_shop_products'
		);

		$query_args = array(
			'post_type'      => 'mrws_product',
			'posts_per_page' => max( 1, absint( $atts['limit'] ) ),
		);

		if ( '' !== $atts['category'] ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'mrws_product_category',
					'field'    => 'slug',
					'terms'    => sanitize_title( $atts['category'] ),
				),
			);
		}

		$query = new WP_Query( apply_filters( 'mr_wordy_shop_products_query_args', $query_args, $atts ) );

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No products found.', 'mr-wordy-shop' ) . '</p>';
		}

		$currency = esc_html( $settings['currency_code'] );
		$output   = '<div class="mr-wordy-shop-products">';

		while ( $query->have_posts() ) {
			$query->the_post();

			$price   = get_post_meta( get_the_ID(), self::META_PRICE, true );
			$excerpt = get_the_excerpt();
			$output .= '<article class="mr-wordy-shop-product">';
			$output .= '<h2 class="mr-wordy-shop-product__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';

			if ( '' !== $excerpt ) {
				$output .= '<div class="mr-wordy-shop-product__excerpt">' . wp_kses_post( wpautop( $excerpt ) ) . '</div>';
			}

			if ( '' !== $price ) {
				$output .= '<p class="mr-wordy-shop-product__price">' . sprintf(
					/* translators: 1: currency code, 2: product price. */
					esc_html__( '%1$s %2$s', 'mr-wordy-shop' ),
					$currency,
					esc_html( $price )
				) . '</p>';
			}

			$output .= '</article>';
		}

		wp_reset_postdata();

		$output .= '</div>';

		return apply_filters( 'mr_wordy_shop_products_shortcode_output', $output, $atts, $settings );
	}

	/**
	 * Get plugin settings merged with defaults.
	 *
	 * @return array
	 */
	private static function get_settings() {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), self::get_default_settings() );
	}

	/**
	 * Sanitize a product price into a normalized decimal string.
	 *
	 * @param string $price Raw price value.
	 * @return string
	 */
	private static function sanitize_price( $price ) {
		$price = str_replace( ',', '.', trim( sanitize_text_field( $price ) ) );

		if ( ! preg_match( '/^\d+(?:\.\d{1,2})?$/', $price ) ) {
			return '';
		}

		return $price;
	}

	/**
	 * Get the default plugin settings.
	 *
	 * @return array
	 */
	private static function get_default_settings() {
		return array(
			'currency_code'     => 'USD',
			'products_per_page' => 12,
		);
	}
}

MR_Wordy_Shop_Plugin::init();
