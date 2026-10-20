<?php
/**
 * Settings storage: one option, typed defaults, typed sanitising.
 *
 * Every setting the admin screens can write is declared here with its type,
 * so sanitising is driven by the schema rather than by each screen.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_OPTION = 'allbiohub_settings';

/**
 * Schema: key => array( type, default ). Types: bool, int, text, textarea, url,
 * color, select:<a|b|c>, ids (comma list of post IDs), slugs (comma list of slugs).
 */
function pulse_schema() {
	static $schema = null;
	if ( null !== $schema ) {
		return $schema;
	}
	$schema = array(
		// General.
		'tagline'               => array( 'text', 'Inform. Inspire. Impact.' ),
		'footer_about'          => array( 'textarea', 'AllBioHub is an African digital publication covering the people, stories, business and culture shaping the continent and the world.' ),
		'logo_bundled'          => array( 'bool', 1 ),
		'logo_dark'             => array( 'int', 0 ),
		'advertise_url'         => array( 'url', 'https://mediakitad.netlify.app/' ),

		// Header.
		'header_sticky'         => array( 'bool', 1 ),
		'header_search'         => array( 'bool', 1 ),
		'header_darkmode'       => array( 'bool', 1 ),

		// Colors / dark mode.
		'accent'                => array( 'color', '#5B2BD9' ),
		'accent_dark'           => array( 'color', '#A88BFF' ),
		'color_scheme_default'  => array( 'select:system|light|dark', 'system' ),

		// Homepage: order + toggles.
		'home_order'            => array( 'slugs', 'hero,pinned,sponsored,biography,video,categories,sponsored_banner,newsletter' ),
		'home_hero'             => array( 'bool', 1 ),
		'home_pinned'           => array( 'bool', 1 ),
		'home_sponsored'        => array( 'bool', 1 ),
		'home_biography'        => array( 'bool', 1 ),
		'home_video'            => array( 'bool', 1 ),
		'home_categories'       => array( 'bool', 1 ),
		'home_sponsored_banner' => array( 'bool', 1 ),
		'home_newsletter'       => array( 'bool', 1 ),
		'hero_post'             => array( 'int', 0 ),

		// Trending.
		'ticker_enabled'        => array( 'bool', 1 ),
		'ticker_source'         => array( 'select:automatic|manual|hybrid', 'automatic' ),
		'ticker_posts'          => array( 'ids', '' ),
		'ticker_count'          => array( 'int', 8 ),
		'trending_source'       => array( 'select:most_viewed|recent|manual', 'most_viewed' ),
		'trending_posts'        => array( 'ids', '' ),
		'trending_days'         => array( 'int', 14 ),

		// Biography / people.
		'bio_category'          => array( 'text', 'biography' ),
		'bio_featured_post'     => array( 'int', 0 ),
		'people_title'          => array( 'text', 'People You Should Know' ),
		'people_count'          => array( 'int', 6 ),
		'people_posts'          => array( 'ids', '' ),

		// Video.
		'video_title'           => array( 'text', 'AllBioHub Video' ),
		'video_feature'         => array( 'select:week|month|featured', 'week' ),
		'video_more_count'      => array( 'int', 3 ),
		'video_style'           => array( 'select:split|stacked', 'split' ),

		// Category sections.
		'sections'              => array( 'slugs', 'celebrity,business,technology,women,news,lifestyle,biography,reviews' ),
		'section_posts'         => array( 'int', 5 ),

		// Newsletter.
		'newsletter_title'      => array( 'text', 'Stay updated with AllBioHub' ),
		'newsletter_text'       => array( 'text', 'Subscribe to our newsletter and never miss our best stories.' ),
		'newsletter_shortcode'  => array( 'text', '' ),
		'newsletter_action'     => array( 'url', '' ),
		'newsletter_brevo_list' => array( 'int', 0 ),
		'newsletter_brevo_key'  => array( 'text', '' ),

		// Social.
		'social_facebook'       => array( 'url', '' ),
		'social_x'              => array( 'url', '' ),
		'social_instagram'      => array( 'url', '' ),
		'social_youtube'        => array( 'url', '' ),
		'social_tiktok'         => array( 'url', '' ),
		'social_linkedin'       => array( 'url', '' ),

		// Articles.
		'article_toc'           => array( 'bool', 1 ),
		'article_share'         => array( 'bool', 1 ),
		'article_related'       => array( 'int', 6 ),
		'reading_wpm'           => array( 'int', 200 ),

		// SEO / performance.
		'schema_fallback'       => array( 'bool', 1 ),
		'perf_disable_emoji'    => array( 'bool', 1 ),
		'perf_preload_fonts'    => array( 'bool', 1 ),
		'views_enabled'         => array( 'bool', 1 ),
	);
	return $schema;
}

/**
 * Read one setting with its default.
 */
function pulse_opt( $key ) {
	static $stored = null;
	if ( null === $stored ) {
		$stored = (array) get_option( PULSE_OPTION, array() );
	}
	$schema = pulse_schema();
	if ( array_key_exists( $key, $stored ) ) {
		return $stored[ $key ];
	}
	return isset( $schema[ $key ] ) ? $schema[ $key ][1] : null;
}

/**
 * Parse an "ids" setting into ints.
 */
function pulse_opt_ids( $key ) {
	return array_values( array_filter( array_map( 'absint', explode( ',', (string) pulse_opt( $key ) ) ) ) );
}

/**
 * Parse a "slugs" setting into sanitized slugs.
 */
function pulse_opt_slugs( $key ) {
	return array_values( array_filter( array_map( 'sanitize_key', explode( ',', (string) pulse_opt( $key ) ) ) ) );
}

/**
 * Sanitise one value by schema type.
 */
function pulse_sanitize_value( $type, $value ) {
	if ( 0 === strpos( $type, 'select:' ) ) {
		$choices = explode( '|', substr( $type, 7 ) );
		return in_array( $value, $choices, true ) ? $value : $choices[0];
	}
	switch ( $type ) {
		case 'bool':
			return empty( $value ) ? 0 : 1;
		case 'int':
			return absint( $value );
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		case 'url':
			return esc_url_raw( (string) $value );
		case 'color':
			$c = sanitize_hex_color( (string) $value );
			return $c ? $c : '';
		case 'ids':
			$v = is_array( $value ) ? $value : explode( ',', (string) $value );
			return implode( ',', array_values( array_unique( array_filter( array_map( 'absint', $v ) ) ) ) );
		case 'slugs':
			$v = is_array( $value ) ? $value : explode( ',', (string) $value );
			return implode( ',', array_values( array_unique( array_filter( array_map( 'sanitize_key', $v ) ) ) ) );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Merge a submitted subset of settings into the stored option.
 * Only keys in $keys are touched, so each admin tab saves only its own fields.
 */
function pulse_save_settings( array $input, array $keys ) {
	$schema = pulse_schema();
	$stored = (array) get_option( PULSE_OPTION, array() );
	foreach ( $keys as $key ) {
		if ( ! isset( $schema[ $key ] ) ) {
			continue;
		}
		$type           = $schema[ $key ][0];
		$raw            = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : ( 'bool' === $type ? 0 : '' );
		$stored[ $key ] = pulse_sanitize_value( $type, $raw );
	}
	update_option( PULSE_OPTION, $stored, true );
}
