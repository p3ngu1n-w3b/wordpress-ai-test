<?php
/**
 * AI Site theme functions.
 *
 * This theme is a standard WordPress theme. Branding, hero content, and the
 * front-page sections are stored in theme mods that the automation populates
 * from site-config/site.json, but everything remains editable in wp-admin.
 *
 * @package AI_Site
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_SITE_VERSION', '1.0.0' );

/**
 * Theme setup.
 */
function ai_site_setup() {
	load_theme_textdomain( 'ai-site', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'custom-background' );
	add_theme_support( 'custom-header' );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'ai-site' ),
		'footer'  => __( 'Footer Menu', 'ai-site' ),
	) );
}
add_action( 'after_setup_theme', 'ai_site_setup' );

/**
 * Widget areas.
 */
function ai_site_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'ai-site' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Main sidebar shown on blog and archive pages.', 'ai-site' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer', 'ai-site' ),
		'id'            => 'footer-1',
		'description'   => __( 'Footer widget area.', 'ai-site' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );
}
add_action( 'widgets_init', 'ai_site_widgets_init' );

/**
 * Enqueue styles and scripts.
 */
function ai_site_assets() {
	wp_enqueue_style( 'ai-site-style', get_stylesheet_uri(), array(), AI_SITE_VERSION );
	wp_enqueue_style( 'ai-site-main', get_template_directory_uri() . '/assets/css/main.css', array( 'ai-site-style' ), AI_SITE_VERSION );

	$fonts_heading = ai_site_mod( 'font_heading', "Georgia, 'Times New Roman', serif" );
	$fonts_body    = ai_site_mod( 'font_body', "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" );

	$inline = sprintf(
		':root{--color-primary:%1$s;--color-accent:%2$s;--color-text:%3$s;--color-bg:%4$s;--font-heading:%5$s;--font-body:%6$s;}',
		esc_html( ai_site_mod( 'color_primary', '#6f4e37' ) ),
		esc_html( ai_site_mod( 'color_accent', '#e0a458' ) ),
		esc_html( ai_site_mod( 'color_text', '#2b2b2b' ) ),
		esc_html( ai_site_mod( 'color_bg', '#fffaf3' ) ),
		$fonts_heading,
		$fonts_body
	);
	wp_add_inline_style( 'ai-site-main', $inline );

	wp_enqueue_script( 'ai-site-main', get_template_directory_uri() . '/assets/js/main.js', array(), AI_SITE_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ai_site_assets' );

/**
 * Helper to read a theme mod with a fallback.
 *
 * @param string $key     Mod key (without the ai_site_ prefix).
 * @param mixed  $default Default value.
 * @return mixed
 */
function ai_site_mod( $key, $default = '' ) {
	return get_theme_mod( 'ai_site_' . $key, $default );
}

/**
 * Decode the JSON "sections" theme mod into an array.
 *
 * @return array
 */
function ai_site_sections() {
	$raw = ai_site_mod( 'sections_json', '' );
	if ( empty( $raw ) ) {
		return array();
	}
	$data = json_decode( $raw, true );
	return is_array( $data ) ? $data : array();
}

/**
 * Register Customizer settings so the AI-populated content stays editable.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function ai_site_customize_register( $wp_customize ) {
	$wp_customize->add_panel( 'ai_site_panel', array(
		'title'    => __( 'AI Site Settings', 'ai-site' ),
		'priority' => 20,
	) );

	// --- Colors & fonts ---
	$wp_customize->add_section( 'ai_site_brand', array(
		'title' => __( 'Brand', 'ai-site' ),
		'panel' => 'ai_site_panel',
	) );

	$colors = array(
		'color_primary' => array( __( 'Primary color', 'ai-site' ), '#6f4e37' ),
		'color_accent'  => array( __( 'Accent color', 'ai-site' ), '#e0a458' ),
		'color_text'    => array( __( 'Text color', 'ai-site' ), '#2b2b2b' ),
		'color_bg'      => array( __( 'Background color', 'ai-site' ), '#fffaf3' ),
	);
	foreach ( $colors as $key => $meta ) {
		$wp_customize->add_setting( 'ai_site_' . $key, array(
			'default'           => $meta[1],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ai_site_' . $key, array(
			'label'   => $meta[0],
			'section' => 'ai_site_brand',
		) ) );
	}

	// --- Hero ---
	$wp_customize->add_section( 'ai_site_hero', array(
		'title' => __( 'Hero', 'ai-site' ),
		'panel' => 'ai_site_panel',
	) );
	$hero_text = array(
		'hero_heading'        => __( 'Hero heading', 'ai-site' ),
		'hero_subheading'     => __( 'Hero subheading', 'ai-site' ),
		'hero_cta_label'      => __( 'Primary button label', 'ai-site' ),
		'hero_cta_url'        => __( 'Primary button URL', 'ai-site' ),
		'hero_cta2_label'     => __( 'Secondary button label', 'ai-site' ),
		'hero_cta2_url'       => __( 'Secondary button URL', 'ai-site' ),
		'hero_image'          => __( 'Hero image URL', 'ai-site' ),
	);
	foreach ( $hero_text as $key => $label ) {
		$wp_customize->add_setting( 'ai_site_' . $key, array(
			'default'           => '',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'ai_site_' . $key, array(
			'label'   => $label,
			'section' => 'ai_site_hero',
			'type'    => 'text',
		) );
	}

	// --- Contact ---
	$wp_customize->add_section( 'ai_site_contact', array(
		'title' => __( 'Contact & Social', 'ai-site' ),
		'panel' => 'ai_site_panel',
	) );
	$contact = array(
		'contact_email'   => __( 'Email', 'ai-site' ),
		'contact_phone'   => __( 'Phone', 'ai-site' ),
		'contact_address' => __( 'Address', 'ai-site' ),
		'social_twitter'  => __( 'Twitter URL', 'ai-site' ),
		'social_instagram'=> __( 'Instagram URL', 'ai-site' ),
		'social_facebook' => __( 'Facebook URL', 'ai-site' ),
	);
	foreach ( $contact as $key => $label ) {
		$wp_customize->add_setting( 'ai_site_' . $key, array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'ai_site_' . $key, array(
			'label'   => $label,
			'section' => 'ai_site_contact',
			'type'    => 'text',
		) );
	}
}
add_action( 'customize_register', 'ai_site_customize_register' );

/**
 * Allow SVG uploads so SVG logos/branding from site-config work in the
 * media library. Restricted to users who can already upload files.
 */
function ai_site_allow_svg( $mimes ) {
	if ( current_user_can( 'upload_files' ) ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
	}
	return $mimes;
}
add_filter( 'upload_mimes', 'ai_site_allow_svg' );

/**
 * Fix the mime-type check WordPress performs on SVG uploads.
 */
function ai_site_fix_svg_mime( $data, $file, $filename, $mimes ) {
	$ext = isset( $data['ext'] ) ? $data['ext'] : '';
	if ( '' === $ext ) {
		$exploded = explode( '.', $filename );
		$ext      = strtolower( end( $exploded ) );
	}
	if ( 'svg' === $ext ) {
		$data['type'] = 'image/svg+xml';
		$data['ext']  = 'svg';
	} elseif ( 'svgz' === $ext ) {
		$data['type'] = 'image/svg+xml';
		$data['ext']  = 'svgz';
	}
	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'ai_site_fix_svg_mime', 10, 4 );

/**
 * Pagination markup.
 */
function ai_site_pagination() {
	the_posts_pagination( array(
		'mid_size'  => 2,
		'prev_text' => __( '&larr; Newer', 'ai-site' ),
		'next_text' => __( 'Older &rarr;', 'ai-site' ),
	) );
}

/**
 * Fallback menu when no primary menu is assigned.
 */
function ai_site_default_menu() {
	echo '<ul class="nav-menu">';
	wp_list_pages( array( 'title_li' => '', 'depth' => 1 ) );
	echo '</ul>';
}
