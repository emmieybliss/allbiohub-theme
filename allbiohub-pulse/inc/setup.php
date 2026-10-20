<?php
/**
 * Theme supports, menus, image sizes, sidebars and small content compatibility shims.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'pulse_setup' );
function pulse_setup() {
	load_theme_textdomain( 'allbiohub-pulse', PULSE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_editor_style( 'assets/css/editor.css' );

	// Pulse sizes. Existing uploads keep their current sizes; srcset picks the closest.
	add_image_size( 'pulse-hero', 1200, 675, true );
	add_image_size( 'pulse-card', 640, 480, true );
	add_image_size( 'pulse-thumb', 240, 180, true );
	add_image_size( 'pulse-square', 400, 400, true );
	add_image_size( 'pulse-portrait', 720, 900, true );

	register_nav_menus(
		array(
			'primary'      => __( 'Primary navigation', 'allbiohub-pulse' ),
			'mobile'       => __( 'Mobile drawer', 'allbiohub-pulse' ),
			'mobile-quick' => __( 'Mobile quick links', 'allbiohub-pulse' ),
			'top-bar'      => __( 'Top bar', 'allbiohub-pulse' ),
			'footer-1'     => __( 'Footer: Quick links', 'allbiohub-pulse' ),
			'footer-2'     => __( 'Footer: More', 'allbiohub-pulse' ),
			'social'       => __( 'Social profiles', 'allbiohub-pulse' ),
		)
	);
}

add_action( 'after_setup_theme', 'pulse_content_width', 0 );
function pulse_content_width() {
	$GLOBALS['content_width'] = 760;
}

add_action( 'widgets_init', 'pulse_sidebars' );
function pulse_sidebars() {
	$common = array(
		'before_widget' => '<section id="%1$s" class="pulse-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="pulse-widget__title">',
		'after_title'   => '</h2>',
	);
	register_sidebar( array_merge( $common, array( 'id' => 'pulse-article', 'name' => __( 'Article sidebar', 'allbiohub-pulse' ) ) ) );
	register_sidebar( array_merge( $common, array( 'id' => 'pulse-archive', 'name' => __( 'Archive sidebar', 'allbiohub-pulse' ) ) ) );
	register_sidebar( array_merge( $common, array( 'id' => 'pulse-footer', 'name' => __( 'Footer extra', 'allbiohub-pulse' ) ) ) );
}

add_filter( 'image_size_names_choose', 'pulse_size_names' );
function pulse_size_names( $sizes ) {
	return array_merge( $sizes, array( 'pulse-hero' => __( 'Pulse wide (16:9)', 'allbiohub-pulse' ) ) );
}

/**
 * Body classes for layout hooks.
 */
add_filter( 'body_class', 'pulse_body_class' );
function pulse_body_class( $classes ) {
	if ( is_singular( 'post' ) && pulse_is_biography() ) {
		$classes[] = 'pulse-is-bio';
	}
	if ( pulse_opt( 'header_sticky' ) ) {
		$classes[] = 'pulse-sticky-header';
	}
	return $classes;
}

/**
 * [calculate_years] was used in 2 posts by a previous theme or plugin that is no
 * longer active, so readers currently see raw brackets. Registered here only if
 * nothing else provides it. Behaviour is inferred: whole years since a date.
 * Accepts year="1990", date="1990-05-12" or birth="1990-05-12".
 */
add_action( 'init', 'pulse_legacy_shortcodes', 20 );
function pulse_legacy_shortcodes() {
	if ( ! shortcode_exists( 'calculate_years' ) ) {
		add_shortcode( 'calculate_years', 'pulse_shortcode_calculate_years' );
	}
}

function pulse_shortcode_calculate_years( $atts ) {
	$atts = shortcode_atts( array( 'year' => '', 'date' => '', 'birth' => '', 'from' => '' ), (array) $atts, 'calculate_years' );
	$raw  = $atts['date'] ? $atts['date'] : ( $atts['birth'] ? $atts['birth'] : ( $atts['from'] ? $atts['from'] : $atts['year'] ) );
	$raw  = trim( (string) $raw );
	if ( preg_match( '/^\d{4}$/', $raw ) ) {
		$raw .= '-01-01';
	}
	$ts = strtotime( $raw );
	if ( ! $ts ) {
		return '';
	}
	$from = new DateTime( '@' . $ts );
	$now  = new DateTime( 'now', wp_timezone() );
	return (string) $from->diff( $now )->y;
}

/**
 * Accessible submenu toggles: add a button after parent menu links.
 */
add_filter( 'walker_nav_menu_start_el', 'pulse_submenu_toggle', 10, 4 );
function pulse_submenu_toggle( $output, $item, $depth, $args ) {
	if ( isset( $args->theme_location ) && in_array( $args->theme_location, array( 'primary', 'mobile' ), true ) && in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		$output .= sprintf(
			'<button class="pulse-subtoggle" type="button" aria-expanded="false" aria-label="%s">%s</button>',
			/* translators: %s: menu item title */
			esc_attr( sprintf( __( 'Show %s submenu', 'allbiohub-pulse' ), wp_strip_all_tags( $item->title ) ) ),
			pulse_icon( 'chevron', 14 )
		);
	}
	return $output;
}

/**
 * Menu titles on the site are stored in capitals; show them in title case so
 * they don't shout, without editing the menu items themselves.
 */
add_filter( 'nav_menu_item_title', 'pulse_menu_title_case', 10, 4 );
function pulse_menu_title_case( $title, $item, $args, $depth ) {
	if ( isset( $args->theme_location ) && in_array( $args->theme_location, array( 'primary', 'mobile', 'mobile-quick' ), true ) ) {
		$plain = html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' );
		if ( function_exists( 'mb_strtoupper' ) && mb_strtoupper( $plain ) === $plain && preg_match( '/[A-Z]{3}/', $plain ) ) {
			return esc_html( mb_convert_case( mb_strtolower( $plain ), MB_CASE_TITLE, 'UTF-8' ) );
		}
	}
	return $title;
}

/**
 * Archive titles without the "Category:" prefix, in title case.
 */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
