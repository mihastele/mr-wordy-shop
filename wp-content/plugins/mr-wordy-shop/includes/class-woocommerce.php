<?php
/**
 * WooCommerce integration layer.
 *
 * When WooCommerce is active this class:
 * - Adds an "Add to Cart" button to each product card.
 * - Optionally syncs MR Wordy Shop products to WooCommerce
 *   simple products so they can be purchased through WC checkout.
 * - Provides admin settings to toggle the integration.
 *
 * @package MR_Wordy_Shop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRWS_WooCommerce
 */
final class MRWS_WooCommerce {

	const OPTION_NAME     = 'mrws_woocommerce_settings';
	const META_WC_PRODUCT = '_mrws_wc_product_id';

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		if ( ! self::is_woocommerce_active() ) {
			return;
		}

		$settings = self::get_settings();

		if ( $settings['sync_products'] ) {
			add_action( 'save_post_mrws_product', array( __CLASS__, 'sync_product_to_wc' ), 20 );
		}

		add_filter( 'mr_wordy_shop_products_shortcode_output', array( __CLASS__, 'inject_add_to_cart' ), 10, 3 );
	}

	/* -------------------------------------------------------------- */
	/*  Detection                                                      */
	/* -------------------------------------------------------------- */

	/**
	 * Check whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/* -------------------------------------------------------------- */
	/*  Product sync                                                   */
	/* -------------------------------------------------------------- */

	/**
	 * Create or update a WC simple product when an MRWS product is saved.
	 *
	 * @param int $post_id MRWS product post ID.
	 * @return void
	 */
	public static function sync_product_to_wc( $post_id ) {
		if ( ! self::is_woocommerce_active() ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'mrws_product' !== get_post_type( $post_id ) ) {
			return;
		}

		$post  = get_post( $post_id );
		$price = get_post_meta( $post_id, MR_Wordy_Shop_Plugin::META_PRICE, true );
		$sku   = get_post_meta( $post_id, MR_Wordy_Shop_Plugin::META_SKU, true );
		$wc_id = (int) get_post_meta( $post_id, self::META_WC_PRODUCT, true );

		/* Retrieve or create the WC product. */
		$wc_product = $wc_id ? wc_get_product( $wc_id ) : null;

		if ( ! $wc_product ) {
			$wc_product = new WC_Product_Simple();
		}

		$wc_product->set_name( $post->post_title );
		$wc_product->set_status( $post->post_status );
		$wc_product->set_description( $post->post_content );
		$wc_product->set_short_description( $post->post_excerpt );

		if ( '' !== $price ) {
			$wc_product->set_regular_price( $price );
		}

		if ( '' !== $sku ) {
			try {
				$wc_product->set_sku( $sku );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				/* SKU uniqueness violation – skip. */
			}
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			$wc_product->set_image_id( $thumbnail_id );
		}

		$new_id = $wc_product->save();
		update_post_meta( $post_id, self::META_WC_PRODUCT, $new_id );
	}

	/* -------------------------------------------------------------- */
	/*  Add to Cart button injection                                   */
	/* -------------------------------------------------------------- */

	/**
	 * Append an "Add to Cart" button to each product card in the shortcode output.
	 *
	 * @param string $output   Current shortcode HTML.
	 * @param array  $atts     Shortcode attributes.
	 * @param array  $settings Plugin settings.
	 * @return string
	 */
	public static function inject_add_to_cart( $output, $atts, $settings ) {
		if ( ! self::get_settings()['add_to_cart_button'] ) {
			return $output;
		}

		/*
		 * For every </article> closing tag, insert a WC add-to-cart
		 * link just before it.  We pull the WC product ID from post
		 * meta so we can build a proper WC add-to-cart URL.
		 */
		$pattern     = '/<article class="mr-wordy-shop-product" data-product-id="(\d+)">(.*?)<\/article>/s';
		$replacement = function ( $matches ) {
			$mrws_id   = (int) $matches[1];
			$inner     = $matches[2];
			$wc_id     = (int) get_post_meta( $mrws_id, self::META_WC_PRODUCT, true );

			if ( $wc_id && function_exists( 'wc_get_product' ) ) {
				$wc_product = wc_get_product( $wc_id );
				if ( $wc_product ) {
					$url   = esc_url( $wc_product->add_to_cart_url() );
					$label = esc_html( $wc_product->add_to_cart_text() );
					$inner .= '<div class="mr-wordy-shop-product__actions">';
					$inner .= '<a href="' . $url . '" class="mr-wordy-shop-btn mr-wordy-shop-btn--primary" data-product_id="' . $wc_id . '">' . $label . '</a>';
					$inner .= '</div>';
				}
			}

			return '<article class="mr-wordy-shop-product" data-product-id="' . $mrws_id . '">' . $inner . '</article>';
		};

		return preg_replace_callback( $pattern, $replacement, $output );
	}

	/* -------------------------------------------------------------- */
	/*  Admin settings                                                 */
	/* -------------------------------------------------------------- */

	/**
	 * Register WooCommerce integration settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			MR_Wordy_Shop_Plugin::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
			)
		);

		add_settings_section(
			'mrws_woocommerce',
			__( 'WooCommerce Integration', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_section_description' ),
			'mr-wordy-shop-settings'
		);

		add_settings_field(
			'mrws_wc_sync',
			__( 'Auto-sync products', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_sync_field' ),
			'mr-wordy-shop-settings',
			'mrws_woocommerce'
		);

		add_settings_field(
			'mrws_wc_add_to_cart',
			__( 'Add to Cart button', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_add_to_cart_field' ),
			'mr-wordy-shop-settings',
			'mrws_woocommerce'
		);
	}

	/* -------------------------------------------------------------- */
	/*  Field renderers                                                */
	/* -------------------------------------------------------------- */

	/**
	 * Section description.
	 *
	 * @return void
	 */
	public static function render_section_description() {
		if ( ! self::is_woocommerce_active() ) {
			echo '<p style="color:#b91c1c">' . esc_html__( 'WooCommerce is not active. Install and activate WooCommerce to enable these features.', 'mr-wordy-shop' ) . '</p>';
		}
	}

	/**
	 * Render sync toggle.
	 *
	 * @return void
	 */
	public static function render_sync_field() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[sync_products]" value="1" <?php checked( $settings['sync_products'] ); ?> />
			<?php esc_html_e( 'Automatically create/update a WooCommerce product when saving a Shop product', 'mr-wordy-shop' ); ?>
		</label>
		<?php
	}

	/**
	 * Render add-to-cart toggle.
	 *
	 * @return void
	 */
	public static function render_add_to_cart_field() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[add_to_cart_button]" value="1" <?php checked( $settings['add_to_cart_button'] ); ?> />
			<?php esc_html_e( 'Show WooCommerce "Add to Cart" button on product cards', 'mr-wordy-shop' ); ?>
		</label>
		<?php
	}

	/* -------------------------------------------------------------- */
	/*  Sanitization & defaults                                        */
	/* -------------------------------------------------------------- */

	/**
	 * Sanitize WooCommerce settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'sync_products'     => ! empty( $input['sync_products'] ),
			'add_to_cart_button' => ! empty( $input['add_to_cart_button'] ),
		);
	}

	/**
	 * Get merged settings with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), self::get_default_settings() );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private static function get_default_settings() {
		return array(
			'sync_products'      => false,
			'add_to_cart_button' => true,
		);
	}
}
