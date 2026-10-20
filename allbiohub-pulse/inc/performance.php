<?php
/**
 * Performance: trim head output, preload the homepage hero (LCP) image, and
 * keep queries cached. Compatible with LiteSpeed Cache's own optimisations.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'pulse_perf_cleanup' );
function pulse_perf_cleanup() {
	if ( pulse_opt( 'perf_disable_emoji' ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
}

/**
 * The homepage hero post (admin pick → first pin → latest with image).
 * Shared by the template and the preload so they always agree.
 */
function pulse_hero_id() {
	static $id = null;
	if ( null !== $id ) {
		return $id;
	}
	$pick = (int) pulse_opt( 'hero_post' );
	if ( $pick && 'publish' === get_post_status( $pick ) ) {
		return $id = $pick; // phpcs:ignore
	}
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => false,
			'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$id = $q->posts ? (int) $q->posts[0] : 0;
	return $id;
}

add_action( 'wp_head', 'pulse_preload_lcp', 2 );
function pulse_preload_lcp() {
	if ( is_front_page() && pulse_opt( 'home_hero' ) ) {
		$img = pulse_image_id( pulse_hero_id() );
	} elseif ( is_singular( array( 'post', 'abh_video' ) ) ) {
		$img = pulse_image_id( get_queried_object_id() );
	} else {
		return;
	}
	if ( ! $img ) {
		return;
	}
	$src    = wp_get_attachment_image_url( $img, 'pulse-hero' );
	$srcset = wp_get_attachment_image_srcset( $img, 'pulse-hero' );
	if ( $src ) {
		printf(
			'<link rel="preload" as="image" href="%s"%s imagesizes="(min-width: 1024px) 760px, 100vw" fetchpriority="high">' . "\n",
			esc_url( $src ),
			$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : ''
		);
	}
}

/**
 * Clear Pulse query caches when content changes.
 */
add_action( 'save_post', 'pulse_flush_caches' );
add_action( 'deleted_post', 'pulse_flush_caches' );
function pulse_flush_caches() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_pulse\\_mv\\_%' OR option_name LIKE '\\_transient\\_timeout\\_pulse\\_mv\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Faster page changes: when a reader hovers or presses a link, WordPress
 * (6.8+) starts fetching that page in the background, so it opens almost
 * instantly. Prefetch only, so view counts and ad impressions stay accurate.
 */
add_filter( 'wp_speculation_rules_configuration', 'pulse_speculation_rules' );
function pulse_speculation_rules( $config ) {
	if ( is_array( $config ) ) {
		$config['mode']      = 'prefetch';
		$config['eagerness'] = 'moderate';
	}
	return $config;
}

