<?php
/**
 * Cookie Consent functionality.
 *
 * Renders a GDPR-style cookie consent banner on the front-end and
 * provides admin settings for the banner text and cookie-policy page.
 *
 * @package MR_Wordy_Shop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRWS_Cookie_Consent
 */
final class MRWS_Cookie_Consent {

	const OPTION_NAME = 'mrws_cookie_consent_settings';

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_banner' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/* -------------------------------------------------------------- */
	/*  Front-end                                                      */
	/* -------------------------------------------------------------- */

	/**
	 * Enqueue CSS & JS for the cookie banner.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$settings = self::get_settings();

		if ( ! $settings['enabled'] ) {
			return;
		}

		$base = plugin_dir_url( dirname( __FILE__ ) );

		wp_enqueue_style(
			'mrws-cookie-consent',
			$base . 'assets/css/cookie-consent.css',
			array(),
			'0.2.0'
		);

		wp_enqueue_script(
			'mrws-cookie-consent',
			$base . 'assets/js/cookie-consent.js',
			array(),
			'0.2.0',
			true
		);
	}

	/**
	 * Render the consent banner HTML in the footer.
	 *
	 * @return void
	 */
	public static function render_banner() {
		if ( is_admin() ) {
			return;
		}

		$settings = self::get_settings();

		if ( ! $settings['enabled'] ) {
			return;
		}

		$message     = $settings['message'];
		$policy_page = $settings['policy_page'];
		$policy_url  = $policy_page ? get_permalink( $policy_page ) : '';
		$policy_text = $settings['policy_link_text'];
		?>
		<div class="mrws-cookie-overlay" aria-hidden="true"></div>
		<div class="mrws-cookie-banner" role="dialog" aria-label="<?php esc_attr_e( 'Cookie consent', 'mr-wordy-shop' ); ?>">
			<div class="mrws-cookie-banner__text">
				<?php echo wp_kses_post( $message ); ?>
				<?php if ( $policy_url ) : ?>
					<a href="<?php echo esc_url( $policy_url ); ?>"><?php echo esc_html( $policy_text ); ?></a>
				<?php endif; ?>
			</div>
			<div class="mrws-cookie-banner__actions">
				<button type="button" class="mrws-cookie-banner__btn mrws-cookie-banner__btn--decline">
					<?php esc_html_e( 'Decline', 'mr-wordy-shop' ); ?>
				</button>
				<button type="button" class="mrws-cookie-banner__btn mrws-cookie-banner__btn--accept">
					<?php esc_html_e( 'Accept', 'mr-wordy-shop' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/* -------------------------------------------------------------- */
	/*  Admin settings                                                 */
	/* -------------------------------------------------------------- */

	/**
	 * Register the cookie consent settings section and fields.
	 *
	 * Settings are rendered inside the existing Shop → Settings page.
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
			'mrws_cookie_consent',
			__( 'Cookie Consent', 'mr-wordy-shop' ),
			'__return_false',
			'mr-wordy-shop-settings'
		);

		add_settings_field(
			'mrws_cookie_enabled',
			__( 'Enable banner', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_enabled_field' ),
			'mr-wordy-shop-settings',
			'mrws_cookie_consent'
		);

		add_settings_field(
			'mrws_cookie_message',
			__( 'Banner message', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_message_field' ),
			'mr-wordy-shop-settings',
			'mrws_cookie_consent'
		);

		add_settings_field(
			'mrws_cookie_policy_page',
			__( 'Cookie policy page', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_policy_page_field' ),
			'mr-wordy-shop-settings',
			'mrws_cookie_consent'
		);

		add_settings_field(
			'mrws_cookie_policy_link_text',
			__( 'Policy link text', 'mr-wordy-shop' ),
			array( __CLASS__, 'render_policy_link_text_field' ),
			'mr-wordy-shop-settings',
			'mrws_cookie_consent'
		);
	}

	/* -------------------------------------------------------------- */
	/*  Field renderers                                                */
	/* -------------------------------------------------------------- */

	/**
	 * Render the enabled checkbox.
	 *
	 * @return void
	 */
	public static function render_enabled_field() {
		$settings = self::get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
			<?php esc_html_e( 'Show cookie consent banner to visitors', 'mr-wordy-shop' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the banner message textarea.
	 *
	 * @return void
	 */
	public static function render_message_field() {
		$settings = self::get_settings();
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[message]" class="large-text" rows="3"><?php echo esc_textarea( $settings['message'] ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Basic HTML allowed (links, bold, italic).', 'mr-wordy-shop' ); ?></p>
		<?php
	}

	/**
	 * Render the policy page dropdown.
	 *
	 * @return void
	 */
	public static function render_policy_page_field() {
		$settings = self::get_settings();
		wp_dropdown_pages(
			array(
				'name'             => self::OPTION_NAME . '[policy_page]',
				'selected'         => $settings['policy_page'],
				'show_option_none' => __( '— None —', 'mr-wordy-shop' ),
			)
		);
		?>
		<p class="description"><?php esc_html_e( 'Select the page that contains your cookie / privacy policy.', 'mr-wordy-shop' ); ?></p>
		<?php
	}

	/**
	 * Render the policy link text input.
	 *
	 * @return void
	 */
	public static function render_policy_link_text_field() {
		$settings = self::get_settings();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[policy_link_text]" value="<?php echo esc_attr( $settings['policy_link_text'] ); ?>" />
		<?php
	}

	/* -------------------------------------------------------------- */
	/*  Sanitization & defaults                                        */
	/* -------------------------------------------------------------- */

	/**
	 * Sanitize cookie consent settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$defaults = self::get_default_settings();
		$input    = is_array( $input ) ? $input : array();

		return array(
			'enabled'          => ! empty( $input['enabled'] ),
			'message'          => wp_kses_post( $input['message'] ?? $defaults['message'] ),
			'policy_page'      => absint( $input['policy_page'] ?? 0 ),
			'policy_link_text' => sanitize_text_field( $input['policy_link_text'] ?? $defaults['policy_link_text'] ),
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
			'enabled'          => false,
			'message'          => __( 'We use cookies to enhance your browsing experience. By continuing to use this site you agree to our use of cookies.', 'mr-wordy-shop' ),
			'policy_page'      => 0,
			'policy_link_text' => __( 'Learn more', 'mr-wordy-shop' ),
		);
	}
}
