<?php
/**
 * WordPress Customizer integration.
 *
 * Adds a "MR Wordy Shop" panel with colour, layout, and
 * typography controls so site owners can tweak the storefront
 * design without writing CSS.
 *
 * @package MR_Wordy_Shop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRWS_Customizer
 */
final class MRWS_Customizer {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'customize_register', array( __CLASS__, 'register' ) );
		add_action( 'wp_head', array( __CLASS__, 'inline_css' ), 100 );
	}

	/* -------------------------------------------------------------- */
	/*  Register Customizer controls                                   */
	/* -------------------------------------------------------------- */

	/**
	 * Register panel, sections, settings and controls.
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer instance.
	 * @return void
	 */
	public static function register( $wp_customize ) {

		/* ---- Panel ---- */
		$wp_customize->add_panel( 'mrws_panel', array(
			'title'    => __( 'MR Wordy Shop', 'mr-wordy-shop' ),
			'priority' => 160,
		) );

		/* ====== Colours section ====== */
		$wp_customize->add_section( 'mrws_colors', array(
			'title' => __( 'Colours', 'mr-wordy-shop' ),
			'panel' => 'mrws_panel',
		) );

		/* Primary colour */
		$wp_customize->add_setting( 'mrws_primary_color', array(
			'default'           => '#4f46e5',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'mrws_primary_color', array(
			'label'   => __( 'Primary colour', 'mr-wordy-shop' ),
			'section' => 'mrws_colors',
		) ) );

		/* Card background */
		$wp_customize->add_setting( 'mrws_card_bg', array(
			'default'           => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'mrws_card_bg', array(
			'label'   => __( 'Card background', 'mr-wordy-shop' ),
			'section' => 'mrws_colors',
		) ) );

		/* Text colour */
		$wp_customize->add_setting( 'mrws_text_color', array(
			'default'           => '#1e293b',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'mrws_text_color', array(
			'label'   => __( 'Text colour', 'mr-wordy-shop' ),
			'section' => 'mrws_colors',
		) ) );

		/* ====== Layout section ====== */
		$wp_customize->add_section( 'mrws_layout', array(
			'title' => __( 'Layout', 'mr-wordy-shop' ),
			'panel' => 'mrws_panel',
		) );

		/* Grid columns */
		$wp_customize->add_setting( 'mrws_grid_columns', array(
			'default'           => 3,
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'mrws_grid_columns', array(
			'label'   => __( 'Grid columns', 'mr-wordy-shop' ),
			'section' => 'mrws_layout',
			'type'    => 'select',
			'choices' => array(
				2 => '2',
				3 => '3',
				4 => '4',
			),
		) );

		/* Card border radius */
		$wp_customize->add_setting( 'mrws_card_radius', array(
			'default'           => 12,
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'mrws_card_radius', array(
			'label'       => __( 'Card border radius (px)', 'mr-wordy-shop' ),
			'section'     => 'mrws_layout',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 32,
				'step' => 1,
			),
		) );
	}

	/* -------------------------------------------------------------- */
	/*  Inline CSS output                                              */
	/* -------------------------------------------------------------- */

	/**
	 * Output inline custom-property overrides in <head>.
	 *
	 * @return void
	 */
	public static function inline_css() {
		$primary = sanitize_hex_color( get_theme_mod( 'mrws_primary_color', '#4f46e5' ) );
		$card_bg = sanitize_hex_color( get_theme_mod( 'mrws_card_bg', '#ffffff' ) );
		$text    = sanitize_hex_color( get_theme_mod( 'mrws_text_color', '#1e293b' ) );
		$cols    = absint( get_theme_mod( 'mrws_grid_columns', 3 ) );
		$radius  = absint( get_theme_mod( 'mrws_card_radius', 12 ) );

		$hover = self::darken_hex( $primary, 12 );

		printf(
			'<style id="mrws-customizer-css">:root{--mrws-primary:%s;--mrws-primary-hover:%s;--mrws-card-bg:%s;--mrws-text:%s;--mrws-columns:%d;--mrws-card-radius:%dpx}</style>',
			esc_attr( $primary ),
			esc_attr( $hover ),
			esc_attr( $card_bg ),
			esc_attr( $text ),
			$cols,
			$radius
		);
	}

	/* -------------------------------------------------------------- */
	/*  Helpers                                                        */
	/* -------------------------------------------------------------- */

	/**
	 * Darken a hex colour by a percentage.
	 *
	 * @param string $hex    Hex colour (e.g. #4f46e5).
	 * @param int    $amount Percentage to darken (0-100).
	 * @return string
	 */
	private static function darken_hex( $hex, $amount ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$r = max( 0, (int) hexdec( substr( $hex, 0, 2 ) ) - (int) round( 255 * ( $amount / 100 ) ) );
		$g = max( 0, (int) hexdec( substr( $hex, 2, 2 ) ) - (int) round( 255 * ( $amount / 100 ) ) );
		$b = max( 0, (int) hexdec( substr( $hex, 4, 2 ) ) - (int) round( 255 * ( $amount / 100 ) ) );

		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}
}
